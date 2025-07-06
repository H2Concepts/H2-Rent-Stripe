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
        $session         = $event->data->object;
        $customer_id     = $session->customer;
        $subscription_id = $session->subscription;

        $metadata = $session->metadata ?? [];
        $shipping_price_id = is_array($metadata)
            ? ($metadata['shipping_price_id'] ?? '')
            : (is_object($metadata) ? ($metadata->shipping_price_id ?? '') : '');

        if ($customer_id && $shipping_price_id && $subscription_id) {
            try {
                $invoices = \Stripe\Invoice::all([
                    'subscription' => $subscription_id,
                    'limit' => 1,
                ]);

                if (count($invoices->data)) {
                    $invoice = $invoices->data[0];

                    // Hole das Price-Objekt (für Fallback mit price_data)
                    $price_object = \Stripe\Price::retrieve($shipping_price_id);

                    if ($invoice->status === 'draft') {
                        try {
                            \Stripe\InvoiceItem::create([
                                'customer' => $customer_id,
                                'price'    => $shipping_price_id,
                                'description' => 'Versandkosten (einmalig)',
                                'invoice' => $invoice->id,
                            ]);
                        } catch (\Stripe\Exception\InvalidRequestException $e) {
                            if (str_contains($e->getMessage(), 'Received unknown parameter: price')) {
                                \Stripe\InvoiceItem::create([
                                    'customer' => $customer_id,
                                    'price_data' => [
                                        'currency'     => $price_object->currency,
                                        'unit_amount'  => $price_object->unit_amount,
                                        'product'      => $price_object->product,
                                    ],
                                    'description' => 'Versandkosten (einmalig)',
                                    'invoice' => $invoice->id,
                                ]);
                            } else {
                                throw $e;
                            }
                        }

                        \Stripe\Invoice::finalizeInvoice($invoice->id);
                    } else {
                        try {
                            \Stripe\InvoiceItem::create([
                                'customer' => $customer_id,
                                'price'    => $shipping_price_id,
                                'description' => 'Versandkosten (einmalig)',
                            ]);
                        } catch (\Stripe\Exception\InvalidRequestException $e) {
                            if (str_contains($e->getMessage(), 'Received unknown parameter: price')) {
                                \Stripe\InvoiceItem::create([
                                    'customer' => $customer_id,
                                    'price_data' => [
                                        'currency'     => $price_object->currency,
                                        'unit_amount'  => $price_object->unit_amount,
                                        'product'      => $price_object->product,
                                    ],
                                    'description' => 'Versandkosten (einmalig)',
                                ]);
                            } else {
                                throw $e;
                            }
                        }

                        \Stripe\Invoice::create([
                            'customer'     => $customer_id,
                            'subscription' => $subscription_id,
                            'auto_advance' => true,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                error_log('Stripe Webhook Error: ' . $e->getMessage());
            }
        }
    }

    return new WP_REST_Response(['status' => 'ok'], 200);
}
