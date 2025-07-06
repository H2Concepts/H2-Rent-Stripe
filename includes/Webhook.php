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
    // Ensure the Stripe library is loaded and secret key is set
    $init = StripeService::init();
    if (is_wp_error($init)) {
        return new WP_REST_Response(['error' => 'Stripe init failed'], 500);
    }
    // Explicitly set the API key again at the very beginning as requested
    $secret_key = get_option('federwiegen_stripe_secret_key', '');
    if ($secret_key) {
        \Stripe\Stripe::setApiKey($secret_key);
    }

    $payload = $request->get_body();
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $secret = defined('FEDERWIEGEN_STRIPE_WEBHOOK_SECRET') ? constant('FEDERWIEGEN_STRIPE_WEBHOOK_SECRET') : '';



    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $secret);
    } catch (\UnexpectedValueException $e) {
        return new WP_REST_Response(['error' => 'Invalid payload'], 400);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        return new WP_REST_Response(['error' => 'Invalid signature'], 400);
    }

    if ($event->type === 'checkout.session.completed') {
        $session = $event->data->object;
        $customer_email  = $session->customer_details->email ?? '';
        $subscription_id = $session->subscription;
        $shipping        = $session->shipping_details ?? null;

        // Place for additional processing if needed (e.g., store order data)
    }

    return new WP_REST_Response(['status' => 'ok'], 200);
}
