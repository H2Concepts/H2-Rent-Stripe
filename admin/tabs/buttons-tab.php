<?php
// Buttons & Tooltips Tab Content

if (isset($_POST['submit_buttons'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $settings = [
        'button_text'       => sanitize_text_field($_POST['button_text'] ?? ''),
        'button_icon'       => esc_url_raw($_POST['button_icon'] ?? ''),
        'payment_icons'     => isset($_POST['payment_icons']) ? array_map('sanitize_text_field', (array) $_POST['payment_icons']) : [],
        'price_label'       => sanitize_text_field($_POST['price_label'] ?? ''),
        'shipping_label'    => sanitize_text_field($_POST['shipping_label'] ?? ''),
        'price_period'      => sanitize_text_field($_POST['price_period'] ?? 'month'),
        'vat_included'      => isset($_POST['vat_included']) ? 1 : 0,
        'duration_tooltip'  => sanitize_textarea_field($_POST['duration_tooltip'] ?? ''),
        'condition_tooltip' => sanitize_textarea_field($_POST['condition_tooltip'] ?? ''),
        'show_tooltips'     => isset($_POST['show_tooltips']) ? 1 : 0,
    ];
    update_option('produkt_ui_settings', $settings);
    if (isset($_POST['order_number_start'])) {
        update_option('produkt_next_order_number', sanitize_text_field($_POST['order_number_start']));
    }
    echo '<div class="notice notice-success"><p>✅ Einstellungen gespeichert!</p></div>';
}

$ui = get_option('produkt_ui_settings', [
    'button_text' => '',
    'button_icon' => '',
    'payment_icons' => [],
    'price_label' => '',
    'shipping_label' => '',
    'price_period' => 'month',
    'vat_included' => 0,
    'duration_tooltip' => '',
    'condition_tooltip' => '',
    'show_tooltips' => 1,
]);
$next_order_nr = get_option('produkt_next_order_number', '');
$last_order_nr = get_option('produkt_last_order_number', '');
?>
<div class="settings-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <button type="submit" name="submit_buttons" class="icon-btn buttons-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
            </svg>
        </button>
        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <h2>Buttons</h2>
                <p class="card-subline">Beschriftung und Preisinformationen</p>
                <div class="form-grid">
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
                    <div class="produkt-form-group">
                        <label>Preis-Label</label>
                        <input type="text" name="price_label" value="<?php echo esc_attr($ui['price_label']); ?>" placeholder="Monatlicher Mietpreis">
                    </div>
                    <div class="produkt-form-group">
                        <label>Versand-Label</label>
                        <input type="text" name="shipping_label" value="<?php echo esc_attr($ui['shipping_label']); ?>" placeholder="Einmalige Versandkosten">
                    </div>
                    <div class="produkt-form-group">
                        <label>Preiszeitraum</label>
                        <select name="price_period">
                            <option value="month" <?php selected($ui['price_period'], 'month'); ?>>pro Monat</option>
                            <option value="one-time" <?php selected($ui['price_period'], 'one-time'); ?>>einmalig</option>
                        </select>
                    </div>
                    <div class="produkt-form-group">
                        <label>Mit MwSt.</label>
                        <label class="produkt-toggle-label">
                            <input type="checkbox" name="vat_included" value="1" <?php checked($ui['vat_included'], 1); ?>>
                            <span class="produkt-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2>Bezahlmethoden</h2>
                <p class="card-subline">Icons der unterstützten Zahlungsmittel</p>
                <div class="form-grid">
                    <div class="produkt-form-group full-width">
                        <label>Icons auswählen</label>
                        <div class="produkt-payment-checkboxes">
                            <?php $payment_methods = [
                                'american-express' => 'American Express',
                                'apple-pay'        => 'Apple Pay',
                                'google-pay'       => 'Google Pay',
                                'klarna'           => 'Klarna',
                                'maestro'          => 'Maestro',
                                'mastercard'       => 'Mastercard',
                                'paypal'           => 'Paypal',
                                'shop'             => 'Shop',
                                'union-pay'        => 'Union Pay',
                                'visa'             => 'Visa'
                            ]; ?>
                            <?php foreach ($payment_methods as $key => $label): ?>
                                <label>
                                    <input type="checkbox" name="payment_icons[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, (array)$ui['payment_icons'])); ?>>
                                    <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/payment-icons/' . $key . '.svg'); ?>" alt="<?php echo esc_attr($label); ?>">
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2>Tooltips</h2>
                <p class="card-subline">Hilfetexte auf der Produktseite</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Mietdauer-Tooltip</label>
                        <textarea name="duration_tooltip" rows="3"><?php echo esc_textarea($ui['duration_tooltip']); ?></textarea>
                    </div>
                    <div class="produkt-form-group">
                        <label>Zustand-Tooltip</label>
                        <textarea name="condition_tooltip" rows="4"><?php echo esc_textarea($ui['condition_tooltip']); ?></textarea>
                    </div>
                    <div class="produkt-form-group full-width">
                        <label>Tooltips auf Produktseite anzeigen</label>
                        <label class="produkt-toggle-label">
                            <input type="checkbox" name="show_tooltips" value="1" <?php checked($ui['show_tooltips'], 1); ?>>
                            <span class="produkt-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2>Bestellnummer</h2>
                <p class="card-subline">Startwert der laufenden Nummer</p>
                <div class="form-grid">
                    <div class="produkt-form-group full-width">
                        <label>Bestellnummer Startwert</label>
                        <input type="text" name="order_number_start" value="<?php echo esc_attr($next_order_nr); ?>">
                        <?php if ($last_order_nr): ?>
                        <p class="description">Letzte vergebene Bestellnummer: <?php echo esc_html($last_order_nr); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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
