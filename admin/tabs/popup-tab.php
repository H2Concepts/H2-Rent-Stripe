<?php
// Popup Tab Content

if (isset($_POST['submit_popup'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $settings = [
        'enabled'       => isset($_POST['popup_enabled']) ? 1 : 0,
        'days'          => max(0, intval($_POST['popup_days'] ?? 7)),
        'email_enabled' => isset($_POST['popup_email_enabled']) ? 1 : 0,
        'title'         => sanitize_text_field($_POST['popup_title'] ?? ''),
        'content'       => wp_kses_post($_POST['popup_content'] ?? ''),
        'options'       => sanitize_textarea_field($_POST['popup_options'] ?? '')
    ];
    update_option('produkt_popup_settings', $settings);

    echo '<div class="notice notice-success"><p>✅ Popup-Einstellungen gespeichert!</p></div>';
}

$popup_settings = get_option('produkt_popup_settings');
if ($popup_settings === false) {
    $legacy_key = base64_decode('ZmVkZXJ3aWVnZV9wb3B1cF9zZXR0aW5ncw==');
    $popup_settings = get_option($legacy_key, []);
}
$popup_enabled = isset($popup_settings['enabled']) ? intval($popup_settings['enabled']) : 0;
$popup_days    = isset($popup_settings['days']) ? intval($popup_settings['days']) : 7;
$popup_email_enabled = isset($popup_settings['email_enabled']) ? intval($popup_settings['email_enabled']) : 0;
$popup_title   = $popup_settings['title'] ?? '';
$popup_content = $popup_settings['content'] ?? '';
$popup_options = $popup_settings['options'] ?? '';
?>

<div class="produkt-branding-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <div class="produkt-form-section">
            <h4>📣 Popup Inhalt</h4>
            <div class="produkt-form-grid">
                <div class="produkt-form-group">
                    <label>
                        <input type="checkbox" name="popup_enabled" value="1" <?php checked($popup_enabled, 1); ?>>
                        Popup aktivieren
                    </label>
                </div>
                <div class="produkt-form-group">
                    <label>Nicht erneut anzeigen (Tage)</label>
                    <input type="number" name="popup_days" min="0" value="<?php echo esc_attr($popup_days); ?>">
                </div>
                <div class="produkt-form-group">
                    <label>
                        <input type="checkbox" name="popup_email_enabled" value="1" <?php checked($popup_email_enabled, 1); ?>>
                        E-Mail-Feld anzeigen
                    </label>
                </div>
                <div class="produkt-form-group">
                    <label>Titel</label>
                    <input type="text" name="popup_title" value="<?php echo esc_attr($popup_title); ?>">
                </div>
                <div class="produkt-form-group full-width">
                    <label>Text</label>
                    <?php wp_editor($popup_content, 'popup_content', ['textarea_name' => 'popup_content']); ?>
                </div>
                <div class="produkt-form-group full-width">
                    <label>Auswahloptionen (optional, eine pro Zeile)</label>
                    <textarea name="popup_options" rows="4" placeholder="Option 1\nOption 2\nOption 3"><?php echo esc_textarea($popup_options); ?></textarea>
                </div>
            </div>
        </div>
        <?php submit_button('💾 Einstellungen speichern', 'primary', 'submit_popup'); ?>
    </form>
</div>
