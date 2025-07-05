<?php
// Stripe Integration Tab Content

if (isset($_POST['submit_stripe'])) {
    \FederwiegenVerleih\Admin::verify_admin_action();
    update_option('federwiegen_stripe_publishable_key', sanitize_text_field($_POST['stripe_publishable_key'] ?? ''));
    update_option('federwiegen_stripe_secret_key', sanitize_text_field($_POST['stripe_secret_key'] ?? ''));
    echo '<div class="notice notice-success"><p>✅ Stripe-Einstellungen gespeichert!</p></div>';
}

$stripe_publishable_key = get_option('federwiegen_stripe_publishable_key', '');
$stripe_secret_key = get_option('federwiegen_stripe_secret_key', '');
?>

<div class="federwiegen-branding-tab">
    <form method="post" action="">
        <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
        <div class="federwiegen-form-section">
            <h4>🔑 Stripe API Keys</h4>
            <div class="federwiegen-form-grid">
                <div class="federwiegen-form-group">
                    <label>Publishable Key</label>
                    <input type="text" name="stripe_publishable_key" value="<?php echo esc_attr($stripe_publishable_key); ?>">
                </div>
                <div class="federwiegen-form-group">
                    <label>Secret Key</label>
                    <input type="text" name="stripe_secret_key" value="<?php echo esc_attr($stripe_secret_key); ?>">
                </div>
            </div>
        </div>
        <?php submit_button('💾 Stripe Einstellungen speichern', 'primary', 'submit_stripe'); ?>
    </form>
</div>
