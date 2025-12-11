<?php 
$produkt_cart_mode = get_option('produkt_betriebsmodus', 'miete');
$ui = get_option('produkt_ui_settings', []);
$payment_icons = is_array($ui['payment_icons'] ?? null) ? $ui['payment_icons'] : [];
?>
<div id="produkt-cart-overlay" class="produkt-cart-overlay"></div>
<div id="produkt-cart-panel" class="produkt-cart-panel">
    <div class="cart-header">
        <span class="cart-title">Dein Warenkorb</span>
        <button type="button" class="cart-close" aria-label="Warenkorb schließen">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="12" fill="#000"/>
                <path d="M6 6l12 12M18 6l-12 12" stroke="#fff" stroke-width="2"/>
            </svg>
        </button>
    </div>
    <div class="cart-free-shipping-banner" style="display: none;">
        <div class="cart-free-shipping-text"></div>
        <div class="cart-free-shipping-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div class="cart-free-shipping-progress-bar"></div>
        </div>
    </div>
    <div class="cart-items"></div>
    <div class="cart-shipping"><span>Versand</span><span class="cart-shipping-amount">0€</span></div>
    <div class="cart-summary"><span>Gesamtsumme</span><span class="cart-total-amount" data-suffix="<?php echo $produkt_cart_mode === 'kauf' ? '' : ' / Monat'; ?>">0€<?php echo $produkt_cart_mode === 'kauf' ? '' : ' / Monat'; ?></span></div>
    <?php if (!empty($payment_icons)): ?>
    <div class="cart-payment-icons produkt-payment-icons">
        <?php foreach ($payment_icons as $icon): ?>
            <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/payment-icons/' . $icon . '.svg'); ?>" alt="<?php echo esc_attr($icon); ?>">
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <button id="produkt-cart-checkout">Jetzt bestellen</button>
</div>

<div id="checkout-login-modal" class="checkout-login-modal" style="display:none;">
    <div class="modal-content">
        <div id="checkout-login-email-section">
            <h3>Willkommen zurück</h3>
            <p class="subline">Zum Einloggen bitte Ihre Email Adresse verwenden</p>
            <label for="checkout-login-email" class="checkout-label">Email Adresse</label>
            <input type="email" id="checkout-login-email" placeholder="name@mail.com" required>
            <div id="checkout-email-warning" class="checkout-email-warning" style="display:none;">
                Zu dieser E-Mail-Adresse besteht bereits ein Kundenkonto.<br>
                Sie können sich anmelden, damit wir Ihre Daten automatisch übernehmen.
            </div>
            <button id="checkout-login-btn">Code zum einloggen anfordern</button>
            <button id="checkout-back-shop" class="secondary">&#10229; Zurück zum Shop</button>
            <p class="guest-text"><span>Noch kein Konto bei uns?</span> <a href="#" id="checkout-guest-link">Als Gast bestellen</a></p>
        </div>
        <div id="checkout-login-code-section" style="display:none;">
            <h3>Code eingeben</h3>
            <p class="subline">Bitte geben Sie den 6-stelligen Code ein, den wir Ihnen per E-Mail gesendet haben.</p>
            <div id="checkout-code-error" class="checkout-email-warning" style="display:none;"></div>
            <input id="checkout-login-code-combined" type="hidden" name="code" required>
            <div class="code-input-group">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Code Ziffer 1" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Code Ziffer 2" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Code Ziffer 3" autocomplete="off">
                <span class="code-separator">-</span>
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Code Ziffer 4" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Code Ziffer 5" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Code Ziffer 6" autocomplete="off">
            </div>
            <button id="checkout-verify-code-btn">Einloggen</button>
            <button id="checkout-back-email" class="secondary">Zurück zur E-Mail-Eingabe</button>
        </div>
    </div>
</div>

