<?php
require_once plugin_dir_path(__FILE__) . 'stripe-php/init.php';

if (!defined('ABSPATH')) {
    exit;
}

use ProduktVerleih\StripeService;

function produkt_delete_or_archive_stripe_product($product_id, $local_id = null, $table = 'produkt_variants') {
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

        if ($local_id && in_array($table, ['produkt_variants', 'produkt_extras'])) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . $table,
                ['stripe_archived' => 1],
                ['id' => $local_id],
                ['%d'],
                ['%d']
            );
        }

    } catch (\Stripe\Exception\InvalidRequestException $e) {
        if (strpos($e->getMessage(), 'No such product') !== false) {
            error_log('Stripe-Produkt existiert nicht mehr – wird lokal archiviert: ' . $product_id);
            if ($local_id && in_array($table, ['produkt_variants', 'produkt_extras'])) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . $table,
                    ['stripe_archived' => 1],
                    ['id' => $local_id],
                    ['%d'],
                    ['%d']
                );
            }
        } else {
            error_log('Stripe archive error: ' . $e->getMessage());
        }
    } catch (\Exception $e) {
        error_log('Stripe archive error: ' . $e->getMessage());
    }
}

function produkt_deactivate_stripe_price($price_id) {
    if (!$price_id) {
        return;
    }

    try {
        \Stripe\Stripe::setApiKey(get_option('produkt_stripe_secret_key'));

        $price = \Stripe\Price::retrieve($price_id);
        if ($price && $price->active) {
            \Stripe\Price::update($price_id, ['active' => false]);
        }

        // Mark local record as archived
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'produkt_duration_prices',
            ['stripe_archived' => 1],
            ['stripe_price_id' => $price_id],
            ['%d'],
            ['%s']
        );

    } catch (\Exception $e) {
        error_log('Stripe price archive error: ' . $e->getMessage());
    }
}
