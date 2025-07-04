<?php
// Branding Tab Content

// Handle form submissions
if (isset($_POST['submit_branding'])) {
    $plugin_name = sanitize_text_field($_POST['plugin_name']);
    $plugin_description = sanitize_textarea_field($_POST['plugin_description']);
    $company_name = sanitize_text_field($_POST['company_name']);
    $company_url = esc_url_raw($_POST['company_url']);
    $admin_color_primary = sanitize_hex_color($_POST['admin_color_primary']);
    $admin_color_secondary = sanitize_hex_color($_POST['admin_color_secondary']);
    $footer_text = sanitize_text_field($_POST['footer_text']);

    $table_name = $wpdb->prefix . 'federwiegen_branding';

    $settings = array(
        'plugin_name' => $plugin_name,
        'plugin_description' => $plugin_description,
        'company_name' => $company_name,
        'company_url' => $company_url,
        'admin_color_primary' => $admin_color_primary,
        'admin_color_secondary' => $admin_color_secondary,
        'footer_text' => $footer_text
    );

    $success_count = 0;
    $total_count = count($settings);
    
    foreach ($settings as $key => $value) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $table_name WHERE setting_key = %s",
            $key
        ));
        
        if ($existing !== null) {
            // Update existing
            $result = $wpdb->update(
                $table_name,
                array('setting_value' => $value),
                array('setting_key' => $key),
                array('%s'),
                array('%s')
            );
            if ($result !== false) {
                $success_count++;
            }
        } else {
            // Insert new
            $result = $wpdb->insert(
                $table_name,
                array(
                    'setting_key' => $key,
                    'setting_value' => $value
                ),
                array('%s', '%s')
            );
            if ($result !== false) {
                $success_count++;
            }
        }
    }

    if ($success_count === $total_count) {
        echo '<div class="notice notice-success"><p>✅ Branding-Einstellungen erfolgreich gespeichert!</p></div>';
    } else {
        echo '<div class="notice notice-warning"><p>⚠️ ' . ($total_count - $success_count) . ' von ' . $total_count . ' Einstellungen konnten nicht gespeichert werden.</p></div>';
    }
}
?>

