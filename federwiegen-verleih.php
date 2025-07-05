<?php
 /**
  * Plugin Name: Rent Plugin
  * Plugin URI: https://h2concepts.de
  * Description: Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration
* Version: 2.6.4
  * Author: H2 Concepts
  * License: GPL v2 or later
  * Text Domain: h2-concepts
  */
 
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
const FEDERWIEGEN_PLUGIN_VERSION = '2.6.4';
const FEDERWIEGEN_PLUGIN_DIR = __DIR__ . '/';
define('FEDERWIEGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FEDERWIEGEN_PLUGIN_PATH', FEDERWIEGEN_PLUGIN_DIR);
define('FEDERWIEGEN_VERSION', FEDERWIEGEN_PLUGIN_VERSION);
define('FEDERWIEGEN_PLUGIN_FILE', __FILE__);
// Stripe price ID for the one-time shipping charge
define('FEDERWIEGEN_SHIPPING_PRICE_ID', 'price_1QKQDzRxDui5dUOqdlAFIJcr');
// Display amount for shipping in Euros
define('FEDERWIEGEN_SHIPPING_COST', 9.99);
// Payment Method Configuration ID for PayPal
define('FEDERWIEGEN_PMC_ID', 'pmc_1QKPcvRxDui5dUOqaNaxNjsL');

// Control whether default demo data is inserted on activation
if (!defined('FEDERWIEGEN_LOAD_DEFAULT_DATA')) {
    define('FEDERWIEGEN_LOAD_DEFAULT_DATA', false);
}

require_once FEDERWIEGEN_PLUGIN_DIR . 'includes/Autoloader.php';
FederwiegenVerleih\Autoloader::register();

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['FederwiegenVerleih\\Plugin', 'activate_plugin']);
register_deactivation_hook(__FILE__, ['FederwiegenVerleih\\Plugin', 'deactivate_plugin']);

// Initialize the plugin after WordPress has loaded
add_action('plugins_loaded', function () {
    new \FederwiegenVerleih\Plugin();
});

add_shortcode('stripe_elements_form', 'federwiegen_stripe_elements_form');
function federwiegen_stripe_elements_form() {
    $publishable_key = \FederwiegenVerleih\StripeService::get_publishable_key();
    if (empty($publishable_key)) {
        return '<p>Stripe API-Schl\xC3\xBCssel fehlt. Bitte in den Plugin-Einstellungen eintragen.</p>';
    }

    ob_start(); ?>

    <div class="federwiegen-checkout-wrapper">
      <div class="federwiegen-checkout-left">
        <div id="checkout"></div>
      </div>

      <div class="federwiegen-checkout-right">
        <h3>Bestellübersicht</h3>
        <?php
          $preis_cents = isset($_GET['preis']) ? intval($_GET['preis']) : 0;
          $shipping_cents = isset($_GET['shipping']) ? intval($_GET['shipping']) : 0;
          $total_first_cents = $preis_cents + $shipping_cents;
        ?>
        <ul class="federwiegen-checkout-summary">
          <?php if (!empty($_GET['produkt'])): ?>
          <li>Produkt: <?php echo esc_html($_GET['produkt']); ?></li>
          <?php endif; ?>
          <?php if (!empty($_GET['extra'])): ?>
          <li>Extra: <?php echo esc_html($_GET['extra']); ?></li>
          <?php endif; ?>
          <?php if (!empty($_GET['dauer']) || !empty($_GET['dauer_name'])): ?>
          <li>Mietdauer: <?php echo esc_html($_GET['dauer_name'] ?? $_GET['dauer']); ?></li>
          <?php endif; ?>
          <?php if (!empty($_GET['zustand'])): ?>
          <li>Zustand: <?php echo esc_html($_GET['zustand']); ?></li>
          <?php endif; ?>
          <?php if (!empty($_GET['produktfarbe'])): ?>
          <li>Produktfarbe: <?php echo esc_html($_GET['produktfarbe']); ?></li>
          <?php endif; ?>
          <?php if (!empty($_GET['gestellfarbe'])): ?>
          <li>Gestellfarbe: <?php echo esc_html($_GET['gestellfarbe']); ?></li>
          <?php endif; ?>
          <?php if ($preis_cents): ?>
          <li>Preis: <?php echo number_format($preis_cents / 100, 2, ',', '.'); ?> €</li>
          <?php endif; ?>
          <?php if ($shipping_cents): ?>
          <li>Versand: <?php echo number_format($shipping_cents / 100, 2, ',', '.'); ?> €</li>
          <?php endif; ?>
          <li>Gesamt 1. Monat: <?php echo number_format($total_first_cents / 100, 2, ',', '.'); ?> €</li>
          <li>Jeder weitere Monat: <?php echo number_format($preis_cents / 100, 2, ',', '.'); ?> €</li>
        </ul>
      </div>
    </div>

    <script src="https://js.stripe.com/basil/stripe.js"></script>
    <script>
      function getUrlParameter(name) {
        name = name.replace(/[[]/, '\\[').replace(/[\]]/, '\\]');
        const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        const results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
      }

      const baseData = {
        produkt: getUrlParameter('produkt'),
        extra: getUrlParameter('extra'),
        dauer: getUrlParameter('dauer'),
        dauer_name: getUrlParameter('dauer_name'),
        zustand: getUrlParameter('zustand'),
        farbe: getUrlParameter('farbe'),
        produktfarbe: getUrlParameter('produktfarbe'),
        gestellfarbe: getUrlParameter('gestellfarbe'),
        preis: getUrlParameter('preis'),
        shipping: getUrlParameter('shipping'),
        variant_id: getUrlParameter('variant_id'),
        duration_id: getUrlParameter('duration_id'),
        price_id: getUrlParameter('price_id')
      };

      const SHIPPING_PRICE_ID = '<?php echo esc_js(FEDERWIEGEN_SHIPPING_PRICE_ID); ?>';

      const stripe = Stripe('<?php echo esc_js($publishable_key); ?>');

      const fetchClientSecret = async () => {
        const res = await fetch('<?php echo admin_url("admin-ajax.php?action=create_checkout_session"); ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            ...baseData,
            shipping_price_id: SHIPPING_PRICE_ID
          })
        });
        const { client_secret } = await res.json();
        return client_secret;
      };

      (async () => {
        const checkout = await stripe.initEmbeddedCheckout({ fetchClientSecret });
        checkout.mount('#checkout');
      })();
    </script>
    </div>

    <?php return ob_get_clean();
}