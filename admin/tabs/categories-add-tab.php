<?php
// Categories Add Tab Content
?>

<div class="produkt-add-category">
    <div class="produkt-form-header">
        <h3>➕ Neues Produkt hinzufügen</h3>
        <p>Erstellen Sie ein neues Produkt mit SEO-Einstellungen und individueller Konfiguration.</p>
    </div>
    
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <!-- Grunddaten -->
        <div class="produkt-form-section">
            <h4>📝 Grunddaten</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Produkt-Name *</label>
                    <input type="text" name="name" required placeholder="z.B. Nonomo Produkt">
                </div>
                <div class="produkt-form-group">
                    <label>Shortcode-Bezeichnung *</label>
                    <input type="text" name="shortcode" required pattern="[a-z0-9_-]+" placeholder="z.B. nonomo-premium">
                    <small>Nur Kleinbuchstaben, Zahlen, _ und -</small>
                </div>
                <div class="produkt-form-group">
                    <label>Slug *</label>
                    <input type="text" name="slug" required pattern="[a-z0-9-]+" placeholder="z.B. wohnzimmer">
                    <small>Wird in der URL verwendet</small>
                </div>
            </div>
        </div>
        
        <!-- SEO-Einstellungen -->
        <div class="produkt-form-section">
            <h4>🔍 SEO-Einstellungen</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>SEO-Titel</label>
                    <input type="text" name="meta_title" maxlength="60" placeholder="Optimiert für Suchmaschinen">
                    <small>Max. 60 Zeichen für Google</small>
                </div>
                <div class="produkt-form-group">
                    <label>Layout-Stil</label>
                    <select name="layout_style">
                        <option value="default">Standard (Horizontal)</option>
                        <option value="grid">Grid (Karten-Layout)</option>
                        <option value="list">Liste (Vertikal)</option>
                    </select>
                </div>
            </div>
            
            <div class="produkt-form-group">
                <label>SEO-Beschreibung</label>
                <textarea name="meta_description" rows="3" maxlength="160" placeholder="Beschreibung für Suchmaschinen (max. 160 Zeichen)"></textarea>
            </div>
        </div>
        
        <!-- Seiteninhalte -->
        <div class="produkt-form-section">
            <h4>📄 Seiteninhalte</h4>
            
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Produkttitel *</label>
                    <input type="text" name="product_title" required placeholder="Titel unter dem Produktbild">
                </div>
                <div class="produkt-form-group">
                    <label>Versanddienstleister</label>
                    <div class="produkt-shipping-radios">
                        <?php $shipping_providers = [
                            'dhl' => 'DHL',
                            'hermes' => 'Hermes',
                            'ups' => 'UPS',
                            'dpd' => 'DPD'
                        ]; ?>
                        <?php foreach ($shipping_providers as $key => $label): ?>
                            <label>
                                <input type="radio" name="shipping_provider" value="<?php echo esc_attr($key); ?>" <?php checked($key, 'dhl'); ?>>
                                <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/shipping-icons/' . $key . '.svg'); ?>" alt="<?php echo esc_attr($label); ?>">
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="produkt-form-group">
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
        <div class="produkt-form-section">
            <h4>📸 Standard-Produktbild</h4>
            <div class="produkt-form-group">
                <label>Standard-Produktbild</label>
                <div class="produkt-upload-area">
                    <input type="url" name="default_image" id="default_image" placeholder="https://example.com/standard-bild.jpg">
                    <button type="button" class="button produkt-media-button" data-target="default_image">📁 Aus Mediathek wählen</button>
                </div>
                <small>Fallback-Bild wenn für Ausführungen kein spezifisches Bild hinterlegt ist</small>
            </div>
        </div>
        
        <!-- Features -->
        <div class="produkt-form-section">
            <h4>🌟 Features-Sektion</h4>
            <div class="produkt-form-group">
                <label><input type="checkbox" name="show_features" value="1" checked> Features-Sektion anzeigen</label>
            </div>
            <div class="produkt-form-group">
                <label>Features-Überschrift</label>
                <input type="text" name="features_title" placeholder="z.B. Warum unser Produkt?">
            </div>
            
            <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="produkt-feature-group">
                <h5>Feature <?php echo $i; ?></h5>
                <div class="produkt-form-row">
                    <div class="produkt-form-group">
                        <label>Titel</label>
                        <input type="text" name="feature_<?php echo $i; ?>_title" placeholder="z.B. Sicherheit First">
                    </div>
                    <div class="produkt-form-group">
                        <label>Icon-Bild</label>
                        <div class="produkt-upload-area">
                            <input type="url" name="feature_<?php echo $i; ?>_icon" id="feature_<?php echo $i; ?>_icon" placeholder="https://example.com/icon<?php echo $i; ?>.png">
                            <button type="button" class="button produkt-media-button" data-target="feature_<?php echo $i; ?>_icon">📁</button>
                        </div>
                    </div>
                </div>
                <div class="produkt-form-group">
                    <label>Beschreibung</label>
                    <textarea name="feature_<?php echo $i; ?>_description" rows="2" placeholder="Beschreibung für Feature <?php echo $i; ?>"></textarea>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Accordion Settings -->
        <div class="produkt-form-section">
            <h4>📑 Accordion</h4>
            <div id="accordion-container">
                <div class="produkt-accordion-group">
                    <div class="produkt-form-row">
                        <div class="produkt-form-group" style="flex:1;">
                            <label>Titel</label>
                            <input type="text" name="accordion_titles[]">
                        </div>
                        <button type="button" class="button produkt-remove-accordion">-</button>
                    </div>
                    <div class="produkt-form-group">
                        <?php wp_editor('', 'accordion_content_0_add', ['textarea_name' => 'accordion_contents[]', 'textarea_rows' => 3, 'media_buttons' => false]); ?>
                    </div>
                </div>
            </div>
            <button type="button" id="add-accordion" class="button">+ Accordion hinzufügen</button>
        </div>
        
        <!-- Button & Tooltips -->
        <div class="produkt-form-section">
            <h4>🔘 Button & Tooltips</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Button-Text</label>
                    <input type="text" name="button_text" placeholder="z.B. Jetzt Mieten">
                </div>
                <div class="produkt-form-group">
                    <label>Button-Icon</label>
                    <div class="produkt-upload-area">
                        <input type="url" name="button_icon" id="button_icon" placeholder="https://example.com/button-icon.png">
                        <button type="button" class="button produkt-media-button" data-target="button_icon">📁</button>
                    </div>
                </div>
            </div>

            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Stripe Versandkosten Preis ID</label>
                    <input type="text" name="shipping_price_id" placeholder="price_123...">
                </div>
                <div class="produkt-form-group">
                    <label>Text Versandkosten</label>
                    <input type="text" name="shipping_label" placeholder="Einmalige Versandkosten:">
                </div>
                <div class="produkt-form-group">
                    <label>Preis-Label</label>
                    <input type="text" name="price_label" placeholder="Monatlicher Mietpreis">
                </div>
                <div class="produkt-form-group">
                    <label>Preiszeitraum</label>
                    <select name="price_period">
                        <option value="month">pro Monat</option>
                        <option value="one-time">einmalig</option>
                    </select>
                </div>
                <div class="produkt-form-group">
                    <label><input type="checkbox" name="vat_included" value="1"> Mit MwSt.</label>
                </div>
            </div>

            <div class="produkt-form-group">
                <label>Bezahlmethoden</label>
                <div class="produkt-payment-checkboxes">
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
                            <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/payment-icons/' . $key . '.svg'); ?>" alt="<?php echo esc_attr($label); ?>">
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div class="produkt-form-group">
                <label>Mietdauer-Tooltip</label>
                <textarea name="duration_tooltip" rows="3" placeholder="Text der bei 'Wählen Sie Ihre Mietdauer' angezeigt wird"></textarea>
            </div>
            
            <div class="produkt-form-group">
                <label>Zustand-Tooltip</label>
                <textarea name="condition_tooltip" rows="4" placeholder="Text der bei 'Zustand' angezeigt wird"></textarea>
            </div>
        <div class="produkt-form-group">
            <label><input type="checkbox" name="show_tooltips" value="1" checked> Tooltips auf Produktseite anzeigen</label>
        </div>
    </div>

    <!-- Produktbewertung -->
    <div class="produkt-form-section">
        <h4>⭐ Produktbewertung</h4>
        <div class="produkt-form-group">
            <label><input type="checkbox" name="show_rating" value="1"> Produktbewertung anzeigen</label>
        </div>
        <div class="produkt-form-row">
            <div class="produkt-form-group">
                <label>Sterne-Bewertung (1-5)</label>
                <input type="number" name="rating_value" step="0.1" min="1" max="5">
            </div>
            <div class="produkt-form-group">
                <label>Bewertungs-Link</label>
                <input type="url" name="rating_link" placeholder="https://example.com/bewertungen">
            </div>
        </div>
    </div>
        
        <!-- Einstellungen -->
        <div class="produkt-form-section">
            <h4>⚙️ Einstellungen</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" min="0">
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="produkt-form-actions">
            <button type="submit" name="submit_category" class="button button-primary button-large">
                ✅ Produkt erstellen
            </button>
            <a href="<?php echo admin_url('admin.php?page=produkt-categories&tab=list'); ?>" class="button button-large">
                ❌ Abbrechen
            </a>
        </div>
    </form>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // WordPress Media Library Integration
    document.querySelectorAll('.produkt-media-button').forEach(function(button) {
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
    const slugInput = document.querySelector('input[name="slug"]');

    if (nameInput && shortcodeInput && slugInput) {
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
            if (!slugInput.value) {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim();
                slugInput.value = slug;
            }
        });
    }

    var accordionIndex = document.querySelectorAll('#accordion-container .produkt-accordion-group').length;
    document.getElementById('add-accordion').addEventListener('click', function(e){
        e.preventDefault();
        var id = 'accordion_content_' + accordionIndex + '_new';
        var wrapper = document.createElement('div');
        wrapper.className = 'produkt-accordion-group';
        wrapper.innerHTML = '<div class="produkt-form-row">' +
            '<div class="produkt-form-group" style="flex:1;">' +
            '<label>Titel</label>' +
            '<input type="text" name="accordion_titles[]" />' +
            '</div>' +
            '<button type="button" class="button produkt-remove-accordion">-</button>' +
            '</div>' +
            '<div class="produkt-form-group"><textarea id="' + id + '" name="accordion_contents[]" rows="3"></textarea></div>';
        document.getElementById('accordion-container').appendChild(wrapper);
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
            wp.editor.initialize(id, { tinymce: true, quicktags: true });
        }
        accordionIndex++;
    });

    document.getElementById('accordion-container').addEventListener('click', function(e){
        if(e.target.classList.contains('produkt-remove-accordion')){
            e.preventDefault();
            var group = e.target.closest('.produkt-accordion-group');
            if(group){ group.remove(); }
        }
    });
});
</script>
