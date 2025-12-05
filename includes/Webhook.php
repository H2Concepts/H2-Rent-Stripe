<?php
namespace ProduktVerleih;

use WP_REST_Request;
use WP_REST_Response;

require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

add_action('rest_api_init', function () {
    register_rest_route('produkt/v1', '/stripe-webhook', [
        'methods'  => 'POST',
        'callback' => __NAMESPACE__ . '\\handle_stripe_webhook',
        'permission_callback' => '__return_true',
    ]);
});

function handle_stripe_webhook(WP_REST_Request $request) {

    $secret_key = get_option('produkt_stripe_secret_key', '');
    if ($secret_key) {
        \Stripe\Stripe::setApiKey($secret_key);
    }

    $payload    = $request->get_body();
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $secret     = get_option('produkt_stripe_webhook_secret', '');

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $secret);
    } catch (\Exception $e) {
        return new WP_REST_Response(['error' => $e->getMessage()], 400);
    }

    register_shutdown_function(function () use ($event) {
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            \ProduktVerleih\StripeService::process_checkout_session($session);
        }
    });

    return new WP_REST_Response(['status' => 'ok'], 200);
}

function send_produkt_welcome_email(array $order, int $order_id, bool $attach_invoice = true, bool $force_send = false) {
    if (empty($order['customer_email'])) {
        return;
    }

    if (!$force_send && !pv_should_send_invoice_email($order, $order_id)) {
        return;
    }

    $invoice_emails_enabled = pv_is_invoice_email_enabled();

    $full_name = trim($order['customer_name']);
    if (strpos($full_name, ' ') !== false) {
        [$first, $last] = explode(' ', $full_name, 2);
    } else {
        $first = $full_name;
        $last  = '';
    }

    global $wpdb;
    if (empty($order['produkt_name']) || empty($order['extra_text'])) {
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT variant_id, category_id, extra_ids FROM {$wpdb->prefix}produkt_orders WHERE id = %d",
            $order_id
        ));
        if ($row) {
            if (empty($order['produkt_name'])) {
                if ($row->variant_id) {
                    $order['produkt_name'] = $wpdb->get_var($wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
                        $row->variant_id
                    ));
                }
                if (empty($order['produkt_name']) && $row->category_id) {
                    $order['produkt_name'] = $wpdb->get_var($wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                        $row->category_id
                    ));
                }
            }
            if (empty($order['extra_text']) && !empty($row->extra_ids)) {
                $ids = array_filter(array_map('intval', explode(',', $row->extra_ids)));
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                    $order['extra_text'] = implode(', ', $wpdb->get_col($wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}produkt_extras WHERE id IN ($placeholders)",
                        ...$ids
                    )));
                }
            }
        }
    }

    if (empty($order['order_items'])) {
        $raw_items = $wpdb->get_var($wpdb->prepare(
            "SELECT order_items FROM {$wpdb->prefix}produkt_orders WHERE id = %d",
            $order_id
        ));
        if ($raw_items) {
            $order['order_items'] = $raw_items;
        }
    }

    $subject    = 'Herzlich willkommen und vielen Dank für Ihre Bestellung!';
    $order_date = date_i18n('d.m.Y', strtotime($order['created_at']));
    $price      = number_format((float) $order['final_price'], 2, ',', '.') . '€';
    $shipping   = number_format((float) $order['shipping_cost'], 2, ',', '.') . '€';
    $total_first = number_format((float) $order['final_price'] + (float) $order['shipping_cost'], 2, ',', '.') . '€';

    $address = trim($order['customer_street'] . ', ' . $order['customer_postal'] . ' ' . $order['customer_city']);

    $site_title     = get_bloginfo('name');
    $logo_url       = get_option('plugin_firma_logo_url', '');
    $bestellnr      = !empty($order['order_number']) ? $order['order_number'] : $order_id;
    $divider        = '<div style="height:1px;background:#E6E8ED;margin:20px 0;"></div>';
    $items          = pv_expand_order_products($order);
    $shipping_name  = '';

    if (!empty($order['shipping_price_id'])) {
        $shipping_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}produkt_shipping_methods WHERE stripe_price_id = %s", $order['shipping_price_id']));
    }

    $account_page_id = get_option(PRODUKT_CUSTOMER_PAGE_OPTION);
    $account_url     = $account_page_id ? get_permalink($account_page_id) : home_url('/kundenkonto');
    $customer_name   = trim($first . ' ' . $last);

    $message  = '<html><body style="margin:0;padding:0;background:#F6F7FA;font-family:Arial,sans-serif;color:#000;">';
    $message .= '<div style="max-width:680px;margin:0 auto;padding:24px;">';

    if ($logo_url) {
        $message .= '<div style="text-align:center;margin-bottom:16px;"><img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_title) . '" style="max-width:100px;height:auto;"></div>';
    }

    $message .= '<h1 style="text-align:center;font-size:22px;margin:0 0 40px;">Herzlich willkommen und vielen Dank für Ihre Bestellung!</h1>';
    $message .= '<p style="margin:0 0 16px;font-size:14px;line-height:1.6;">Hallo ' . esc_html($customer_name) . ',<br>herzlichen Dank für Ihre Bestellung! Wir freuen uns sehr, Sie als neuen Kunden begrüßen zu dürfen.</p>';

    $message .= '<div style="background:#FFFFFF;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">';

    $message .= '<h2 style="margin:0 0 12px;font-size:18px;">Bestelldetails</h2>';
    $message .= '<table style="width:100%;border-collapse:collapse;font-size:14px;line-height:1.4;">';
    $message .= '<tr><td style="padding:6px 0;width:40%;"><strong>Bestellnummer:</strong></td><td>' . esc_html($bestellnr) . '</td></tr>';
    $message .= '<tr><td style="padding:6px 0;"><strong>Datum:</strong></td><td>' . esc_html($order_date) . '</td></tr>';
    $message .= '<tr><td style="padding:6px 0;"><strong>Status:</strong></td><td>Bezahlt</td></tr>';
    if ($shipping_name) {
        $message .= '<tr><td style="padding:6px 0;"><strong>Versandart:</strong></td><td>' . esc_html($shipping_name) . '</td></tr>';
    }
    $message .= '</table>';

    $message .= $divider;

    $message .= '<h2 style="margin:0 0 12px;font-size:18px;">Kundendaten</h2>';
    $message .= '<table style="width:100%;border-collapse:collapse;font-size:14px;line-height:1.4;">';
    $message .= '<tr><td style="padding:6px 0;width:40%;"><strong>Name:</strong></td><td>' . esc_html($customer_name) . '</td></tr>';
    $message .= '<tr><td style="padding:6px 0;"><strong>E-Mail:</strong></td><td>' . esc_html($order['customer_email']) . '</td></tr>';
    if (!empty($order['customer_phone'])) {
        $message .= '<tr><td style="padding:6px 0;"><strong>Telefon:</strong></td><td>' . esc_html($order['customer_phone']) . '</td></tr>';
    }
    if (!empty($address)) {
        $message .= '<tr><td style="padding:6px 0;"><strong>Adresse:</strong></td><td>' . esc_html($address) . '</td></tr>';
    }
    $message .= '</table>';

    $message .= $divider;

    $message .= '<h2 style="margin:0 0 12px;font-size:18px;">Produktdaten</h2>';
    $message .= '<div style="display:flex;flex-direction:column;">';
    $item_count = count($items);
    foreach ($items as $idx => $item) {
        $details = [];
        if (!empty($item->variant_name)) { $details[] = 'Ausführung: ' . esc_html($item->variant_name); }
        if (!empty($item->extra_names)) { $details[] = 'Extras: ' . esc_html($item->extra_names); }
        if (!empty($item->product_color_name)) { $details[] = 'Farbe: ' . esc_html($item->product_color_name); }
        if (!empty($item->frame_color_name)) { $details[] = 'Gestellfarbe: ' . esc_html($item->frame_color_name); }
        if (!empty($item->condition_name)) { $details[] = 'Zustand: ' . esc_html($item->condition_name); }
        $period_obj = (object) array_merge((array) $order, (array) $item);
        list($sd, $ed) = pv_get_order_period($period_obj);
        if ($sd && $ed) {
            $details[] = 'Zeitraum: ' . esc_html(date_i18n('d.m.Y', strtotime($sd))) . ' - ' . esc_html(date_i18n('d.m.Y', strtotime($ed)));
        }
        $days = pv_get_order_rental_days($period_obj);
        $duration_text = $item->duration_name ?? ($order['dauer_text'] ?? '');
        if ($days !== null) {
            $details[] = 'Miettage: ' . esc_html($days);
        } elseif (!empty($duration_text)) {
            $details[] = 'Miettage: ' . esc_html($duration_text);
        }

        $message .= '<div style="padding:12px 0;">';
        $message .= '<div style="display:flex;gap:12px;align-items:flex-start;">';
        if (!empty($item->image_url)) {
            $message .= '<div style="width:64px;flex-shrink:0;">'
                . '<img src="' . esc_url($item->image_url) . '" alt="' . esc_attr($item->produkt_name) . '" style="width:64px;height:64px;object-fit:cover;border-radius:8px;background:#F0F1F4;display:block;">'
                . '</div>';
        } else {
            $message .= '<div style="width:64px;height:64px;border-radius:8px;background:#F0F1F4;"></div>';
        }
        $message .= '<div style="flex:1;">';
        $message .= '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">';
        $message .= '<div style="font-weight:700;font-size:14px;line-height:1.4;">' . esc_html($item->produkt_name) . '</div>';
        $message .= '<div style="font-weight:700;font-size:14px;">' . esc_html(number_format((float) ($item->final_price ?? 0), 2, ',', '.')) . '€</div>';
        $message .= '</div>';
        if (!empty($details)) {
            $message .= '<div style="margin-top:6px;font-size:13px;color:#4A4A4A;line-height:1.5;">' . implode('<br>', $details) . '</div>';
        }
        $message .= '</div>';
        $message .= '</div>';
        if ($idx < $item_count - 1) {
            $message .= '<div style="height:1px;background:#E6E8ED;margin:8px 0 4px;"></div>';
        }
        $message .= '</div>';
    }
    $message .= '</div>';

    $message .= $divider;

    $message .= '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
    $message .= '<tr><td style="padding:6px 0;"><strong>Zwischensumme</strong></td><td style="text-align:right;">' . esc_html($price) . '</td></tr>';
    $ship_text = $shipping_name ?: 'Versand';
    $message .= '<tr><td style="padding:6px 0;"><strong>' . esc_html($ship_text) . '</strong></td><td style="text-align:right;">' . esc_html($shipping) . '</td></tr>';
    $message .= '<tr><td style="padding:6px 0;font-size:16px;"><strong>Gesamtsumme</strong></td><td style="text-align:right;font-size:16px;"><strong>' . esc_html($total_first) . '</strong></td></tr>';
    $message .= '</table>';

    $message .= '</div>';

    $message .= '<p style="margin:16px 0 8px;font-size:12px;line-height:1.6;">Bitte prüfen Sie die Angaben und antworten Sie auf diese E-Mail, falls Sie Fragen oder Änderungswünsche haben.</p>';

    $message .= '<div style="text-align:center;margin:18px 0 8px;">';
    $message .= '<a href="' . esc_url($account_url) . '" style="display:inline-block;padding:14px 36px;background:#000;color:#fff;text-decoration:none;border-radius:999px;font-weight:bold;font-size:15px;">Zum Kundenkonto</a>';
    $message .= '</div>';

    if ($logo_url) {
        $message .= '<div style="text-align:center;margin:22px 0 8px;"><img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_title) . '" style="max-width:70px;height:auto;"></div>';
    }

    $message .= $divider;

    $footer_html = pv_get_email_footer_html();
    if ($footer_html) {
        $message .= $footer_html;
    }

    $message .= '</div>';
    $message .= '</body></html>';

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $from_name  = get_bloginfo('name');
    $from_email = get_option('admin_email');
    $headers[]  = 'From: ' . $from_name . ' <' . $from_email . '>';

    // Rechnung erzeugen und Anhang vorbereiten
    $pdf_path = pv_generate_invoice_pdf($order_id);

    $attachments = [];
    if ($attach_invoice && $invoice_emails_enabled && $pdf_path && file_exists($pdf_path)) {
        $attachments[] = $pdf_path;
    }

    wp_mail($order['customer_email'], $subject, $message, $headers, $attachments);
}

