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
        if (empty($variant_ids) || empty($duration_ids)) {
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

        $placeholders_v = implode(',', array_fill(0, count($variant_ids), '%d'));
        $placeholders_d = implode(',', array_fill(0, count($duration_ids), '%d'));
        $sql = "SELECT stripe_price_id FROM {$wpdb->prefix}produkt_duration_prices WHERE variant_id IN ($placeholders_v) AND duration_id IN ($placeholders_d)";
        $query = $wpdb->prepare($sql, array_merge($variant_ids, $duration_ids));
        $prices = $wpdb->get_col($query);

        $lowest = null;
        $lowest_id = '';
        if (!empty($prices)) {
            foreach ($prices as $pid) {
                $amount = self::get_cached_price_amount($pid, $expiration);
                if (is_wp_error($amount)) {
                    continue;
                }
                if ($lowest === null || $amount < $lowest) {
                    $lowest = $amount;
                    $lowest_id = $pid;
                }
            }
        }

        // Fallback for single variant and duration
        if (empty($prices) && count($variant_ids) === 1 && count($duration_ids) === 1) {
            $fallback_id = $wpdb->get_var($wpdb->prepare(
                "SELECT stripe_price_id FROM {$wpdb->prefix}produkt_duration_prices WHERE variant_id = %d AND duration_id = %d",
                $variant_ids[0], $duration_ids[0]
            ));
            if ($fallback_id) {
                $amount = self::get_cached_price_amount($fallback_id, $expiration);
                if (!is_wp_error($amount)) {
                    $lowest = $amount;
                    $lowest_id = $fallback_id;
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
}