<div class="federwiegen-branding-tab">
    <div class="federwiegen-branding-info">
        <h3>🎨 White-Label Features</h3>
        <p>Personalisieren Sie das Plugin mit Ihrem eigenen Branding. Diese Einstellungen ändern das Erscheinungsbild im Admin-Bereich und können für White-Label-Lösungen verwendet werden.</p>
        
        <div class="federwiegen-branding-features">
            <div class="federwiegen-feature-column">
                <h4>🌟 Anpassbare Elemente:</h4>
                <ul>
                    <li><strong>Plugin-Name:</strong> Eigener Name im Admin-Menü</li>
                    <li><strong>Farben:</strong> Corporate Design Farben</li>
                    <li><strong>Footer-Text:</strong> Eigene Copyright-Hinweise</li>
                    <li><strong>Firmeninformationen:</strong> Kontaktdaten und Website</li>
                </ul>
            </div>
            <div class="federwiegen-feature-column">
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
    
    <form method="post" action="" class="federwiegen-branding-form">
        <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
        <div class="federwiegen-form-sections">
            <!-- Plugin Information -->
            <div class="federwiegen-form-section">
                <h4>🏢 Plugin-Informationen</h4>
                <div class="federwiegen-form-grid">
                    <div class="federwiegen-form-group">
                        <label>Plugin-Name *</label>
                        <input type="text" name="plugin_name" value="<?php echo esc_attr($branding['plugin_name'] ?? 'H2 Concepts Rent Plugin'); ?>" required>
                        <small>Name des Plugins im Admin-Menü</small>
                    </div>
                    
                    <div class="federwiegen-form-group full-width">
                        <label>Plugin-Beschreibung</label>
                        <textarea name="plugin_description" rows="3"><?php echo esc_textarea($branding['plugin_description'] ?? 'Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration'); ?></textarea>
                        <small>Beschreibung des Plugins</small>
                    </div>
                </div>
            </div>
            
            <!-- Company Information -->
            <div class="federwiegen-form-section">
                <h4>🏢 Firmen-Informationen</h4>
                <div class="federwiegen-form-grid">
                    <div class="federwiegen-form-group">
                        <label>Firmenname *</label>
                        <input type="text" name="company_name" value="<?php echo esc_attr($branding['company_name'] ?? 'H2 Concepts'); ?>" required>
                        <small>Name Ihres Unternehmens</small>
                    </div>
                    
                    <div class="federwiegen-form-group">
                        <label>Firmen-Website *</label>
                        <input type="url" name="company_url" value="<?php echo esc_attr($branding['company_url'] ?? 'https://h2concepts.de'); ?>" required>
                        <small>URL Ihrer Firmen-Website</small>
                    </div>
                </div>
            </div>
            
            <!-- Design Settings -->
            <div class="federwiegen-form-section">
                <h4>🎨 Design-Anpassungen</h4>
                <div class="federwiegen-form-grid">
                    <div class="federwiegen-form-group">
                        <label>Primärfarbe</label>
                        <input type="color" name="admin_color_primary" value="<?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>" class="federwiegen-color-picker">
                        <small>Hauptfarbe für Buttons und Akzente</small>
                    </div>
                    
                    <div class="federwiegen-form-group">
                        <label>Sekundärfarbe</label>
                        <input type="color" name="admin_color_secondary" value="<?php echo esc_attr($branding['admin_color_secondary'] ?? '#4a674a'); ?>" class="federwiegen-color-picker">
                        <small>Sekundärfarbe für Hover-Effekte und Verläufe</small>
                    </div>
                    
                    <div class="federwiegen-form-group full-width">
                        <label>Footer-Text</label>
                        <input type="text" name="footer_text" value="<?php echo esc_attr($branding['footer_text'] ?? 'Powered by H2 Concepts'); ?>">
                        <small>Text im Admin-Footer (z.B. "Powered by Ihr Firmenname")</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="federwiegen-form-actions">
            <?php submit_button('💾 Branding-Einstellungen speichern', 'primary', 'submit_branding', false); ?>
        </div>
    </form>
    
    <!-- Preview Section -->
    <div class="federwiegen-preview-section">
        <h4>🎨 Design-Vorschau</h4>
        <div class="federwiegen-preview-grid">
            <div class="federwiegen-preview-card">
                <h5>🎯 Aktuelle Einstellungen:</h5>
                <div class="federwiegen-preview-demo">
                    <div class="federwiegen-demo-header">
                        <div class="federwiegen-demo-logo" style="background: <?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>;">
                            🏷️
                        </div>
                        <div class="federwiegen-demo-content">
                            <strong><?php echo esc_html($branding['plugin_name'] ?? 'H2 Concepts Rent Plugin'); ?></strong><br>
                            <small><?php echo esc_html($branding['company_name'] ?? 'H2 Concepts'); ?></small>
                        </div>
                    </div>
                    <button class="federwiegen-demo-button" style="background: <?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;" onmouseover="this.style.background='<?php echo esc_attr($branding['admin_color_secondary'] ?? '#4a674a'); ?>'" onmouseout="this.style.background='<?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>'">
                        Beispiel Button
                    </button>
                </div>
            </div>
            
            <div class="federwiegen-preview-card">
                <h5>📋 Verwendung:</h5>
                <ul>
                    <li><strong>Admin-Header:</strong> Firmenname wird in der Plugin-Oberfläche angezeigt</li>
                    <li><strong>Buttons:</strong> Verwenden die definierten Farben</li>
                    <li><strong>Navigation:</strong> Aktive Tabs in Primärfarbe</li>
                    <li><strong>Footer:</strong> Eigener Copyright-Text</li>
                </ul>
                
                <div class="federwiegen-tip">
                    <strong>💡 Tipp:</strong> Verwenden Sie Farben aus Ihrem Corporate Design für ein konsistentes Erscheinungsbild.
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.federwiegen-branding-tab {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.federwiegen-branding-info {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
}

.federwiegen-branding-info h3 {
    margin: 0 0 15px 0;
    color: #856404;
}

.federwiegen-branding-features {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 15px;
}

.federwiegen-feature-column h4 {
    margin: 0 0 10px 0;
    color: #856404;
}

.federwiegen-feature-column ul {
    margin: 0;
    padding-left: 20px;
}

.federwiegen-branding-form {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 25px;
}

.federwiegen-form-sections {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.federwiegen-form-section {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 20px;
}

.federwiegen-form-section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.federwiegen-form-section h4 {
    margin: 0 0 15px 0;
    color: #5f7f5f;
}

.federwiegen-color-picker {
    width: 60px;
    height: 40px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}

.federwiegen-preview-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
}

.federwiegen-preview-section h4 {
    margin: 0 0 20px 0;
    color: #3c434a;
}

.federwiegen-preview-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.federwiegen-preview-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
}

.federwiegen-preview-card h5 {
    margin: 0 0 15px 0;
    color: #3c434a;
}

.federwiegen-preview-demo {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.federwiegen-demo-header {
    display: flex;
    align-items: center;
    gap: 15px;
}

.federwiegen-demo-logo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.federwiegen-tip {
    margin-top: 15px;
    padding: 10px;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .federwiegen-branding-features,
    .federwiegen-preview-grid {
        grid-template-columns: 1fr;
    }
}
</style>
