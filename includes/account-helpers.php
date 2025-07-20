<?php
if (!defined('ABSPATH')) { exit; }

function pv_get_variant_image_url($variant_id) {
    $img_id = get_post_meta($variant_id, 'produkt_variant_image_id', true);
    return $img_id ? wp_get_attachment_image_url($img_id, 'medium') : '';
}

function pv_format_subscription_period($start, $end) {
    return date('d.m.Y', strtotime($start)) . ' – ' . date('d.m.Y', strtotime($end));
}

function pv_get_subscription_status_badge($status) {
    switch ($status) {
        case 'active':
        case 'trialing':
            return '<span class="badge badge-success">Aktiv</span>';
        case 'past_due':
            return '<span class="badge badge-warning">Zahlung überfällig</span>';
        default:
            return '<span class="badge badge-default">Unbekannt</span>';
    }
}

function pv_get_minimum_duration_months($order) {
    global $wpdb;
    if ($order && !empty($order->duration_id)) {
        $months = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT months_minimum FROM {$wpdb->prefix}produkt_durations WHERE id = %d",
                $order->duration_id
            )
        );
        if ($months) {
            return $months;
        }
    }
    return 3;
}

/**
 * Retrieve the best image URL for a subscription's variant or category.
 *
 * @param int $variant_id  Variant ID from the order.
 * @param int $category_id Category ID from the order.
 * @return string Image URL or empty string when none found.
 */
function pv_get_image_url_by_variant_or_category($variant_id, $category_id) {
    global $wpdb;

    $image_url = '';
    if ($variant_id) {
        $image_url = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT image_url_1 FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
                $variant_id
            )
        );
    }

    if (empty($image_url) && $category_id) {
        $image_url = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT default_image FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                $category_id
            )
        );
    }

    return $image_url ?: '';
}
