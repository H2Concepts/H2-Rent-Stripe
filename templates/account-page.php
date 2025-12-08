<?php
use ProduktVerleih\Database;

require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

if (!defined('ABSPATH')) {
    exit;
}

$modus    = get_option('produkt_betriebsmodus', 'miete');
$is_sale  = ($modus === 'kauf');

if (!isset($message)) {
    $message = '';
}

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


$orders           = [];
$sale_orders      = [];
$order_map        = [];
$subscriptions    = [];
$full_name        = '';
$customer_addr    = '';
$subscription_map = [];
$invoice_orders   = [];
$subscription_order_numbers = [];
$stripe_invoices  = [];
$customer_ids     = [];

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

$rental_orders = [];

foreach ($orders as $o) {
    if (($o->status ?? '') !== 'abgeschlossen') {
        continue;
    }

    $invoice_orders[] = $o;

    if (!empty($o->subscription_id)) {
        $subscription_order_numbers[$o->subscription_id] = !empty($o->order_number)
            ? $o->order_number
            : (string) $o->id;
    }

    if (($o->mode ?? '') === 'miete') {
        $rental_orders[] = $o;

        $produkte = pv_expand_order_products($o);
        foreach ($produkte as $idx => $prod) {
            $base_sub_id = isset($o->subscription_id) ? trim((string) $o->subscription_id) : '';
            $sub_key     = $base_sub_id !== '' ? ($base_sub_id . '-' . $idx) : ('order-' . $o->id . '-' . $idx);

            $item_data = (object) array_merge((array) $o, [
                'category_name'      => $prod->produkt_name,
                'variant_name'       => $prod->variant_name,
                'duration_name'      => $prod->duration_name,
                'condition_name'     => $prod->condition_name,
                'product_color_name' => $prod->product_color_name,
                'frame_color_name'   => $prod->frame_color_name ?? '',
                'final_price'        => $prod->final_price,
                'start_date'         => $prod->start_date ?? ($o->start_date ?? null),
                'end_date'           => $prod->end_date ?? ($o->end_date ?? null),
                'image_url'          => $prod->image_url,
                'variant_id'         => $prod->variant_id ?? 0,
                'category_id'        => $prod->category_id ?? 0,
                'duration_id'        => $prod->duration_id ?? 0,
                'product_index'      => $idx,
            ]);

            if (!isset($order_map[$sub_key])) {
                $order_map[$sub_key] = [];
            }
            $order_map[$sub_key][] = $item_data;

            if (!isset($subscription_map[$sub_key])) {
                $subscription_map[$sub_key] = [
                    'subscription_key' => $sub_key,
                    'subscription_id'  => $base_sub_id,
                    'status'           => $o->status ?? '',
                    'start_date'       => $item_data->start_date ?? '',
                ];
            }
        }
    }

    if ($o->mode === 'kauf') {
        $sale_orders[] = $o;
    }
}

    $customer_id = Database::get_stripe_customer_id_for_user($user_id);
    if ($customer_id) {
        $customer_ids[] = $customer_id;
    }

    foreach ($orders as $order_row) {
        if (!empty($order_row->stripe_customer_id)) {
            $customer_ids[] = trim((string) $order_row->stripe_customer_id);
        }
    }

    $customer_ids = array_values(array_unique(array_filter($customer_ids)));

    if (!empty($customer_ids)) {
        $invoice_lookup = [];

        foreach ($customer_ids as $cid) {
            $invoice_data = \ProduktVerleih\StripeService::get_customer_invoices($cid, 50);
            if (is_wp_error($invoice_data)) {
                $message .= '<p style="color:red;">' . esc_html($invoice_data->get_error_message()) . '</p>';
            } else {
                foreach ($invoice_data as $invoice_row) {
                    if (!empty($invoice_row['id'])) {
                        $invoice_lookup[$invoice_row['id']] = $invoice_row;
                    }
                }
            }

            $subs = \ProduktVerleih\StripeService::get_active_subscriptions_for_customer($cid);
            if (!is_wp_error($subs)) {
                foreach ($subs as $sub) {
                    $base_id = trim((string) ($sub['subscription_id'] ?? ''));
                    if ($base_id === '') {
                        continue;
                    }

                    foreach ($subscription_map as $key => $meta) {
                        if (strpos($key, $base_id) === 0) {
                            $subscription_map[$key]['status']     = $sub['status'] ?? ($meta['status'] ?? 'active');
                            $subscription_map[$key]['start_date'] = $sub['start_date'] ?? ($meta['start_date'] ?? '');
                        }
                    }
                }
            } else {
                $message .= '<p style="color:red;">' . esc_html($subs->get_error_message()) . '</p>';
            }
        }

        if (!empty($invoice_lookup)) {
            $stripe_invoices = array_values($invoice_lookup);
            usort($stripe_invoices, function ($a, $b) {
                return ($b['created'] ?? 0) <=> ($a['created'] ?? 0);
            });
        }
    }

    $subscriptions = array_values($subscription_map);

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
