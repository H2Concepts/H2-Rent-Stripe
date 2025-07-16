<?php
require_once __DIR__ . '/stripe-autoload.php';
if (!defined('ABSPATH')) {
    exit;
}

use ProduktVerleih\StripeService;

function produkt_delete_or_archive_stripe_product($product_id) {
    if (!$product_id) {
        return;
    }

    try {
        \Stripe\Stripe::setApiKey(get_option('produkt_stripe_secret_key'));

        // 1. Produkt bei Stripe archivieren
        \Stripe\Product::update($product_id, ['active' => false]);

        // 2. Alle zugehörigen Preise ebenfalls deaktivieren
        $prices = \Stripe\Price::all([
            'product' => $product_id,
            'limit'   => 100,
        ]);

        foreach ($prices->data as $price) {
            if ($price->active) {
                \Stripe\Price::update($price->id, ['active' => false]);
            }
        }

    } catch (\Exception $e) {
        error_log('Stripe archive error: ' . $e->getMessage());
    }
}
