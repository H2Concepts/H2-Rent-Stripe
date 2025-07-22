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
        error_log('Keine Stripe-Preis-ID übergeben, Abbruch.');
        return;
    }

    $secret_key = get_option('produkt_stripe_secret_key');
    $stripe = new \Stripe\StripeClient($secret_key);

    try {
        $price = $stripe->prices->retrieve($price_id);
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        error_log("Preis nicht gefunden, überspringe: $price_id");
        return;
    } catch (\Exception $e) {
        error_log('Stripe price retrieve error: ' . $e->getMessage());
        return;
    }

    if ($price->active) {
        try {
            $stripe->prices->update($price_id, ['active' => false]);
            error_log("Stripe-Preis {$price_id} deaktiviert.");
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            error_log('Stripe price archive error: ' . $e->getMessage());
        } catch (\Exception $e) {
            error_log('Stripe price archive error: ' . $e->getMessage());
        }
    }

    global $wpdb;
    $wpdb->update(
        $wpdb->prefix . 'produkt_duration_prices',
        ['stripe_archived' => 1],
        ['stripe_price_id' => $price_id],
        ['%d'],
        ['%s']
    );
}

function produkt_sync_sale_price($variant_id, $verkaufspreis_einmalig, $stripe_product_id, $mode = '') {
    if ($mode === '') {
        $mode = get_option('produkt_betriebsmodus', 'miete');
    }

    if ($verkaufspreis_einmalig > 0 && $stripe_product_id) {
        try {
            $modus = $mode;
            if ($modus === 'kauf' || $modus === 'verkauf') {
                $stripe_price = \Stripe\Price::create([
                    'unit_amount' => intval($verkaufspreis_einmalig * 100),
                    'currency'    => 'eur',
                    'product'     => $stripe_product_id,
                    'nickname'    => 'Einmalverkauf',
                ]);
            } else {
                $stripe_price = \Stripe\Price::create([
                    'unit_amount' => intval($verkaufspreis_einmalig * 100),
                    'currency'    => 'eur',
                    'product'     => $stripe_product_id,
                    'recurring'   => [
                        'interval' => 'month',
                    ],
                    'nickname'    => 'Monatspreis',
                ]);
            }

            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'produkt_variants',
                ['stripe_price_id' => $stripe_price->id],
                ['id' => $variant_id]
            );
        } catch (\Exception $e) {
            error_log('❌ Stripe-Verkaufspreis fehlgeschlagen: ' . $e->getMessage());
        }
    }
}

function produkt_hard_delete($produkt_id) {
    if (!$produkt_id) {
        return;
    }

    global $wpdb;
    $wpdb->delete(
        $wpdb->prefix . 'produkt_product_to_category',
        ['produkt_id' => $produkt_id]
    );
    $wpdb->delete(
        $wpdb->prefix . 'produkt_categories',
        ['id' => $produkt_id]
    );
}
