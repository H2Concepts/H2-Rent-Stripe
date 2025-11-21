<?php
require_once plugin_dir_path(__FILE__) . 'stripe-php/init.php';

if (!defined('ABSPATH')) {
    exit;
}

use ProduktVerleih\StripeService;
use ProduktVerleih\Database;

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
            // Stripe product no longer exists; mark local entry as archived
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
            // ignore Stripe archive error
        }
    } catch (\Exception $e) {
        // ignore Stripe archive error
    }
}

function produkt_deactivate_stripe_price($price_id) {
    if (!$price_id) {
        // no stripe price id provided
        return;
    }

    $secret_key = get_option('produkt_stripe_secret_key');
    $stripe = new \Stripe\StripeClient($secret_key);

    try {
        $price = $stripe->prices->retrieve($price_id);
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        // price not found on Stripe
        return;
    } catch (\Exception $e) {
        // ignore Stripe retrieve error
        return;
    }

    if ($price->active) {
        try {
            $stripe->prices->update($price_id, ['active' => false]);
            // price deactivated
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // ignore archive error
        } catch (\Exception $e) {
            // ignore archive error
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

function produkt_sync_weekend_price($variant_id, $weekend_price, $stripe_product_id) {
    if ($weekend_price > 0 && $stripe_product_id) {
        try {
            $stripe_price = \Stripe\Price::create([
                'unit_amount' => intval($weekend_price * 100),
                'currency'    => 'eur',
                'product'     => $stripe_product_id,
                'nickname'    => 'Wochenendtarif',
                'metadata'    => ['type' => 'weekend']
            ]);

            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'produkt_variants',
                ['stripe_weekend_price_id' => $stripe_price->id],
                ['id' => $variant_id]
            );
        } catch (\Exception $e) {
            // ignore Stripe weekend price error
        }
    }
}

function produkt_hard_delete($produkt_id) {
    if (!$produkt_id) {
        return;
    }

    global $wpdb;

    // Collect variants so we can clean up everything tied to them
    $variants = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, stripe_product_id FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d",
            $produkt_id
        )
    );

    $variant_ids = array();
    if ($variants) {
        foreach ($variants as $variant) {
            $variant_ids[] = $variant->id;
            if (!empty($variant->stripe_product_id)) {
                produkt_delete_or_archive_stripe_product($variant->stripe_product_id, $variant->id, 'produkt_variants');
            }
        }
    }

    if (!empty($variant_ids)) {
        $placeholders = implode(',', array_fill(0, count($variant_ids), '%d'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}produkt_color_variant_images WHERE variant_id IN ($placeholders)", ...$variant_ids));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}produkt_variant_options WHERE variant_id IN ($placeholders)", ...$variant_ids));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}produkt_variant_durations WHERE variant_id IN ($placeholders)", ...$variant_ids));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}produkt_duration_prices WHERE variant_id IN ($placeholders)", ...$variant_ids));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}produkt_variants WHERE id IN ($placeholders)", ...$variant_ids));
    }

    // Remove extras linked to the product
    $extras = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, stripe_product_id FROM {$wpdb->prefix}produkt_extras WHERE category_id = %d",
            $produkt_id
        )
    );
    if ($extras) {
        foreach ($extras as $extra) {
            if (!empty($extra->stripe_product_id)) {
                produkt_delete_or_archive_stripe_product($extra->stripe_product_id, $extra->id, 'produkt_extras');
            }
        }
        $wpdb->delete($wpdb->prefix . 'produkt_extras', ['category_id' => $produkt_id]);
    }

    // Remove durations belonging to the product
    $wpdb->delete($wpdb->prefix . 'produkt_durations', ['category_id' => $produkt_id]);

    // Remove conditions
    $wpdb->delete($wpdb->prefix . 'produkt_conditions', ['category_id' => $produkt_id]);

    // Remove colors and linked variant images
    $color_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}produkt_colors WHERE category_id = %d", $produkt_id));
    if (!empty($color_ids)) {
        $color_placeholders = implode(',', array_fill(0, count($color_ids), '%d'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}produkt_color_variant_images WHERE color_id IN ($color_placeholders)", ...$color_ids));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}produkt_colors WHERE id IN ($color_placeholders)", ...$color_ids));
    }

    // Remove category filters, relationships and content blocks
    $wpdb->delete($wpdb->prefix . 'produkt_category_filters', ['category_id' => $produkt_id]);
    $wpdb->delete($wpdb->prefix . 'produkt_product_to_category', ['produkt_id' => $produkt_id]);
    $wpdb->delete($wpdb->prefix . 'produkt_content_blocks', ['category_id' => $produkt_id]);
    if (class_exists(Database::class)) {
        Database::clear_content_blocks_cache($produkt_id);
    }

    // Remove shipping options tied to the product
    $wpdb->delete($wpdb->prefix . 'produkt_shipping', ['category_id' => $produkt_id]);

    // Finally remove the product itself
    $result = $wpdb->delete(
        $wpdb->prefix . 'produkt_categories',
        ['id' => $produkt_id]
    );

    return $result !== false;
}
