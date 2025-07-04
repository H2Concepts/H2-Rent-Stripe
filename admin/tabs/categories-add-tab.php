<?php
// Categories Add Tab Content
?>

<div class="federwiegen-add-category">
    <div class="federwiegen-form-header">
        <h3>➕ Neue Kategorie hinzufügen</h3>
        <p>Erstellen Sie eine neue Produktkategorie mit SEO-Einstellungen und individueller Konfiguration.</p>
    </div>
    
    <form method="post" action="" class="federwiegen-compact-form">
        <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
        <!-- Grunddaten -->
        <div class="federwiegen-form-section">
            <h4>📝 Grunddaten</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Kategorie-Name *</label>
                    <input type="text" name="name" required placeholder="z.B. Nonomo Federwiegen">
                </div>
                <div class="federwiegen-form-group">
                    <label>Shortcode-Bezeichnung *</label>
                    <input type="text" name="shortcode" required pattern="[a-z0-9_-]+" placeholder="z.B. nonomo-premium">
                    <small>Nur Kleinbuchstaben, Zahlen, _ und -</small>
                </div>
            </div>
        </div>
        
        <!-- SEO-Einstellungen -->
        <div class="federwiegen-form-section">
            <h4>🔍 SEO-Einstellungen</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>SEO-Titel</label>
                    <input type="text" name="meta_title" maxlength="60" placeholder="Optimiert für Suchmaschinen">
                    <small>Max. 60 Zeichen für Google</small>
                </div>
                <div class="federwiegen-form-group">
                    <label>Layout-Stil</label>
                    <select name="layout_style">
                        <option value="default">Standard (Horizontal)</option>
                        <option value="grid">Grid (Karten-Layout)</option>
                        <option value="list">Liste (Vertikal)</option>
                    </select>
                </div>
            </div>
            
            <div class="federwiegen-form-group">
                <label>SEO-Beschreibung</label>
                <textarea name="meta_description" rows="3" maxlength="160" placeholder="Beschreibung für Suchmaschinen (max. 160 Zeichen)"></textarea>
            </div>
        </div>
        
        <!-- Seiteninhalte -->
        <div class="federwiegen-form-section">
            <h4>📄 Seiteninhalte</h4>
            
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Produkttitel *</label>
                    <input type="text" name="product_title" required placeholder="Titel unter dem Produktbild">
                </div>
                <div class="federwiegen-form-group">
                    <label>Versandkosten (€)</label>
                    <input type="number" name="shipping_cost" step="0.01" min="0">
                </div>
                <div class="federwiegen-form-group">
                    <label>Versanddienstleister</label>
                    <div class="federwiegen-shipping-radios">
                        <?php $shipping_providers = [
                            'dhl' => 'DHL',
                            'hermes' => 'Hermes',
                            'ups' => 'UPS',
                            'dpd' => 'DPD'
                        ]; ?>
                        <?php foreach ($shipping_providers as $key => $label): ?>
                            <label>
                                <input type="radio" name="shipping_provider" value="<?php echo esc_attr($key); ?>" <?php checked($key, 'dhl'); ?>>
                                <img src="<?php echo esc_url(FEDERWIEGEN_PLUGIN_URL . 'assets/shipping-icons/' . $key . '.svg'); ?>" alt="<?php echo esc_attr($label); ?>">
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="federwiegen-form-group">
                <label>Produktbeschreibung *</label>
                <?php
                wp_editor(
                    '',
                    'category_product_description_add',
                    [
                        'textarea_name' => 'product_description',
                        'textarea_rows' => 5,
                        'media_buttons' => false,
                    ]
                );
                ?>
            </div>
        </div>
        
        <!-- Bilder -->
        <div class="federwiegen-form-section">
            <h4>📸 Standard-Produktbild</h4>
            <div class="federwiegen-form-group">
                <label>Standard-Produktbild</label>
                <div class="federwiegen-upload-area">
                    <input type="url" name="default_image" id="default_image" placeholder="https://example.com/standard-bild.jpg">
                    <button type="button" class="button federwiegen-media-button" data-target="default_image">📁 Aus Mediathek wählen</button>
                </div>
                <small>Fallback-Bild wenn für Ausführungen kein spezifisches Bild hinterlegt ist</small>
            </div>
        </div>
        
        <!-- Features -->
        <div class="federwiegen-form-section">
            <h4>🌟 Features-Sektion</h4>
            <div class="federwiegen-form-group">
                <label>Features-Überschrift</label>
                <input type="text" name="features_title" placeholder="z.B. Warum unser Produkt?">
            </div>
            
            <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="federwiegen-feature-group">
                <h5>Feature <?php echo $i; ?></h5>
                <div class="federwiegen-form-row">
                    <div class="federwiegen-form-group">
                        <label>Titel</label>
                        <input type="text" name="feature_<?php echo $i; ?>_title" placeholder="z.B. Sicherheit First">
                    </div>
                    <div class="federwiegen-form-group">
                        <label>Icon-Bild</label>
                        <div class="federwiegen-upload-area">
                            <input type="url" name="feature_<?php echo $i; ?>_icon" id="feature_<?php echo $i; ?>_icon" placeholder="https://example.com/icon<?php echo $i; ?>.png">
                            <button type="button" class="button federwiegen-media-button" data-target="feature_<?php echo $i; ?>_icon">📁</button>
                        </div>
                    </div>
                </div>
                <div class="federwiegen-form-group">
                    <label>Beschreibung</label>
                    <textarea name="feature_<?php echo $i; ?>_description" rows="2" placeholder="Beschreibung für Feature <?php echo $i; ?>"></textarea>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        
        <!-- Button & Tooltips -->
        <div class="federwiegen-form-section">
            <h4>🔘 Button & Tooltips</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Button-Text</label>
                    <input type="text" name="button_text" placeholder="z.B. Jetzt Mieten">
                </div>
                <div class="federwiegen-form-group">
                    <label>Button-Icon</label>
                    <div class="federwiegen-upload-area">
                        <input type="url" name="button_icon" id="button_icon" placeholder="https://example.com/button-icon.png">
                        <button type="button" class="button federwiegen-media-button" data-target="button_icon">📁</button>
                    </div>
                </div>
            </div>

            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Text Versandkosten</label>
                    <input type="text" name="shipping_label" placeholder="Einmalige Versandkosten:">
                </div>
                <div class="federwiegen-form-group">
                    <label>Preis-Label</label>
                    <input type="text" name="price_label" placeholder="Monatlicher Mietpreis">
                </div>
                <div class="federwiegen-form-group">
                    <label>Preiszeitraum</label>
                    <select name="price_period">
                        <option value="month">pro Monat</option>
                        <option value="one-time">einmalig</option>
                    </select>
                </div>
                <div class="federwiegen-form-group">
                    <label><input type="checkbox" name="vat_included" value="1"> Mit MwSt.</label>
                </div>
            </div>

            <div class="federwiegen-form-group">
                <label>Bezahlmethoden</label>
                <div class="federwiegen-payment-checkboxes">
                    <?php $payment_methods = [
                        'american-express' => 'American Express',
                        'apple-pay' => 'Apple Pay',
                        'google-pay' => 'Google Pay',
                        'klarna' => 'Klarna',
                        'maestro' => 'Maestro',
                        'mastercard' => 'Mastercard',
                        'paypal' => 'Paypal',
                        'shop' => 'Shop',
                        'union-pay' => 'Union Pay',
                        'visa' => 'Visa'
                    ]; ?>
                    <?php foreach ($payment_methods as $key => $label): ?>
                        <label>
                            <input type="checkbox" name="payment_icons[]" value="<?php echo esc_attr($key); ?>">
                            <img src="<?php echo esc_url(FEDERWIEGEN_PLUGIN_URL . 'assets/payment-icons/' . $key . '.svg'); ?>" alt="<?php echo esc_attr($label); ?>">
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div class="federwiegen-form-group">
                <label>Mietdauer-Tooltip</label>
                <textarea name="duration_tooltip" rows="3" placeholder="Text der bei 'Wählen Sie Ihre Mietdauer' angezeigt wird"></textarea>
            </div>
            
            <div class="federwiegen-form-group">
                <label>Zustand-Tooltip</label>
                <textarea name="condition_tooltip" rows="4" placeholder="Text der bei 'Zustand' angezeigt wird"></textarea>
            </div>
        <div class="federwiegen-form-group">
            <label><input type="checkbox" name="show_tooltips" value="1" checked> Tooltips auf Produktseite anzeigen</label>
        </div>
    </div>

    <!-- Produktbewertung -->
    <div class="federwiegen-form-section">
        <h4>⭐ Produktbewertung</h4>
        <div class="federwiegen-form-group">
            <label><input type="checkbox" name="show_rating" value="1"> Produktbewertung anzeigen</label>
        </div>
        <div class="federwiegen-form-row">
            <div class="federwiegen-form-group">
                <label>Sterne-Bewertung (1-5)</label>
                <input type="number" name="rating_value" step="0.1" min="1" max="5">
            </div>
            <div class="federwiegen-form-group">
                <label>Bewertungs-Link</label>
                <input type="url" name="rating_link" placeholder="https://example.com/bewertungen">
            </div>
        </div>
    </div>
        
        <!-- Einstellungen -->
        <div class="federwiegen-form-section">
            <h4>⚙️ Einstellungen</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" min="0">
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="federwiegen-form-actions">
            <button type="submit" name="submit_category" class="button button-primary button-large">
                ✅ Kategorie erstellen
            </button>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&tab=list'); ?>" class="button button-large">
                ❌ Abbrechen
            </a>
        </div>
    </form>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // WordPress Media Library Integration
    document.querySelectorAll('.federwiegen-media-button').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            
            if (!targetInput) return;
            
            const mediaUploader = wp.media({
                title: 'Bild auswählen',
                button: {
                    text: 'Bild verwenden'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                targetInput.value = attachment.url;
            });
            
            mediaUploader.open();
        });
    });
    
    // Auto-generate shortcode from name
    const nameInput = document.querySelector('input[name="name"]');
    const shortcodeInput = document.querySelector('input[name="shortcode"]');
    
    if (nameInput && shortcodeInput) {
        nameInput.addEventListener('input', function() {
            if (!shortcodeInput.value) {
                const shortcode = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim();
                shortcodeInput.value = shortcode;
            }
        });
    }
});
</script>