function send_admin_order_email(array $order, int $order_id, string $session_id): void {
    global $wpdb;

    $subject    = 'Neue Bestellung #' . (!empty($order['order_number']) ? $order['order_number'] : $order_id);
    $order_date = date_i18n('d.m.Y H:i', strtotime($order['created_at']));

    $price       = number_format((float) $order['final_price'], 2, ',', '.') . '€';
    $shipping    = number_format((float) $order['shipping_cost'], 2, ',', '.') . '€';
    $total_first = number_format((float) $order['final_price'] + (float) $order['shipping_cost'], 2, ',', '.') . '€';

    $address = trim($order['customer_street'] . ', ' . $order['customer_postal'] . ' ' . $order['customer_city'] . ', ' . $order['customer_country']);

    if (empty($order['order_items'])) {
        $raw_items = $wpdb->get_var($wpdb->prepare(
            "SELECT order_items FROM {$wpdb->prefix}produkt_orders WHERE id = %d",
            $order_id
        ));
        if ($raw_items) {
            $order['order_items'] = $raw_items;
        }
    }

    if (empty($order['produkt_name']) || empty($order['extra_text'])) {
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT variant_id, category_id, extra_ids FROM {$wpdb->prefix}produkt_orders WHERE id = %d",
            $order_id
        ));
        if ($row) {
            if (empty($order['produkt_name'])) {
                if ($row->variant_id) {
                    $order['produkt_name'] = $wpdb->get_var($wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
                        $row->variant_id
                    ));
                }
                if (empty($order['produkt_name']) && $row->category_id) {
                    $order['produkt_name'] = $wpdb->get_var($wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                        $row->category_id
                    ));
                }
            }
            if (empty($order['extra_text']) && !empty($row->extra_ids)) {
                $ids = array_filter(array_map('intval', explode(',', $row->extra_ids)));
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                    $order['extra_text'] = implode(', ', $wpdb->get_col($wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}produkt_extras WHERE id IN ($placeholders)",
                        ...$ids
                    )));
                }
            }
        }
    }

    $site_title    = get_bloginfo('name');
    $logo_url      = get_option('plugin_firma_logo_url', '');
    $bestellnr     = !empty($order['order_number']) ? $order['order_number'] : $order_id;
    $divider       = '<div style="height:1px;background:#E6E8ED;margin:20px 0;"></div>';
    $items         = pv_expand_order_products($order);
    $shipping_name = '';

    if (!empty($order['shipping_price_id'])) {
        $shipping_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}produkt_shipping_methods WHERE stripe_price_id = %s", $order['shipping_price_id']));
    }

    $message  = '<html><body style="margin:0;padding:0;background:#F6F7FA;font-family:Arial,sans-serif;color:#000;">';
    $message .= '<div style="max-width:680px;margin:0 auto;padding:24px;">';

    if ($logo_url) {
        $message .= '<div style="text-align:center;margin-bottom:16px;"><img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_title) . '" style="max-width:100px;height:auto;"></div>';
    }

    $message .= '<h1 style="text-align:center;font-size:22px;margin:0 0 40px;">Neue Bestellung eingegangen</h1>';
    $message .= '<p style="margin:0 0 16px;font-size:14px;line-height:1.6;">Hallo Team, es ist eine neue Bestellung eingegangen.</p>';

    $message .= '<div style="background:#FFFFFF;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">';

    $message .= '<h2 style="margin:0 0 12px;font-size:18px;">Bestelldetails</h2>';
    $message .= '<table style="width:100%;border-collapse:collapse;font-size:14px;line-height:1.4;">';
    $message .= '<tr><td style="padding:6px 0;width:40%;"><strong>Bestellnummer:</strong></td><td>' . esc_html($bestellnr) . '</td></tr>';
    $message .= '<tr><td style="padding:6px 0;"><strong>Datum:</strong></td><td>' . esc_html($order_date) . '</td></tr>';
    $message .= '<tr><td style="padding:6px 0;"><strong>Status:</strong></td><td>Bezahlt</td></tr>';
    if ($shipping_name) {
        $message .= '<tr><td style="padding:6px 0;"><strong>Versandart:</strong></td><td>' . esc_html($shipping_name) . '</td></tr>';
    }
    $message .= '</table>';

    $message .= $divider;

    $message .= '<h2 style="margin:0 0 12px;font-size:18px;">Kundendaten</h2>';
    $message .= '<table style="width:100%;border-collapse:collapse;font-size:14px;line-height:1.4;">';
    $message .= '<tr><td style="padding:6px 0;width:40%;"><strong>Name:</strong></td><td>' . esc_html($order['customer_name']) . '</td></tr>';
    $message .= '<tr><td style="padding:6px 0;"><strong>E-Mail:</strong></td><td>' . esc_html($order['customer_email']) . '</td></tr>';
    if (!empty($order['customer_phone'])) {
        $message .= '<tr><td style="padding:6px 0;"><strong>Telefon:</strong></td><td>' . esc_html($order['customer_phone']) . '</td></tr>';
    }
    if (!empty($address)) {
        $message .= '<tr><td style="padding:6px 0;"><strong>Adresse:</strong></td><td>' . esc_html($address) . '</td></tr>';
    }
    $message .= '</table>';

    $message .= $divider;

    $message .= '<h2 style="margin:0 0 12px;font-size:18px;">Produktdaten</h2>';
    $message .= '<div style="display:flex;flex-direction:column;">';
    $item_count = count($items);
    foreach ($items as $idx => $item) {
        $details = [];
        if (!empty($item->variant_name)) { $details[] = 'Ausführung: ' . esc_html($item->variant_name); }
        if (!empty($item->extra_names)) { $details[] = 'Extras: ' . esc_html($item->extra_names); }
        if (!empty($item->product_color_name)) { $details[] = 'Farbe: ' . esc_html($item->product_color_name); }
        if (!empty($item->frame_color_name)) { $details[] = 'Gestellfarbe: ' . esc_html($item->frame_color_name); }
        if (!empty($item->condition_name)) { $details[] = 'Zustand: ' . esc_html($item->condition_name); }
        $period_obj = (object) array_merge((array) $order, (array) $item);
        list($sd,$ed) = pv_get_order_period($period_obj);
        if ($sd && $ed) {
            $details[] = 'Zeitraum: ' . esc_html(date_i18n('d.m.Y', strtotime($sd))) . ' - ' . esc_html(date_i18n('d.m.Y', strtotime($ed)));
        }
        $days = pv_get_order_rental_days($period_obj);
        $duration_text = $item->duration_name ?? ($order['dauer_text'] ?? '');
        if ($days !== null) {
            $details[] = 'Miettage: ' . esc_html($days);
        } elseif (!empty($duration_text)) {
            $details[] = 'Miettage: ' . esc_html($duration_text);
        }

        $message .= '<div style="padding:12px 0;">';
        $message .= '<div style="display:flex;gap:12px;align-items:flex-start;">';
        if (!empty($item->image_url)) {
            $message .= '<div style="width:64px;flex-shrink:0;">'
                . '<img src="' . esc_url($item->image_url) . '" alt="' . esc_attr($item->produkt_name) . '" style="width:64px;height:64px;object-fit:cover;border-radius:8px;background:#F0F1F4;display:block;">'
                . '</div>';
        } else {
            $message .= '<div style="width:64px;height:64px;border-radius:8px;background:#F0F1F4;"></div>';
        }
        $message .= '<div style="flex:1;">';
        $message .= '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">';
        $message .= '<div style="font-weight:700;font-size:14px;line-height:1.4;">' . esc_html($item->produkt_name) . '</div>';
        $message .= '<div style="font-weight:700;font-size:14px;">' . esc_html(number_format((float) ($item->final_price ?? 0), 2, ',', '.')) . '€</div>';
        $message .= '</div>';
        if (!empty($details)) {
            $message .= '<div style="margin-top:6px;font-size:13px;color:#4A4A4A;line-height:1.5;">' . implode('<br>', $details) . '</div>';
        }
        $message .= '</div>';
        $message .= '</div>';
        if ($idx < $item_count - 1) {
            $message .= '<div style="height:1px;background:#E6E8ED;margin:8px 0 4px;"></div>';
        }
        $message .= '</div>';
    }
    $message .= '</div>';

    $message .= $divider;

    $message .= '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
    $message .= '<tr><td style="padding:6px 0;"><strong>Zwischensumme</strong></td><td style="text-align:right;">' . esc_html($price) . '</td></tr>';
    $ship_text = $shipping_name ?: 'Versand';
    $message .= '<tr><td style="padding:6px 0;"><strong>' . esc_html($ship_text) . '</strong></td><td style="text-align:right;">' . esc_html($shipping) . '</td></tr>';
    $message .= '<tr><td style="padding:6px 0;font-size:16px;"><strong>Gesamtsumme</strong></td><td style="text-align:right;font-size:16px;"><strong>' . esc_html($total_first) . '</strong></td></tr>';
    $message .= '</table>';

    $message .= '</div>';

    $message .= '<p style="margin:16px 0 0;font-size:12px;line-height:1.6;">Session-ID: ' . esc_html($session_id) . '</p>';

    $footer_html = pv_get_email_footer_html();
    if ($footer_html) {
        $message .= $divider . $footer_html;
    }

    $message .= '</div>';
    $message .= '</body></html>';

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $from_email = get_option('admin_email');
    $headers[] = 'From: H2 Rental Pro <' . $from_email . '>';
    wp_mail(get_option('admin_email'), $subject, $message, $headers);
}

function produkt_add_order_log(int $order_id, string $event, string $message = ''): void {
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'produkt_order_logs',
        [
            'order_id'   => $order_id,
            'event'      => $event,
            'message'    => $message,
            'created_at' => current_time('mysql', true),
        ],
        ['%d', '%s', '%s', '%s']
    );
}
