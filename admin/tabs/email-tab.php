<?php
// Email Settings Tab

if (!function_exists('produkt_send_test_customer_email')) {
    function produkt_send_test_customer_email() {
        $admin_email = get_option('admin_email');
        if (empty($admin_email)) {
            return new \WP_Error('missing_admin_email', 'Es ist keine Admin-E-Mail-Adresse hinterlegt.');
        }

        $start = current_time('timestamp');
        $end   = strtotime('+30 days', $start);

        $order = [
            'customer_email'    => $admin_email,
            'customer_name'     => 'Max Mustermann',
            'customer_phone'    => '01234 567890',
            'customer_street'   => 'Musterstraße 1',
            'customer_postal'   => '12345',
            'customer_city'     => 'Musterstadt',
            'customer_country'  => 'Deutschland',
            'created_at'        => current_time('mysql'),
            'final_price'       => 199.99,
            'shipping_cost'     => 9.99,
            'order_items'       => json_encode([
                [
                    'produkt_name'       => 'Kinderwagen Modell X',
                    'variant_name'       => 'Edition 2024',
                    'extra_names'        => 'Regenschutz, Becherhalter',
                    'product_color_name' => 'Space Grau',
                    'frame_color_name'   => 'Schwarz',
                    'condition_name'     => 'Neu',
                    'duration_name'      => '1 Monat',
                    'start_date'         => date('Y-m-d', $start),
                    'end_date'           => date('Y-m-d', $end),
                    'final_price'        => 199.99,
                ],
            ]),
            'order_number'      => 'TEST-1001',
            'produkt_name'      => 'Kinderwagen Modell X',
            'extra_text'        => 'Regenschutz, Becherhalter',
            'dauer_text'        => '1 Monat',
            'zustand_text'      => 'Neu',
            'produktfarbe_text' => 'Space Grau',
            'gestellfarbe_text' => 'Schwarz',
        ];

        \ProduktVerleih\send_produkt_welcome_email($order, 0, false);

        return true;
    }
}

