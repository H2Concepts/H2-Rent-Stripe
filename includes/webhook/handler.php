<?php
// Datei: /includes/webhook/handler.php

require_once __DIR__ . '/../../../vendor/autoload.php'; // Stripe SDK laden

$payload = isset($GLOBALS['stripe_payload']) ? $GLOBALS['stripe_payload'] : file_get_contents('php://input');
$sig     = isset($GLOBALS['stripe_signature']) ? $GLOBALS['stripe_signature'] : ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
$secret  = get_option('produkt_stripe_webhook_secret', '');

$log_file = WP_CONTENT_DIR . '/uploads/webhook-test.log';
file_put_contents($log_file, "Webhook empfangen:\n" . $payload . "\n", FILE_APPEND);

try {
    \Stripe\Stripe::setApiKey(get_option('produkt_stripe_secret_key', ''));
    $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
} catch (\Exception $e) {
    file_put_contents($log_file, "Signature Error: " . $e->getMessage() . "\n", FILE_APPEND);
    exit;
}

// Spezielle Event-Verarbeitung
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    try {
        \ProduktVerleih\StripeService::process_checkout_session($session);
        file_put_contents(
            $log_file,
            "Session verarbeitet: " . json_encode($session, JSON_PRETTY_PRINT) . "\n",
            FILE_APPEND
        );
    } catch (\Exception $e) {
        file_put_contents(
            $log_file,
            "Process Error: " . $e->getMessage() . "\n",
            FILE_APPEND
        );
    }
}
