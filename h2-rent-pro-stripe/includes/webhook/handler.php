<?php
require_once __DIR__ . '/../stripe-php/init.php'; // Stripe SDK laden

// 💡 TEST: Wird diese Datei überhaupt ausgeführt?
file_put_contents(__DIR__ . '/test-handler.log', "Handler wurde erreicht\n", FILE_APPEND);

// Sicherstellen, dass WP-Funktionen verfügbar sind
if (!function_exists('get_option')) {
    require_once dirname(__DIR__, 3) . '/wp-load.php';
}

// Fallback für Logverzeichnis
$upload_dir = function_exists('wp_upload_dir') ? wp_upload_dir() : ['basedir' => __DIR__];
$log_file   = $upload_dir['basedir'] . '/stripe-logs/webhook-test.log';

// Payload + Signatur vorbereiten
$payload = $GLOBALS['stripe_payload'] ?? file_get_contents('php://input');
$sig     = $GLOBALS['stripe_signature'] ?? ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
$secret  = get_option('produkt_stripe_webhook_secret', '');

file_put_contents($log_file, "Webhook empfangen:\n" . $payload . "\n", FILE_APPEND);

// Stripe-Event validieren
try {
    \Stripe\Stripe::setApiKey(get_option('produkt_stripe_secret_key', ''));
    $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
} catch (\Exception $e) {
    file_put_contents($log_file, "Signature Error: " . $e->getMessage() . "\n", FILE_APPEND);
    exit;
}

// Event behandeln
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    try {
        \ProduktVerleih\StripeService::process_checkout_session($session);
        file_put_contents($log_file, "Session verarbeitet: " . json_encode($session, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    } catch (\Exception $e) {
        file_put_contents($log_file, "Process Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
