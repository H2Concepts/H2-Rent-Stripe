<?php
 /**
 * Plugin Name: H2 Concepts Rental Pro
  * Plugin URI: https://h2concepts.de
  * Description: Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration
* Version: 2.8.33
  * Author: H2 Concepts
  * License: GPL v2 or later
  * Text Domain: h2-concepts
  */
 
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
const PRODUKT_PLUGIN_VERSION = '2.8.33';
const PRODUKT_PLUGIN_DIR = __DIR__ . '/';
define('PRODUKT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRODUKT_PLUGIN_PATH', PRODUKT_PLUGIN_DIR);
define('PRODUKT_VERSION', PRODUKT_PLUGIN_VERSION);
define('PRODUKT_PLUGIN_FILE', __FILE__);
define('PRODUKT_SHOP_PAGE_OPTION', 'produkt_shop_page_id');
define('PRODUKT_CUSTOMER_PAGE_OPTION', 'produkt_customer_page_id');

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
function produkt_simple_checkout_button() {
    ob_start(); ?>
    <button id="mieten-button">Jetzt mieten</button>
    <script>
    document.getElementById('mieten-button').addEventListener('click', async () => {
        const res = await fetch('<?php echo admin_url("admin-ajax.php?action=create_checkout_session"); ?>');
        const data = await res.json();
        if (data.url) {
            window.location.href = data.url;
        }
    });
    </script>
    <?php return ob_get_clean();
}
