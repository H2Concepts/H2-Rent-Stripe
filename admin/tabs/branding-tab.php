<?php
// Branding Tab Content

// Handle form submissions
if (isset($_POST['submit_branding'])) {
    global $wpdb;

    $plugin_name = sanitize_text_field($_POST['plugin_name']);
    $plugin_description = sanitize_textarea_field($_POST['plugin_description']);
    $company_name = sanitize_text_field($_POST['company_name']);
    $company_url = esc_url_raw($_POST['company_url']);
    $admin_color_primary = sanitize_hex_color($_POST['admin_color_primary']);
    $admin_color_secondary = sanitize_hex_color($_POST['admin_color_secondary']);
    $admin_color_text = sanitize_hex_color($_POST['admin_color_text']);
    $front_button_color = sanitize_hex_color($_POST['front_button_color']);
    $front_text_color   = sanitize_hex_color($_POST['front_text_color']);
    $front_border_color = sanitize_hex_color($_POST['front_border_color']);
    $front_button_text_color = sanitize_hex_color($_POST['front_button_text_color']);
    $filter_button_color = sanitize_hex_color($_POST['filter_button_color']);
    $login_bg_image = esc_url_raw($_POST['login_bg_image']);
    $footer_text = sanitize_text_field($_POST['footer_text']);
    $custom_css = sanitize_textarea_field($_POST['custom_css']);
    $product_padding = isset($_POST['product_padding']) ? 1 : 0;

    $table_name = $wpdb->prefix . 'produkt_branding';

    $settings = array(
        'plugin_name' => $plugin_name,
        'plugin_description' => $plugin_description,
        'company_name' => $company_name,
        'company_url' => $company_url,
        'admin_color_primary' => $admin_color_primary,
        'admin_color_secondary' => $admin_color_secondary,
        'admin_color_text' => $admin_color_text,
        'front_button_color' => $front_button_color,
        'front_text_color' => $front_text_color,
        'front_border_color' => $front_border_color,
        'front_button_text_color' => $front_button_text_color,
        'filter_button_color' => $filter_button_color,
        'product_padding' => $product_padding,
        'login_bg_image' => $login_bg_image,
        'footer_text' => $footer_text,
        'custom_css' => $custom_css
    );

    $success_count = 0;
    $total_count = count($settings);
    
    foreach ($settings as $key => $value) {
        $result = $wpdb->replace(
            $table_name,
            array(
                'setting_key'   => $key,
                'setting_value' => $value
            ),
            array('%s', '%s')
        );

        if ($result !== false) {
            $success_count++;
        }
    }

    if ($success_count === $total_count) {
        echo '<div class="notice notice-success"><p>✅ Branding-Einstellungen erfolgreich gespeichert!</p></div>';
    } else {
        echo '<div class="notice notice-warning"><p>⚠️ ' . ($total_count - $success_count) . ' von ' . $total_count . ' Einstellungen konnten nicht gespeichert werden.</p></div>';
    }

    // Reload settings so the form shows the updated values without refresh
    $branding = [];
    $results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$table_name}");
    foreach ($results as $r) {
        $branding[$r->setting_key] = $r->setting_value;
    }
}
?>

