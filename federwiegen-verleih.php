<?php
 /**
  * Plugin Name: Rent Plugin
  * Plugin URI: https://h2concepts.de
  * Description: Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration
* Version: 2.8.2
  * Author: H2 Concepts
  * License: GPL v2 or later
  * Text Domain: h2-concepts
  */
 
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
const FEDERWIEGEN_PLUGIN_VERSION = '2.8.2';
const FEDERWIEGEN_PLUGIN_DIR = __DIR__ . '/';
define('FEDERWIEGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FEDERWIEGEN_PLUGIN_PATH', FEDERWIEGEN_PLUGIN_DIR);
define('FEDERWIEGEN_VERSION', FEDERWIEGEN_PLUGIN_VERSION);
define('FEDERWIEGEN_PLUGIN_FILE', __FILE__);
// Payment Method Configuration ID for PayPal
define('FEDERWIEGEN_PMC_ID', 'pmc_1QKPcvRxDui5dUOqaNaxNjsL');

// Control whether default demo data is inserted on activation
if (!defined('FEDERWIEGEN_LOAD_DEFAULT_DATA')) {
    define('FEDERWIEGEN_LOAD_DEFAULT_DATA', false);
}

// Load the autoloader for the plugin classes. Some installations failed to
// find the class when it was called directly, so ensure the file exists and
// require it explicitly before registering.
if (!class_exists('FederwiegenVerleih\\Autoloader')) {
    $autoloader = FEDERWIEGEN_PLUGIN_DIR . 'includes/Autoloader.php';
    if (file_exists($autoloader)) {
        require_once $autoloader;
    }
}

if (class_exists('FederwiegenVerleih\\Autoloader')) {
    \FederwiegenVerleih\Autoloader::register();
}
require_once FEDERWIEGEN_PLUGIN_DIR . 'includes/Webhook.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['FederwiegenVerleih\\Plugin', 'activate_plugin']);
register_deactivation_hook(__FILE__, ['FederwiegenVerleih\\Plugin', 'deactivate_plugin']);

// Initialize the plugin after WordPress has loaded
add_action('plugins_loaded', function () {
    new \FederwiegenVerleih\Plugin();
});


add_shortcode('stripe_elements_form', 'federwiegen_simple_checkout_button');
function federwiegen_simple_checkout_button() {
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
