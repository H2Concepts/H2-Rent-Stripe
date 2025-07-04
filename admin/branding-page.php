<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Handle form submissions
if (isset($_POST['submit_branding'])) {
    \FederwiegenVerleih\Admin::verify_admin_action();
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

// Get current branding settings
$branding = array();
$results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}federwiegen_branding");
foreach ($results as $result) {
    $branding[$result->setting_key] = $result->setting_value;
}
?>

<div class="wrap">
    <!-- Standard Admin Header (nicht gebrandet) -->
    <div class="federwiegen-admin-header">
        <div class="federwiegen-admin-logo">
            🎨
        </div>
        <div class="federwiegen-admin-title">
            <h1>Plugin Branding</h1>
            <p>Passen Sie das Erscheinungsbild und die Informationen des Plugins an</p>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="federwiegen-admin-nav">
        <h3>🧭 Schnellnavigation</h3>
        <div class="federwiegen-nav-grid">
            <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>" class="federwiegen-nav-item">
                <span class="dashicons dashicons-dashboard"></span>
                Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-categories'); ?>" class="federwiegen-nav-item">
                <span class="dashicons dashicons-category"></span>
                Kategorien
            </a>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-variants'); ?>" class="federwiegen-nav-item">
                <span class="dashicons dashicons-images-alt2"></span>
                Ausführungen
            </a>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-analytics'); ?>" class="federwiegen-nav-item">
                <span class="dashicons dashicons-chart-bar"></span>
                Analytics
            </a>
        </div>
    </div>
    
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <h3>🎨 White-Label Features</h3>
        <p>Personalisieren Sie das Plugin mit Ihrem eigenen Branding. Diese Einstellungen ändern das Erscheinungsbild im Admin-Bereich und können für White-Label-Lösungen verwendet werden.</p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
            <div>
                <h4>🌟 Anpassbare Elemente:</h4>
                <ul>
                    <li><strong>Plugin-Name:</strong> Eigener Name im Admin-Menü</li>
                    <li><strong>Farben:</strong> Corporate Design Farben</li>
                    <li><strong>Footer-Text:</strong> Eigene Copyright-Hinweise</li>
                    <li><strong>Firmeninformationen:</strong> Kontaktdaten und Website</li>
                </ul>
            </div>
            <div>
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
    
    <form method="post" action="">
        <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
        <h2>🏢 Plugin-Informationen</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Plugin-Name</th>
                <td>
                    <input type="text" name="plugin_name" value="<?php echo esc_attr($branding['plugin_name'] ?? 'H2 Concepts Rent Plugin'); ?>" class="regular-text" required>
                    <p class="description">Name des Plugins im Admin-Menü</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Plugin-Beschreibung</th>
                <td>
                    <textarea name="plugin_description" rows="3" class="large-text"><?php echo esc_textarea($branding['plugin_description'] ?? 'Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration'); ?></textarea>
                    <p class="description">Beschreibung des Plugins</p>
                </td>
            </tr>
        </table>
        
        <h2>🏢 Firmen-Informationen</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Firmenname</th>
                <td>
                    <input type="text" name="company_name" value="<?php echo esc_attr($branding['company_name'] ?? 'H2 Concepts'); ?>" class="regular-text" required>
                    <p class="description">Name Ihres Unternehmens</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Firmen-Website</th>
                <td>
                    <input type="url" name="company_url" value="<?php echo esc_attr($branding['company_url'] ?? 'https://h2concepts.de'); ?>" class="regular-text" required>
                    <p class="description">URL Ihrer Firmen-Website</p>
                </td>
            </tr>
        </table>
        
        <h2>🎨 Design-Anpassungen</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Primärfarbe</th>
                <td>
                    <input type="color" name="admin_color_primary" value="<?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>" class="color-picker">
                    <p class="description">Hauptfarbe für Buttons und Akzente</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Sekundärfarbe</th>
                <td>
                    <input type="color" name="admin_color_secondary" value="<?php echo esc_attr($branding['admin_color_secondary'] ?? '#4a674a'); ?>" class="color-picker">
                    <p class="description">Sekundärfarbe für Hover-Effekte und Verläufe</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Footer-Text</th>
                <td>
                    <input type="text" name="footer_text" value="<?php echo esc_attr($branding['footer_text'] ?? 'Powered by H2 Concepts'); ?>" class="regular-text">
                    <p class="description">Text im Admin-Footer (z.B. "Powered by Ihr Firmenname")</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('💾 Branding-Einstellungen speichern', 'primary', 'submit_branding', false, array('style' => 'font-size: 16px; padding: 10px 20px;')); ?>
    </form>
    
    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px;">
        <h3>🎨 Design-Vorschau</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h4>🎯 Aktuelle Einstellungen:</h4>
                <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <div style="width: 40px; height: 40px; background: <?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">
                            🏷️
                        </div>
                        <div>
                            <strong><?php echo esc_html($branding['plugin_name'] ?? 'H2 Concepts Rent Plugin'); ?></strong><br>
                            <small style="color: #666;"><?php echo esc_html($branding['company_name'] ?? 'H2 Concepts'); ?></small>
                        </div>
                    </div>
                    <button style="background: <?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;" onmouseover="this.style.background='<?php echo esc_attr($branding['admin_color_secondary'] ?? '#4a674a'); ?>'" onmouseout="this.style.background='<?php echo esc_attr($branding['admin_color_primary'] ?? '#5f7f5f'); ?>'">
                        Beispiel Button
                    </button>
                </div>
            </div>
            
            <div>
                <h4>📋 Verwendung:</h4>
                <ul>
                    <li><strong>Admin-Header:</strong> Firmenname wird in der Plugin-Oberfläche angezeigt</li>
                    <li><strong>Buttons:</strong> Verwenden die definierten Farben</li>
                    <li><strong>Navigation:</strong> Aktive Tabs in Primärfarbe</li>
                    <li><strong>Footer:</strong> Eigener Copyright-Text</li>
                </ul>
                
                <div style="margin-top: 15px; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
                    <strong>💡 Tipp:</strong> Verwenden Sie Farben aus Ihrem Corporate Design für ein konsistentes Erscheinungsbild.
                </div>
            </div>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px;">
            <h4>🔧 White-Label Möglichkeiten:</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h5>✅ Was angepasst wird:</h5>
                    <ul>
                        <li>Plugin-Name im WordPress Admin-Menü</li>
                        <li>Farben für Buttons und Navigation</li>
                        <li>Footer-Text mit Ihrem Branding</li>
                        <li>Firmeninformationen in Links</li>
                    </ul>
                </div>
                <div>
                    <h5>ℹ️ Was unverändert bleibt:</h5>
                    <ul>
                        <li>Plugin-Funktionalität</li>
                        <li>Frontend-Darstellung (Shortcodes)</li>
                        <li>WordPress Plugin-Informationen</li>
                        <li>Update-Mechanismus</li>
                        <li>Lizenz und Copyright</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-table th {
    width: 200px;
    padding: 20px 10px 20px 0;
    font-weight: 600;
}

.form-table td {
    padding: 15px 10px;
}

.form-table input[type="text"],
.form-table input[type="url"],
.form-table textarea {
    width: 100%;
    max-width: 500px;
}

.form-table textarea {
    resize: vertical;
}

.color-picker {
    width: 60px;
    height: 40px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}
</style>
