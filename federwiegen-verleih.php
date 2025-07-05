<?php
 /**
  * Plugin Name: Rent Plugin
  * Plugin URI: https://h2concepts.de
  * Description: Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration
* Version: 2.7.0
  * Author: H2 Concepts
  * License: GPL v2 or later
  * Text Domain: h2-concepts
  */
 
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
const FEDERWIEGEN_PLUGIN_VERSION = '2.7.0';
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

require_once FEDERWIEGEN_PLUGIN_DIR . 'includes/Autoloader.php';
FederwiegenVerleih\Autoloader::register();
require_once FEDERWIEGEN_PLUGIN_DIR . 'includes/Webhook.php';

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
        <div class="checkout-section">
          <label for="email">E-Mail-Adresse</label>
          <input type="text" id="email" />
          <div id="email-errors"></div>
        </div>
        <div id="billing-address" class="checkout-section"></div>
        <div id="shipping-address" class="checkout-section"></div>
        <div id="payment-element"></div>
        <button id="pay-button">Jetzt bezahlen</button>
        <div id="confirm-errors"></div>
      </div>

      <div class="federwiegen-checkout-right">
        <h3>Bestellübersicht</h3>
        <?php
          $preis_cents = isset($_GET['preis']) ? intval($_GET['preis']) : 0;
          $shipping_cents = isset($_GET['shipping']) ? intval($_GET['shipping']) : 0;
          if (!$shipping_cents && !empty($_GET['shipping_price_id'])) {
              $amount = \FederwiegenVerleih\StripeService::get_price_amount(sanitize_text_field($_GET['shipping_price_id']));
              if (!is_wp_error($amount)) {
                  $shipping_cents = intval(round($amount * 100));
              }
          }
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
        <div>
          <h3> Totals </h3>
          <div id="subtotal"></div>
          <div id="shipping"></div>
          <div id="total"></div>
        </div>
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

      const SHIPPING_PRICE_ID = getUrlParameter('shipping_price_id');

      const stripe = Stripe('<?php echo esc_js($publishable_key); ?>');

      const fetchClientSecret = () => {
        return fetch('<?php echo admin_url("admin-ajax.php?action=create_checkout_session"); ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            ...baseData,
            shipping_price_id: SHIPPING_PRICE_ID
          })
        })
          .then((response) => response.json())
          .then((json) => {
            if (json.checkoutSessionClientSecret) {
              return json.checkoutSessionClientSecret;
            }
            throw new Error(json.message || 'Fehler beim Erstellen der Checkout-Sitzung');
          });
      };

      stripe.initCheckout({ fetchClientSecret }).then((checkout) => {
        const emailInput = document.getElementById('email');
        const emailErrors = document.getElementById('email-errors');

        emailInput.addEventListener('input', () => {
          emailErrors.textContent = '';
        });

        emailInput.addEventListener('blur', () => {
          const newEmail = emailInput.value;
          checkout.updateEmail(newEmail).then((result) => {
            if (result.error) {
              emailErrors.textContent = result.error.message;
            }
          });
        });

        const billingAddressElement = checkout.createBillingAddressElement();
        billingAddressElement.mount('#billing-address');

        const shippingAddressElement = checkout.createShippingAddressElement();
        shippingAddressElement.mount('#shipping-address');

        const paymentElement = checkout.createPaymentElement();
        paymentElement.mount('#payment-element');

        const subtotal = document.getElementById('subtotal');
        const total = document.getElementById('total');

        checkout.on('change', (session) => {
          subtotal.textContent = `Subtotal: ${session.total.subtotal.amount}`;
          total.textContent = `Total: ${session.total.total.amount}`;
        });

        const button = document.getElementById('pay-button');
        const errors = document.getElementById('confirm-errors');
        button.addEventListener('click', () => {
          errors.textContent = '';
          checkout.confirm().then((result) => {
            if (result.type === 'error') {
              errors.textContent = result.error.message;
            }
          });
        });
      }).catch((err) => {
        document.getElementById('confirm-errors').textContent = err.message;
      });
    </script>
    </div>

    <?php return ob_get_clean();
}