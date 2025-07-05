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
        error_log('Stripe init error: ' . $init->get_error_message());
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

    // Log details for debugging signature verification issues
    error_log('[Stripe Webhook] Signature Header: ' . print_r($sig_header, true));
    error_log('[Stripe Webhook] Payload: ' . $payload);
    error_log('[Stripe Webhook] Secret: ' . $secret);

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $secret);
    } catch (\UnexpectedValueException $e) {
        return new WP_REST_Response(['error' => 'Invalid payload'], 400);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        return new WP_REST_Response(['error' => 'Invalid signature'], 400);
    }

    if ($event->type === 'checkout.session.completed') {
        $session        = $event->data->object;
        $customer_id    = $session->customer;
        $subscription_id = $session->subscription;

        $metadata = $session->metadata ?? [];
        $shipping_price_id = is_array($metadata)
            ? ($metadata['shipping_price_id'] ?? '')
            : (is_object($metadata) ? ($metadata->shipping_price_id ?? '') : '');

        if ($customer_id && $shipping_price_id) {
            try {
                \Stripe\InvoiceItem::create([
                    'customer'    => $customer_id,
                    'price'       => $shipping_price_id,
                    'description' => 'Versandkosten (einmalig)',
                    'subscription' => $subscription_id,
                ]);
            } catch (\Exception $e) {
                error_log('Stripe InvoiceItem Error: ' . $e->getMessage());
            }
        }
    }

    return new WP_REST_Response(['status' => 'ok'], 200);
}
