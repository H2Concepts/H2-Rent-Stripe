<?php
namespace ProduktVerleih;

class StripeService {
    private static $use_library = false;
    private static $secret_key  = '';

    private static function load_library() {
        $stripe_init = plugin_dir_path(__FILE__) . '/../vendor/stripe/stripe-php/init.php';
        if (file_exists($stripe_init)) {
            require_once $stripe_init;
        }
        if (class_exists('\\Stripe\\Stripe')) {
            self::$use_library = true;
        }
        return true; // library is optional, we can fallback to HTTP calls
    }

    private static function set_secret_key() {
        $secret = get_option('produkt_stripe_secret_key', '');
        if (empty($secret)) {
            return new \WP_Error('stripe_secret', 'Stripe secret key not set');
        }
        self::$secret_key = $secret;
        if (self::$use_library) {
            \Stripe\Stripe::setApiKey($secret);
        }
        return true;
    }

    public static function init() {
        self::load_library();
        return self::set_secret_key();
    }

    public static function uses_library() {
        return self::$use_library;
    }

    private static function request($method, $endpoint, array $params = []) {
        $url = 'https://api.stripe.com/v1' . $endpoint;
        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . self::$secret_key,
            ],
        ];
        if ($method === 'get') {
            if (!empty($params)) {
                $url = add_query_arg($params, $url);
            }
        } else {
            $args['body'] = $params;
        }
        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return $response;
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($code < 200 || $code >= 300) {
            return new \WP_Error('stripe_http', $body);
        }
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('stripe_json', 'Invalid JSON from Stripe');
        }
        return $data;
    }

    public static function create_payment_intent(array $params) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        if (self::$use_library) {
            return \Stripe\PaymentIntent::create($params);
        }
        $res = self::request('post', '/payment_intents', $params);
        return is_wp_error($res) ? $res : (object) $res;
    }

    public static function create_customer(array $params) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        if (self::$use_library) {
            return \Stripe\Customer::create($params);
        }
        $res = self::request('post', '/customers', $params);
        return is_wp_error($res) ? $res : (object) $res;
    }

    public static function create_subscription(array $params) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        if (self::$use_library) {
            return \Stripe\Subscription::create($params);
        }
        $res = self::request('post', '/subscriptions', $params);
        return is_wp_error($res) ? $res : (object) $res;
    }

    public static function create_checkout_session(array $params) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        if (self::$use_library && class_exists('\\Stripe\\Checkout\\Session')) {
            return \Stripe\Checkout\Session::create($params);
        }
        $res = self::request('post', '/checkout/sessions', $params);
        return is_wp_error($res) ? $res : (object) $res;
    }

    public static function get_price_amount($price_id) {
        $init = self::init();
        if (is_wp_error($init)) {
            return $init;
        }
        if (self::$use_library) {
            try {
                $price = \Stripe\Price::retrieve($price_id);
                return $price->unit_amount / 100;
            } catch (\Exception $e) {
                return new \WP_Error('stripe_price', $e->getMessage());
            }
        }
        $res = self::request('get', '/prices/' . $price_id);
        if (is_wp_error($res)) {
            return $res;
        }
        return isset($res['unit_amount']) ? $res['unit_amount'] / 100 : new \WP_Error('stripe_price', 'No amount');
    }

    public static function verify_signature($payload, $sig_header, $secret) {
        if (empty($sig_header) || empty($secret)) {
            return false;
        }
        $parts = [];
        foreach (explode(',', $sig_header) as $part) {
            [$k, $v] = array_map('trim', explode('=', $part, 2));
            $parts[$k] = $v;
        }
        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }
        $signed = $parts['t'] . '.' . $payload;
        $expected = hash_hmac('sha256', $signed, $secret);
        return hash_equals($expected, $parts['v1']);
    }

    public static function get_publishable_key() {
        return get_option('produkt_stripe_publishable_key', '');
    }

    public static function get_payment_method_configuration_id() {
        return get_option('produkt_stripe_pmc_id', '');
    }
}
