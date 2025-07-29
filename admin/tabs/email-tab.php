<?php
// Email Settings Tab

if (isset($_POST['submit_email_settings'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $footer = [
        'company'      => sanitize_text_field($_POST['footer_company'] ?? ''),
        'owner'        => sanitize_text_field($_POST['footer_owner'] ?? ''),
        'street'       => sanitize_text_field($_POST['footer_street'] ?? ''),
        'postal_city'  => sanitize_text_field($_POST['footer_postal_city'] ?? ''),
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

    echo '<div class="notice notice-success"><p>✅ Einstellungen gespeichert!</p></div>';
}

$footer = get_option('produkt_email_footer', [
    'company' => '',
    'owner' => '',
    'street' => '',
    'postal_city' => ''
]);

$invoice = get_option('produkt_invoice_sender', [
    'firma_name'    => '',
    'firma_strasse' => '',
    'firma_plz_ort' => '',
    'firma_ust_id'  => '',
    'firma_email'   => '',
    'firma_telefon' => '',
]);
?>
<div class="produkt-branding-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <div class="produkt-form-section">
            <h4>📧 Email Footer</h4>
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
        </div>
        <div class="produkt-form-section">
            <h4>📨 Daten Rechnungsversand</h4>
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
        </div>
        <?php submit_button('💾 Einstellungen speichern', 'primary', 'submit_email_settings'); ?>
    </form>
</div>
