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
    $account_card_bg = sanitize_hex_color($_POST['account_card_bg']);
    $account_card_text = sanitize_hex_color($_POST['account_card_text']);
    $cart_badge_bg = sanitize_hex_color($_POST['cart_badge_bg']);
    $cart_badge_text = sanitize_hex_color($_POST['cart_badge_text']);
    $login_bg_image = esc_url_raw($_POST['login_bg_image']);
    $login_layout = sanitize_text_field($_POST['login_layout'] ?? 'classic');
    $login_logo   = esc_url_raw($_POST['login_logo'] ?? '');
    $login_text_color = sanitize_hex_color($_POST['login_text_color']);
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
        'account_card_bg' => $account_card_bg,
        'account_card_text' => $account_card_text,
        'cart_badge_bg' => $cart_badge_bg,
        'cart_badge_text' => $cart_badge_text,
        'product_padding' => $product_padding,
        'login_bg_image' => $login_bg_image,
        'login_layout' => $login_layout,
        'login_logo'   => $login_logo,
        'login_text_color' => $login_text_color,
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
                        <input type="text" name="plugin_name" value="<?php echo esc_attr($branding['plugin_name'] ?? 'H2 Rental Pro'); ?>" required>
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
                        <label>Karten-Hintergrund (Kundenkonto)</label>
                        <div class="produkt-color-picker">
                            <?php $account_card_bg = esc_attr($branding['account_card_bg'] ?? '#e8e8e8'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $account_card_bg; ?>;"></div>
                            <input type="text" name="account_card_bg" value="<?php echo $account_card_bg; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $account_card_bg; ?>" class="produkt-color-input">
                        </div>
                        <small>Hintergrundfarbe für Karten im Kundenkonto</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Karten-Textfarbe (Kundenkonto)</label>
                        <div class="produkt-color-picker">
                            <?php $account_card_text = esc_attr($branding['account_card_text'] ?? '#000000'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $account_card_text; ?>;"></div>
                            <input type="text" name="account_card_text" value="<?php echo $account_card_text; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $account_card_text; ?>" class="produkt-color-input">
                        </div>
                        <small>Textfarbe für Karten im Kundenkonto</small>
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
                        <label>Warenkorb-Badge Hintergrund</label>
                        <div class="produkt-color-picker">
                            <?php $cart_badge_bg = esc_attr($branding['cart_badge_bg'] ?? '#000000'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $cart_badge_bg; ?>;"></div>
                            <input type="text" name="cart_badge_bg" value="<?php echo $cart_badge_bg; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $cart_badge_bg; ?>" class="produkt-color-input">
                        </div>
                        <small>Hintergrundfarbe der Warenkorb-Badge</small>
                    </div>

                    <div class="produkt-form-group">
                        <label>Warenkorb-Badge Textfarbe</label>
                        <div class="produkt-color-picker">
                            <?php $cart_badge_text = esc_attr($branding['cart_badge_text'] ?? '#ffffff'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $cart_badge_text; ?>;"></div>
                            <input type="text" name="cart_badge_text" value="<?php echo $cart_badge_text; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $cart_badge_text; ?>" class="produkt-color-input">
                        </div>
                        <small>Textfarbe der Warenkorb-Badge</small>
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

            <div class="dashboard-card">
                <h2>Login-Seite</h2>
                <p class="card-subline">Layout und Medien für den Login</p>

                <div class="layout-option-grid" data-input-name="login_layout">
                    <?php $selected_login_layout = $branding['login_layout'] ?? 'classic'; ?>
                    <div class="layout-option-card <?php echo ($selected_login_layout === 'classic') ? 'active' : ''; ?>" data-value="classic">
                        <div class="layout-option-name">Standard</div>
                        <div class="layout-option-preview">
                            <svg viewBox="0 0 160 100" xmlns="http://www.w3.org/2000/svg" role="presentation" aria-hidden="true">
                                <rect x="0" y="0" width="160" height="100" rx="12" fill="#eef2f7" />
                                <rect x="55" y="18" width="50" height="64" rx="8" fill="#ffffff" stroke="#d1d5db" stroke-width="3" />
                                <rect x="64" y="32" width="32" height="8" rx="4" fill="#d1d5db" />
                                <rect x="64" y="48" width="32" height="8" rx="4" fill="#d1d5db" />
                                <rect x="64" y="64" width="32" height="8" rx="4" fill="#9ca3af" />
                            </svg>
                        </div>
                        <input type="hidden" name="login_layout" value="<?php echo esc_attr($selected_login_layout); ?>">
                    </div>
                    <div class="layout-option-card <?php echo ($selected_login_layout === 'split') ? 'active' : ''; ?>" data-value="split">
                        <div class="layout-option-name">Neben dem Bild</div>
                        <div class="layout-option-preview">
                            <svg viewBox="0 0 160 100" xmlns="http://www.w3.org/2000/svg" role="presentation" aria-hidden="true">
                                <rect x="0" y="0" width="160" height="100" rx="12" fill="#e3f0e7" />
                                <rect x="10" y="12" width="70" height="76" rx="10" fill="#d8e8de" />
                                <rect x="18" y="24" width="30" height="6" rx="3" fill="#9ca3af" />
                                <rect x="18" y="36" width="40" height="6" rx="3" fill="#9ca3af" />
                                <rect x="18" y="48" width="40" height="18" rx="6" fill="#ffffff" stroke="#cbd5e1" />
                                <rect x="90" y="12" width="60" height="76" rx="10" fill="#c4c4c4" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Textfarbe Kundenlogin</label>
                        <div class="produkt-color-picker">
                            <?php $login_text_color = esc_attr($branding['login_text_color'] ?? '#1f1f1f'); ?>
                            <div class="produkt-color-preview-circle" style="background-color: <?php echo $login_text_color; ?>;"></div>
                            <input type="text" name="login_text_color" value="<?php echo $login_text_color; ?>" class="produkt-color-value">
                            <input type="color" value="<?php echo $login_text_color; ?>" class="produkt-color-input">
                        </div>
                        <small>Farbe für Texte und Links im Kundenlogin</small>
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
                                <svg id="Ebene_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.3 82.6"><path d="M74.5.6H7.8C3.8.6.6,3.9.5,7.9v66.7c0,4,3.3,7.3,7.3,7.3h66.7c4,0,7.3-3.3,7.3-7.3V7.9c0-4-3.3-7.3-7.3-7.3ZM7.8,6.8h66.7c.3,0,.5.1.7.3.2.2.3.5.3.7v43.5l-13.2-10.6c-2.6-2-6.3-2-8.9,0l-11.9,8.8-11.8-11.8c-2.9-2.8-7.4-2.8-10.3,0l-12.5,12.5V7.9c0-.6.4-1,1-1h0ZM74.5,75.6H7.8c-.6,0-1-.5-1-1v-15.4l17-17c.2-.2.5-.3.8-.3s.6.1.8.3l17.9,17.9c1.2,1.2,3.2,1.2,4.4,0s1.2-3.2,0-4.4l-1.6-1.6,11.2-8.3c.4-.3.9-.3,1.3,0l17.1,13.7v15.1c0,.6-.5,1-1,1h0ZM45.3,36c4.6,0,8.8-2.8,10.6-7.1,1.8-4.3.8-9.2-2.5-12.5-3.3-3.3-8.2-4.3-12.5-2.5-4.3,1.8-7.1,6-7.1,10.6s5.1,11.5,11.5,11.5h0ZM45.3,19.3c2.1,0,4,1.3,4.8,3.2.8,1.9.4,4.2-1.1,5.7-1.5,1.5-3.7,1.9-5.7,1.1-1.9-.8-3.2-2.7-3.2-4.8s2.3-5.2,5.2-5.2Z"/></svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="login_bg_image" aria-label="Bild entfernen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="login_bg_image" id="login_bg_image" value="<?php echo esc_attr($branding['login_bg_image'] ?? ''); ?>">
                        <small>Hintergrundbild für die Login-Seite</small>
                    </div>

                    <div class="produkt-form-group full-width">
                        <label>Firmenlogo (Login)</label>
                        <div class="image-field-row">
                            <div id="login_logo_preview" class="image-preview">
                                <?php if (!empty($branding['login_logo'])): ?>
                                    <img src="<?php echo esc_url($branding['login_logo']); ?>" alt="">
                                <?php else: ?>
                                    <span>Noch kein Bild vorhanden</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="login_logo" aria-label="Bild auswählen">
                                <svg id="Ebene_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.3 82.6"><path d="M74.5.6H7.8C3.8.6.6,3.9.5,7.9v66.7c0,4,3.3,7.3,7.3,7.3h66.7c4,0,7.3-3.3,7.3-7.3V7.9c0-4-3.3-7.3-7.3-7.3ZM7.8,6.8h66.7c.3,0,.5.1.7.3.2.2.3.5.3.7v43.5l-13.2-10.6c-2.6-2-6.3-2-8.9,0l-11.9,8.8-11.8-11.8c-2.9-2.8-7.4-2.8-10.3,0l-12.5,12.5V7.9c0-.6.4-1,1-1h0ZM74.5,75.6H7.8c-.6,0-1-.5-1-1v-15.4l17-17c.2-.2.5-.3.8-.3s.6.1.8.3l17.9,17.9c1.2,1.2,3.2,1.2,4.4,0s1.2-3.2,0-4.4l-1.6-1.6,11.2-8.3c.4-.3.9-.3,1.3,0l17.1,13.7v15.1c0,.6-.5,1-1,1h0ZM45.3,36c4.6,0,8.8-2.8,10.6-7.1,1.8-4.3.8-9.2-2.5-12.5-3.3-3.3-8.2-4.3-12.5-2.5-4.3,1.8-7.1,6-7.1,10.6s5.1,11.5,11.5,11.5h0ZM45.3,19.3c2.1,0,4,1.3,4.8,3.2.8,1.9.4,4.2-1.1,5.7-1.5,1.5-3.7,1.9-5.7,1.1-1.9-.8-3.2-2.7-3.2-4.8s2.3-5.2,5.2-5.2Z"/></svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="login_logo" aria-label="Bild entfernen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="login_logo" id="login_logo" value="<?php echo esc_attr($branding['login_logo'] ?? ''); ?>">
                        <small>Logo für die linke Spalte des neuen Login-Layouts</small>
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
