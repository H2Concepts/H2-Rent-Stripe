<?php
 /**
 * Plugin Name: H2 Concepts Rental Pro
  * Plugin URI: https://h2concepts.de
  * Description: Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration
* Version: 2.8.37
  * Author: H2 Concepts
  * License: GPL v2 or later
  * Text Domain: h2-concepts
  */
 
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
const PRODUKT_PLUGIN_VERSION = '2.8.37';
const PRODUKT_PLUGIN_DIR = __DIR__ . '/';
define('PRODUKT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRODUKT_PLUGIN_PATH', PRODUKT_PLUGIN_DIR);
define('PRODUKT_VERSION', PRODUKT_PLUGIN_VERSION);
define('PRODUKT_PLUGIN_FILE', __FILE__);
define('PRODUKT_SHOP_PAGE_OPTION', 'produkt_shop_page_id');
define('PRODUKT_CUSTOMER_PAGE_OPTION', 'produkt_customer_page_id');
define('PRODUKT_CHECKOUT_PAGE_OPTION', 'produkt_checkout_page_id');

// Load Stripe SDK if available
require_once plugin_dir_path(__FILE__) . 'includes/stripe-autoload.php';

// Control whether default demo data is inserted on activation
if (!defined('PRODUKT_LOAD_DEFAULT_DATA')) {
    define('PRODUKT_LOAD_DEFAULT_DATA', false);
}

// Load the autoloader for the plugin classes. Some installations failed to
// find the class when it was called directly, so ensure the file exists and
// require it explicitly before registering.
if (!class_exists('ProduktVerleih\\Autoloader')) {
    $autoloader = plugin_dir_path(__FILE__) . 'includes/Autoloader.php';
    if (file_exists($autoloader)) {
        require_once $autoloader;
    }
}

if (class_exists('ProduktVerleih\\Autoloader')) {
    \ProduktVerleih\Autoloader::register();
}

// Explicitly load the main plugin class in case the autoloader is not yet
// active on some installations.
$plugin_file = plugin_dir_path(__FILE__) . 'includes/Plugin.php';
if (file_exists($plugin_file) && !class_exists('ProduktVerleih\\Plugin')) {
    require_once $plugin_file;
}

$webhook_file = plugin_dir_path(__FILE__) . 'includes/Webhook.php';
if (file_exists($webhook_file)) {
    require_once $webhook_file;
} else {
    error_log('Webhook.php not found at ' . $webhook_file);
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['ProduktVerleih\\Plugin', 'activate_plugin']);
register_deactivation_hook(__FILE__, ['ProduktVerleih\\Plugin', 'deactivate_plugin']);

// Schedule daily cron job to refresh Stripe archive cache
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('produkt_stripe_status_cron')) {
        wp_schedule_event(time(), 'daily', 'produkt_stripe_status_cron');
    }
});

// Clear cron job on deactivation
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('produkt_stripe_status_cron');
});

add_action('produkt_stripe_status_cron', ['\ProduktVerleih\StripeService', 'cron_refresh_stripe_archive_cache']);

// Initialize the plugin after WordPress has loaded
add_action('plugins_loaded', function () {
    new \ProduktVerleih\Plugin();
});


add_shortcode('stripe_elements_form', 'produkt_simple_checkout_button');
function produkt_simple_checkout_button($atts = []) {
    $atts = shortcode_atts([
        'price_id' => '',
    ], $atts, 'stripe_elements_form');

    if (empty($atts['price_id']) && isset($_GET['price_id'])) {
        $atts['price_id'] = sanitize_text_field($_GET['price_id']);
    }

    $extra_price_ids = [];
    if (isset($_GET['extra_price_ids'])) {
        $raw  = sanitize_text_field($_GET['extra_price_ids']);
        $extra_price_ids = array_filter(array_map('sanitize_text_field', explode(',', $raw)));
    }

    $shipping_price_id = '';
    if (isset($_GET['shipping_price_id'])) {
        $shipping_price_id = sanitize_text_field($_GET['shipping_price_id']);
    }

    ob_start();
    ?>
    <div id="checkout-mount-point"></div>
    <script>
    (async () => {
        const publishableKey = <?php echo json_encode(\ProduktVerleih\StripeService::get_publishable_key()); ?>;
        if (!publishableKey) return;
        const stripe = Stripe(publishableKey);
        const fetchClientSecret = async () => {
            const res = await fetch('<?php echo admin_url('admin-ajax.php?action=create_embedded_checkout_session'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    price_id: <?php echo json_encode($atts['price_id']); ?>,
                    extra_price_ids: <?php echo json_encode($extra_price_ids); ?>,
                    shipping_price_id: <?php echo json_encode($shipping_price_id); ?>
                })
            });
            const data = await res.json();
            return data.client_secret;
        };
        const checkout = await stripe.initEmbeddedCheckout({ fetchClientSecret });
        checkout.mount('#checkout-mount-point');
    })();
    </script>
    <?php
    return ob_get_clean();
}

require_once plugin_dir_path(__FILE__) . 'includes/seo-module.php';
\ProduktVerleih\SeoModule::init();
