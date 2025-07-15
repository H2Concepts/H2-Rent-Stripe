<?php
if (!defined('ABSPATH')) {
    exit;
}

use ProduktVerleih\StripeService;

function produkt_delete_or_archive_stripe_product($stripe_product_id) {
    if (!$stripe_product_id) {
        return;
    }

    $res = StripeService::init();
    if (is_wp_error($res)) {
        return;
    }

    try {
        $product = \Stripe\Product::retrieve($stripe_product_id);
        if (!$product || (isset($product->deleted) && $product->deleted)) {
            return;
        }

        try {
            $product->delete();
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            try {
                \Stripe\Product::update($stripe_product_id, ['active' => false]);
            } catch (Exception $e2) {
                error_log('Stripe update error: ' . $e2->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log('Stripe retrieve error: ' . $e->getMessage());
    }
}
