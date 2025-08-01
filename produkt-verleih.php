<?php
 /**
 * Plugin Name: H2 Concepts Rental Pro
  * Plugin URI: https://h2concepts.de
  * Description: Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration
 * Version: 2.8.52
  * Author: H2 Concepts
  * License: GPL v2 or later
  * Text Domain: h2-concepts
  */
 
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin path constants
if (!defined('H2_RENT_PLUGIN_DIR')) {
    define('H2_RENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('H2_RENT_PLUGIN_URL')) {
    define('H2_RENT_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Plugin constants
const PRODUKT_PLUGIN_VERSION = '2.8.52';
const PRODUKT_PLUGIN_DIR = __DIR__ . '/';
define('PRODUKT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRODUKT_PLUGIN_PATH', PRODUKT_PLUGIN_DIR);
define('PRODUKT_VERSION', PRODUKT_PLUGIN_VERSION);
define('PRODUKT_PLUGIN_FILE', __FILE__);
define('PRODUKT_SHOP_PAGE_OPTION', 'produkt_shop_page_id');
define('PRODUKT_CUSTOMER_PAGE_OPTION', 'produkt_customer_page_id');
define('PRODUKT_CHECKOUT_PAGE_OPTION', 'produkt_checkout_page_id');
define('PRODUKT_CONFIRM_PAGE_OPTION', 'produkt_confirm_page_id');

// Initialize Freemius
if (!function_exists('hrp_fs')) {
    function hrp_fs() {
        global $hrp_fs;

        if (!isset($hrp_fs)) {
            $freemius_start = H2_RENT_PLUGIN_DIR . 'vendor/freemius/start.php';
            if (file_exists($freemius_start)) {
                require_once $freemius_start;
                $hrp_fs = fs_dynamic_init([
                    'id'              => 19941,
                    'slug'            => 'h2-rental-pro',
                    'premium_slug'    => 'h2-rental-pro',
                    'type'            => 'plugin',
                    'public_key'      => 'pk_e29255c0fb90b039a0e64e550b1ad',
                    'is_premium'      => true,
                    'is_premium_only' => true,
                    'has_addons'      => false,
                    'has_paid_plans'  => true,
                    'is_org_compliant'=> false,
                    'menu'            => [
                        'slug'    => 'produkt-verleih',
                        'parent'  => null,
                        'support' => false,
                        'contact' => false,
                    ],
                ]);
            }
        }

        return $hrp_fs;
    }

    hrp_fs();
    do_action('hrp_fs_loaded');
}

// Require valid license
if (!hrp_fs() || !hrp_fs()->can_use_premium_code()) {
    return;
}

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
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['ProduktVerleih\\Plugin', 'activate_plugin']);
register_deactivation_hook(__FILE__, ['ProduktVerleih\\Plugin', 'deactivate_plugin']);

// Schedule daily cron job to refresh Stripe archive cache
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('produkt_stripe_status_cron')) {
        wp_schedule_event(time(), 'daily', 'produkt_stripe_status_cron');
    }
    if (!wp_next_scheduled('produkt_inventory_return_cron')) {
        wp_schedule_event(time(), 'daily', 'produkt_inventory_return_cron');
    }
});

// Clear cron job on deactivation
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('produkt_stripe_status_cron');
    wp_clear_scheduled_hook('produkt_inventory_return_cron');
});

