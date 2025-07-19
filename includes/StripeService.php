<?php
namespace ProduktVerleih;

class StripeService {
    private static function load_library() {
        $stripe_init = plugin_dir_path(__FILE__) . 'stripe-php/init.php';
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
        global $wpdb;

        $variant_ids  = array_map('intval', (array) $variant_ids);
        $duration_ids = array_map('intval', (array) $duration_ids);

        if (!empty($variant_ids)) {
            $placeholders = implode(',', array_fill(0, count($variant_ids), '%d'));
            $sql         = "SELECT id FROM {$wpdb->prefix}produkt_variants WHERE id IN ($placeholders) AND available = 1";
            $variant_ids = $wpdb->get_col($wpdb->prepare($sql, $variant_ids));
        }

        if (empty($variant_ids)) {
            return null;
        }

        if (!empty($duration_ids)) {
            $placeholders = implode(',', array_fill(0, count($duration_ids), '%d'));
            $sql          = "SELECT id FROM {$wpdb->prefix}produkt_durations WHERE id IN ($placeholders) AND active = 1";
            $duration_ids = $wpdb->get_col($wpdb->prepare($sql, $duration_ids));
        }

        sort($variant_ids);
        sort($duration_ids);
        $cache_key = 'lowest_price_cache_' . md5(implode('_', $variant_ids) . '|' . implode('_', $duration_ids));
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $lowest    = null;
        $lowest_id = '';

        // Fetch base data for variants
        $placeholders_v = implode(',', array_fill(0, count($variant_ids), '%d'));
        $variants_data  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, stripe_price_id, base_price, mietpreis_monatlich FROM {$wpdb->prefix}produkt_variants WHERE id IN ($placeholders_v)",
                $variant_ids
            ),
            OBJECT_K
        );

        // Fetch duration specific prices once
        $duration_map = [];
        if (!empty($duration_ids)) {
            $placeholders_d = implode(',', array_fill(0, count($duration_ids), '%d'));
            $sql   = "SELECT variant_id, duration_id, stripe_price_id, custom_price FROM {$wpdb->prefix}produkt_duration_prices WHERE variant_id IN ($placeholders_v) AND duration_id IN ($placeholders_d)";
            $query = $wpdb->prepare($sql, array_merge($variant_ids, $duration_ids));
            $rows  = $wpdb->get_results($query);
            foreach ($rows as $row) {
                $duration_map[$row->variant_id][$row->duration_id] = $row;
            }
        }

        foreach ($variants_data as $v_id => $variant) {
            $base = floatval($variant->base_price);
            if ($base <= 0) {
                $base = floatval($variant->mietpreis_monatlich);
            }
            $base_amount = null;
            if (!empty($variant->stripe_price_id)) {
                $res = self::get_cached_price_amount($variant->stripe_price_id, $expiration);
                if (!is_wp_error($res)) {
                    $base_amount = $res;
                }
            }
            if ($base_amount === null) {
                $base_amount = $base;
            }

            if (empty($duration_ids)) {
                if ($base_amount !== null && ($lowest === null || $base_amount < $lowest)) {
                    $lowest    = $base_amount;
                    $lowest_id = $variant->stripe_price_id;
                }
                continue;
            }

            foreach ($duration_ids as $d_id) {
                $row    = $duration_map[$v_id][$d_id] ?? null;
                $amount = null;
                $pid    = '';

                if ($row) {
                    $stripe_amount = null;
                    $custom_amount = null;

                    if (!empty($row->stripe_price_id)) {
                        $stripe_amount = self::get_cached_price_amount($row->stripe_price_id, $expiration);
                        if (is_wp_error($stripe_amount)) {
                            $stripe_amount = null;
                        } else {
                            $pid = $row->stripe_price_id;
                        }
                    }

                    if ($row->custom_price !== null) {
                        $custom_amount = floatval($row->custom_price);
                    }

                    if ($custom_amount !== null && ($stripe_amount === null || $custom_amount < $stripe_amount)) {
                        $amount = $custom_amount;
                        $pid    = '';
                    } else {
                        $amount = $stripe_amount;
                    }
                }

                if ($amount === null) {
                    $amount = $base_amount;
                    $pid    = $variant->stripe_price_id;
                }

                if ($amount !== null && ($lowest === null || $amount < $lowest)) {
                    $lowest    = $amount;
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
    public static function create_price($product_id, $amount_cents, $mode = null, $nickname = null, $metadata = []) {
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

        if ($nickname !== null) {
            $params['nickname'] = $nickname;
        }

        if (!empty($metadata)) {
            $params['metadata'] = $metadata;
        }

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
    /**
     * Create a Stripe product and price for an extra.
     *
     * @param string $name                 Extra name
     * @param float  $price                Price in EUR
     * @param string $related_product_name Related product name
     * @return array|\WP_Error
     */
    public static function create_extra_price($name, $price, $related_product_name = '') {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }

        $full_name = trim($name);
        if ($related_product_name !== '') {
            $full_name .= ' – ' . $related_product_name;
        }

        try {
            $found = \Stripe\Product::search([
                'query' => 'name:"' . $full_name . '"',
                'limit' => 1,
            ]);

            $product = ($found && !empty($found->data)) ? $found->data[0] : null;

            if (!$product) {
                $product = \Stripe\Product::create([
                    'name'        => $full_name,
                    'description' => 'Extra für Produkt: ' . $related_product_name,
                ]);
            }

            $price_obj = \Stripe\Price::create([
                'unit_amount' => intval(round($price * 100)),
                'currency'    => 'eur',
                'recurring'   => ['interval' => 'month'],
                'product'     => $product->id,
            ]);

            return [
                'product_id' => $product->id,
                'price_id'   => $price_obj->id,
            ];
        } catch (\Exception $e) {
            return new \WP_Error('stripe_extra', $e->getMessage());
        }
    }

    /**
     * Check if a Stripe price still exists and is active.
     */
    public static function price_exists($price_id) {
        $init = self::init();
        if (is_wp_error($init)) {
            return false;
        }
        try {
            $price = \Stripe\Price::retrieve($price_id);
            return !empty($price) && empty($price->deleted);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a Stripe price is currently active.
     *
     * @param string $price_id
     * @return bool|null True if active, false if archived, null on error
     */
    public static function is_price_active($price_id) {
        $init = self::init();
        if (is_wp_error($init)) {
            return null;
        }
        try {
            $price = \Stripe\Price::retrieve($price_id);
            return $price->active;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Determine if a Stripe price is archived or invalid.
     *
     * @param string $price_id
     * @return bool True if archived or not found, false if active
     */
    public static function is_price_archived($price_id) {
        if (!$price_id) {
            return true;
        }

        $init = self::init();
        if (is_wp_error($init)) {
            return true;
        }

        try {
            $price = \Stripe\Price::retrieve($price_id);
            return !$price->active;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Check if a Stripe price is archived using a transient cache.
     * Falls back to live lookup if no cache entry exists.
     *
     * @param string $price_id
     * @return bool True if archived or not found, false if active
     */
    public static function is_price_archived_cached($price_id) {
        if (!$price_id) {
            return true;
        }

        $cache_key = 'stripe_price_archived_' . $price_id;
        $cached    = get_transient($cache_key);
        if ($cached !== false) {
            return (bool) $cached;
        }

        $archived = self::is_price_archived($price_id);
        set_transient($cache_key, $archived ? 1 : 0, 6 * HOUR_IN_SECONDS);
        return $archived;
    }

    /**
     * Determine if a Stripe product is archived or invalid.
     *
     * @param string $product_id
     * @return bool True if archived or not found, false if active
     */
    public static function is_product_archived($product_id) {
        if (!$product_id) {
            return true;
        }

        $init = self::init();
        if (is_wp_error($init)) {
            return true;
        }

        try {
            $product = \Stripe\Product::retrieve($product_id);
            return !$product->active;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Check if a Stripe product is archived using a transient cache.
     * Falls back to a live lookup if no cache entry exists.
     *
     * @param string $product_id
     * @return bool True if archived or not found, false if active
     */
    public static function is_product_archived_cached($product_id) {
        if (!$product_id) {
            return true;
        }

        $cache_key = 'stripe_product_archived_' . $product_id;
        $cached    = get_transient($cache_key);
        if ($cached !== false) {
            return (bool) $cached;
        }

        $archived = self::is_product_archived($product_id);
        set_transient($cache_key, $archived ? 1 : 0, 6 * HOUR_IN_SECONDS);
        return $archived;
    }

    /**
     * Clear cached Stripe archive status for all known products and prices.
     * Iterates over variant, extra and duration price tables.
     */
    public static function clear_stripe_archive_cache() {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'produkt_variants',
            $wpdb->prefix . 'produkt_extras',
            $wpdb->prefix . 'produkt_duration_prices',
        ];

        foreach ($tables as $table) {
            $ids = $wpdb->get_results("SELECT stripe_price_id, stripe_product_id FROM $table");
            foreach ($ids as $entry) {
                if (!empty($entry->stripe_price_id)) {
                    delete_transient('stripe_price_archived_' . $entry->stripe_price_id);
                }
                if (!empty($entry->stripe_product_id)) {
                    delete_transient('stripe_product_archived_' . $entry->stripe_product_id);
                }
            }
        }
    }

    /**
     * Refresh Stripe archive cache for all known products and prices.
     * Intended to run via WP-Cron.
     */
    public static function cron_refresh_stripe_archive_cache() {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'produkt_variants',
            $wpdb->prefix . 'produkt_extras',
            $wpdb->prefix . 'produkt_duration_prices',
        ];

        $secret = get_option('produkt_stripe_secret_key', '');
        if (empty($secret)) {
            error_log('Stripe secret key not set - cannot refresh archive cache');
            return;
        }
        $stripe = new \Stripe\StripeClient($secret);

        foreach ($tables as $table) {
            $entries = $wpdb->get_results("SELECT stripe_product_id, stripe_price_id FROM $table");

            foreach ($entries as $entry) {
                if (!empty($entry->stripe_price_id)) {
                    try {
                        $price    = $stripe->prices->retrieve($entry->stripe_price_id);
                        $archived = !$price->active;
                    } catch (\Exception $e) {
                        $archived = true;
                    }
                    set_transient('stripe_price_archived_' . $entry->stripe_price_id, $archived ? 1 : 0, 6 * HOUR_IN_SECONDS);
                }

                if (!empty($entry->stripe_product_id)) {
                    try {
                        $product  = $stripe->products->retrieve($entry->stripe_product_id);
                        $archived = !$product->active;
                    } catch (\Exception $e) {
                        $archived = true;
                    }
                    set_transient('stripe_product_archived_' . $entry->stripe_product_id, $archived ? 1 : 0, 6 * HOUR_IN_SECONDS);
                }
            }
        }

        error_log('✅ Stripe-Archivstatus erfolgreich via Cron aktualisiert');
    }

    /**
     * Trigger a synchronization of all Stripe products and prices.
     * Placeholder implementation that fetches items from Stripe.
     */
    public static function sync_all() {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        try {
            $products = \Stripe\Product::all(['limit' => 100]);
            foreach ($products->data as $product) {
                // Hier könnte ein Abgleich mit der Datenbank erfolgen
            }
            $prices = \Stripe\Price::all(['limit' => 100]);
            foreach ($prices->data as $price) {
                // Ebenso könnte hier ein Abgleich stattfinden
            }
            return true;
        } catch (\Exception $e) {
            return new \WP_Error('stripe_sync', $e->getMessage());
        }
    }

    /**
     * Delete cached lowest price transients for a category when variants or
     * durations change.
     *
     * @param int $category_id ID of the affected category
     */
    public static function delete_lowest_price_cache_for_category($category_id) {
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

        if (!empty($variant_ids) && !empty($duration_ids)) {
            $cache_key = 'lowest_price_cache_' . md5(implode('_', $variant_ids) . '|' . implode('_', $duration_ids));
            delete_transient($cache_key);
        }
    }
}
