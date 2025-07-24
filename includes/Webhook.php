<?php
namespace ProduktVerleih;

use WP_REST_Request;
use WP_REST_Response;

add_action('rest_api_init', function () {
    register_rest_route('produkt/v1', '/stripe-webhook', [
        'methods'  => 'POST',
        'callback' => __NAMESPACE__ . '\\handle_stripe_webhook',
        'permission_callback' => '__return_true',
    ]);
});

function handle_stripe_webhook(WP_REST_Request $request) {
    $init = StripeService::init();
    if (is_wp_error($init)) {
        return new WP_REST_Response(['error' => 'Stripe init failed'], 500);
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
        http_response_code(400);
        exit('Ungültige Signatur: ' . $e->getMessage());
    }

    // Log webhook event
    global $wpdb;
    $log_table = $wpdb->prefix . 'produkt_webhook_logs';
    $wpdb->insert($log_table, [
        'event_type'    => $event->type,
        'stripe_object' => wp_json_encode($event->data->object),
        'message'       => 'Webhook verarbeitet'
    ]);

    if ($event->type === 'checkout.session.completed') {
        $session  = $event->data->object;
        $mode     = $session->mode ?? 'subscription';
        $subscription_id   = $mode === 'subscription' ? ($session->subscription ?? '') : '';
        $metadata = $session->metadata ? $session->metadata->toArray() : [];

        if ($mode === 'payment' || $mode === 'subscription') {

        $email              = $session->customer_details->email ?? '';
        $stripe_customer_id = $session->customer ?? '';
        $full_name          = sanitize_text_field($session->customer_details->name ?? '');
        $first_name = $full_name;
        $last_name  = '';
        if (strpos($full_name, ' ') !== false) {
            list($first_name, $last_name) = explode(' ', $full_name, 2);
        }
        $phone = sanitize_text_field($session->customer_details->phone ?? '');

        if ($email && $stripe_customer_id) {
            $user = get_user_by('email', $email);

            if (!$user) {
                // Benutzer anlegen
                $user_id = wp_create_user($email, wp_generate_password(), $email);
                if (!is_wp_error($user_id)) {
                    wp_update_user([
                        'ID'          => $user_id,
                        'role'        => 'kunde',
                        'display_name'=> $full_name ?: $email,
                    ]);
                    update_user_meta($user_id, 'stripe_customer_id', $stripe_customer_id);
                    update_user_meta($user_id, 'first_name', $first_name);
                    update_user_meta($user_id, 'last_name', $last_name);
                    if ($phone) {
                        update_user_meta($user_id, 'phone', $phone);
                    }
                }
            } else {
                // Benutzer existiert – Daten aktualisieren
                wp_update_user([
                    'ID'          => $user->ID,
                    'role'        => 'kunde',
                    'display_name'=> $full_name ?: $user->display_name,
                ]);
                update_user_meta($user->ID, 'stripe_customer_id', $stripe_customer_id);
                update_user_meta($user->ID, 'first_name', $first_name);
                update_user_meta($user->ID, 'last_name', $last_name);
                if ($phone) {
                    update_user_meta($user->ID, 'phone', $phone);
                }
            }
        }

        $produkt_name  = sanitize_text_field($metadata['produkt'] ?? '');
        $zustand       = sanitize_text_field($metadata['zustand'] ?? '');
        $produktfarbe  = sanitize_text_field($metadata['produktfarbe'] ?? '');
        $gestellfarbe  = sanitize_text_field($metadata['gestellfarbe'] ?? '');
        $extra         = sanitize_text_field($metadata['extra'] ?? '');
        $dauer         = sanitize_text_field($metadata['dauer_name'] ?? '');
        $start_date    = sanitize_text_field($metadata['start_date'] ?? '');
        $end_date      = sanitize_text_field($metadata['end_date'] ?? '');
        $days          = intval($metadata['days'] ?? 0);
        $user_ip       = sanitize_text_field($metadata['user_ip'] ?? '');
        $user_agent    = sanitize_text_field($metadata['user_agent'] ?? '');

        $email    = sanitize_email($session->customer_details->email ?? '');
        $phone    = sanitize_text_field($session->customer_details->phone ?? '');

        $address = $session->customer_details->address ?? null;

        // Persist customer information in custom table
        Database::upsert_customer_record_by_email(
            $email,
            $stripe_customer_id,
            $full_name,
            $phone,
            [
                'street'      => $address->line1 ?? '',
                'postal_code' => $address->postal_code ?? '',
                'city'        => $address->city ?? '',
                'country'     => $address->country ?? '',
            ]
        );

        global $wpdb;
        $existing_order = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status, created_at, category_id, shipping_cost, variant_id FROM {$wpdb->prefix}produkt_orders WHERE stripe_session_id = %s",
            $session->id
        ));
        $existing_id = $existing_order->id ?? 0;
        $shipping_cost = 0;
        if ($existing_id) {
            $shipping_cost = floatval($existing_order->shipping_cost);
            if (!$shipping_cost && !empty($existing_order->category_id)) {
                $shipping_cost = (float) $wpdb->get_var($wpdb->prepare(
                    "SELECT shipping_cost FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                    $existing_order->category_id
                ));
            }
        } else {
            $shipping_price_id = $metadata['shipping_price_id'] ?? '';
            if ($shipping_price_id) {
                $amt = StripeService::get_price_amount($shipping_price_id);
                if (!is_wp_error($amt)) {
                    $shipping_cost = floatval($amt);
                }
            }
        }

        $discount_amount = ($session->total_details->amount_discount ?? 0) / 100;

        if (!$dauer && $days > 0) {
            $dauer = $days . ' Tag' . ($days > 1 ? 'e' : '');
            if ($start_date && $end_date) {
                $dauer .= ' (' . $start_date . ' - ' . $end_date . ')';
            }
        }

        $data = [
            'customer_email'    => $email,
            'customer_name'     => sanitize_text_field($session->customer_details->name ?? ''),
            'customer_phone'    => $phone,
            'customer_street'   => $street,
            'customer_postal'   => $postal,
            'customer_city'     => $city,
            'customer_country'  => $country,
            'final_price'       => (($session->amount_total ?? 0) / 100) - $shipping_cost,
            'shipping_cost'     => $shipping_cost,
            'amount_total'      => $session->amount_total ?? 0,
            'discount_amount'   => $discount_amount,
            'produkt_name'      => $produkt_name,
            'zustand_text'      => $zustand,
            'produktfarbe_text' => $produktfarbe,
            'gestellfarbe_text' => $gestellfarbe,
            'extra_text'        => $extra,
            'dauer_text'        => $dauer,
            'mode'              => ($mode === 'payment' ? 'kauf' : 'miete'),
            'start_date'        => $start_date ?: null,
            'end_date'          => $end_date ?: null,
            'inventory_reverted'=> 0,
            'user_ip'           => $user_ip,
            'user_agent'        => $user_agent,
            'stripe_subscription_id' => $subscription_id,
            'status'            => 'abgeschlossen',
            'created_at'        => current_time('mysql', 1),
        ];

        $send_welcome = false;
        if ($existing_id) {
            $send_welcome = ($existing_order->status !== 'abgeschlossen');
            $data['created_at'] = $existing_order->created_at;
            $wpdb->update(
                "{$wpdb->prefix}produkt_orders",
                $data,
                ['id' => $existing_id]
            );
            if ($send_welcome) {
                produkt_add_order_log($existing_id, 'status_updated', 'offen -> abgeschlossen');
            }
        } else {
            $data['stripe_session_id'] = $session->id;
            $data['stripe_subscription_id'] = $subscription_id;
            $wpdb->insert("{$wpdb->prefix}produkt_orders", $data);
            $existing_id = $wpdb->insert_id;
            produkt_add_order_log($existing_id, 'order_created');
            $send_welcome = true;
        }

        if ($send_welcome) {
            produkt_add_order_log($existing_id, 'checkout_completed');
            send_produkt_welcome_email($data, $existing_id);
            send_admin_order_email($data, $existing_id, $session->id);
            produkt_add_order_log($existing_id, 'welcome_email_sent');
        }

        if ($existing_order) {
            if ($existing_order->variant_id) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}produkt_variants SET stock_available = GREATEST(stock_available - 1,0), stock_rented = stock_rented + 1 WHERE id = %d",
                    $existing_order->variant_id
                ));
            }
            if (!empty($existing_order->extra_ids)) {
                $ids = array_filter(array_map('intval', explode(',', $existing_order->extra_ids)));
                foreach ($ids as $eid) {
                    $wpdb->query($wpdb->prepare(
                        "UPDATE {$wpdb->prefix}produkt_extras SET stock_available = GREATEST(stock_available - 1,0), stock_rented = stock_rented + 1 WHERE id = %d",
                        $eid
                    ));
                }
            }
        }

        if ($data['mode'] === 'kauf') {
            error_log('Checkout-Mode: ' . $data['mode']);
        }
        }
    }
    elseif ($event->type === 'payment_intent.succeeded') {
        return new WP_REST_Response(['status' => 'payment intent processed'], 200);
    }
    elseif ($event->type === 'customer.subscription.deleted') {
        $subscription     = $event->data->object;
        $subscription_id  = $subscription->id;
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}produkt_orders",
            ['status' => 'gekündigt'],
            ['stripe_subscription_id' => $subscription_id]
        );
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}produkt_orders WHERE stripe_subscription_id = %s",
            $subscription_id
        ));
        if ($order_id) {
            produkt_add_order_log((int) $order_id, 'subscription_cancelled');
        }
        return new WP_REST_Response(['status' => 'subscription cancelled'], 200);
    } elseif ($event->type === 'product.deleted') {
        $product    = $event->data->object;
        $product_id = $product->id;
        global $wpdb;
        if (!empty($product->deleted) && $product->deleted === true) {
            $wpdb->delete("{$wpdb->prefix}produkt_variants", ['stripe_product_id' => $product_id]);
            $wpdb->delete("{$wpdb->prefix}produkt_extras", ['stripe_product_id' => $product_id]);
            return new WP_REST_Response(['status' => 'product removed in plugin'], 200);
        }
        $wpdb->update(
            "{$wpdb->prefix}produkt_variants",
            ['active' => 0],
            ['stripe_product_id' => $product_id]
        );
        return new WP_REST_Response(['status' => 'product archived in plugin'], 200);
    } elseif ($event->type === 'price.deleted') {
        $price    = $event->data->object;
        $price_id = $price->id;
        global $wpdb;
        if (!empty($price->deleted) && $price->deleted === true) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}produkt_variants SET stripe_price_id = '' WHERE stripe_price_id = %s",
                $price_id
            ));
        } else {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}produkt_variants SET active = 0 WHERE stripe_price_id = %s",
                $price_id
            ));
        }
        return new WP_REST_Response(['status' => 'price unlinked in plugin'], 200);
    }

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
    $message .= '<table style="width:100%;border-collapse:collapse;">';
    $message .= '<tr style="background:#f8f9fa;"><th style="text-align:left;padding:6px;">Produkt</th><th style="text-align:left;padding:6px;">Menge</th><th style="text-align:left;padding:6px;">Variante</th><th style="text-align:left;padding:6px;">Extras</th><th style="text-align:left;padding:6px;">Farbe</th><th style="text-align:left;padding:6px;">Gestell</th><th style="text-align:left;padding:6px;">Mietdauer</th><th style="text-align:left;padding:6px;">Preis</th></tr>';
    $message .= '<tr><td style="padding:6px;">' . esc_html($order['produkt_name']) . '</td><td style="padding:6px;">1</td><td style="padding:6px;">' . esc_html($order['zustand_text']) . '</td><td style="padding:6px;">' . esc_html($order['extra_text']) . '</td><td style="padding:6px;">' . esc_html($order['produktfarbe_text']) . '</td><td style="padding:6px;">' . esc_html($order['gestellfarbe_text']) . '</td><td style="padding:6px;">' . esc_html($order['dauer_text']) . '</td><td style="padding:6px;">' . esc_html($price) . '</td></tr>';
    if ($order['shipping_cost'] > 0) {
        $message .= '<tr><td colspan="7" style="text-align:right;padding:6px;">Versand (einmalig):</td><td style="padding:6px;">' . esc_html($shipping) . '</td></tr>';
    }
    $message .= '<tr><td colspan="8" style="text-align:right;padding:6px;">Gesamtsumme: <strong>' . esc_html($total_first) . '</strong></td></tr>';
    $message .= '</table>';

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
    $message .= '<table style="width:100%;border-collapse:collapse;">';
    $message .= '<tr style="background:#f8f9fa;"><th style="text-align:left;padding:6px;">Produkt</th><th style="text-align:left;padding:6px;">Menge</th><th style="text-align:left;padding:6px;">Variante</th><th style="text-align:left;padding:6px;">Extras</th><th style="text-align:left;padding:6px;">Farbe</th><th style="text-align:left;padding:6px;">Gestell</th><th style="text-align:left;padding:6px;">Mietdauer</th><th style="text-align:left;padding:6px;">Preis</th></tr>';
    $message .= '<tr><td style="padding:6px;">' . esc_html($order['produkt_name']) . '</td><td style="padding:6px;">1</td><td style="padding:6px;">' . esc_html($order['zustand_text']) . '</td><td style="padding:6px;">' . esc_html($order['extra_text']) . '</td><td style="padding:6px;">' . esc_html($order['produktfarbe_text']) . '</td><td style="padding:6px;">' . esc_html($order['gestellfarbe_text']) . '</td><td style="padding:6px;">' . esc_html($order['dauer_text']) . '</td><td style="padding:6px;">' . esc_html($price) . '</td></tr>';
    if ($order['shipping_cost'] > 0) {
        $message .= '<tr><td colspan="7" style="text-align:right;padding:6px;">Versand (einmalig):</td><td style="padding:6px;">' . esc_html($shipping) . '</td></tr>';
    }
    $message .= '<tr><td colspan="8" style="text-align:right;padding:6px;">Gesamtsumme: <strong>' . esc_html($total_first) . '</strong></td></tr>';
    $message .= '</table>';

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
