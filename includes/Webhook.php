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
    $log_file = WP_CONTENT_DIR . '/uploads/webhook-test.log';
    $written = file_put_contents(
        $log_file,
        "Webhook empfangen:\n" . json_encode(json_decode($request->get_body(), true), JSON_PRETTY_PRINT) . "\n",
        FILE_APPEND
    );
    if ($written === false) {
        error_log("🔴 Webhook konnte nicht in Logdatei geschrieben werden: $log_file");
    }

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

function send_produkt_welcome_email(array $order, int $order_id) {
    if (empty($order['customer_email'])) {
        return;
    }

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

    $subject    = 'Herzlich willkommen und vielen Dank für Ihre Bestellung!';
    $order_date = date_i18n('d.m.Y', strtotime($order['created_at']));
    $price      = number_format((float) $order['final_price'], 2, ',', '.') . '€';
    $shipping    = number_format((float) $order['shipping_cost'], 2, ',', '.') . '€';
    $total_first = number_format((float) $order['final_price'] + (float) $order['shipping_cost'], 2, ',', '.') . '€';

    $address = trim($order['customer_street'] . ', ' . $order['customer_postal'] . ' ' . $order['customer_city']);

    $site_title = get_bloginfo('name');
    $message  = '<html><body style="font-family:Arial,sans-serif;color:#333;margin:0;padding:0;">';
    $message .= '<div style="max-width:600px;margin:auto;">';
    $message .= '<div style="background:#007cba;color:#fff;padding:20px;text-align:center;font-size:20px;font-weight:bold;">' . esc_html($site_title) . '</div>';
    $message .= '<div style="padding:20px;">';
    $message .= '<h2 style="color:#007cba;margin-top:0;">Herzlich willkommen und vielen Dank für Ihre Bestellung!</h2>';
    $message .= '<p>Hallo ' . esc_html($first . ' ' . $last) . ',</p>';
    $message .= '<p>herzlichen Dank für Ihre Bestellung!<br>Wir freuen uns sehr, Sie als neuen Kunden begrüßen zu dürfen.</p>';

    $message .= '<h3>Ihre Bestellübersicht</h3>';
    $message .= '<table style="width:100%;border-collapse:collapse;">';
    $message .= '<tr><td style="padding:4px 0;"><strong>Bestellnummer:</strong></td><td>' . esc_html($order_id) . '</td></tr>';
    $message .= '<tr><td style="padding:4px 0;"><strong>Bestelldatum:</strong></td><td>' . esc_html($order_date) . '</td></tr>';
    $message .= '</table>';

    $message .= '<h3>Ihre Kundendaten</h3>';
    $message .= '<table style="width:100%;border-collapse:collapse;">';
    $message .= '<tr><td style="padding:4px 0;"><strong>Name:</strong></td><td>' . esc_html($full_name) . '</td></tr>';
    $message .= '<tr><td style="padding:4px 0;"><strong>E-Mail:</strong></td><td>' . esc_html($order['customer_email']) . '</td></tr>';
    if (!empty($order['customer_phone'])) {
        $message .= '<tr><td style="padding:4px 0;"><strong>Telefon:</strong></td><td>' . esc_html($order['customer_phone']) . '</td></tr>';
    }
    if (!empty($address)) {
        $message .= '<tr><td style="padding:4px 0;"><strong>Adresse:</strong></td><td>' . esc_html($address) . '</td></tr>';
    }
    $message .= '</table>';

    $message .= '<h3>Ihre Produktdaten</h3>';
    $message .= '<div style="line-height:1.5;">';
    $message .= '<p><strong>Produkt:</strong> ' . esc_html($order['produkt_name']) . '</p>';
    if (!empty($order['zustand_text'])) {
        $message .= '<p><strong>Ausführung:</strong> ' . esc_html($order['zustand_text']) . '</p>';
    }
    if (!empty($order['extra_text'])) {
        $message .= '<p><strong>Extras:</strong> ' . esc_html($order['extra_text']) . '</p>';
    }
    if (!empty($order['produktfarbe_text'])) {
        $message .= '<p><strong>Farbe:</strong> ' . esc_html($order['produktfarbe_text']) . '</p>';
    }
    if (!empty($order['gestellfarbe_text'])) {
        $message .= '<p><strong>Gestellfarbe:</strong> ' . esc_html($order['gestellfarbe_text']) . '</p>';
    }
    $order_obj = (object) $order;
    list($sd, $ed) = pv_get_order_period($order_obj);
    if ($sd && $ed) {
        $message .= '<p><strong>Zeitraum:</strong> ' . esc_html(date_i18n('d.m.Y', strtotime($sd))) . ' - ' . esc_html(date_i18n('d.m.Y', strtotime($ed))) . '</p>';
    }
    $days = pv_get_order_rental_days($order_obj);
    if ($days !== null) {
        $message .= '<p><strong>Miettage:</strong> ' . esc_html($days) . '</p>';
    } elseif (!empty($order['dauer_text'])) {
        $message .= '<p><strong>Miettage:</strong> ' . esc_html($order['dauer_text']) . '</p>';
    }
    $message .= '<p><strong>Preis:</strong> ' . esc_html($price) . '</p>';
    $shipping_name = '';
    if (!empty($order['shipping_price_id'])) {
        global $wpdb;
        $shipping_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}produkt_shipping_methods WHERE stripe_price_id = %s", $order['shipping_price_id']));
    }
    if ($order['shipping_cost'] > 0 || $shipping_name) {
        $ship_text = $shipping_name ?: 'Versand';
        $message .= '<p><strong>Versand:</strong> ' . esc_html($ship_text);
        if ($order['shipping_cost'] > 0) {
            $message .= ' - ' . esc_html($shipping);
        }
        $message .= '</p>';
    }
    $message .= '<p><strong>Gesamtsumme:</strong> ' . esc_html($total_first) . '</p>';
    $message .= '</div>';

    $message .= '<p>Bitte prüfen Sie die Angaben und antworten Sie auf diese E-Mail, falls Sie Fragen oder Änderungswünsche haben.</p>';
    $message .= '</div>';
    $message .= '<div style="background:#f8f9fa;color:#555;padding:20px;text-align:center;font-size:12px;">Kleine Helden Verleih GbR<br>Kadir Üner &amp; Tim Braunleder<br>Siegenkamp 28<br>52499 Baesweiler</div>';
    $message .= '</div>';
    $message .= '</body></html>';

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $from_name  = get_bloginfo('name');
    $from_email = get_option('admin_email');
    $headers[]  = 'From: ' . $from_name . ' <' . $from_email . '>';
    wp_mail($order['customer_email'], $subject, $message, $headers);
}

