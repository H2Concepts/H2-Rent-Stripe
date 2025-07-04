<?php
// Popup Tab Content

if (isset($_POST['submit_popup'])) {
    \FederwiegenVerleih\Admin::verify_admin_action();
    $settings = [
        'enabled' => isset($_POST['popup_enabled']) ? 1 : 0,
        'days'    => max(1, intval($_POST['popup_days'] ?? 7)),
        'title'   => sanitize_text_field($_POST['popup_title'] ?? ''),
        'content' => wp_kses_post($_POST['popup_content'] ?? ''),
        'options' => sanitize_textarea_field($_POST['popup_options'] ?? '')
    ];
    update_option('federwiegen_popup_settings', $settings);
    echo '<div class="notice notice-success"><p>✅ Popup-Einstellungen gespeichert!</p></div>';
}

$popup_settings = get_option('federwiegen_popup_settings', []);
$popup_enabled = isset($popup_settings['enabled']) ? intval($popup_settings['enabled']) : 0;
$popup_days    = isset($popup_settings['days']) ? intval($popup_settings['days']) : 7;
$popup_title   = $popup_settings['title'] ?? '';
$popup_content = $popup_settings['content'] ?? '';
$popup_options = $popup_settings['options'] ?? '';
?>

<div class="federwiegen-branding-tab">
    <form method="post" action="">
        <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
        <div class="federwiegen-form-section">
            <h4>📣 Popup Inhalt</h4>
            <div class="federwiegen-form-grid">
                <div class="federwiegen-form-group">
                    <label>
                        <input type="checkbox" name="popup_enabled" value="1" <?php checked($popup_enabled, 1); ?>>
                        Popup aktivieren
                    </label>
                </div>
                <div class="federwiegen-form-group">
                    <label>Nicht erneut anzeigen (Tage)</label>
                    <input type="number" name="popup_days" min="1" value="<?php echo esc_attr($popup_days); ?>">
                </div>
                <div class="federwiegen-form-group">
                    <label>Titel</label>
                    <input type="text" name="popup_title" value="<?php echo esc_attr($popup_title); ?>">
                </div>
                <div class="federwiegen-form-group full-width">
                    <label>Text</label>
                    <?php wp_editor($popup_content, 'popup_content', ['textarea_name' => 'popup_content']); ?>
                </div>
                <div class="federwiegen-form-group full-width">
                    <label>Auswahloptionen (optional, eine pro Zeile)</label>
                    <textarea name="popup_options" rows="4" placeholder="Option 1\nOption 2\nOption 3"><?php echo esc_textarea($popup_options); ?></textarea>
                </div>
            </div>
        </div>
        <?php submit_button('💾 Einstellungen speichern', 'primary', 'submit_popup'); ?>
    </form>
</div>