if (isset($_POST['submit_email_settings']) || isset($_POST['send_test_email'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $footer = [
        'company'      => sanitize_text_field($_POST['footer_company'] ?? ''),
        'owner'        => sanitize_text_field($_POST['footer_owner'] ?? ''),
        'street'       => sanitize_text_field($_POST['footer_street'] ?? ''),
        'postal_city'  => sanitize_text_field($_POST['footer_postal_city'] ?? ''),
        'website'      => sanitize_text_field($_POST['footer_website'] ?? ''),
        'copyright'    => sanitize_text_field($_POST['footer_copyright'] ?? ''),
    ];
    update_option('produkt_email_footer', $footer);

    $invoice = [
        'firma_name'    => sanitize_text_field($_POST['firma_name'] ?? ''),
        'firma_strasse' => sanitize_text_field($_POST['firma_strasse'] ?? ''),
        'firma_plz_ort' => sanitize_text_field($_POST['firma_plz_ort'] ?? ''),
        'firma_ust_id'  => sanitize_text_field($_POST['firma_ust_id'] ?? ''),
        'firma_email'   => sanitize_text_field($_POST['firma_email'] ?? ''),
        'firma_telefon' => sanitize_text_field($_POST['firma_telefon'] ?? ''),
    ];
    update_option('produkt_invoice_sender', $invoice);

    $email_toggle = !empty($_POST['invoice_email_enabled']) ? '1' : '0';
    update_option('produkt_invoice_email_enabled', $email_toggle);

    // Logo für Rechnungen separat speichern
    if (!empty($_POST['firma_logo_url'])) {
        update_option('plugin_firma_logo_url', esc_url_raw($_POST['firma_logo_url']));
    } else {
        delete_option('plugin_firma_logo_url');
    }

    echo '<div class="notice notice-success"><p>✅ Einstellungen gespeichert!</p></div>';

    if (isset($_POST['send_test_email'])) {
        $test_result = produkt_send_test_customer_email();
        if (true === $test_result) {
            echo '<div class="notice notice-success"><p>✅ Test-E-Mail wurde versendet.</p></div>';
        } elseif (is_wp_error($test_result)) {
            echo '<div class="notice notice-error"><p>❌ Test-E-Mail konnte nicht gesendet werden: ' . esc_html($test_result->get_error_message()) . '</p></div>';
        }
    }
}

$footer_defaults = [
    'company'     => '',
    'owner'       => '',
    'street'      => '',
    'postal_city' => '',
    'website'     => '',
    'copyright'   => '',
];
$footer = wp_parse_args((array) get_option('produkt_email_footer', []), $footer_defaults);

$invoice_defaults = [
    'firma_name'    => '',
    'firma_strasse' => '',
    'firma_plz_ort' => '',
    'firma_ust_id'  => '',
    'firma_email'   => '',
    'firma_telefon' => '',
];
$invoice = wp_parse_args((array) get_option('produkt_invoice_sender', []), $invoice_defaults);
$logo_url = get_option('plugin_firma_logo_url', '');
$invoice_email_enabled = get_option('produkt_invoice_email_enabled', '1');
?>
<div class="settings-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <button type="submit" name="submit_email_settings" class="icon-btn email-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
            </svg>
        </button>
        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Email Footer</h2>
                        <p class="card-subline">Absenderinformationen</p>
                    </div>
                    <button type="submit" name="send_test_email" value="1" class="button button-secondary">Test Email versenden</button>
                </div>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Firmenname</label>
                        <input type="text" name="footer_company" value="<?php echo esc_attr($footer['company']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Ansprechpartner / Inhaber</label>
                        <input type="text" name="footer_owner" value="<?php echo esc_attr($footer['owner']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Straße &amp; Hausnummer</label>
                        <input type="text" name="footer_street" value="<?php echo esc_attr($footer['street']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>PLZ &amp; Ort</label>
                        <input type="text" name="footer_postal_city" value="<?php echo esc_attr($footer['postal_city']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Webseite</label>
                        <input type="text" name="footer_website" value="<?php echo esc_attr($footer['website']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Copyright</label>
                        <input type="text" name="footer_copyright" value="<?php echo esc_attr($footer['copyright']); ?>">
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Daten Rechnungsversand</h2>
                        <p class="card-subline">Angaben für Rechnungen</p>
                    </div>
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="invoice_email_enabled" value="1" <?php checked($invoice_email_enabled, '1'); ?>>
                        <span class="produkt-toggle-slider"></span>
                        <span>Rechnungsversand aktivieren</span>
                    </label>
                </div>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Firmenname</label>
                        <input type="text" name="firma_name" value="<?php echo esc_attr($invoice['firma_name']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Straße &amp; Hausnummer</label>
                        <input type="text" name="firma_strasse" value="<?php echo esc_attr($invoice['firma_strasse']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>PLZ &amp; Ort</label>
                        <input type="text" name="firma_plz_ort" value="<?php echo esc_attr($invoice['firma_plz_ort']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>USt-ID</label>
                        <input type="text" name="firma_ust_id" value="<?php echo esc_attr($invoice['firma_ust_id']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>E-Mail (optional)</label>
                        <input type="text" name="firma_email" value="<?php echo esc_attr($invoice['firma_email']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Telefon (optional)</label>
                        <input type="text" name="firma_telefon" value="<?php echo esc_attr($invoice['firma_telefon']); ?>">
                    </div>
                    <div class="produkt-form-group full-width">
                        <label>Logo für Rechnung (optional)</label>
                        <div class="image-field-row">
                            <div id="firma_logo_url_preview" class="image-preview">
                                <?php if ($logo_url) : ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="">
                                <?php else: ?>
                                    <span>Noch kein Bild vorhanden</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="icon-btn produkt-media-button" data-target="firma_logo_url" aria-label="Bild auswählen">
                                <svg id="Ebene_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.3 82.6"><path d="M74.5.6H7.8C3.8.6.6,3.9.5,7.9v66.7c0,4,3.3,7.3,7.3,7.3h66.7c4,0,7.3-3.3,7.3-7.3V7.9c0-4-3.3-7.3-7.3-7.3ZM7.8,6.8h66.7c.3,0,.5.1.7.3.2.2.3.5.3.7v43.5l-13.2-10.6c-2.6-2-6.3-2-8.9,0l-11.9,8.8-11.8-11.8c-2.9-2.8-7.4-2.8-10.3,0l-12.5,12.5V7.9c0-.6.4-1,1-1h0ZM74.5,75.6H7.8c-.6,0-1-.5-1-1v-15.4l17-17c.2-.2.5-.3.8-.3s.6.1.8.3l17.9,17.9c1.2,1.2,3.2,1.2,4.4,0s1.2-3.2,0-4.4l-1.6-1.6,11.2-8.3c.4-.3.9-.3,1.3,0l17.1,13.7v15.1c0,.6-.5,1-1,1h0ZM45.3,36c4.6,0,8.8-2.8,10.6-7.1,1.8-4.3.8-9.2-2.5-12.5-3.3-3.3-8.2-4.3-12.5-2.5-4.3,1.8-7.1,6-7.1,10.6s5.1,11.5,11.5,11.5h0ZM45.3,19.3c2.1,0,4,1.3,4.8,3.2.8,1.9.4,4.2-1.1,5.7-1.5,1.5-3.7,1.9-5.7,1.1-1.9-.8-3.2-2.7-3.2-4.8s2.3-5.2,5.2-5.2Z"/></svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="firma_logo_url" aria-label="Bild entfernen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="firma_logo_url" id="firma_logo_url" value="<?php echo esc_attr($logo_url); ?>">
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
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
                if (target) { target.value = attachment.url; }
                if (preview) { preview.innerHTML = '<img src="' + attachment.url + '" alt="">'; }
            });
            frame.open();
        });
    });
    document.querySelectorAll('.produkt-remove-image').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const target = document.getElementById(this.dataset.target);
            const preview = document.getElementById(this.dataset.target + '_preview');
            if (target) { target.value = ''; }
            if (preview) { preview.innerHTML = '<span>Noch kein Bild vorhanden</span>'; }
        });
    });
});
</script>
