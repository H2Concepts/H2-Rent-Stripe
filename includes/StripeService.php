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
     * Determine the lowest price across all variant/duration combinations of a category.
     * Includes verbose logging and a fallback for single variant/duration setups.
     *
     * @param int $categoryId
     * @return array{amount:?float, price_id?:string, reason?:string}
     */
    public static function getLowestPriceWithDurations($categoryId) {
        global $wpdb;

        $variantIds = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}produkt_variants
            WHERE category_id = %d",
            $categoryId
        ));

        $durationIds = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}produkt_durations");

        $prices = [];

        foreach ($variantIds as $variantId) {
            foreach ($durationIds as $durationId) {
                $stripePriceId = $wpdb->get_var($wpdb->prepare(
                    "SELECT stripe_price_id FROM {$wpdb->prefix}variant_durations
                    WHERE variant_id = %d AND duration_id = %d",
                    $variantId,
                    $durationId
                ));

                error_log("[Preisermittlung] Variante: $variantId | Dauer: $durationId | Preis-ID: $stripePriceId");

                if ($stripePriceId) {
                    $amount = self::get_price_amount($stripePriceId);
                    if (!is_wp_error($amount)) {
                        $prices[] = [
                            'amount'   => $amount,
                            'price_id' => $stripePriceId,
                        ];
                    } else {
                        error_log("[WARNUNG] Preis konnte nicht von Stripe geladen werden: $stripePriceId");
                    }
                }
            }
        }

        if (!empty($prices)) {
            usort($prices, fn($a, $b) => $a['amount'] <=> $b['amount']);
            $lowest = $prices[0];
            error_log('[Erfolg] Günstigster Preis: ' . $lowest['amount']);
            return $lowest;
        }

        if (count($variantIds) === 1 && count($durationIds) === 1) {
            $variantId = $variantIds[0];
            $durationId = $durationIds[0];
            $stripePriceId = $wpdb->get_var($wpdb->prepare(
                "SELECT stripe_price_id FROM {$wpdb->prefix}variant_durations
                WHERE variant_id = %d AND duration_id = %d",
                $variantId,
                $durationId
            ));

            error_log('[Fallback aktiv] Nur eine Variante + Dauer gefunden.');
            error_log("[Fallback] Preis-ID: $stripePriceId");

            if ($stripePriceId) {
                $amount = self::get_price_amount($stripePriceId);
                if (!is_wp_error($amount)) {
                    return [
                        'amount'   => $amount,
                        'price_id' => $stripePriceId,
                    ];
                }

                error_log('[Fallback-Fehler] Preis von Stripe nicht erhalten.');
            } else {
                error_log('[Fallback-Fehler] Keine Preis-ID vorhanden.');
            }
        }

        error_log("[FEHLER] Keine gültige Preis-Kombination gefunden für Kategorie ID: $categoryId");
        return [
            'amount' => null,
            'reason' => 'no-price-found',
        ];
    }

    public static function get_publishable_key() {
        return get_option('produkt_stripe_publishable_key', '');
    }

    public static function get_payment_method_configuration_id() {
        return get_option('produkt_stripe_pmc_id', '');
    }
}
