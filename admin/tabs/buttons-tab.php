<?php
// Buttons & Tooltips Tab Content

if (isset($_POST['submit_buttons'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $settings = [
        'button_text'       => sanitize_text_field($_POST['button_text'] ?? ''),
        'button_icon'       => esc_url_raw($_POST['button_icon'] ?? ''),
        'payment_icons'     => isset($_POST['payment_icons']) ? array_map('sanitize_text_field', (array) $_POST['payment_icons']) : [],
        'price_label'       => sanitize_text_field($_POST['price_label'] ?? ''),
        'price_period'      => sanitize_text_field($_POST['price_period'] ?? 'month'),
        'vat_included'      => isset($_POST['vat_included']) ? 1 : 0,
        'duration_tooltip'  => sanitize_textarea_field($_POST['duration_tooltip'] ?? ''),
        'condition_tooltip' => sanitize_textarea_field($_POST['condition_tooltip'] ?? ''),
        'show_tooltips'     => isset($_POST['show_tooltips']) ? 1 : 0,
    ];
    update_option('produkt_ui_settings', $settings);
    echo '<div class="notice notice-success"><p>✅ Einstellungen gespeichert!</p></div>';
}

$ui = get_option('produkt_ui_settings', [
    'button_text' => '',
    'button_icon' => '',
    'payment_icons' => [],
    'price_label' => '',
    'price_period' => 'month',
    'vat_included' => 0,
    'duration_tooltip' => '',
    'condition_tooltip' => '',
    'show_tooltips' => 1,
]);
?>
<div class="produkt-branding-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <div class="produkt-form-section">
            <h4>🔘 Button & Tooltips</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Button-Text</label>
                    <input type="text" name="button_text" value="<?php echo esc_attr($ui['button_text']); ?>">
                </div>
                <div class="produkt-form-group">
                    <label>Button-Icon</label>
                    <div class="produkt-upload-area">
                        <input type="url" name="button_icon" id="ui_button_icon" value="<?php echo esc_attr($ui['button_icon']); ?>">
                        <button type="button" class="button produkt-media-button" data-target="ui_button_icon">📁</button>
                    </div>
                    <?php if (!empty($ui['button_icon'])): ?>
                    <div class="produkt-icon-preview">
                        <img src="<?php echo esc_url($ui['button_icon']); ?>" alt="Button Icon">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Preis-Label</label>
                    <input type="text" name="price_label" value="<?php echo esc_attr($ui['price_label']); ?>" placeholder="Monatlicher Mietpreis">
                </div>
                <div class="produkt-form-group">
                    <label>Preiszeitraum</label>
                    <select name="price_period">
                        <option value="month" <?php selected($ui['price_period'], 'month'); ?>>pro Monat</option>
                        <option value="one-time" <?php selected($ui['price_period'], 'one-time'); ?>>einmalig</option>
                    </select>
                </div>
                <div class="produkt-form-group">
                    <label><input type="checkbox" name="vat_included" value="1" <?php checked($ui['vat_included'], 1); ?>> Mit MwSt.</label>
                </div>
            </div>
            <div class="produkt-form-group">
                <label>Bezahlmethoden</label>
                <div class="produkt-payment-checkboxes">
                    <?php $payment_methods = [
                        'american-express' => 'American Express',
                        'apple-pay' => 'Apple Pay',
                        'google-pay' => 'Google Pay',
                        'klarna' => 'Klarna',
                        'maestro' => 'Maestro',
                        'mastercard' => 'Mastercard',
                        'paypal' => 'Paypal',
                        'shop' => 'Shop',
                        'union-pay' => 'Union Pay',
                        'visa' => 'Visa'
                    ]; ?>
                    <?php foreach ($payment_methods as $key => $label): ?>
                        <label>
                            <input type="checkbox" name="payment_icons[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, (array)$ui['payment_icons'])); ?>>
                            <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/payment-icons/' . $key . '.svg'); ?>" alt="<?php echo esc_attr($label); ?>">
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="produkt-form-group">
                <label>Mietdauer-Tooltip</label>
                <textarea name="duration_tooltip" rows="3"><?php echo esc_textarea($ui['duration_tooltip']); ?></textarea>
            </div>
            <div class="produkt-form-group">
                <label>Zustand-Tooltip</label>
                <textarea name="condition_tooltip" rows="4"><?php echo esc_textarea($ui['condition_tooltip']); ?></textarea>
            </div>
            <div class="produkt-form-group">
                <label><input type="checkbox" name="show_tooltips" value="1" <?php checked($ui['show_tooltips'], 1); ?>> Tooltips auf Produktseite anzeigen</label>
            </div>
        </div>
        <?php submit_button('💾 Einstellungen speichern', 'primary', 'submit_buttons'); ?>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.produkt-media-button').forEach(function(button){
        button.addEventListener('click', function(e){
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            if(!targetInput) return;
            const mediaUploader = wp.media({title:'Bild auswählen',button:{text:'Bild verwenden'},multiple:false});
            mediaUploader.on('select', function(){
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                targetInput.value = attachment.url;
            });
            mediaUploader.open();
        });
    });
});
</script>