function send_admin_order_email(array $order, int $order_id, string $session_id): void {
    $subject    = 'Neue Bestellung #' . $order_id;
    $order_date = date_i18n('d.m.Y H:i', strtotime($order['created_at']));

    $price       = number_format((float) $order['final_price'], 2, ',', '.') . '€';
    $shipping    = number_format((float) $order['shipping_cost'], 2, ',', '.') . '€';
    $total_first = number_format((float) $order['final_price'] + (float) $order['shipping_cost'], 2, ',', '.') . '€';

    $address = trim($order['customer_street'] . ', ' . $order['customer_postal'] . ' ' . $order['customer_city'] . ', ' . $order['customer_country']);

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

    $site_title = get_bloginfo('name');
    $message  = '<html><body style="font-family:Arial,sans-serif;color:#333;margin:0;padding:0;">';
    $message .= '<div style="max-width:600px;margin:auto;">';
    $message .= '<div style="background:#007cba;color:#fff;padding:20px;text-align:center;font-size:20px;font-weight:bold;">' . esc_html($site_title) . '</div>';
    $message .= '<div style="padding:20px;">';
    $message .= '<h2 style="color:#007cba;margin-top:0;">Neue Bestellung eingegangen</h2>';

    $message .= '<h3>Bestelldetails</h3>';
    $message .= '<table style="width:100%;border-collapse:collapse;">';
    $message .= '<tr><td style="padding:4px 0;"><strong>Bestellnummer:</strong></td><td>' . esc_html($order_id) . '</td></tr>';
    $message .= '<tr><td style="padding:4px 0;"><strong>Datum:</strong></td><td>' . esc_html($order_date) . '</td></tr>';
    $message .= '<tr><td style="padding:4px 0;"><strong>Status:</strong></td><td>Abgeschlossen</td></tr>';
    $message .= '</table>';

    $message .= '<h3>Kundendaten</h3>';
    $message .= '<table style="width:100%;border-collapse:collapse;">';
    $message .= '<tr><td style="padding:4px 0;"><strong>Name:</strong></td><td>' . esc_html($order['customer_name']) . '</td></tr>';
    $message .= '<tr><td style="padding:4px 0;"><strong>E-Mail:</strong></td><td>' . esc_html($order['customer_email']) . '</td></tr>';
    if (!empty($order['customer_phone'])) {
        $message .= '<tr><td style="padding:4px 0;"><strong>Telefon:</strong></td><td>' . esc_html($order['customer_phone']) . '</td></tr>';
    }
    if (!empty($address)) {
        $message .= '<tr><td style="padding:4px 0;"><strong>Adresse:</strong></td><td>' . esc_html($address) . '</td></tr>';
    }
    $message .= '</table>';

    $message .= '<h3>Produktdaten</h3>';
    $message .= '<div style="line-height:1.5;">';
    $message .= '<p><strong>Produkt:</strong> ' . esc_html($order['produkt_name']) . '</p>';
    if (!empty($order['zustand_text'])) {
        $message .= '<p><strong>Ausführung:</strong> ' . esc_html($order['zustand_text']) . '</p>';
    }
    if (!empty($order['extra_text'])) {
        $message .= '<p><strong>Extras:</strong> ' . esc_html($order['extra_text']) . '</p>';
    }
    if (!empty($order['produktfarbe_text'])) {
        $message .= '<p><strong>Farbe:</strong> ' . esc_html($order['produktfarbe_text']) . '</p>';
    }
    if (!empty($order['gestellfarbe_text'])) {
        $message .= '<p><strong>Gestellfarbe:</strong> ' . esc_html($order['gestellfarbe_text']) . '</p>';
    }
    $order_obj = (object) $order;
    list($sd,$ed) = pv_get_order_period($order_obj);
    if ($sd && $ed) {
        $message .= '<p><strong>Zeitraum:</strong> ' . esc_html(date_i18n('d.m.Y', strtotime($sd))) . ' - ' . esc_html(date_i18n('d.m.Y', strtotime($ed))) . '</p>';
    }
    $days = pv_get_order_rental_days($order_obj);
    if ($days !== null) {
        $message .= '<p><strong>Miettage:</strong> ' . esc_html($days) . '</p>';
    } elseif (!empty($order['dauer_text'])) {
        $message .= '<p><strong>Miettage:</strong> ' . esc_html($order['dauer_text']) . '</p>';
    }
    $message .= '<p><strong>Preis:</strong> ' . esc_html($price) . '</p>';
    $shipping_name = '';
    if (!empty($order['shipping_price_id'])) {
        global $wpdb;
        $shipping_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}produkt_shipping_methods WHERE stripe_price_id = %s", $order['shipping_price_id']));
    }
    if ($order['shipping_cost'] > 0 || $shipping_name) {
        $ship_text = $shipping_name ?: 'Versand';
        $message .= '<p><strong>Versand:</strong> ' . esc_html($ship_text);
        if ($order['shipping_cost'] > 0) {
            $message .= ' - ' . esc_html($shipping);
        }
        $message .= '</p>';
    }
    $message .= '<p><strong>Gesamtsumme:</strong> ' . esc_html($total_first) . '</p>';
    $message .= '</div>';

    $message .= '<p>Session-ID: ' . esc_html($session_id) . '</p>';
    $message .= '</div>';
    $message .= '<div style="background:#f8f9fa;color:#555;padding:20px;text-align:center;font-size:12px;">Kleine Helden Verleih GbR<br>Kadir Üner &amp; Tim Braunleder<br>Siegenkamp 28<br>52499 Baesweiler</div>';
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
