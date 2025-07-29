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
}
?>

<div class="produkt-branding-tab">
    <div class="produkt-branding-info">
        <h3>🎨 White-Label Features</h3>
        <p>Personalisieren Sie das Plugin mit Ihrem eigenen Branding. Diese Einstellungen ändern das Erscheinungsbild im Admin-Bereich und können für White-Label-Lösungen verwendet werden.</p>
        
        <div class="produkt-branding-features">
            <div class="produkt-feature-column">
                <h4>🌟 Anpassbare Elemente:</h4>
                <ul>
                    <li><strong>Plugin-Name:</strong> Eigener Name im Admin-Menü</li>
                    <li><strong>Farben:</strong> Corporate Design Farben</li>
                    <li><strong>Footer-Text:</strong> Eigene Copyright-Hinweise</li>
                    <li><strong>Firmeninformationen:</strong> Kontaktdaten und Website</li>
                </ul>
            </div>
            <div class="produkt-feature-column">
                <h4>💼 Professionelle Vorteile:</h4>
                <ul>
                    <li><strong>Markenidentität:</strong> Konsistentes Erscheinungsbild</li>
                    <li><strong>Kundenvertrauen:</strong> Professioneller Auftritt</li>
                    <li><strong>White-Label:</strong> Plugin unter eigenem Namen</li>
                    <li><strong>Corporate Design:</strong> Firmenfarben verwenden</li>
                </ul>
            </div>
        </div>
    </div>
    
    <form method="post" action="" class="produkt-branding-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <div class="produkt-form-sections">
            <!-- Plugin Information -->
            <div class="produkt-form-section">
                <h4>🏢 Plugin-Informationen</h4>
                <div class="produkt-form-grid">
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
            <div class="produkt-form-section">
                <h4>🏢 Firmen-Informationen</h4>
                <div class="produkt-form-grid">
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
            <div class="produkt-form-section">
                <h4>🎨 Design-Anpassungen</h4>
                <div class="produkt-form-grid">
                    <div class="produkt-form-group">
                        <label>Primärfarbe</label>
                        <input type="color" name="admin_color_primary" value="<?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>" class="produkt-color-picker">
                        <small>Hauptfarbe für Buttons und Akzente</small>
                    </div>
                    
                    <div class="produkt-form-group">
                        <label>Sekundärfarbe</label>
                        <input type="color" name="admin_color_secondary" value="<?php echo esc_attr($branding['admin_color_secondary'] ?? '#4a674a'); ?>" class="produkt-color-picker">
                        <small>Sekundärfarbe für Hover-Effekte und Verläufe</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Textfarbe</label>
                        <input type="color" name="admin_color_text" value="<?php echo esc_attr($branding['admin_color_text'] ?? '#ffffff'); ?>" class="produkt-color-picker">
                        <small>Farbe für Text auf Buttons und Tabs</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Button-Farbe (Frontend)</label>
                        <input type="color" name="front_button_color" value="<?php echo esc_attr($branding['front_button_color'] ?? '#5f7f5f'); ?>" class="produkt-color-picker">
                        <small>Hauptfarbe der Handlungs-Buttons</small>
                    </div>
                    <div class="produkt-form-group">
                        <label>Filter-Button-Farbe</label>
                        <input type="color" name="filter_button_color" value="<?php echo esc_attr($branding['filter_button_color'] ?? '#5f7f5f'); ?>" class="produkt-color-picker">
                        <small>Farbe des mobilen Filter-Buttons</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Textfarbe (Frontend)</label>
                        <input type="color" name="front_text_color" value="<?php echo esc_attr($branding['front_text_color'] ?? '#4a674a'); ?>" class="produkt-color-picker">
                        <small>Farbe für Preis- und Hinweistexte</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Border-Farbe</label>
                        <input type="color" name="front_border_color" value="<?php echo esc_attr($branding['front_border_color'] ?? '#a4b8a4'); ?>" class="produkt-color-picker">
                        <small>Rahmenfarbe für Optionen</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Button-Textfarbe</label>
                        <input type="color" name="front_button_text_color" value="<?php echo esc_attr($branding['front_button_text_color'] ?? '#ffffff'); ?>" class="produkt-color-picker">
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
                        <input type="url" name="login_bg_image" id="login_bg_image" value="<?php echo esc_attr($branding['login_bg_image'] ?? ''); ?>">
                        <button type="button" class="button produkt-media-button" data-target="login_bg_image">📁 Aus Mediathek wählen</button>
                        <?php if (!empty($branding['login_bg_image'])) : ?>
                            <div class="produkt-image-preview"><img src="<?php echo esc_url($branding['login_bg_image']); ?>" alt=""></div>
                        <?php endif; ?>
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
        
        <div class="produkt-form-actions">
            <?php submit_button('💾 Branding-Einstellungen speichern', 'primary', 'submit_branding', false); ?>
        </div>
    </form>
    
    <!-- Preview Section -->
    <div class="produkt-preview-section">
        <h4>🎨 Design-Vorschau</h4>
        <div class="produkt-preview-grid">
            <div class="produkt-preview-card">
                <h5>🎯 Aktuelle Einstellungen:</h5>
                <div class="produkt-preview-demo">
                    <div class="produkt-demo-header">
                        <div class="produkt-demo-logo" style="background: <?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>;">
                            🏷️
                        </div>
                        <div class="produkt-demo-content">
                            <strong><?php echo esc_html($branding['plugin_name'] ?? 'H2 Concepts Rental Pro'); ?></strong><br>
                            <small><?php echo esc_html($branding['company_name'] ?? 'H2 Concepts'); ?></small>
                        </div>
                    </div>
                    <button class="produkt-demo-button" style="background: <?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>; color: <?php echo esc_attr($branding['admin_color_text'] ?? '#ffffff'); ?>; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;" onmouseover="this.style.background='<?php echo esc_attr($branding['admin_color_secondary'] ?? '#4a674a'); ?>'" onmouseout="this.style.background='<?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>'">
                        Beispiel Button
                    </button>
                </div>
            </div>
            
            <div class="produkt-preview-card">
                <h5>📋 Verwendung:</h5>
                <ul>
                    <li><strong>Admin-Header:</strong> Firmenname wird in der Plugin-Oberfläche angezeigt</li>
                    <li><strong>Buttons:</strong> Verwenden die definierten Farben</li>
                    <li><strong>Navigation:</strong> Aktive Tabs in Primärfarbe</li>
                    <li><strong>Footer:</strong> Eigener Copyright-Text</li>
                </ul>
                
                <div class="produkt-tip">
                    <strong>💡 Tipp:</strong> Verwenden Sie Farben aus Ihrem Corporate Design für ein konsistentes Erscheinungsbild.
                </div>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.produkt-media-button').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.getElementById(this.getAttribute('data-target'));
            if (!target) return;
            const frame = wp.media({ title: 'Bild auswählen', button: { text: 'Bild verwenden' }, multiple: false });
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                target.value = attachment.url;
            });
            frame.open();
        });
    });
});
</script>
