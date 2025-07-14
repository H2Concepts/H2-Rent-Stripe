<?php
namespace ProduktVerleih;

class StripeService {
    private static function load_library() {
        $stripe_init = plugin_dir_path(__FILE__) . '/../vendor/stripe/stripe-php/init.php';
        if (file_exists($stripe_init)) {
            require_once $stripe_init;
        }
        if (!class_exists('\\Stripe\\Stripe')) {
            return new \WP_Error('stripe_library', 'Stripe library not found');
        }
        return true;
    }

    private static function set_secret_key() {
        $secret = get_option('produkt_stripe_secret_key', '');
        if (empty($secret)) {
            return new \WP_Error('stripe_secret', 'Stripe secret key not set');
        }
        \Stripe\Stripe::setApiKey($secret);
        return true;
    }

    public static function init() {
        $res = self::load_library();
        if (is_wp_error($res)) {
            return $res;
        }
        return self::set_secret_key();
    }

    public static function create_payment_intent(array $params) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        return \Stripe\PaymentIntent::create($params);
    }

    public static function create_customer(array $params) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        return \Stripe\Customer::create($params);
    }

    public static function create_subscription(array $params) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        return \Stripe\Subscription::create($params);
    }

    public static function get_price_amount($price_id) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        try {
            $price = \Stripe\Price::retrieve($price_id);
            return $price->unit_amount / 100;
        } catch (\Exception $e) {
            return new \WP_Error('stripe_price', $e->getMessage());
        }
    }

    /**
     * Retrieve the Stripe price amount while caching results using a transient.
     *
     * @param string $price_id
     * @param int    $expiration Number of seconds to cache the value
     * @return float|\WP_Error
     */
    public static function get_cached_price_amount($price_id, $expiration = 43200) {
        $cache_key = 'produkt_stripe_price_' . $price_id;
        $cached    = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $amount = self::get_price_amount($price_id);

        if (!is_wp_error($amount)) {
            set_transient($cache_key, $amount, $expiration);
        }

        return $amount;
    }

    /**
     * Retrieve and cache the Stripe price as a formatted string.
     */
    public static function get_cached_price($price_id, $expiration = 43200) {
        $cache_key = 'produkt_stripe_price_formatted_' . $price_id;
        $cached    = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $amount = self::get_cached_price_amount($price_id, $expiration);

        if (!is_wp_error($amount)) {
            $formatted = number_format((float) $amount, 2, ',', '.');
            set_transient($cache_key, $formatted, $expiration);
            return $formatted;
        }

        return $amount;
    }

    /**
     * Retrieve the lowest price among multiple Stripe price IDs with caching.
     *
     * @param array $price_ids Array of Stripe price IDs
     * @param int   $expiration Cache lifetime in seconds
     * @return float|null|\WP_Error Formatted amount or WP_Error on failure
     */
    public static function get_lowest_price_cached($price_ids, $expiration = 43200) {
        if (empty($price_ids) || !is_array($price_ids)) {
            return null;
        }

        $cache_key = 'produkt_lowest_stripe_price_' . md5(implode('_', $price_ids));
        $cached    = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }

        $lowest = null;
        foreach ($price_ids as $price_id) {
            try {
                $price = \Stripe\Price::retrieve($price_id);
                if (!isset($price->unit_amount)) {
                    continue;
                }
                $amount = $price->unit_amount / 100;
                if ($lowest === null || $amount < $lowest) {
                    $lowest = $amount;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        if ($lowest !== null) {
            set_transient($cache_key, $lowest, $expiration);
        }

        return $lowest;
    }

    /**
     * Retrieve the lowest Stripe price among multiple IDs and cache the formatted result.
     */
    public static function get_lowest_price_formatted($price_ids, $expiration = 43200) {
        $cache_key = 'produkt_lowest_price_formatted_' . md5(implode('_', $price_ids));
        $cached    = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $lowest = self::get_lowest_price_cached($price_ids, $expiration);

        if ($lowest !== null && !is_wp_error($lowest)) {
            $formatted = number_format((float) $lowest, 2, ',', '.');
            set_transient($cache_key, $formatted, $expiration);
            return $formatted;
        }

        return $lowest;
    }

    /**
     * Determine the lowest price across all variant/duration combinations.
     * Returns array with 'amount' and 'price_id' keys or null on failure.
     */
    public static function get_lowest_price_with_durations($variant_ids, $duration_ids, $expiration = 43200) {
        if (empty($variant_ids)) {
            return null;
        }

        sort($variant_ids);
        sort($duration_ids);
        $cache_key = 'lowest_price_cache_' . md5(implode('_', $variant_ids) . '|' . implode('_', $duration_ids));
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;

        $lowest = null;
        $lowest_id = '';

        // 1. Variante + Dauer kombiniert
        if (!empty($duration_ids)) {
            $placeholders_v = implode(',', array_fill(0, count($variant_ids), '%d'));
            $placeholders_d = implode(',', array_fill(0, count($duration_ids), '%d'));

            $sql = "SELECT stripe_price_id FROM {$wpdb->prefix}produkt_duration_prices WHERE variant_id IN ($placeholders_v) AND duration_id IN ($placeholders_d)";
            $query = $wpdb->prepare($sql, array_merge($variant_ids, $duration_ids));
            $prices = $wpdb->get_col($query);

            foreach ($prices as $pid) {
                $amount = self::get_cached_price_amount($pid, $expiration);
                if (is_wp_error($amount)) continue;
                if ($lowest === null || $amount < $lowest) {
                    $lowest = $amount;
                    $lowest_id = $pid;
                }
            }
        }

        // 2. Fallback: Direkt aus variants ohne Dauer
        if ($lowest === null) {
            $placeholders_v = implode(',', array_fill(0, count($variant_ids), '%d'));
            $sql = "SELECT stripe_price_id FROM {$wpdb->prefix}produkt_variants WHERE id IN ($placeholders_v)";
            $query = $wpdb->prepare($sql, $variant_ids);
            $variant_prices = $wpdb->get_col($query);

            foreach ($variant_prices as $pid) {
                if (!$pid) continue;
                $amount = self::get_cached_price_amount($pid, $expiration);
                if (is_wp_error($amount)) continue;
                if ($lowest === null || $amount < $lowest) {
                    $lowest = $amount;
                    $lowest_id = $pid;
                }
            }
        }

        $result = null;
        if ($lowest !== null) {
            $result = ['amount' => $lowest, 'price_id' => $lowest_id];
            set_transient($cache_key, $result, $expiration);
        }

        return $result;
    }

    public static function get_publishable_key() {
        return get_option('produkt_stripe_publishable_key', '');
    }

    public static function get_payment_method_configuration_id() {
        return get_option('produkt_stripe_pmc_id', '');
    }

    /**
     * Retrieve active subscriptions for a given customer.
     *
     * @param string $customer_id Stripe customer ID
     * @return array|\WP_Error
     */
    public static function get_active_subscriptions_for_customer($customer_id) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        try {
            $subs = \Stripe\Subscription::all([
                'customer' => $customer_id,
                'status'   => 'all',
                'limit'    => 100,
            ]);
            $result = [];
            foreach ($subs->autoPagingIterator() as $sub) {
                if (in_array($sub->status, ['active', 'trialing', 'past_due'], true)) {
                    $result[] = [
                        'subscription_id'      => $sub->id,
                        'start_date'           => date('Y-m-d H:i:s', $sub->start_date),
                        'cancel_at_period_end' => $sub->cancel_at_period_end,
                        'current_period_start' => isset($sub->current_period_start) ? date('Y-m-d H:i:s', $sub->current_period_start) : null,
                        'current_period_end'   => isset($sub->current_period_end) ? date('Y-m-d H:i:s', $sub->current_period_end) : null,
                        'status'               => $sub->status,
                    ];
                }
            }
            return $result;
        } catch (\Exception $e) {
            return new \WP_Error('stripe_subscriptions', $e->getMessage());
        }
    }

    /**
     * Mark a subscription to cancel at the period end.
     *
     * @param string $subscription_id
     * @return true|\WP_Error
     */
    public static function cancel_subscription_at_period_end($subscription_id) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        try {
            \Stripe\Subscription::update($subscription_id, ['cancel_at_period_end' => true]);
            return true;
        } catch (\Exception $e) {
            return new \WP_Error('stripe_cancel', $e->getMessage());
        }
    }

    /**
     * Update an existing Stripe product name.
     *
     * @param string $product_id Stripe product ID
     * @param string $new_name   New product name
     * @return \Stripe\Product|\WP_Error
     */
    public static function update_product_name($product_id, $new_name) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }

        try {
            return \Stripe\Product::update($product_id, ['name' => $new_name]);
        } catch (\Exception $e) {
            return new \WP_Error('stripe_product_update', $e->getMessage());
        }
    }

    /**
     * Create a new price for the given Stripe product.
     *
     * @param string $product_id     Stripe product ID
     * @param int    $amount_cents   Price amount in cents
     * @param string $mode           Pricing mode (miete|kauf)
     * @return \Stripe\Price|\WP_Error
     */
    public static function create_price($product_id, $amount_cents, $mode = null) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }

        if ($mode === null) {
            $mode = get_option('produkt_betriebsmodus', 'miete');
        }

        $params = [
            'unit_amount' => (int) $amount_cents,
            'currency'    => 'eur',
            'product'     => $product_id,
        ];

        if ($mode === 'miete') {
            $params['recurring'] = ['interval' => 'month'];
        }

        try {
            return \Stripe\Price::create($params);
        } catch (\Exception $e) {
            return new \WP_Error('stripe_price_create', $e->getMessage());
        }
    }

    /**
     * Erstellt ein Stripe-Produkt + Preis je nach Modus.
     *
     * @param array $product_data {
     *     @type string   $name
     *     @type float    $price
     *     @type string   $mode       'miete'|'kauf'
     *     @type int      $plugin_product_id
     *     @type int      $variant_id
     *     @type int|null $duration_id
     * }
     * @return array|\WP_Error
     */
    public static function create_or_update_product_and_price($product_data) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }

        try {
            $cache_key = 'produkt_stripe_pair_' . md5(json_encode($product_data));
            $cached    = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }

            $query = sprintf(
                'metadata["plugin_product_id"]:"%d" AND metadata["variant_id"]:"%d" AND metadata["duration_id"]:"%d" AND metadata["mode"]:"%s"',
                $product_data['plugin_product_id'],
                $product_data['variant_id'],
                $product_data['duration_id'] ?? 0,
                $product_data['mode']
            );

            $found = \Stripe\Product::search(['query' => $query, 'limit' => 1]);
            $stripe_product = $found && !empty($found->data) ? $found->data[0] : null;

            if (!$stripe_product) {
                $stripe_product = \Stripe\Product::create([
                    'name'     => $product_data['name'],
                    'metadata' => [
                        'plugin_product_id' => $product_data['plugin_product_id'],
                        'variant_id'        => $product_data['variant_id'],
                        'duration_id'       => $product_data['duration_id'] ?? 0,
                        'mode'              => $product_data['mode'],
                    ],
                ]);
            }

            $prices = \Stripe\Price::all(['product' => $stripe_product->id, 'limit' => 100]);
            $stripe_price = null;
            $amount = (int) ($product_data['price'] * 100);
            foreach ($prices->data as $p) {
                $match = $p->unit_amount == $amount && $p->currency === 'eur';
                if ($product_data['mode'] === 'miete') {
                    $match = $match && isset($p->recurring) && $p->recurring->interval === 'month';
                } else {
                    $match = $match && !isset($p->recurring);
                }
                if ($match) {
                    $stripe_price = $p;
                    break;
                }
            }

            if (!$stripe_price) {
                $price_params = [
                    'unit_amount' => $amount,
                    'currency'    => 'eur',
                    'product'     => $stripe_product->id,
                ];

                if ($product_data['mode'] === 'miete') {
                    $price_params['recurring'] = [
                        'interval' => 'month',
                    ];
                }

                $stripe_price = \Stripe\Price::create($price_params);
            }

            $result = [
                'stripe_product_id' => $stripe_product->id,
                'stripe_price_id'   => $stripe_price->id,
            ];

            set_transient($cache_key, $result, DAY_IN_SECONDS);

            return $result;

        } catch (\Exception $e) {
            return new \WP_Error('stripe_create', $e->getMessage());
        }
    }
}
