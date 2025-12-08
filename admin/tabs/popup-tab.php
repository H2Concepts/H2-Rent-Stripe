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
        'options'       => sanitize_textarea_field($_POST['popup_options'] ?? ''),
        'triggers'      => [
            'desktop_exit'      => isset($_POST['popup_trigger_desktop_exit']) ? 1 : 0,
            'mobile_scroll'     => isset($_POST['popup_trigger_mobile_scroll']) ? 1 : 0,
            'mobile_inactivity' => isset($_POST['popup_trigger_mobile_inactivity']) ? 1 : 0,
        ],
        'google_optin_enabled' => isset($_POST['google_optin_enabled']) ? 1 : 0,
        'google_merchant_id'   => sanitize_text_field($_POST['google_merchant_id'] ?? ''),
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
$google_optin_enabled = isset($popup_settings['google_optin_enabled']) ? intval($popup_settings['google_optin_enabled']) : 0;
$google_merchant_id   = isset($popup_settings['google_merchant_id']) ? sanitize_text_field($popup_settings['google_merchant_id']) : '';
$trigger_defaults = [
    'desktop_exit'      => 1,
    'mobile_scroll'     => 1,
    'mobile_inactivity' => 1,
];
$popup_triggers = $popup_settings['triggers'] ?? [];
if (!is_array($popup_triggers)) {
    $popup_triggers = [];
}
$popup_triggers = array_merge($trigger_defaults, array_intersect_key($popup_triggers, $trigger_defaults));
$popup_triggers = array_map('intval', $popup_triggers);
?>

<div class="settings-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <button type="submit" name="submit_popup" class="icon-btn popup-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
            </svg>
        </button>
        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Popup Inhalt</h2>
                        <p class="card-subline">Einstellungen für das Hinweis-Popup</p>
                    </div>
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="popup_enabled" value="1" <?php checked($popup_enabled, 1); ?>>
                        <span class="produkt-toggle-slider"></span>
                        <span>Popup aktivieren</span>
                    </label>
                </div>
                <div class="form-grid">
                    <div class="produkt-form-group full-width">
                        <label>Titel</label>
                        <input type="text" name="popup_title" value="<?php echo esc_attr($popup_title); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Text</label>
                        <?php wp_editor($popup_content, 'popup_content', ['textarea_name' => 'popup_content']); ?>
                    </div>
                    <div class="produkt-form-group">
                        <label>Auswahloptionen (optional, eine pro Zeile)</label>
                        <textarea name="popup_options" rows="4" placeholder="Option 1\nOption 2\nOption 3"><?php echo esc_textarea($popup_options); ?></textarea>
                    </div>
                    <div class="produkt-form-group">
                        <label>Nicht erneut anzeigen (Tage)</label>
                        <input type="number" name="popup_days" min="0" value="<?php echo esc_attr($popup_days); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>E-Mail-Feld anzeigen</label>
                        <label class="produkt-toggle-label">
                            <input type="checkbox" name="popup_email_enabled" value="1" <?php checked($popup_email_enabled, 1); ?>>
                            <span class="produkt-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="produkt-form-group full-width popup-trigger-group">
                        <label>Trigger</label>
                        <div class="popup-trigger-grid">
                            <div class="popup-trigger-column">
                                <span class="popup-trigger-heading">Desktop</span>
                                <label class="produkt-toggle-label">
                                    <input type="checkbox" name="popup_trigger_desktop_exit" value="1" <?php checked($popup_triggers['desktop_exit'] ?? 1, 1); ?>>
                                    <span class="produkt-toggle-slider"></span>
                                    <span>Exit-Intent (Maus verlässt Seite)</span>
                                </label>
                            </div>
                            <div class="popup-trigger-column">
                                <span class="popup-trigger-heading">Mobile</span>
                                <label class="produkt-toggle-label">
                                    <input type="checkbox" name="popup_trigger_mobile_scroll" value="1" <?php checked($popup_triggers['mobile_scroll'] ?? 1, 1); ?>>
                                    <span class="produkt-toggle-slider"></span>
                                    <span>Scroll-Trigger (nach oben)</span>
                                </label>
                                <label class="produkt-toggle-label">
                                    <input type="checkbox" name="popup_trigger_mobile_inactivity" value="1" <?php checked($popup_triggers['mobile_inactivity'] ?? 1, 1); ?>>
                                    <span class="produkt-toggle-slider"></span>
                                    <span>Inaktivität (60 Sekunden)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Google Opt-In</h2>
                        <p class="card-subline">Aktiviere das Google Kundenrezensionen Opt-In auf der Bestellbestätigung</p>
                    </div>
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="google_optin_enabled" value="1" <?php checked($google_optin_enabled, 1); ?>>
                        <span class="produkt-toggle-slider"></span>
                        <span>Opt-In aktivieren</span>
                    </label>
                </div>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Merchant ID</label>
                        <input type="text" name="google_merchant_id" value="<?php echo esc_attr($google_merchant_id); ?>" placeholder="z.B. 1234567890">
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
