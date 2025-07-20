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