add_action('produkt_stripe_status_cron', ['\ProduktVerleih\StripeService', 'cron_refresh_stripe_archive_cache']);
add_action('produkt_inventory_return_cron', ['\ProduktVerleih\Database', 'process_inventory_returns']);

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
    $cart_mode = isset($_GET['cart']);

    $extra_ids_raw   = isset($_GET['extra_ids']) ? sanitize_text_field($_GET['extra_ids']) : '';
    $category_id     = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    $variant_id      = isset($_GET['variant_id']) ? intval($_GET['variant_id']) : 0;
    $duration_id     = isset($_GET['duration_id']) ? intval($_GET['duration_id']) : 0;
    $condition_id    = isset($_GET['condition_id']) ? intval($_GET['condition_id']) : 0;
    $product_color_id= isset($_GET['product_color_id']) ? intval($_GET['product_color_id']) : 0;
    $frame_color_id  = isset($_GET['frame_color_id']) ? intval($_GET['frame_color_id']) : 0;
    $final_price     = isset($_GET['final_price']) ? floatval($_GET['final_price']) : 0;

    $metadata = [
        'produkt'       => isset($_GET['produkt']) ? sanitize_text_field($_GET['produkt']) : '',
        'extra'         => isset($_GET['extra']) ? sanitize_text_field($_GET['extra']) : '',
        'dauer'         => isset($_GET['dauer']) ? sanitize_text_field($_GET['dauer']) : '',
        'dauer_name'    => isset($_GET['dauer_name']) ? sanitize_text_field($_GET['dauer_name']) : '',
        'zustand'       => isset($_GET['zustand']) ? sanitize_text_field($_GET['zustand']) : '',
        'produktfarbe'  => isset($_GET['produktfarbe']) ? sanitize_text_field($_GET['produktfarbe']) : '',
        'gestellfarbe'  => isset($_GET['gestellfarbe']) ? sanitize_text_field($_GET['gestellfarbe']) : '',
        'start_date'   => isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '',
        'end_date'     => isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '',
        'days'         => isset($_GET['days']) ? intval($_GET['days']) : 1,
    ];

    ob_start();
    ?>
    <div class="produkt-container shop-overview-container">
        <div id="checkout-mount-point"></div>
    </div>
    <script>
    (async () => {
        const publishableKey = <?php echo json_encode(\ProduktVerleih\StripeService::get_publishable_key()); ?>;
        if (!publishableKey) return;
        const stripe = Stripe(publishableKey);
        const fetchClientSecret = async () => {
<?php if ($cart_mode): ?>
            const items = JSON.parse(localStorage.getItem('produkt_cart') || '[]');
            const res = await fetch('<?php echo admin_url('admin-ajax.php?action=create_embedded_checkout_session'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cart_items: items,
                    shipping_price_id: <?php echo json_encode($shipping_price_id); ?>
                })
            });
<?php else: ?>
            const res = await fetch('<?php echo admin_url('admin-ajax.php?action=create_embedded_checkout_session'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    price_id: <?php echo json_encode($atts['price_id']); ?>,
                    extra_price_ids: <?php echo json_encode($extra_price_ids); ?>,
                    shipping_price_id: <?php echo json_encode($shipping_price_id); ?>,
                    extra_ids: <?php echo json_encode($extra_ids_raw); ?>,
                    category_id: <?php echo json_encode($category_id); ?>,
                    variant_id: <?php echo json_encode($variant_id); ?>,
                    duration_id: <?php echo json_encode($duration_id); ?>,
                    condition_id: <?php echo json_encode($condition_id); ?>,
                    product_color_id: <?php echo json_encode($product_color_id); ?>,
                    frame_color_id: <?php echo json_encode($frame_color_id); ?>,
                    final_price: <?php echo json_encode($final_price); ?>,
                    produkt: <?php echo json_encode($metadata['produkt']); ?>,
                    extra: <?php echo json_encode($metadata['extra']); ?>,
                    dauer: <?php echo json_encode($metadata['dauer']); ?>,
                    dauer_name: <?php echo json_encode($metadata['dauer_name']); ?>,
                    zustand: <?php echo json_encode($metadata['zustand']); ?>,
                    produktfarbe: <?php echo json_encode($metadata['produktfarbe']); ?>,
                    gestellfarbe: <?php echo json_encode($metadata['gestellfarbe']); ?>,
                    start_date: <?php echo json_encode($metadata['start_date']); ?>,
                    end_date: <?php echo json_encode($metadata['end_date']); ?>,
                    days: <?php echo json_encode($metadata['days']); ?>
                })
            });
<?php endif; ?>
            const data = await res.json();
            return data.client_secret;
        };
        const checkout = await stripe.initEmbeddedCheckout({ fetchClientSecret });
        checkout.mount('#checkout-mount-point');
<?php if ($cart_mode): ?>
        localStorage.removeItem('produkt_cart');
<?php endif; ?>
    })();
    </script>
    <?php
    return ob_get_clean();
}

require_once plugin_dir_path(__FILE__) . 'includes/seo-module.php';
\ProduktVerleih\SeoModule::init();
