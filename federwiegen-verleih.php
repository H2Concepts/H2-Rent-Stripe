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

    <div class="federwiegen-checkout">
    <h2>Deine Bestellung</h2>
    <ul class="federwiegen-checkout-summary">
      <li>Produkt: <?php echo esc_html($_GET['produkt'] ?? ''); ?></li>
      <li>Extra: <?php echo esc_html($_GET['extra'] ?? ''); ?></li>
      <li>Abo: <?php echo esc_html($_GET['dauer_name'] ?? ($_GET['dauer'] ?? '')); ?></li>
      <li>Zustand: <?php echo esc_html($_GET['zustand'] ?? ''); ?></li>
      <li>Farbe: <?php echo esc_html($_GET['farbe'] ?? ''); ?></li>
      <li>Preis: <?php echo isset($_GET['preis']) ? number_format($_GET['preis'] / 100, 2, ',', '.') . ' €' : ''; ?></li>
    </ul>

    <form id="checkout-form" class="federwiegen-checkout-form">
      <h3>Lieferadresse</h3>

      <label for="fullname">Vollständiger Name*</label>
      <input type="text" id="fullname" name="fullname" required>

      <label for="email">E-Mail-Adresse*</label>
      <input type="email" id="email" name="email" required>

      <label for="phone">Telefonnummer*</label>
      <input type="tel" id="phone" name="phone" required>

      <label for="street">Straße &amp; Hausnummer*</label>
      <input type="text" id="street" name="street" required>

      <label for="postal">PLZ*</label>
      <input type="text" id="postal" name="postal" required>

      <label for="city">Ort*</label>
      <input type="text" id="city" name="city" required>

      <label for="country">Land*</label>
      <input type="text" id="country" name="country" value="DE" required>

      <label class="checkbox">
        <input type="checkbox" id="agb" required>
        Ich akzeptiere die <a href="/agb" target="_blank">Allgemeinen Geschäftsbedingungen</a>*
      </label>

      <h3>Zahlung</h3>
      <div id="card-element"></div>

      <button id="submit">Jetzt bezahlen</button>
      <div id="payment-message"></div>
    </form>

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
        preis: getUrlParameter('preis')
      };

      const stripe = Stripe('pk_live_51QGi8URxDui5dUOqbXPixQCsZWvMyoYD0jLcZ3b0UHIBzK1dO3veMHVK4R8HY2G5ZKVjkKqVep0jYs4UdcPpDVYt00BYmp1Z6S');
      let elements = stripe.elements();
      let card = elements.create('card');
      card.mount('#card-element');

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
          produkt: baseData.produkt,
          extra: baseData.extra,
          dauer: baseData.dauer,
          dauer_name: baseData.dauer_name,
          zustand: baseData.zustand,
          farbe: baseData.farbe,
          preis: baseData.preis,
          price_id: getPriceId(baseData.dauer)
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
          const { error, paymentIntent } = await stripe.confirmCardPayment(responseData.client_secret, {
            payment_method: { card: card }
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