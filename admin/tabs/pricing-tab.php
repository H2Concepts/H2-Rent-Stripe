<?php
// Global pricing and shipping settings
if (isset($_POST['submit_pricing'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    update_option('produkt_price_label', sanitize_text_field($_POST['price_label'] ?? ''));
    update_option('produkt_price_period', sanitize_text_field($_POST['price_period'] ?? 'month'));
    update_option('produkt_vat_included', isset($_POST['vat_included']) ? 1 : 0);
    update_option('produkt_shipping_provider', sanitize_text_field($_POST['shipping_provider'] ?? ''));
    update_option('produkt_shipping_price_id', sanitize_text_field($_POST['shipping_price_id'] ?? ''));
    update_option('produkt_shipping_label', sanitize_text_field($_POST['shipping_label'] ?? ''));
    update_option('produkt_button_text', sanitize_text_field($_POST['button_text'] ?? ''));
    update_option('produkt_button_icon', esc_url_raw($_POST['button_icon'] ?? ''));
    $icons = isset($_POST['payment_icons']) ? array_map('sanitize_text_field', (array)$_POST['payment_icons']) : array();
    update_option('produkt_payment_icons', implode(',', $icons));
    update_option('produkt_duration_tooltip', sanitize_textarea_field($_POST['duration_tooltip'] ?? ''));
    update_option('produkt_condition_tooltip', sanitize_textarea_field($_POST['condition_tooltip'] ?? ''));
    update_option('produkt_show_tooltips', isset($_POST['show_tooltips']) ? 1 : 0);
    echo '<div class="notice notice-success"><p>✅ Preis-Einstellungen gespeichert!</p></div>';
}

$price_label       = get_option('produkt_price_label', 'Monatlicher Mietpreis');
$price_period      = get_option('produkt_price_period', 'month');
$vat_included      = get_option('produkt_vat_included', 0);
$shipping_provider = get_option('produkt_shipping_provider', '');
$shipping_price_id = get_option('produkt_shipping_price_id', '');
$shipping_label    = get_option('produkt_shipping_label', 'Einmalige Versandkosten:');
$button_text       = get_option('produkt_button_text', '');
$button_icon       = get_option('produkt_button_icon', '');
$payment_icons     = array_filter(array_map('trim', explode(',', get_option('produkt_payment_icons', ''))));
$duration_tooltip  = get_option('produkt_duration_tooltip', '');
$condition_tooltip = get_option('produkt_condition_tooltip', '');
$show_tooltips     = get_option('produkt_show_tooltips', 1);
?>

<div class="produkt-branding-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <div class="produkt-form-section">
            <h4>💲 Preise</h4>
            <div class="produkt-form-grid">
                <div class="produkt-form-group">
                    <label>Preis-Label</label>
                    <input type="text" name="price_label" value="<?php echo esc_attr($price_label); ?>">
                </div>
                <div class="produkt-form-group">
                    <label>Preiszeitraum</label>
                    <select name="price_period">
                        <option value="month" <?php selected($price_period, 'month'); ?>>pro Monat</option>
                        <option value="one-time" <?php selected($price_period, 'one-time'); ?>>einmalig</option>
                    </select>
                </div>
                <div class="produkt-form-group">
                    <label><input type="checkbox" name="vat_included" value="1" <?php checked($vat_included, 1); ?>> Mit MwSt.</label>
                </div>
            </div>
        </div>

        <div class="produkt-form-section">
            <h4>🚚 Versand</h4>
            <div class="produkt-form-grid">
                <div class="produkt-form-group">
                    <label>Versanddienstleister</label>
                    <input type="text" name="shipping_provider" value="<?php echo esc_attr($shipping_provider); ?>">
                </div>
                <div class="produkt-form-group">
                    <label>Stripe Versandkosten Preis ID</label>
                    <input type="text" name="shipping_price_id" value="<?php echo esc_attr($shipping_price_id); ?>">
                </div>
                <div class="produkt-form-group">
                    <label>Text Versandkosten</label>
                    <input type="text" name="shipping_label" value="<?php echo esc_attr($shipping_label); ?>">
                </div>
            </div>
        </div>

        <div class="produkt-form-section">
            <h4>🔘 Button &amp; Bezahlmethoden</h4>
            <div class="produkt-form-grid">
                <div class="produkt-form-group">
                    <label>Button-Text</label>
                    <input type="text" name="button_text" value="<?php echo esc_attr($button_text); ?>">
                </div>
                <div class="produkt-form-group">
                    <label>Button-Icon</label>
                    <div class="produkt-upload-area">
                        <input type="url" name="button_icon" id="button_icon" value="<?php echo esc_attr($button_icon); ?>">
                        <button type="button" class="button produkt-media-button" data-target="button_icon">📁</button>
                    </div>
                </div>
            </div>
            <div class="produkt-form-group">
                <label>Bezahlmethoden</label>
                <div class="produkt-payment-checkboxes">
                    <?php $methods = ['american-express','apple-pay','google-pay','klarna','maestro','mastercard','paypal','shop','union-pay','visa']; ?>
                    <?php foreach ($methods as $key): ?>
                    <label>
                        <input type="checkbox" name="payment_icons[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $payment_icons)); ?>>
                        <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/payment-icons/' . $key . '.svg'); ?>" alt="<?php echo esc_attr($key); ?>">
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="produkt-form-section">
            <h4>💬 Tooltips</h4>
            <div class="produkt-form-group">
                <label>Mietdauer-Tooltip</label>
                <textarea name="duration_tooltip" rows="3" class="large-text"><?php echo esc_textarea($duration_tooltip); ?></textarea>
            </div>
            <div class="produkt-form-group">
                <label>Zustand-Tooltip</label>
                <textarea name="condition_tooltip" rows="3" class="large-text"><?php echo esc_textarea($condition_tooltip); ?></textarea>
            </div>
            <div class="produkt-form-group">
                <label><input type="checkbox" name="show_tooltips" value="1" <?php checked($show_tooltips, 1); ?>> Tooltips anzeigen</label>
            </div>
        </div>

        <?php submit_button('💾 Preis-Einstellungen speichern', 'primary', 'submit_pricing'); ?>
    </form>
</div>

<script>
// WordPress Media Library integration
jQuery(function($){
    $('.produkt-media-button').on('click', function(e){
        e.preventDefault();
        const target = $('#' + $(this).data('target'));
        const frame = wp.media({title:'Bild auswählen',button:{text:'Bild verwenden'},multiple:false});
        frame.on('select', function(){
            const attachment = frame.state().get('selection').first().toJSON();
            target.val(attachment.url);
        });
        frame.open();
    });
});
</script>
