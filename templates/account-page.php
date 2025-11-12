<?php
use ProduktVerleih\Database;

require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

if (!defined('ABSPATH')) {
    exit;
}

$modus    = get_option('produkt_betriebsmodus', 'miete');
$is_sale  = ($modus === 'kauf');

if (isset($_POST['cancel_subscription'], $_POST['cancel_subscription_nonce'])) {
    if (wp_verify_nonce($_POST['cancel_subscription_nonce'], 'cancel_subscription_action')) {
        $sub_id = sanitize_text_field($_POST['subscription_id']);
        $res    = \ProduktVerleih\StripeService::cancel_subscription_at_period_end($sub_id);
        if (is_wp_error($res)) {
            $message = '<p style="color:red;">' . esc_html($res->get_error_message()) . '</p>';
        } else {
            $message = '<p>' . esc_html__('Kündigung vorgemerkt.', 'h2-concepts') . '</p>';
        }
    }
}


$orders        = [];
$sale_orders   = [];
$order_map     = [];
$subscriptions = [];
$full_name     = '';
$customer_addr = '';

if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $orders  = Database::get_orders_for_user($user_id);

    $current_user = wp_get_current_user();
    global $wpdb;
    $addr_row = $wpdb->get_row($wpdb->prepare(
        "SELECT street, postal_code, city, country FROM {$wpdb->prefix}produkt_customers WHERE email = %s",
        $current_user->user_email
    ));
    if ($addr_row) {
        $customer_addr = trim($addr_row->street . ', ' . $addr_row->postal_code . ' ' . $addr_row->city);
        if ($addr_row->country) {
            $customer_addr .= ', ' . $addr_row->country;
        }
    }

    foreach ($orders as $o) {
        if (($o->status ?? '') !== 'abgeschlossen') {
            continue;
        }
        if (!empty($o->subscription_id)) {
            $order_map[$o->subscription_id] = $o;
        }
        if ($o->mode === 'kauf') {
            $sale_orders[] = $o;
        }
    }

    $customer_id = Database::get_stripe_customer_id_for_user($user_id);
    if ($customer_id) {
        $subs = \ProduktVerleih\StripeService::get_active_subscriptions_for_customer($customer_id);
        if (!is_wp_error($subs)) {
            $subscriptions = $subs;
        }

    }

    foreach ($orders as $o) {
        if (!empty($o->customer_name)) {
            $full_name = $o->customer_name;
            break;
        }
    }

    if (!$full_name) {
        $full_name = wp_get_current_user()->display_name;
    }
}

include PRODUKT_PLUGIN_PATH . 'views/account/dashboard.php';
