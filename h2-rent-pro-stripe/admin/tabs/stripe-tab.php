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
    update_option('produkt_ct_shipping', wp_kses_post($_POST['ct_shipping'] ?? ''));
    update_option('produkt_ct_submit', wp_kses_post($_POST['ct_submit'] ?? ''));
    update_option('produkt_ct_after_submit', wp_kses_post($_POST['ct_after_submit'] ?? ''));
    update_option('produkt_ct_agb', wp_kses_post($_POST['ct_agb'] ?? ''));
    update_option('produkt_betriebsmodus', sanitize_text_field($_POST['produkt_betriebsmodus'] ?? 'miete'));
    echo '<div class="notice notice-success"><p>✅ Stripe-Einstellungen gespeichert!</p></div>';
}

if (isset($_POST['clear_price_cache']) && check_admin_referer('clear_price_cache_action')) {
    \ProduktVerleih\StripeService::clear_price_cache();
    echo '<div class="notice notice-success"><p>✅ Preis-Cache geleert.</p></div>';
}

$stripe_publishable_key = get_option('produkt_stripe_publishable_key', '');
$stripe_secret_key   = get_option('produkt_stripe_secret_key', '');
$stripe_pmc_id       = get_option('produkt_stripe_pmc_id', '');
$stripe_webhook_secret = get_option('produkt_stripe_webhook_secret', '');
$tos_url             = get_option('produkt_tos_url', home_url('/agb'));
$success_url         = get_option('produkt_success_url', home_url('/danke'));
$cancel_url          = get_option('produkt_cancel_url', home_url('/abbrechen'));
$ct_shipping         = get_option('produkt_ct_shipping', '');
$ct_submit           = get_option('produkt_ct_submit', '');
$ct_after_submit     = get_option('produkt_ct_after_submit', '');
$ct_agb              = get_option('produkt_ct_agb', '');
$modus               = get_option('produkt_betriebsmodus', 'miete');
?>

<div class="produkt-branding-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <div class="produkt-form-section">
            <h4>🔑 Stripe API Keys</h4>
            <div class="produkt-form-grid">
                <div class="produkt-form-group">
                    <label>Publishable Key</label>
                    <input type="text" name="stripe_publishable_key" value="<?php echo esc_attr($stripe_publishable_key); ?>">
                </div>
                <div class="produkt-form-group">
                    <label>Secret Key</label>
                    <input type="text" name="stripe_secret_key" value="<?php echo esc_attr($stripe_secret_key); ?>">
                </div>
                <div class="produkt-form-group">
                    <label>PayPal Payment Method Configuration ID</label>
                    <input type="text" name="stripe_pmc_id" value="<?php echo esc_attr($stripe_pmc_id); ?>">
                </div>
                <div class="produkt-form-group">
                    <label>Webhook Signing Secret</label>
                    <input type="text" name="stripe_webhook_secret" value="<?php echo esc_attr($stripe_webhook_secret); ?>">
                </div>
                <div class="produkt-form-group">
                    <label for="produkt_betriebsmodus">Betriebsmodus</label>
                    <select name="produkt_betriebsmodus" id="produkt_betriebsmodus">
                        <option value="miete" <?php selected($modus, 'miete'); ?>>Vermietung</option>
                        <option value="kauf" <?php selected($modus, 'kauf'); ?>>Einmalverkauf</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="produkt-form-section">
            <h4>📄 AGB-Link</h4>
            <div class="produkt-form-group">
                <label>URL zur AGB-Seite</label>
                <input type="text" name="tos_url" value="<?php echo esc_attr($tos_url); ?>" placeholder="<?php echo esc_attr(home_url('/agb')); ?>">
                <p class="description">Link, der im Checkout angezeigt wird.</p>
            </div>
        </div>
        <div class="produkt-form-section">
            <h4>🔗 Weiterleitungs-URLs</h4>
            <div class="produkt-form-grid">
                <div class="produkt-form-group">
                    <label>Success URL</label>
                    <input type="text" name="success_url" value="<?php echo esc_attr($success_url); ?>" placeholder="<?php echo esc_attr(home_url('/danke')); ?>">
                    <p class="description">Der Parameter <code>?session_id=CHECKOUT_SESSION_ID</code> wird automatisch angehängt.</p>
                </div>
                <div class="produkt-form-group">
                    <label>Cancel URL</label>
                    <input type="text" name="cancel_url" value="<?php echo esc_attr($cancel_url); ?>" placeholder="<?php echo esc_attr(home_url('/abbrechen')); ?>">
                </div>
            </div>
        </div>
        <div class="produkt-form-section">
            <h4>💬 Custom Checkout Texte</h4>
            <div class="produkt-form-group">
                <label>Nachricht unter Versandadresse</label>
                <textarea name="ct_shipping" rows="2" class="large-text"><?php echo esc_textarea($ct_shipping); ?></textarea>
            </div>
            <div class="produkt-form-group">
                <label>Text neben AGB-Checkbox</label>
                <textarea name="ct_agb" rows="2" class="large-text"><?php echo esc_textarea($ct_agb); ?></textarea>
            </div>
            <div class="produkt-form-group">
                <label>Nachricht auf dem Bezahl-Button</label>
                <textarea name="ct_submit" rows="2" class="large-text"><?php echo esc_textarea($ct_submit); ?></textarea>
            </div>
            <div class="produkt-form-group">
                <label>Text nach Absenden</label>
                <textarea name="ct_after_submit" rows="2" class="large-text"><?php echo esc_textarea($ct_after_submit); ?></textarea>
            </div>
            <p class="description">Bleibt ein Feld leer, wird kein Text angezeigt.</p>
        </div>
        <?php submit_button('💾 Stripe Einstellungen speichern', 'primary', 'submit_stripe'); ?>
    </form>
    <form method="post" action="" style="margin-top:15px;">
        <?php wp_nonce_field('clear_price_cache_action'); ?>
        <input type="submit" name="clear_price_cache" class="button button-secondary" value="Preis-Cache löschen">
    </form>
</div>
