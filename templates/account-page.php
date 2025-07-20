<?php
use ProduktVerleih\Database;

require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

if (!defined('ABSPATH')) {
    exit;
}

$db = new Database();

if (isset($_POST['cancel_subscription'], $_POST['cancel_subscription_nonce'])) {
    if (wp_verify_nonce($_POST['cancel_subscription_nonce'], 'cancel_subscription_action')) {
        $sub_id = sanitize_text_field($_POST['subscription_id']);
        $res    = \ProduktVerleih\StripeService::cancel_subscription_at_period_end($sub_id);
        if (is_wp_error($res)) {
            $message = '<p style="color:red;">' . esc_html($res->get_error_message()) . '</p>';
        } else {
            $message = '<p>K\xFCndigung vorgemerkt.</p>';
        }
    }
}

$subscriptions = [];
$current_user_id = get_current_user_id();
if ($current_user_id) {
    $stripe_customer_id = $db->get_stripe_customer_id_for_user($current_user_id);
    if ($stripe_customer_id) {
        $subs = \ProduktVerleih\StripeService::get_active_subscriptions_for_customer($stripe_customer_id);
        if (!is_wp_error($subs)) {
            $subscriptions = $subs;
        }
    }
}

$orders    = [];
$full_name = '';
if (is_user_logged_in()) {
    $orders = Database::get_orders_for_user(get_current_user_id());
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
