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

    /**
     * Create a Stripe Checkout Session for one-time sales.
     *
     * @param array $args Session parameters
     * @return \Stripe\Checkout\Session|\WP_Error
     */
    public static function create_checkout_session_for_sale(array $args) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }

        $success_url = $args['success_url'] ?? home_url('/danke');
        $cancel_url  = $args['cancel_url'] ?? home_url('/abbrechen');

        try {
            $session_data = [
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price'    => $args['price_id'],
                    'quantity' => $args['quantity'] ?? 1,
                ]],
                'client_reference_id' => $args['reference'] ?? null,
                'metadata' => $args['metadata'] ?? [],
                'success_url' => $success_url . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => $cancel_url,
            ];

            $customer_id = $args['customer'] ?? null;
            if ($customer_id) {
                $session_data['customer'] = $customer_id;
            } else {
                $session_data['customer_creation'] = 'always';
                // Für Gäste: E-Mail vorbefüllen
                if (!empty($args['customer_email'])) {
                    $session_data['customer_email'] = $args['customer_email'];
                }
            }

            $session = \Stripe\Checkout\Session::create($session_data);

            return $session;
        } catch (\Exception $e) {
            return new \WP_Error('stripe_checkout_error', $e->getMessage());
        }
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

        // 2. Auch Preise der Varianten selbst berücksichtigen
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
     * Retrieve paid invoices for a Stripe customer.
     *
     * @param string $customer_id Stripe customer ID
     * @param int    $limit       Maximum number of invoices to fetch
     * @return array|\WP_Error
     */
    public static function get_customer_invoices($customer_id, $limit = 20) {
        if (empty($customer_id)) {
            return [];
        }

        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }

        try {
            $page_limit = max(1, min($limit, 100));
            $invoices   = \Stripe\Invoice::all([
                'customer' => $customer_id,
                'limit'    => $page_limit,
                'expand'   => ['data.lines'],
            ]);

            $result = [];
            $count  = 0;

            foreach ($invoices->autoPagingIterator() as $invoice) {
                $count++;
                if ($count > $limit) {
                    break;
                }
                $period_start = null;
                $period_end   = null;

                if (!empty($invoice->lines->data) && !empty($invoice->lines->data[0]->period)) {
                    $period_start = $invoice->lines->data[0]->period->start ?? null;
                    $period_end   = $invoice->lines->data[0]->period->end ?? null;
                }

                $result[] = [
                    'id'           => $invoice->id,
                    'number'       => $invoice->number ?? $invoice->id,
                    'subscription' => $invoice->subscription ?? '',
                    'order_number' => $invoice->metadata->order_number ?? $invoice->metadata->order_id ?? '',
                    'hosted_url'   => $invoice->hosted_invoice_url ?? '',
                    'pdf_url'      => $invoice->invoice_pdf ?? '',
                    'created'      => $invoice->created ?? null,
                    'period_start' => $period_start,
                    'period_end'   => $period_end,
                    'amount_total' => $invoice->total ?? 0,
                    'currency'     => isset($invoice->currency) ? strtoupper($invoice->currency) : '',
                    'status'       => $invoice->status ?? '',
                    'paid'         => !empty($invoice->paid),
                ];
            }

            return $result;
        } catch (\Exception $e) {
            return new \WP_Error('stripe_invoices', $e->getMessage());
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

        $amount_cents = (int) $amount_cents;

        try {
            $prices = \Stripe\Price::all(['product' => $product_id, 'limit' => 100]);
            foreach ($prices->data as $p) {
                $match = $p->unit_amount == $amount_cents && $p->currency === 'eur';
                if ($mode === 'miete') {
                    $match = $match && isset($p->recurring) && $p->recurring->interval === 'month';
                } else {
                    $match = $match && !isset($p->recurring);
                }

                if ($match && !empty($metadata)) {
                    foreach ($metadata as $mk => $mv) {
                        if (!isset($p->metadata[$mk]) || (string) $p->metadata[$mk] !== (string) $mv) {
                            $match = false;
                            break;
                        }
                    }
                }

                if ($match) {
                    if ($nickname !== null && $p->nickname !== $nickname) {
                        \Stripe\Price::update($p->id, ['nickname' => $nickname]);
                    }
                    if (!empty($metadata)) {
                        \Stripe\Price::update($p->id, ['metadata' => $metadata]);
                    }
                    return $p;
                }
            }

            $params = [
                'unit_amount' => $amount_cents,
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
                    'nickname'    => ($product_data['mode'] === 'kauf') ? 'Einmalverkauf' : 'Vermietung pro Monat',
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
     * Create or retrieve a Stripe product without creating a price.
     *
     * @param array $product_data {
     *     @type string   $name
     *     @type string   $mode
     *     @type int      $plugin_product_id
     *     @type int      $variant_id
     *     @type int|null $duration_id
     * }
     * @return array|\WP_Error
     */
    public static function create_or_retrieve_product($product_data) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }

        try {
            $cache_key = 'produkt_stripe_product_' . md5(json_encode($product_data));
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

            $result = [
                'stripe_product_id' => $stripe_product->id,
            ];

            set_transient($cache_key, $result, DAY_IN_SECONDS);

            return $result;
        } catch (\Exception $e) {
            return new \WP_Error('stripe_product_create', $e->getMessage());
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
    public static function create_extra_price($name, $price, $related_product_name = '', $mode = 'miete') {
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
                    'type'        => 'service',
                    'active'      => true,
                ]);
            } elseif (!$product->active) {
                \Stripe\Product::update($product->id, ['active' => true]);
            }

            $amount_cents = intval(round($price * 100));
            $prices       = \Stripe\Price::all(['product' => $product->id, 'limit' => 100]);
            $price_obj    = null;
            foreach ($prices->data as $p) {
                $match = $p->unit_amount == $amount_cents && $p->currency === 'eur';
                if ($mode === 'miete') {
                    $match = $match && isset($p->recurring) && $p->recurring->interval === 'month';
                } else {
                    $match = $match && !isset($p->recurring);
                }
                if ($match) {
                    $price_obj = $p;
                    if ($p->nickname !== (($mode === 'kauf') ? 'Einmalverkauf' : 'Vermietung pro Monat')) {
                        \Stripe\Price::update($p->id, ['nickname' => ($mode === 'kauf') ? 'Einmalverkauf' : 'Vermietung pro Monat']);
                    }
                    break;
                }
            }

            if (!$price_obj) {
                $price_params = [
                    'unit_amount' => $amount_cents,
                    'currency'    => 'eur',
                    'product'     => $product->id,
                    'nickname'    => ($mode === 'kauf') ? 'Einmalverkauf' : 'Vermietung pro Monat',
                ];

                if ($mode === 'miete') {
                    $price_params['recurring'] = ['interval' => 'month'];
                }

                $price_obj = \Stripe\Price::create($price_params);
            }

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

    /**
     * Process a completed Stripe Checkout Session asynchronously.
     *
     * @param object $session The session object passed from the webhook.
     */
    public static function process_checkout_session($session) {
        if (empty($session->customer_details) && !empty($session->id)) {
            try {
                $session = \Stripe\Checkout\Session::retrieve(
                    $session->id,
                    ['expand' => ['customer_details', 'shipping_details']]
                );
            } catch (\Exception $e) {
                // keep original session data if retrieval fails
            }
        }
        $mode            = $session->mode ?? 'subscription';
        $subscription_id = $mode === 'subscription' ? ($session->subscription ?? '') : '';
        $metadata        = $session->metadata ? $session->metadata->toArray() : [];

        if ($mode === 'payment' || $mode === 'subscription') {
            $email              = $session->customer_details->email ?? '';
            $stripe_customer_id = $session->customer ?? '';
            $full_name          = sanitize_text_field($session->customer_details->name ?? '');
            $first_name         = $full_name;
            $last_name          = '';
            if (strpos($full_name, ' ') !== false) {
                list($first_name, $last_name) = explode(' ', $full_name, 2);
            }
            $phone = sanitize_text_field($session->customer_details->phone ?? '');

            if ($email && $stripe_customer_id) {
                $user = get_user_by('email', $email);
                if (!$user) {
                    $user_id = wp_create_user($email, wp_generate_password(), $email);
                    if (!is_wp_error($user_id)) {
                        wp_update_user([
                            'ID'          => $user_id,
                            'role'        => 'kunde',
                            'display_name'=> $full_name ?: $email,
                        ]);
                        update_user_meta($user_id, 'stripe_customer_id', $stripe_customer_id);
                        update_user_meta($user_id, 'first_name', $first_name);
                        update_user_meta($user_id, 'last_name', $last_name);
                        if ($phone) {
                            update_user_meta($user_id, 'phone', $phone);
                        }
                    }
                } else {
                    wp_update_user([
                        'ID'          => $user->ID,
                        'role'        => 'kunde',
                        'display_name'=> $full_name ?: $user->display_name,
                    ]);
                    update_user_meta($user->ID, 'stripe_customer_id', $stripe_customer_id);
                    update_user_meta($user->ID, 'first_name', $first_name);
                    update_user_meta($user->ID, 'last_name', $last_name);
                    if ($phone) {
                        update_user_meta($user->ID, 'phone', $phone);
                    }
                }
            }

            $produkt_name  = sanitize_text_field($metadata['produkt'] ?? '');
            $zustand       = sanitize_text_field($metadata['zustand'] ?? '');
            $produktfarbe  = sanitize_text_field($metadata['produktfarbe'] ?? '');
            $gestellfarbe  = sanitize_text_field($metadata['gestellfarbe'] ?? '');
            $extra         = sanitize_text_field($metadata['extra'] ?? '');
            $dauer         = sanitize_text_field($metadata['dauer_name'] ?? '');
            $start_date_raw = isset($metadata['start_date']) ? sanitize_text_field($metadata['start_date']) : '';
            $end_date_raw   = isset($metadata['end_date']) ? sanitize_text_field($metadata['end_date']) : '';
            $start_date    = $start_date_raw !== '' ? $start_date_raw : null;
            $end_date      = $end_date_raw !== '' ? $end_date_raw : null;
            $days          = intval($metadata['days'] ?? 0);
            $weekend_tarif  = intval($metadata['weekend_tarif'] ?? 0);
            $user_ip       = sanitize_text_field($metadata['user_ip'] ?? '');
            $user_agent    = sanitize_text_field($metadata['user_agent'] ?? '');

            $email  = sanitize_email($session->customer_details->email ?? '');
            $phone  = sanitize_text_field($session->customer_details->phone ?? '');

            $shipping_details = $session->shipping_details ? $session->shipping_details->toArray() : [];
            $customer_details = $session->customer_details ? $session->customer_details->toArray() : [];
            $address = $shipping_details['address'] ?? $customer_details['address'] ?? null;
            $street  = $address['line1'] ?? '';
            $postal  = $address['postal_code'] ?? '';
            $city    = $address['city'] ?? '';
            $country = $address['country'] ?? '';

            Database::upsert_customer_record_by_email(
                $email,
                $stripe_customer_id,
                $full_name,
                $phone,
                [
                    'street'      => $street,
                    'postal_code' => $postal,
                    'city'        => $city,
                    'country'     => $country,
                ]
            );

            global $wpdb;
            $existing_orders = $wpdb->get_results($wpdb->prepare(
                "SELECT id, status, created_at, category_id, shipping_cost, shipping_price_id, variant_id, product_color_id, extra_ids, order_number, order_items FROM {$wpdb->prefix}produkt_orders WHERE stripe_session_id = %s",
                $session->id
            ));

            if (!empty($existing_orders)) {
                $produkt_name = $produkt_name ?: ($existing_orders[0]->produkt_name ?? '');
                $zustand      = $zustand ?: ($existing_orders[0]->zustand_text ?? '');
                $produktfarbe = $produktfarbe ?: ($existing_orders[0]->produktfarbe_text ?? '');
                $gestellfarbe = $gestellfarbe ?: ($existing_orders[0]->gestellfarbe_text ?? '');
                $extra        = $extra ?: ($existing_orders[0]->extra_text ?? '');
                $dauer        = $dauer ?: ($existing_orders[0]->dauer_text ?? '');
                if (!$weekend_tarif) {
                    $weekend_tarif = intval($existing_orders[0]->weekend_tariff ?? 0);
                }
            }

            $shipping_price_id = $metadata['shipping_price_id'] ?? '';
            if (!$shipping_price_id && !empty($existing_orders)) {
                $order_ref = $existing_orders[0];
                $shipping_price_id = $order_ref->shipping_price_id ?: '';
                if (!$shipping_price_id && !empty($order_ref->category_id)) {
                    $shipping_price_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT shipping_price_id FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                        $order_ref->category_id
                    ));
                }
            }

            $shipping_cost = 0;
            if (!empty($session->shipping_cost) && !empty($session->shipping_cost->amount_total)) {
                $shipping_cost = $session->shipping_cost->amount_total / 100;
            } elseif (!empty($existing_orders)) {
                $order_ref = $existing_orders[0];
                if (!$shipping_price_id) {
                    $shipping_price_id = $order_ref->shipping_price_id ?: '';
                    if (!$shipping_price_id && !empty($order_ref->category_id)) {
                        $shipping_price_id = $wpdb->get_var($wpdb->prepare(
                            "SELECT shipping_price_id FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                            $order_ref->category_id
                        ));
                    }
                }
                $shipping_cost = floatval($order_ref->shipping_cost);
                if (!$shipping_cost && !empty($order_ref->category_id)) {
                    $shipping_cost = (float) $wpdb->get_var($wpdb->prepare(
                        "SELECT shipping_cost FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                        $order_ref->category_id
                    ));
                }
            }

            if (!$shipping_cost && $shipping_price_id) {
                $amt = self::get_price_amount($shipping_price_id);
                if (!is_wp_error($amt)) {
                    $shipping_cost = floatval($amt);
                }
            }

            $discount_amount = ($session->total_details->amount_discount ?? 0) / 100;

            if (!$dauer && $days > 0) {
                $dauer = $days . ' Tag' . ($days > 1 ? 'e' : '');
                if ($start_date && $end_date) {
                    $dauer .= ' (' . $start_date . ' - ' . $end_date . ')';
                }
            }

            $data = [
                'customer_email'    => $email,
                'customer_name'     => sanitize_text_field($session->customer_details->name ?? ''),
                'customer_phone'    => $phone,
                'customer_street'   => $street,
                'customer_postal'   => $postal,
                'customer_city'     => $city,
                'customer_country'  => $country,
                'final_price'       => (($session->amount_total ?? 0) / 100) - $shipping_cost,
                'shipping_cost'     => $shipping_cost,
                'shipping_price_id' => $shipping_price_id,
                'amount_total'      => $session->amount_total ?? 0,
                'discount_amount'   => $discount_amount,
                'produkt_name'      => $produkt_name,
                'zustand_text'      => $zustand,
                'produktfarbe_text' => $produktfarbe,
                'gestellfarbe_text' => $gestellfarbe,
                'extra_text'        => $extra,
                'dauer_text'        => $dauer,
                'mode'              => ($mode === 'payment' ? 'kauf' : 'miete'),
                'start_date'        => $start_date,
                'end_date'          => $end_date,
                'weekend_tariff'    => $weekend_tarif,
                'inventory_reverted'=> 0,
                'user_ip'           => $user_ip,
                'user_agent'        => $user_agent,
                'stripe_subscription_id' => $subscription_id,
                'status'            => 'abgeschlossen',
                'created_at'        => current_time('mysql', 1),
            ];

            if ($data['start_date'] === null) {
                unset($data['start_date']);
            }
            if ($data['end_date'] === null) {
                unset($data['end_date']);
            }

            $welcome_sent = false;
            $final_order_number = null;

            if (!empty($existing_orders)) {
                foreach ($existing_orders as $ord) {
                    $send_welcome = ($ord->status !== 'abgeschlossen');
                    $update_data = $data;
                    $update_data['created_at'] = $ord->created_at;
                    $finalize_number = ($ord->status === 'offen');
                    if ($finalize_number && $final_order_number === null) {
                        $final_order_number = pv_generate_order_number();
                    }

                    if ($finalize_number && $final_order_number !== '') {
                        $update_data['order_number'] = $final_order_number;
                    } elseif (empty($ord->order_number)) {
                        $gen_num = pv_generate_order_number();
                        if ($gen_num !== '') {
                            $update_data['order_number'] = $gen_num;
                        }
                    } else {
                        unset($update_data['order_number']);
                    }
                    if ($start_date === null) {
                        unset($update_data['start_date']);
                    }
                    if ($end_date === null) {
                        unset($update_data['end_date']);
                    }
                    $wpdb->update(
                        "{$wpdb->prefix}produkt_orders",
                        $update_data,
                        ['id' => $ord->id]
                    );
                    if (!empty($update_data['order_number']) && $update_data['order_number'] !== $ord->order_number) {
                        produkt_add_order_log(
                            $ord->id,
                            'order_number_finalized',
                            ($ord->order_number ? $ord->order_number . ' -> ' : '') . $update_data['order_number']
                        );
                    }
                    if ($send_welcome) {
                        produkt_add_order_log($ord->id, 'status_updated', 'offen -> abgeschlossen');
                    }
                    if ($send_welcome && !$welcome_sent) {
                        produkt_add_order_log($ord->id, 'checkout_completed');
                        send_produkt_welcome_email($update_data, $ord->id);
                        send_admin_order_email($update_data, $ord->id, $session->id);
                        produkt_add_order_log($ord->id, 'welcome_email_sent');
                        $welcome_sent = true;
                    }
                    if ($ord->status === 'offen') {
                        $items = [];
                        if (!empty($ord->order_items)) {
                            $decoded = json_decode($ord->order_items, true);
                            if (is_array($decoded)) {
                                $items = $decoded;
                            }
                        }

                        if (empty($items)) {
                            $items[] = [
                                'variant_id'       => $ord->variant_id,
                                'product_color_id' => $ord->product_color_id,
                                'extra_ids'        => $ord->extra_ids,
                            ];
                        }

                        foreach ($items as $itm) {
                            $variant_id = intval($itm['variant_id'] ?? 0);
                            $product_color_id = intval($itm['product_color_id'] ?? 0);
                            if ($variant_id) {
                                $wpdb->query($wpdb->prepare(
                                    "UPDATE {$wpdb->prefix}produkt_variants SET stock_available = GREATEST(stock_available - 1,0), stock_rented = stock_rented + 1 WHERE id = %d",
                                    $variant_id
                                ));
                                if ($product_color_id) {
                                    $wpdb->query($wpdb->prepare(
                                        "UPDATE {$wpdb->prefix}produkt_variant_options SET stock_available = GREATEST(stock_available - 1,0), stock_rented = stock_rented + 1 WHERE variant_id = %d AND option_type = 'product_color' AND option_id = %d",
                                        $variant_id,
                                        $product_color_id
                                    ));
                                }
                            }

                            $extra_ids_raw = $itm['extra_ids'] ?? '';
                            $extra_ids = [];
                            if (is_array($extra_ids_raw)) {
                                $extra_ids = array_filter(array_map('intval', $extra_ids_raw));
                            } elseif (!empty($extra_ids_raw)) {
                                $extra_ids = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
                            }

                            foreach ($extra_ids as $eid) {
                                $wpdb->query($wpdb->prepare(
                                    "UPDATE {$wpdb->prefix}produkt_extras SET stock_available = GREATEST(stock_available - 1,0), stock_rented = stock_rented + 1 WHERE id = %d",
                                    $eid
                                ));
                            }
                        }
                    }
                }
            } else {
                $gen_num = pv_generate_order_number();
                if ($gen_num !== '') {
                    $data['order_number'] = $gen_num;
                }
                $data['stripe_session_id'] = $session->id;
                $data['stripe_subscription_id'] = $subscription_id;
                $wpdb->insert("{$wpdb->prefix}produkt_orders", $data);
                $order_id = $wpdb->insert_id;
                produkt_add_order_log($order_id, 'order_created');
                produkt_add_order_log($order_id, 'checkout_completed');
                send_produkt_welcome_email($data, $order_id);
                send_admin_order_email($data, $order_id, $session->id);
                produkt_add_order_log($order_id, 'welcome_email_sent');
            }

            if ($data['mode'] === 'kauf') {
                // additional handling for purchase mode if needed
            }
        }
    }
}
