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
    } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {
        return new WP_REST_Response(['error' => 'Webhook verification failed'], 400);
    }

    if ($event->type === 'checkout.session.completed') {
        $session  = $event->data->object;
        $subscription_id = $session->subscription ?? '';
        $metadata = $session->metadata ? $session->metadata->toArray() : [];

        $produkt_name  = sanitize_text_field($metadata['produkt'] ?? '');
        $zustand       = sanitize_text_field($metadata['zustand'] ?? '');
        $produktfarbe  = sanitize_text_field($metadata['produktfarbe'] ?? '');
        $gestellfarbe  = sanitize_text_field($metadata['gestellfarbe'] ?? '');
        $extra         = sanitize_text_field($metadata['extra'] ?? '');
        $dauer         = sanitize_text_field($metadata['dauer_name'] ?? '');
        $user_ip       = sanitize_text_field($metadata['user_ip'] ?? '');
        $user_agent    = sanitize_text_field($metadata['user_agent'] ?? '');

        $email  = sanitize_email($session->customer_details->email ?? '');
        $phone  = sanitize_text_field($session->customer_details->phone ?? '');
        $addr   = $session->customer_details->address ?? null;
        $street = sanitize_text_field($addr->line1 ?? '');
        $postal = sanitize_text_field($addr->postal_code ?? '');
        $city   = sanitize_text_field($addr->city ?? '');
        $country = sanitize_text_field($addr->country ?? '');

        global $wpdb;
        $existing_order = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status, created_at FROM {$wpdb->prefix}produkt_orders WHERE stripe_session_id = %s",
            $session->id
        ));
        $existing_id = $existing_order->id ?? 0;

        $discount_amount = ($session->total_details->amount_discount ?? 0) / 100;

        $data = [
            'customer_email'    => $email,
            'customer_name'     => sanitize_text_field($session->customer_details->name ?? ''),
            'customer_phone'    => $phone,
            'customer_street'   => $street,
            'customer_postal'   => $postal,
            'customer_city'     => $city,
            'customer_country'  => $country,
            'final_price'       => ($session->amount_total ?? 0) / 100,
            'amount_total'      => $session->amount_total ?? 0,
            'discount_amount'   => $discount_amount,
            'produkt_name'      => $produkt_name,
            'zustand_text'      => $zustand,
            'produktfarbe_text' => $produktfarbe,
            'gestellfarbe_text' => $gestellfarbe,
            'extra_text'        => $extra,
            'dauer_text'        => $dauer,
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
        } else {
            $data['stripe_session_id'] = $session->id;
            $data['stripe_subscription_id'] = $subscription_id;
            $wpdb->insert("{$wpdb->prefix}produkt_orders", $data);
            $existing_id = $wpdb->insert_id;
            $send_welcome = true;
        }

        if ($send_welcome) {
            send_produkt_welcome_email($data, $existing_id);
        }

        $admin_email = get_option('admin_email');
        $subject     = 'Neue Stripe-Bestellung mit Details';
        $message     = "Neue Bestellung:\n\n";
        $message    .= "E-Mail: $email\n";
        if ($phone) {
            $message .= "Telefon: $phone\n";
        }
        if ($street) {
            $message .= "Adresse: $street, $postal $city, $country\n";
        }
        if ($produkt_name) {
            $message .= "Produkt: $produkt_name\n";
        }
        $message    .= "Zustand: $zustand\n";
        $message    .= "Produktfarbe: $produktfarbe\n";
        $message    .= "Gestellfarbe: $gestellfarbe\n";
        $message    .= "Session-ID: {$session->id}\n";

        wp_mail($admin_email, $subject, $message);
    } elseif ($event->type === 'customer.subscription.deleted') {
        $subscription = $event->data->object;
        $subscription_id = $subscription->id;
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}produkt_orders",
            [ 'status' => 'gekündigt' ],
            [ 'stripe_subscription_id' => $subscription_id ]
        );
        return new WP_REST_Response(['status' => 'subscription cancelled'], 200);
    }

    return new WP_REST_Response(['status' => 'ok'], 200);
}

function send_produkt_welcome_email(array $order, int $order_id) {
    if (empty($order['customer_email'])) {
        return;
    }

    global $wpdb;
    $branding = [];
    $results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}produkt_branding");
    foreach ($results as $row) {
        $branding[$row->setting_key] = $row->setting_value;
    }
    $company = $branding['company_name'] ?? get_bloginfo('name');

    $full_name = trim($order['customer_name']);
    if (strpos($full_name, ' ') !== false) {
        list($first, $last) = explode(' ', $full_name, 2);
    } else {
        $first = $full_name;
        $last  = '';
    }

    $subject = 'Herzlich willkommen und vielen Dank f\xC3\xBCr Ihre Bestellung bei ' . $company . '!';
    $order_date = date_i18n('d.m.Y', strtotime($order['created_at']));
    $price = number_format((float) $order['final_price'], 2, ',', '.') . '\xE2\x82\xAC';

    $address = trim($order['customer_street'] . ', ' . $order['customer_postal'] . ' ' . $order['customer_city']);

    $message  = '<html><body style="font-family:Arial,sans-serif;color:#333;">';
    $message .= '<h2 style="color:#007cba;">Herzlich willkommen und vielen Dank f\xC3\xBCr Ihre Bestellung bei ' . esc_html($company) . '!</h2>';
    $message .= '<p>Hallo ' . esc_html($first . ' ' . $last) . ',</p>';
    $message .= '<p>herzlichen Dank f\xC3\xBCr Ihre Bestellung bei ' . esc_html($company) . '!<br>Wir freuen uns sehr, Sie als neuen Kunden begr\xC3\xBC\xC3\x9Fen zu d\xC3\xBCrfen.</p>';

    $message .= '<h3>Ihre Bestell\xC3\xBCbersicht</h3>';
    $message .= '<table style="width:100%;border-collapse:collapse;">';
    $message .= '<tr><td style="padding:4px 0;"><strong>Bestellnummer:</strong></td><td>' . esc_html($order_id) . '</td></tr>';
    $message .= '<tr><td style="padding:4px 0;"><strong>Bestelldatum:</strong></td><td>' . esc_html($order_date) . '</td></tr>';
    $message .= '<tr><td style="padding:4px 0;"><strong>Status:</strong></td><td>Abgeschlossen</td></tr>';
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
    $message .= '<tr style="background:#f8f9fa;"><th style="text-align:left;padding:6px;">Produkt</th><th style="text-align:left;padding:6px;">Menge</th><th style="text-align:left;padding:6px;">Variante</th><th style="text-align:left;padding:6px;">Preis</th></tr>';
    $message .= '<tr><td style="padding:6px;">' . esc_html($order['produkt_name']) . '</td><td style="padding:6px;">1</td><td style="padding:6px;">' . esc_html($order['zustand_text']) . '</td><td style="padding:6px;">' . esc_html($price) . '</td></tr>';
    $message .= '<tr><td colspan="4" style="text-align:right;padding:6px;">Gesamtsumme: <strong>' . esc_html($price) . '</strong></td></tr>';
    $message .= '</table>';

    $message .= '<p>Bitte pr\xC3\xBCfen Sie die Angaben und antworten Sie auf diese E-Mail, falls Sie Fragen oder \xC3\x84nderungsw\xC3\xBCnsche haben.</p>';
    $message .= '</body></html>';

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    wp_mail($order['customer_email'], $subject, $message, $headers);
}
