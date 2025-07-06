<?php
namespace FederwiegenVerleih;

use WP_REST_Request;
use WP_REST_Response;

add_action('rest_api_init', function () {
    register_rest_route('federwiegen/v1', '/stripe-webhook', [
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

    $secret_key = get_option('federwiegen_stripe_secret_key', '');
    if ($secret_key) {
        \Stripe\Stripe::setApiKey($secret_key);
    }

    $payload    = $request->get_body();
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $secret     = defined('FEDERWIEGEN_STRIPE_WEBHOOK_SECRET') ? constant('FEDERWIEGEN_STRIPE_WEBHOOK_SECRET') : '';

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $secret);
    } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {
        return new WP_REST_Response(['error' => 'Webhook verification failed'], 400);
    }

    if ($event->type === 'checkout.session.completed') {
        $session  = $event->data->object;
        $metadata = $session->metadata ? $session->metadata->toArray() : [];
        file_put_contents(__DIR__ . '/webhook_meta_debug.log', print_r($metadata, true));

        $produkt_name  = sanitize_text_field($metadata['produkt'] ?? '');
        $zustand       = sanitize_text_field($metadata['zustand'] ?? '');
        $produktfarbe  = sanitize_text_field($metadata['produktfarbe'] ?? '');
        $gestellfarbe  = sanitize_text_field($metadata['gestellfarbe'] ?? '');
        $extra         = sanitize_text_field($metadata['extra'] ?? '');
        $dauer         = sanitize_text_field($metadata['dauer_name'] ?? '');
        $user_ip       = sanitize_text_field($metadata['user_ip'] ?? '');
        $user_agent    = sanitize_text_field($metadata['user_agent'] ?? '');
        $email         = sanitize_email($session->customer_details->email ?? '');

        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}federwiegen_orders",
            [
                'stripe_session_id' => $session->id,
                'customer_email'    => $email,
                'customer_name'     => sanitize_text_field($session->customer_details->name ?? ''),
                'amount_total'      => $session->amount_total ?? 0,
                'produkt_name'      => $produkt_name,
                'zustand_text'      => $zustand,
                'produktfarbe_text' => $produktfarbe,
                'gestellfarbe_text' => $gestellfarbe,
                'extra_text'        => $extra,
                'dauer_text'        => $dauer,
                'user_ip'           => $user_ip,
                'user_agent'        => $user_agent,
                'created_at'        => current_time('mysql', 1),
            ]
        );

        $admin_email = get_option('admin_email');
        $subject     = 'Neue Stripe-Bestellung mit Details';
        $message     = "Neue Bestellung:\n\n";
        $message    .= "E-Mail: $email\n";
        if ($produkt_name) {
            $message .= "Produkt: $produkt_name\n";
        }
        $message    .= "Zustand: $zustand\n";
        $message    .= "Produktfarbe: $produktfarbe\n";
        $message    .= "Gestellfarbe: $gestellfarbe\n";
        $message    .= "Session-ID: {$session->id}\n";

        wp_mail($admin_email, $subject, $message);
    }

    return new WP_REST_Response(['status' => 'ok'], 200);
}