<div class="produkt-branding-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <button type="submit" name="submit_branding" class="icon-btn branding-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
            </svg>
        </button>
        <div class="produkt-form-sections">
            <!-- Plugin Information -->
            <div class="dashboard-card">
                <h2>Plugin-Informationen</h2>
                <p class="card-subline">Basisdaten des Plugins</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Plugin-Name *</label>
                        <input type="text" name="plugin_name" value="<?php echo esc_attr($branding['plugin_name'] ?? 'H2 Concepts Rental Pro'); ?>" required>
                        <small>Name des Plugins im Admin-Menü</small>
                    </div>
                    
                    <div class="produkt-form-group full-width">
                        <label>Plugin-Beschreibung</label>
                        <textarea name="plugin_description" rows="3"><?php echo esc_textarea($branding['plugin_description'] ?? 'Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration'); ?></textarea>
                        <small>Beschreibung des Plugins</small>
                    </div>
                </div>
            </div>
            
            <!-- Company Information -->
            <div class="dashboard-card">
                <h2>Firmen-Informationen</h2>
                <p class="card-subline">Angaben zum Unternehmen</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Firmenname *</label>
                        <input type="text" name="company_name" value="<?php echo esc_attr($branding['company_name'] ?? 'H2 Concepts'); ?>" required>
                        <small>Name Ihres Unternehmens</small>
                    </div>
                    
                    <div class="produkt-form-group">
                        <label>Firmen-Website *</label>
                        <input type="url" name="company_url" value="<?php echo esc_attr($branding['company_url'] ?? 'https://h2concepts.de'); ?>" required>
                        <small>URL Ihrer Firmen-Website</small>
                    </div>
                </div>
            </div>
            
            <!-- Design Settings -->
            <div class="dashboard-card">
                <h2>Design-Anpassungen</h2>
                <p class="card-subline">Farben und Layout</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Primärfarbe</label>
                        <div class="produkt-color-picker">
                            <?php $admin_color_primary = esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $admin_color_primary; ?>;"></div>
                            <input type="text" name="admin_color_primary" value="<?php echo $admin_color_primary; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $admin_color_primary; ?>" class="produkt-color-input">
                        </div>
                        <small>Hauptfarbe für Buttons und Akzente</small>
                    </div>
                    
                    <div class="produkt-form-group">
                        <label>Sekundärfarbe</label>
                        <div class="produkt-color-picker">
                            <?php $admin_color_secondary = esc_attr($branding['admin_color_secondary'] ?? '#4a674a'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $admin_color_secondary; ?>;"></div>
                            <input type="text" name="admin_color_secondary" value="<?php echo $admin_color_secondary; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $admin_color_secondary; ?>" class="produkt-color-input">
                        </div>
                        <small>Sekundärfarbe für Hover-Effekte und Verläufe</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Textfarbe</label>
                        <div class="produkt-color-picker">
                            <?php $admin_color_text = esc_attr($branding['admin_color_text'] ?? '#ffffff'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $admin_color_text; ?>;"></div>
                            <input type="text" name="admin_color_text" value="<?php echo $admin_color_text; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $admin_color_text; ?>" class="produkt-color-input">
                        </div>
                        <small>Farbe für Text auf Buttons und Tabs</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Button-Farbe (Frontend)</label>
                        <div class="produkt-color-picker">
                            <?php $front_button_color = esc_attr($branding['front_button_color'] ?? '#5f7f5f'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $front_button_color; ?>;"></div>
                            <input type="text" name="front_button_color" value="<?php echo $front_button_color; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $front_button_color; ?>" class="produkt-color-input">
                        </div>
                        <small>Hauptfarbe der Handlungs-Buttons</small>
                    </div>
                    <div class="produkt-form-group">
                        <label>Filter-Button-Farbe</label>
                        <div class="produkt-color-picker">
                            <?php $filter_button_color = esc_attr($branding['filter_button_color'] ?? '#5f7f5f'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $filter_button_color; ?>;"></div>
                            <input type="text" name="filter_button_color" value="<?php echo $filter_button_color; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $filter_button_color; ?>" class="produkt-color-input">
                        </div>
                        <small>Farbe des mobilen Filter-Buttons</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Textfarbe (Frontend)</label>
                        <div class="produkt-color-picker">
                            <?php $front_text_color = esc_attr($branding['front_text_color'] ?? '#4a674a'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $front_text_color; ?>;"></div>
                            <input type="text" name="front_text_color" value="<?php echo $front_text_color; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $front_text_color; ?>" class="produkt-color-input">
                        </div>
                        <small>Farbe für Preis- und Hinweistexte</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Border-Farbe</label>
                        <div class="produkt-color-picker">
                            <?php $front_border_color = esc_attr($branding['front_border_color'] ?? '#a4b8a4'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $front_border_color; ?>;"></div>
                            <input type="text" name="front_border_color" value="<?php echo $front_border_color; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $front_border_color; ?>" class="produkt-color-input">
                        </div>
                        <small>Rahmenfarbe für Optionen</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Button-Textfarbe</label>
                        <div class="produkt-color-picker">
                            <?php $front_button_text_color = esc_attr($branding['front_button_text_color'] ?? '#ffffff'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $front_button_text_color; ?>;"></div>
                            <input type="text" name="front_button_text_color" value="<?php echo $front_button_text_color; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $front_button_text_color; ?>" class="produkt-color-input">
                        </div>
                        <small>Textfarbe der Buttons im Frontend</small>
                    </div>

                    <div class="produkt-form-group">
                        <label class="produkt-toggle-label">
                            <input type="checkbox" name="product_padding" value="1" <?php echo !isset($branding['product_padding']) || $branding['product_padding'] == '1' ? 'checked' : ''; ?>>
                            <span class="produkt-toggle-slider"></span>
                            <span>Padding um Produktboxen</span>
                        </label>
                        <small>Aktiviert 1.5&nbsp;rem Innenabstand und geringere Spaltenabst&auml;nde</small>
                    </div>

                    <div class="produkt-form-group full-width">
                        <label>Login Hintergrundbild</label>
                        <div class="image-field-row">
                            <div id="login_bg_image_preview" class="image-preview">
                                <?php if (!empty($branding['login_bg_image'])): ?>
                                    <img src="<?php echo esc_url($branding['login_bg_image']); ?>" alt="">
                                <?php else: ?>
                                    <span>Noch kein Bild vorhanden</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="login_bg_image" aria-label="Bild auswählen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 26.2"><path d="M16,7c-3.9,0-7,3.1-7,7s3.1,7,7,7,7-3.1,7-7-3.1-7-7-7ZM16,19c-2.8,0-5-2.2-5-5s2.2-5,5-5,5,2.2,5,5-2.2,5-5,5ZM29,4h-4c-1,0-3-4-4-4h-10c-1.1,0-3.1,4-4,4H3c-1.7,0-3,1.3-3,3v16c0,1.7,1.3,3,3,3h26c1.7,0,3-1.3,3-3V7c0-1.7-1.3-3-3-3ZM30,22c0,1.1-.9,2-2,2H4c-1.1,0-2-.9-2-2v-14c0-1.1.9-2,2-2h4c.9,0,2.9-4,4-4h8c1,0,3,4,3.9,4h4.1c1.1,0,2,.9,2,2v14Z"/></svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="login_bg_image" aria-label="Bild entfernen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="login_bg_image" id="login_bg_image" value="<?php echo esc_attr($branding['login_bg_image'] ?? ''); ?>">
                        <small>Hintergrundbild für die Login-Seite</small>
                    </div>
                    
                    <div class="produkt-form-group full-width">
                        <label>Footer-Text</label>
                        <input type="text" name="footer_text" value="<?php echo esc_attr($branding['footer_text'] ?? 'Powered by H2 Concepts'); ?>">
                        <small>Text im Admin-Footer (z.B. "Powered by Ihr Firmenname")</small>
                    </div>

                    <div class="produkt-form-group full-width">
                        <label>Custom CSS</label>
                        <textarea name="custom_css" rows="4"><?php echo esc_textarea($branding['custom_css'] ?? ''); ?></textarea>
                        <small>Eigene CSS-Regeln für die Produktseite</small>
                    </div>
                </div>
            </div>
        </div>
        
    </form>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.produkt-media-button').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.getElementById(this.dataset.target);
            const preview = document.getElementById(this.dataset.target + '_preview');
            const frame = wp.media({ title: 'Bild auswählen', button: { text: 'Bild verwenden' }, multiple: false });
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                target.value = attachment.url;
                if (preview) {
                    preview.innerHTML = '<img src="' + attachment.url + '" alt="">';
                }
            });
            frame.open();
        });
    });
    document.querySelectorAll('.produkt-remove-image').forEach(function(btn){
        btn.addEventListener('click', function(){
            const target = document.getElementById(this.dataset.target);
            const preview = document.getElementById(this.dataset.target + '_preview');
            if(target){ target.value = ''; }
            if(preview){ preview.innerHTML = '<span>Noch kein Bild vorhanden</span>'; }
        });
    });
});
</script>

</div>
