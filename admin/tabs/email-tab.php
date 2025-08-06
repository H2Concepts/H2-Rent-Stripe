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

    // Logo für Rechnungen separat speichern
    if (!empty($_POST['firma_logo_url'])) {
        update_option('plugin_firma_logo_url', esc_url_raw($_POST['firma_logo_url']));
    } else {
        delete_option('plugin_firma_logo_url');
    }

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
$logo_url = get_option('plugin_firma_logo_url', '');
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
                <h2>Email Footer</h2>
                <p class="card-subline">Absenderinformationen</p>
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
                </div>
            </div>
            <div class="dashboard-card">
                <h2>Daten Rechnungsversand</h2>
                <p class="card-subline">Angaben für Rechnungen</p>
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
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
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
