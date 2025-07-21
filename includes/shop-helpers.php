<?php
if (!defined('ABSPATH')) { exit; }

use ProduktVerleih\StripeService;

/**
 * Get the lowest Stripe price for all variants and durations in a category.
 *
 * @param int $category_id Category ID.
 * @return array{amount: ?float, price_id: ?string, count: int}
 */
function pv_get_lowest_stripe_price_by_category($category_id) {
    global $wpdb;

    $variant_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d",
            $category_id
        )
    );

    $duration_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}produkt_durations WHERE category_id = %d",
            $category_id
        )
    );

    $price_data = StripeService::get_lowest_price_with_durations($variant_ids, $duration_ids);

    $price_count = 0;
    if (!empty($variant_ids) && !empty($duration_ids)) {
        $placeholders_variant  = implode(',', array_fill(0, count($variant_ids), '%d'));
        $placeholders_duration = implode(',', array_fill(0, count($duration_ids), '%d'));
        $count_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_duration_prices
             WHERE variant_id IN ($placeholders_variant)
               AND duration_id IN ($placeholders_duration)",
            array_merge($variant_ids, $duration_ids)
        );
        $price_count = (int) $wpdb->get_var($count_query);
    }

    return [
        'amount'   => $price_data['amount'] ?? null,
        'price_id' => $price_data['price_id'] ?? null,
        'count'    => $price_count,
    ];
}

/**
 * Format a price label based on price data.
 *
 * @param array|null $price_data Price data array from pv_get_lowest_stripe_price_by_category.
 * @return string Formatted price string.
 */
function pv_format_price_label($price_data) {
    if (!$price_data || !isset($price_data['amount'])) {
        return 'Preis auf Anfrage';
    }

    $formatted = number_format((float) $price_data['amount'], 2, ',', '.');
    if (($price_data['count'] ?? 0) > 1) {
        return 'ab ' . $formatted . '€';
    }

    return $formatted . '€';
}

