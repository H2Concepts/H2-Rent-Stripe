<?php
// Stripe Integration Tab Content

if (isset($_POST['submit_stripe'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    update_option('produkt_stripe_publishable_key', sanitize_text_field($_POST['stripe_publishable_key'] ?? ''));
    update_option('produkt_stripe_secret_key', sanitize_text_field($_POST['stripe_secret_key'] ?? ''));
    update_option('produkt_stripe_pmc_id', sanitize_text_field($_POST['stripe_pmc_id'] ?? ''));
    update_option('produkt_stripe_webhook_secret', sanitize_text_field($_POST['stripe_webhook_secret'] ?? ''));
    update_option('produkt_tos_url', esc_url_raw($_POST['tos_url'] ?? ''));
    update_option('produkt_success_url', esc_url_raw($_POST['success_url'] ?? ''));
    update_option('produkt_cancel_url', esc_url_raw($_POST['cancel_url'] ?? ''));
    update_option('produkt_newsletter_success_url', esc_url_raw($_POST['newsletter_success_url'] ?? ''));
    update_option('produkt_newsletter_error_url', esc_url_raw($_POST['newsletter_error_url'] ?? ''));
    update_option('produkt_ct_shipping', wp_kses_post($_POST['ct_shipping'] ?? ''));
    update_option('produkt_ct_submit', wp_kses_post($_POST['ct_submit'] ?? ''));
    update_option('produkt_ct_after_submit', wp_kses_post($_POST['ct_after_submit'] ?? ''));
    update_option('produkt_ct_agb', wp_kses_post($_POST['ct_agb'] ?? ''));
    update_option('produkt_betriebsmodus', sanitize_text_field($_POST['produkt_betriebsmodus'] ?? 'miete'));
    update_option('produkt_miete_cart_mode', sanitize_text_field($_POST['produkt_miete_cart_mode'] ?? 'direct'));
    echo '<div class="notice notice-success"><p>✅ ' . esc_html__('Stripe-Einstellungen gespeichert!', 'h2-rental-pro') . '</p></div>';
}

if (isset($_POST['produkt_clear_stripe_cache'])) {
    \ProduktVerleih\Admin::verify_admin_action();

    $deleted = 0;
    if (class_exists('\\ProduktVerleih\\StripeService')) {
        $deleted = \ProduktVerleih\StripeService::clear_stripe_cache();
    }

    echo '<div class="notice notice-success"><p>🔄 ' . sprintf(esc_html__('Stripe-Cache wurde gelöscht. Alle Stripe-Produkte und -Preise werden beim nächsten Sync neu geladen. (Gelöschte Cache-Einträge: %d)', 'h2-rental-pro'), intval($deleted)) . '</p></div>';
}

$stripe_publishable_key = get_option('produkt_stripe_publishable_key', '');
$stripe_secret_key = get_option('produkt_stripe_secret_key', '');
$stripe_pmc_id = get_option('produkt_stripe_pmc_id', '');
$stripe_webhook_secret = get_option('produkt_stripe_webhook_secret', '');
$tos_url = get_option('produkt_tos_url', home_url('/agb'));
$success_url = get_option('produkt_success_url', home_url('/danke'));
$cancel_url = get_option('produkt_cancel_url', home_url('/abbrechen'));
$newsletter_success_url = get_option('produkt_newsletter_success_url', home_url('/newsletter-bestaetigt'));
$newsletter_error_url = get_option('produkt_newsletter_error_url', home_url('/newsletter-fehler'));
$ct_shipping = get_option('produkt_ct_shipping', '');
$ct_submit = get_option('produkt_ct_submit', '');
$ct_after_submit = get_option('produkt_ct_after_submit', '');
$ct_agb = get_option('produkt_ct_agb', '');
$modus = get_option('produkt_betriebsmodus', 'miete');
$cart_mode = get_option('produkt_miete_cart_mode', 'direct');
?>

<div class="settings-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <button type="submit" name="submit_stripe" class="icon-btn stripe-save-btn"
            aria-label="<?php echo esc_attr__('Speichern', 'h2-rental-pro'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                <path
                    d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z" />
                <path
                    d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z" />
            </svg>
        </button>
        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2><?php echo esc_html__('Stripe API Keys', 'h2-rental-pro'); ?></h2>
                        <p class="card-subline">
                            <?php echo esc_html__('Zugangsdaten für den Zahlungsanbieter', 'h2-rental-pro'); ?>
                        </p>
                    </div>
                    <button type="submit" name="produkt_clear_stripe_cache" value="1"
                        class="button button-secondary"><?php echo esc_html__('Stripe Cache löschen', 'h2-rental-pro'); ?></button>
                </div>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Publishable Key', 'h2-rental-pro'); ?></label>
                        <div class="key-input-wrapper">
                            <input type="text" name="stripe_publishable_key"
                                value="<?php echo esc_attr($stripe_publishable_key); ?>">
                            <button type="button" class="key-toggle"
                                aria-label="<?php echo esc_attr__('Key anzeigen/verbergen', 'h2-rental-pro'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Secret Key', 'h2-rental-pro'); ?></label>
                        <div class="key-input-wrapper">
                            <input type="text" name="stripe_secret_key"
                                value="<?php echo esc_attr($stripe_secret_key); ?>">
                            <button type="button" class="key-toggle"
                                aria-label="<?php echo esc_attr__('Key anzeigen/verbergen', 'h2-rental-pro'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('PayPal Payment Method Configuration ID', 'h2-rental-pro'); ?></label>
                        <input type="text" name="stripe_pmc_id" value="<?php echo esc_attr($stripe_pmc_id); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Webhook Signing Secret', 'h2-rental-pro'); ?></label>
                        <div class="key-input-wrapper">
                            <input type="text" name="stripe_webhook_secret"
                                value="<?php echo esc_attr($stripe_webhook_secret); ?>">
                            <button type="button" class="key-toggle"
                                aria-label="<?php echo esc_attr__('Key anzeigen/verbergen', 'h2-rental-pro'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="produkt-form-group">
                        <label
                            for="produkt_betriebsmodus"><?php echo esc_html__('Betriebsmodus', 'h2-rental-pro'); ?></label>
                        <div class="produkt-form-inline">
                            <select name="produkt_betriebsmodus" id="produkt_betriebsmodus">
                                <option value="miete" <?php selected($modus, 'miete'); ?>>
                                    <?php echo esc_html__('Vermietung', 'h2-rental-pro'); ?>
                                </option>
                                <option value="kauf" <?php selected($modus, 'kauf'); ?>>
                                    <?php echo esc_html__('Einmalverkauf', 'h2-rental-pro'); ?>
                                </option>
                            </select>
                            <div class="produkt-form-group" id="produkt-cart-mode"
                                style="display: <?php echo $modus === 'miete' ? 'block' : 'none'; ?>; margin: 0;">
                                <select name="produkt_miete_cart_mode" id="produkt_miete_cart_mode">
                                    <option value="cart" <?php selected($cart_mode, 'cart'); ?>>
                                        <?php echo esc_html__('Mit Warenkorb-Funktion', 'h2-rental-pro'); ?>
                                    </option>
                                    <option value="direct" <?php selected($cart_mode, 'direct'); ?>>
                                        <?php echo esc_html__('Ohne Warenkorb-Funktion', 'h2-rental-pro'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2><?php echo esc_html__('AGB-Link', 'h2-rental-pro'); ?></h2>
                <p class="card-subline"><?php echo esc_html__('Verweis auf Ihre AGB-Seite', 'h2-rental-pro'); ?></p>
                <div class="form-grid">
                    <div class="produkt-form-group full-width">
                        <label><?php echo esc_html__('URL zur AGB-Seite', 'h2-rental-pro'); ?></label>
                        <input type="text" name="tos_url" value="<?php echo esc_attr($tos_url); ?>"
                            placeholder="<?php echo esc_attr(home_url('/agb')); ?>">
                        <p class="description">
                            <?php echo esc_html__('Link, der im Checkout angezeigt wird.', 'h2-rental-pro'); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2><?php echo esc_html__('Weiterleitungs-URLs', 'h2-rental-pro'); ?></h2>
                <p class="card-subline"><?php echo esc_html__('Zielseiten nach dem Checkout', 'h2-rental-pro'); ?></p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Success URL', 'h2-rental-pro'); ?></label>
                        <input type="text" name="success_url" value="<?php echo esc_attr($success_url); ?>"
                            placeholder="<?php echo esc_attr(home_url('/danke')); ?>">
                        <p class="description"><?php echo esc_html__('Der Parameter', 'h2-rental-pro'); ?>
                            <code>?session_id=CHECKOUT_SESSION_ID</code>
                            <?php echo esc_html__('wird automatisch angehängt.', 'h2-rental-pro'); ?>
                        </p>
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Cancel URL', 'h2-rental-pro'); ?></label>
                        <input type="text" name="cancel_url" value="<?php echo esc_attr($cancel_url); ?>"
                            placeholder="<?php echo esc_attr(home_url('/abbrechen')); ?>">
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2><?php echo esc_html__('Newsletter', 'h2-rental-pro'); ?></h2>
                <p class="card-subline">
                    <?php echo esc_html__('Weiterleitungen nach Double-Opt-In Bestätigung', 'h2-rental-pro'); ?>
                </p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Newsletter bestätigt (Danke-Seite URL)', 'h2-rental-pro'); ?></label>
                        <input type="text" name="newsletter_success_url"
                            value="<?php echo esc_attr($newsletter_success_url); ?>"
                            placeholder="<?php echo esc_attr(home_url('/newsletter-bestaetigt')); ?>">
                        <p class="description">
                            <?php echo esc_html__('Hierhin wird nach erfolgreicher Newsletter-Bestätigung weitergeleitet.', 'h2-rental-pro'); ?>
                        </p>
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Newsletter Fehler (Fehler-Seite URL)', 'h2-rental-pro'); ?></label>
                        <input type="text" name="newsletter_error_url"
                            value="<?php echo esc_attr($newsletter_error_url); ?>"
                            placeholder="<?php echo esc_attr(home_url('/newsletter-fehler')); ?>">
                        <p class="description">
                            <?php echo esc_html__('Hierhin wird geleitet, wenn Token ungültig/abgelaufen ist.', 'h2-rental-pro'); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2><?php echo esc_html__('Custom Checkout Texte', 'h2-rental-pro'); ?></h2>
                <p class="card-subline">
                    <?php echo esc_html__('Individuelle Hinweise im Bezahlprozess', 'h2-rental-pro'); ?>
                </p>
                <div class="form-grid">
                    <div class="produkt-form-group full-width">
                        <label><?php echo esc_html__('Nachricht unter Versandadresse', 'h2-rental-pro'); ?></label>
                        <textarea name="ct_shipping" rows="2"><?php echo esc_textarea($ct_shipping); ?></textarea>
                    </div>
                    <div class="produkt-form-group full-width">
                        <label><?php echo esc_html__('Text neben AGB-Checkbox', 'h2-rental-pro'); ?></label>
                        <textarea name="ct_agb" rows="2"><?php echo esc_textarea($ct_agb); ?></textarea>
                    </div>
                    <div class="produkt-form-group full-width">
                        <label><?php echo esc_html__('Nachricht auf dem Bezahl-Button', 'h2-rental-pro'); ?></label>
                        <textarea name="ct_submit" rows="2"><?php echo esc_textarea($ct_submit); ?></textarea>
                    </div>
                    <div class="produkt-form-group full-width">
                        <label><?php echo esc_html__('Text nach Absenden', 'h2-rental-pro'); ?></label>
                        <textarea name="ct_after_submit"
                            rows="2"><?php echo esc_textarea($ct_after_submit); ?></textarea>
                    </div>
                </div>
                <p class="description">
                    <?php echo esc_html__('Bleibt ein Feld leer, wird kein Text angezeigt.', 'h2-rental-pro'); ?>
                </p>
            </div>
        </div>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.key-input-wrapper').forEach(function (wrapper) {
            const input = wrapper.querySelector('input');
            const toggle = wrapper.querySelector('.key-toggle');
            function maskValue(val) {
                return val ? val.slice(0, 4) + '*'.repeat(Math.max(0, val.length - 4)) : '';
            }
            input.dataset.full = input.value;
            input.value = maskValue(input.value);
            input.dataset.visible = 'false';
            toggle.addEventListener('click', function () {
                if (input.dataset.visible === 'true') {
                    input.value = maskValue(input.dataset.full);
                    input.dataset.visible = 'false';
                } else {
                    input.value = input.dataset.full;
                    input.dataset.visible = 'true';
                    input.focus();
                }
            });
            input.addEventListener('input', function () {
                input.dataset.full = input.value;
                if (input.dataset.visible === 'false') {
                    input.value = maskValue(input.dataset.full);
                }
            });
        });
        const form = document.querySelector('.settings-tab form');
        form.addEventListener('submit', function () {
            document.querySelectorAll('.key-input-wrapper input').forEach(function (input) {
                input.value = input.dataset.full;
            });
        });

        const modusSelect = document.getElementById('produkt_betriebsmodus');
        const cartModeWrapper = document.getElementById('produkt-cart-mode');
        if (modusSelect && cartModeWrapper) {
            modusSelect.addEventListener('change', function () {
                cartModeWrapper.style.display = this.value === 'miete' ? 'block' : 'none';
            });
        }
    });
</script>