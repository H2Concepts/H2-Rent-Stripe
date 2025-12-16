<?php 
$produkt_cart_mode = get_option('produkt_betriebsmodus', 'miete');
$ui = get_option('produkt_ui_settings', []);
$payment_icons = is_array($ui['payment_icons'] ?? null) ? $ui['payment_icons'] : [];
?>
<div id="produkt-cart-overlay" class="produkt-cart-overlay"></div>
<div id="produkt-cart-panel" class="produkt-cart-panel">
    <div class="cart-header">
        <span class="cart-title"><?php echo esc_html__('Dein Warenkorb', 'h2-rental-pro'); ?></span>
        <button type="button" class="cart-close" aria-label="<?php echo esc_attr__('Warenkorb schließen', 'h2-rental-pro'); ?>">
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
    <div class="cart-shipping"><span><?php echo esc_html__('Versand', 'h2-rental-pro'); ?></span><span class="cart-shipping-amount">0€</span></div>
    <div class="cart-summary"><span><?php echo esc_html__('Gesamtsumme', 'h2-rental-pro'); ?></span><span class="cart-total-amount" data-suffix="<?php echo $produkt_cart_mode === 'kauf' ? '' : esc_attr__(' / Monat', 'h2-rental-pro'); ?>">0€<?php echo $produkt_cart_mode === 'kauf' ? '' : esc_html__(' / Monat', 'h2-rental-pro'); ?></span></div>
    <?php if (!empty($payment_icons)): ?>
    <div class="cart-payment-icons produkt-payment-icons">
        <?php foreach ($payment_icons as $icon): ?>
            <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/payment-icons/' . $icon . '.svg'); ?>" alt="<?php echo esc_attr($icon); ?>">
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <button id="produkt-cart-checkout"><?php echo esc_html__('Jetzt bestellen', 'h2-rental-pro'); ?></button>
</div>

<div id="checkout-login-modal" class="checkout-login-modal" style="display:none;">
    <div class="modal-content">
        <div id="checkout-login-email-section">
            <h3><?php echo esc_html__('Willkommen zurück', 'h2-rental-pro'); ?></h3>
            <p class="subline"><?php echo esc_html__('Zum Einloggen bitte Ihre Email Adresse verwenden', 'h2-rental-pro'); ?></p>
            <label for="checkout-login-email" class="checkout-label"><?php echo esc_html__('Email Adresse', 'h2-rental-pro'); ?></label>
            <input type="email" id="checkout-login-email" placeholder="<?php echo esc_attr__('name@mail.com', 'h2-rental-pro'); ?>" required>
            <div id="checkout-email-warning" class="checkout-email-warning" style="display:none;">
                <?php echo esc_html__('Zu dieser E-Mail-Adresse besteht bereits ein Kundenkonto.', 'h2-rental-pro'); ?><br>
                <?php echo esc_html__('Sie können sich anmelden, damit wir Ihre Daten automatisch übernehmen.', 'h2-rental-pro'); ?>
            </div>
            <?php 
            $newsletter_enabled = get_option('produkt_newsletter_enabled', '1');
            if ($newsletter_enabled === '1'): 
            ?>
            <div class="checkout-newsletter-optin" style="margin: 14px 0 6px;">
                <label class="checkout-label" style="display:flex; gap:10px; align-items:flex-start;">
                    <input type="checkbox" id="checkout-newsletter-optin" value="1" style="margin-top:3px;">
                    <span><?php echo esc_html__('Registriere dich für E-Mails, um aktuelle Informationen von LittleLoopa zu Produkten und exklusiven Angeboten zu erhalten.', 'h2-rental-pro'); ?></span>
                </label>
            </div>
            <?php endif; ?>
            <button id="checkout-login-btn"><?php echo esc_html__('Code zum einloggen anfordern', 'h2-rental-pro'); ?></button>
            <button id="checkout-back-shop" class="secondary">&#10229; <?php echo esc_html__('Zurück zum Shop', 'h2-rental-pro'); ?></button>
            <p class="guest-text"><span><?php echo esc_html__('Noch kein Konto bei uns?', 'h2-rental-pro'); ?></span> <a href="#" id="checkout-guest-link"><?php echo esc_html__('Als Gast bestellen', 'h2-rental-pro'); ?></a></p>
        </div>
        <div id="checkout-login-code-section" style="display:none;">
            <h3><?php echo esc_html__('Code eingeben', 'h2-rental-pro'); ?></h3>
            <p class="subline"><?php echo esc_html__('Bitte geben Sie den 6-stelligen Code ein, den wir Ihnen per E-Mail gesendet haben.', 'h2-rental-pro'); ?></p>
            <div id="checkout-code-error" class="checkout-email-warning" style="display:none;"></div>
            <input id="checkout-login-code-combined" type="hidden" name="code" required>
            <div class="code-input-group">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="<?php echo esc_attr__('Code Ziffer 1', 'h2-rental-pro'); ?>" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="<?php echo esc_attr__('Code Ziffer 2', 'h2-rental-pro'); ?>" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="<?php echo esc_attr__('Code Ziffer 3', 'h2-rental-pro'); ?>" autocomplete="off">
                <span class="code-separator">-</span>
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="<?php echo esc_attr__('Code Ziffer 4', 'h2-rental-pro'); ?>" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="<?php echo esc_attr__('Code Ziffer 5', 'h2-rental-pro'); ?>" autocomplete="off">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="<?php echo esc_attr__('Code Ziffer 6', 'h2-rental-pro'); ?>" autocomplete="off">
            </div>
            <button id="checkout-verify-code-btn"><?php echo esc_html__('Einloggen', 'h2-rental-pro'); ?></button>
            <button id="checkout-back-email" class="secondary"><?php echo esc_html__('Zurück zur E-Mail-Eingabe', 'h2-rental-pro'); ?></button>
        </div>
    </div>
</div>

