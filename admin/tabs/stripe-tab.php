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

<div class="settings-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <button type="submit" name="submit_stripe" class="icon-btn stripe-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
            </svg>
        </button>
        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <h2>Stripe API Keys</h2>
                <p class="card-subline">Zugangsdaten für den Zahlungsanbieter</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Publishable Key</label>
                        <div class="key-input-wrapper">
                            <input type="text" name="stripe_publishable_key" value="<?php echo esc_attr($stripe_publishable_key); ?>">
                            <button type="button" class="key-toggle" aria-label="Key anzeigen/verbergen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="produkt-form-group">
                        <label>Secret Key</label>
                        <div class="key-input-wrapper">
                            <input type="text" name="stripe_secret_key" value="<?php echo esc_attr($stripe_secret_key); ?>">
                            <button type="button" class="key-toggle" aria-label="Key anzeigen/verbergen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="produkt-form-group">
                        <label>PayPal Payment Method Configuration ID</label>
                        <input type="text" name="stripe_pmc_id" value="<?php echo esc_attr($stripe_pmc_id); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Webhook Signing Secret</label>
                        <div class="key-input-wrapper">
                            <input type="text" name="stripe_webhook_secret" value="<?php echo esc_attr($stripe_webhook_secret); ?>">
                            <button type="button" class="key-toggle" aria-label="Key anzeigen/verbergen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
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
            <div class="dashboard-card">
                <h2>AGB-Link</h2>
                <p class="card-subline">Verweis auf Ihre AGB-Seite</p>
                <div class="form-grid">
                    <div class="form-field full">
                        <label>URL zur AGB-Seite</label>
                        <input type="text" name="tos_url" value="<?php echo esc_attr($tos_url); ?>" placeholder="<?php echo esc_attr(home_url('/agb')); ?>">
                        <p class="description">Link, der im Checkout angezeigt wird.</p>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2>Weiterleitungs-URLs</h2>
                <p class="card-subline">Zielseiten nach dem Checkout</p>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Success URL</label>
                        <input type="text" name="success_url" value="<?php echo esc_attr($success_url); ?>" placeholder="<?php echo esc_attr(home_url('/danke')); ?>">
                        <p class="description">Der Parameter <code>?session_id=CHECKOUT_SESSION_ID</code> wird automatisch angehängt.</p>
                    </div>
                    <div class="form-field">
                        <label>Cancel URL</label>
                        <input type="text" name="cancel_url" value="<?php echo esc_attr($cancel_url); ?>" placeholder="<?php echo esc_attr(home_url('/abbrechen')); ?>">
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2>Custom Checkout Texte</h2>
                <p class="card-subline">Individuelle Hinweise im Bezahlprozess</p>
                <div class="form-grid">
                    <div class="form-field full">
                        <label>Nachricht unter Versandadresse</label>
                        <textarea name="ct_shipping" rows="2" class="large-text"><?php echo esc_textarea($ct_shipping); ?></textarea>
                    </div>
                    <div class="form-field full">
                        <label>Text neben AGB-Checkbox</label>
                        <textarea name="ct_agb" rows="2" class="large-text"><?php echo esc_textarea($ct_agb); ?></textarea>
                    </div>
                    <div class="form-field full">
                        <label>Nachricht auf dem Bezahl-Button</label>
                        <textarea name="ct_submit" rows="2" class="large-text"><?php echo esc_textarea($ct_submit); ?></textarea>
                    </div>
                    <div class="form-field full">
                        <label>Text nach Absenden</label>
                        <textarea name="ct_after_submit" rows="2" class="large-text"><?php echo esc_textarea($ct_after_submit); ?></textarea>
                    </div>
                </div>
                <p class="description">Bleibt ein Feld leer, wird kein Text angezeigt.</p>
            </div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.key-input-wrapper').forEach(function(wrapper) {
        const input = wrapper.querySelector('input');
        const toggle = wrapper.querySelector('.key-toggle');
        function maskValue(val) {
            return val ? val.slice(0, 4) + '*'.repeat(Math.max(0, val.length - 4)) : '';
        }
        input.dataset.full = input.value;
        input.value = maskValue(input.value);
        input.dataset.visible = 'false';
        toggle.addEventListener('click', function() {
            if (input.dataset.visible === 'true') {
                input.value = maskValue(input.dataset.full);
                input.dataset.visible = 'false';
            } else {
                input.value = input.dataset.full;
                input.dataset.visible = 'true';
                input.focus();
            }
        });
        input.addEventListener('input', function() {
            input.dataset.full = input.value;
            if (input.dataset.visible === 'false') {
                input.value = maskValue(input.dataset.full);
            }
        });
    });
    const form = document.querySelector('.settings-tab form');
    form.addEventListener('submit', function() {
        document.querySelectorAll('.key-input-wrapper input').forEach(function(input) {
            input.value = input.dataset.full;
        });
    });
});
</script>
