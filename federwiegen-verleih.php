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
define('FEDERWIEGEN_SHIPPING_PRICE_ID', 'price_versand_once');

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
    ob_start(); ?>

    <div class="federwiegen-checkout-wrapper">
      <div class="federwiegen-checkout-left">
        <form id="checkout-form" class="federwiegen-checkout-form">
          <div class="checkout-section">
            <h3>1. Kontaktinformationen</h3>
            <p>Wir werden diese E-Mail verwenden, um Ihnen Details und Aktualisierungen zu Ihrer Bestellung zu senden.</p>
            <label for="email">E-Mail-Adresse*</label>
            <input type="email" id="email" name="email" required>
          </div>

          <div class="checkout-section">
            <h3>2. Versandadresse</h3>
            <p>Geben Sie die Adresse ein, an die Ihre Bestellung geliefert werden soll.</p>
            <label for="country">Land*</label>
            <input type="text" id="country" name="country" value="DE" required>

            <label for="fullname">Vollständiger Name*</label>
            <input type="text" id="fullname" name="fullname" required>

            <label for="street">Adresse*</label>
            <input type="text" id="street" name="street" required>

            <label for="city">Stadt*</label>
            <input type="text" id="city" name="city" required>

            <label for="postal">PLZ*</label>
            <input type="text" id="postal" name="postal" required>

            <label for="phone">Telefon*</label>
            <input type="tel" id="phone" name="phone" required>

            <label class="checkbox">
              <input type="checkbox" id="same-address" checked>
              Dieselbe Adresse für die Rechnungsstellung verwenden
            </label>

            <div id="billing-fields" style="display: none;">
              <h4>Rechnungsadresse</h4>
              <label for="bill_country">Land*</label>
              <input type="text" id="bill_country" name="bill_country" value="DE">

              <label for="bill_fullname">Vollständiger Name*</label>
              <input type="text" id="bill_fullname" name="bill_fullname">

              <label for="bill_street">Adresse*</label>
              <input type="text" id="bill_street" name="bill_street">

              <label for="bill_city">Stadt*</label>
              <input type="text" id="bill_city" name="bill_city">

              <label for="bill_postal">PLZ*</label>
              <input type="text" id="bill_postal" name="bill_postal">
            </div>
          </div>

          <div class="checkout-section">
            <h3>3. Zahlungsoptionen</h3>
            <div id="card-element"></div>
            <label class="checkbox">
              <input type="checkbox" id="agb" required>
              Ich akzeptiere die <a href="/agb" target="_blank">Allgemeinen Geschäftsbedingungen</a>*
            </label>
            <button id="submit">Jetzt bezahlen</button>
            <div id="payment-message"></div>
          </div>
        </form>
      </div>

      <div class="federwiegen-checkout-right">
        <h3>Bestellübersicht</h3>
        <?php
          $preis_cents = isset($_GET['preis']) ? intval($_GET['preis']) : 0;
          $shipping_cents = isset($_GET['shipping']) ? intval($_GET['shipping']) : 0;
          $total_first_cents = $preis_cents + $shipping_cents;
        ?>
        <ul class="federwiegen-checkout-summary">
          <li>Produkt: <?php echo esc_html($_GET['produkt'] ?? ''); ?></li>
          <li>Extra: <?php echo esc_html($_GET['extra'] ?? ''); ?></li>
          <li>Mietdauer: <?php echo esc_html($_GET['dauer_name'] ?? ($_GET['dauer'] ?? '')); ?></li>
          <li>Zustand: <?php echo esc_html($_GET['zustand'] ?? ''); ?></li>
          <li>Farbe: <?php echo esc_html($_GET['farbe'] ?? ''); ?></li>
          <li>Preis: <?php echo $preis_cents ? number_format($preis_cents / 100, 2, ',', '.') . ' €' : ''; ?></li>
          <li>Versand: <?php echo $shipping_cents ? number_format($shipping_cents / 100, 2, ',', '.') . ' €' : ''; ?></li>
          <li>Gesamt 1. Monat: <?php echo number_format($total_first_cents / 100, 2, ',', '.'); ?> €</li>
          <li>Jeder weitere Monat: <?php echo number_format($preis_cents / 100, 2, ',', '.'); ?> €</li>
        </ul>
      </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
      function getUrlParameter(name) {
        name = name.replace(/[[]/, '\\[').replace(/[\]]/, '\\]');
        const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        const results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
      }

      function getPriceId(dauer) {
        switch (parseInt(dauer, 10)) {
          case 1:
            return 'price_1QutK3RxDui5dUOqWEiBal7P';
          case 2:
            return 'price_1RgsfURxDui5dUOqCrHj06pj';
          case 4:
            return 'price_1RgslZRxDui5dUOqDKCSmqkU';
          case 6:
            return 'price_1RgssSRxDui5dUOqOUj6o0ZB';
          default:
            return '';
        }
      }

      const baseData = {
        produkt: getUrlParameter('produkt'),
        extra: getUrlParameter('extra'),
        dauer: getUrlParameter('dauer'),
        dauer_name: getUrlParameter('dauer_name'),
        zustand: getUrlParameter('zustand'),
        farbe: getUrlParameter('farbe'),
        preis: getUrlParameter('preis'),
        shipping: getUrlParameter('shipping')
      };

      const SHIPPING_PRICE_ID = '<?php echo esc_js(FEDERWIEGEN_SHIPPING_PRICE_ID); ?>';

      const stripe = Stripe('<?php echo esc_js(\FederwiegenVerleih\StripeService::get_publishable_key()); ?>');
      const elements = stripe.elements();
      const card = elements.create('card');
      card.mount('#card-element');

      const sameAddressCheckbox = document.getElementById('same-address');
      const billingFields = document.getElementById('billing-fields');
      sameAddressCheckbox.addEventListener('change', () => {
        billingFields.style.display = sameAddressCheckbox.checked ? 'none' : 'block';
      });

      const form = document.getElementById('checkout-form');
      form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!document.getElementById('agb').checked) {
          alert('Bitte akzeptiere die AGB.');
          return;
        }

        const formData = {
          fullname: document.getElementById('fullname').value,
          email: document.getElementById('email').value,
          phone: document.getElementById('phone').value,
          street: document.getElementById('street').value,
          postal: document.getElementById('postal').value,
          city: document.getElementById('city').value,
          country: document.getElementById('country').value,
          bill_fullname: document.getElementById('bill_fullname').value,
          bill_street: document.getElementById('bill_street').value,
          bill_postal: document.getElementById('bill_postal').value,
          bill_city: document.getElementById('bill_city').value,
          bill_country: document.getElementById('bill_country').value,
          produkt: baseData.produkt,
          extra: baseData.extra,
          dauer: baseData.dauer,
          dauer_name: baseData.dauer_name,
          zustand: baseData.zustand,
          farbe: baseData.farbe,
          preis: baseData.preis,
          shipping: baseData.shipping,
          price_id: getPriceId(baseData.dauer),
          shipping_price_id: SHIPPING_PRICE_ID
        };

        const messageEl = document.getElementById('payment-message');
        messageEl.textContent = '';

        try {
          const res = await fetch('<?php echo admin_url("admin-ajax.php?action=create_subscription"); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
          });
          if (!res.ok) {
            throw new Error('Serverfehler');
          }
          const responseData = await res.json();
          const shipping = {
            name: document.getElementById('fullname').value,
            phone: document.getElementById('phone').value,
            address: {
              line1: document.getElementById('street').value,
              postal_code: document.getElementById('postal').value,
              city: document.getElementById('city').value,
              country: document.getElementById('country').value,
            }
          };

          const billing = {
            name: document.getElementById('bill_fullname').value || shipping.name,
            email: document.getElementById('email').value,
            address: {
              line1: document.getElementById('bill_street').value || shipping.address.line1,
              postal_code: document.getElementById('bill_postal').value || shipping.address.postal_code,
              city: document.getElementById('bill_city').value || shipping.address.city,
              country: document.getElementById('bill_country').value || shipping.address.country,
            }
          };

          const { error, paymentIntent } = await stripe.confirmCardPayment(responseData.client_secret, {
            payment_method: { card: card, billing_details: billing },
            shipping: shipping
          });
          if (error) {
            messageEl.textContent = error.message;
          } else if (paymentIntent.status === 'succeeded') {
            messageEl.textContent = 'Zahlung erfolgreich!';
          }
        } catch (err) {
          messageEl.textContent = err.message;
        }
      });
    </script>
    </div>

    <?php return ob_get_clean();
}