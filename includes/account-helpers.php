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
            $class = 'active';
            $label = 'Aktiv';
            break;
        case 'canceled':
        case 'cancelled':
            $class = 'cancelled';
            $label = 'Gekündigt';
            break;
        default:
            $class = 'scheduled';
            $label = ucfirst($status);
            break;
    }

    return '<span class="status-badge ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
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

/**
 * Determine the start and end dates for an order.
 * Falls back to parsing the dauer_text when the explicit
 * date columns are empty.
 *
 * @param object $order Order row object
 * @return array{0:?string,1:?string} ISO dates or null when unavailable
 */
function pv_get_order_period($order) {
    $start = '';
    $end   = '';
    if (!empty($order->start_date) && !empty($order->end_date)) {
        $start = $order->start_date;
        $end   = $order->end_date;
    } elseif (!empty($order->dauer_text)) {
        if (preg_match('/(\d{4}-\d{2}-\d{2})\s*-\s*(\d{4}-\d{2}-\d{2})/', $order->dauer_text, $m)) {
            $start = $m[1];
            $end   = $m[2];
        } elseif (preg_match('/(\d{2}\.\d{2}\.\d{4})\s*-\s*(\d{2}\.\d{2}\.\d{4})/', $order->dauer_text, $m)) {
            $d1 = DateTime::createFromFormat('d.m.Y', $m[1]);
            $d2 = DateTime::createFromFormat('d.m.Y', $m[2]);
            if ($d1 && $d2) {
                $start = $d1->format('Y-m-d');
                $end   = $d2->format('Y-m-d');
            }
        }
    }

    if ($start && $end) {
        return [$start, $end];
    }

    return [null, null];
}

/**
 * Calculate the number of rental days between two dates (inclusive).
 *
 * @param string $start ISO start date.
 * @param string $end   ISO end date.
 * @return int|null Rental days or null when invalid.
 */
function pv_calc_rental_days($start, $end) {
    if ($start && $end) {
        $s = new DateTime($start);
        $e = new DateTime($end);
        return $e->diff($s)->days + 1;
    }
    return null;
}

/**
 * Determine an order's rental days from its start and end date.
 *
 * @param object $order Order row object
 * @return int|null Number of days or null when unavailable
 */
function pv_get_order_rental_days($order) {
    list($s, $e) = pv_get_order_period($order);
    return pv_calc_rental_days($s, $e);
}
