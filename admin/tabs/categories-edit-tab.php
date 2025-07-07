<?php
// Categories Edit Tab Content
?>

<div class="produkt-edit-category">
    <div class="produkt-form-header">
        <h3>✏️ Produkt bearbeiten</h3>
        <p>Bearbeiten Sie das Produkt "<?php echo esc_html($edit_item->name); ?>" mit allen Einstellungen und Inhalten.</p>
    </div>
    
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">

        <div class="produkt-subtab-nav">
            <a href="#" class="produkt-subtab active" data-tab="general">Allgemein</a>
            <a href="#" class="produkt-subtab" data-tab="product">Produktseite</a>
            <a href="#" class="produkt-subtab" data-tab="shipping">Versand</a>
            <a href="#" class="produkt-subtab" data-tab="features">Features</a>
            <a href="#" class="produkt-subtab" data-tab="tooltips">Tooltips</a>
            <a href="#" class="produkt-subtab" data-tab="pricing">Preis-Einstellungen</a>
        </div>

        <div id="tab-general" class="produkt-subtab-content active">
        
        <!-- Grunddaten -->
        <div class="produkt-form-section">
            <h4>📝 Grunddaten</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Produkt-Name *</label>
                    <input type="text" name="name" value="<?php echo esc_attr($edit_item->name); ?>" required>
                </div>
                <div class="produkt-form-group">
                    <label>Shortcode-Bezeichnung *</label>
                    <input type="text" name="shortcode" value="<?php echo esc_attr($edit_item->shortcode); ?>" required pattern="[a-z0-9_-]+">
                    <small>Nur Kleinbuchstaben, Zahlen, _ und -</small>
                </div>
            </div>
        </div>
        
        <!-- SEO-Einstellungen -->
        <div class="produkt-form-section">
            <h4>🔍 SEO-Einstellungen</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>SEO-Titel</label>
                    <input type="text" name="meta_title" value="<?php echo esc_attr($edit_item->meta_title ?? ''); ?>" maxlength="60">
                    <small>Max. 60 Zeichen für Google</small>
                </div>
                <div class="produkt-form-group">
                    <label>Layout-Stil</label>
                    <select name="layout_style">
                        <option value="default" <?php selected($edit_item->layout_style ?? 'default', 'default'); ?>>Standard (Horizontal)</option>
                        <option value="grid" <?php selected($edit_item->layout_style ?? 'default', 'grid'); ?>>Grid (Karten-Layout)</option>
                        <option value="list" <?php selected($edit_item->layout_style ?? 'default', 'list'); ?>>Liste (Vertikal)</option>
                    </select>
                </div>
            </div>
            
            <div class="produkt-form-group">
                <label>SEO-Beschreibung</label>
                <textarea name="meta_description" rows="3" maxlength="160"><?php echo esc_textarea($edit_item->meta_description ?? ''); ?></textarea>
            </div>
        </div>

        <div class="produkt-form-section">
            <h4>⚙️ Einstellungen</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_item->sort_order; ?>" min="0">
                </div>
            </div>
        </div>

        </div><!-- end tab-general -->

        <div id="tab-product" class="produkt-subtab-content">

        <!-- Seiteninhalte -->
        <div class="produkt-form-section">
            <h4>📄 Seiteninhalte</h4>
            
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Produkttitel *</label>
                    <input type="text" name="product_title" value="<?php echo esc_attr($edit_item->product_title); ?>" required>
                </div>
            </div>
            
            <div class="produkt-form-group">
                <label>Produktbeschreibung *</label>
                <?php
                wp_editor(
                    $edit_item->product_description,
                    'category_product_description_edit',
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
                    <input type="url" name="default_image" id="default_image" value="<?php echo esc_attr($edit_item->default_image); ?>">
                    <button type="button" class="button produkt-media-button" data-target="default_image">📁 Aus Mediathek wählen</button>
                </div>
                <small>Fallback-Bild wenn für Ausführungen kein spezifisches Bild hinterlegt ist</small>
                
                <?php if (!empty($edit_item->default_image)): ?>
                <div class="produkt-image-preview">
                    <img src="<?php echo esc_url($edit_item->default_image); ?>" alt="Standard-Produktbild">
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Button -->
        <div class="produkt-form-section">
            <h4>🔘 Button</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Button-Text</label>
                    <input type="text" name="button_text" value="<?php echo esc_attr($edit_item->button_text); ?>">
                </div>
                <div class="produkt-form-group">
                    <label>Button-Icon</label>
                    <div class="produkt-upload-area">
                        <input type="url" name="button_icon" id="button_icon" value="<?php echo esc_attr($edit_item->button_icon); ?>">
                        <button type="button" class="button produkt-media-button" data-target="button_icon">📁</button>
                    </div>
                    <?php if (!empty($edit_item->button_icon)): ?>
                    <div class="produkt-icon-preview">
                        <img src="<?php echo esc_url($edit_item->button_icon); ?>" alt="Button Icon">
                    </div>
                    <?php endif; ?>
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
                    ];
                    $selected_icons = [];
                    if (isset($edit_item->payment_icons)) {
                        $selected_icons = array_filter(array_map('trim', explode(',', $edit_item->payment_icons)));
                    }
                    ?>
                    <?php foreach ($payment_methods as $key => $label): ?>
                        <label>
                            <input type="checkbox" name="payment_icons[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $selected_icons)); ?>>
                            <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/payment-icons/' . $key . '.svg'); ?>" alt="<?php echo esc_attr($label); ?>">
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- Produktbewertung -->
        <div class="produkt-form-section">
            <h4>⭐ Produktbewertung</h4>
            <div class="produkt-form-group">
                <label><input type="checkbox" name="show_rating" value="1" <?php checked($edit_item->show_rating ?? 0, 1); ?>> Produktbewertung anzeigen</label>
            </div>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Sterne-Bewertung (1-5)</label>
                    <input type="number" name="rating_value" value="<?php echo esc_attr($edit_item->rating_value); ?>" step="0.1" min="1" max="5">
                </div>
                <div class="produkt-form-group">
                    <label>Bewertungs-Link</label>
                    <input type="url" name="rating_link" value="<?php echo esc_attr($edit_item->rating_link); ?>">
                </div>
            </div>
        </div>
        </div><!-- end tab-product -->

        <div id="tab-shipping" class="produkt-subtab-content">
            <div class="produkt-form-section">
                <h4>🚚 Versand</h4>
                <div class="produkt-form-row">
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
                                    <input type="radio" name="shipping_provider" value="<?php echo esc_attr($key); ?>" <?php checked($edit_item->shipping_provider ?? '', $key); ?>>
                                    <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/shipping-icons/' . $key . '.svg'); ?>" alt="<?php echo esc_attr($label); ?>">
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="produkt-form-group">
                        <label>Stripe Versandkosten Preis ID</label>
                        <input type="text" name="shipping_price_id" value="<?php echo esc_attr($edit_item->shipping_price_id ?? ''); ?>" placeholder="price_123...">
                    </div>
                    <div class="produkt-form-group">
                        <label>Text Versandkosten</label>
                        <input type="text" name="shipping_label" value="<?php echo isset($edit_item->shipping_label) ? esc_attr($edit_item->shipping_label) : ''; ?>" placeholder="Einmalige Versandkosten:">
                    </div>
                </div>
            </div>
        </div><!-- end tab-shipping -->

        <div id="tab-features" class="produkt-subtab-content">
        <!-- Features -->
        <div class="produkt-form-section">
            <h4>🌟 Features-Sektion</h4>
            <div class="produkt-form-group">
                <label><input type="checkbox" name="show_features" value="1" <?php checked($edit_item->show_features ?? 1, 1); ?>> Features-Sektion anzeigen</label>
            </div>
            <div class="produkt-form-group">
                <label>Features-Überschrift</label>
                <input type="text" name="features_title" value="<?php echo esc_attr($edit_item->features_title); ?>">
            </div>

            <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="produkt-feature-group">
                <h5>Feature <?php echo $i; ?></h5>
                <div class="produkt-form-row">
                    <div class="produkt-form-group">
                        <label>Titel</label>
                        <input type="text" name="feature_<?php echo $i; ?>_title" value="<?php echo esc_attr($edit_item->{'feature_' . $i . '_title'}); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Icon-Bild</label>
                        <div class="produkt-upload-area">
                            <input type="url" name="feature_<?php echo $i; ?>_icon" id="feature_<?php echo $i; ?>_icon" value="<?php echo esc_attr($edit_item->{'feature_' . $i . '_icon'}); ?>">
                            <button type="button" class="button produkt-media-button" data-target="feature_<?php echo $i; ?>_icon">📁</button>
                        </div>
                        <?php if (!empty($edit_item->{'feature_' . $i . '_icon'})): ?>
                        <div class="produkt-icon-preview">
                            <img src="<?php echo esc_url($edit_item->{'feature_' . $i . '_icon'}); ?>" alt="Feature <?php echo $i; ?> Icon">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="produkt-form-group">
                    <label>Beschreibung</label>
                    <textarea name="feature_<?php echo $i; ?>_description" rows="2"><?php echo esc_textarea($edit_item->{'feature_' . $i . '_description'}); ?></textarea>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <div class="produkt-form-section">
            <h4>📑 Accordion</h4>
            <div id="accordion-container">
                <?php
                $accordion_data = !empty($edit_item->accordion_data) ? json_decode($edit_item->accordion_data, true) : [];
                if (!is_array($accordion_data) || empty($accordion_data)) {
                    $accordion_data = [['title' => '', 'content' => '']];
                }
                foreach ($accordion_data as $idx => $acc): ?>
                <div class="produkt-accordion-group">
                    <div class="produkt-form-row">
                        <div class="produkt-form-group" style="flex:1;">
                            <label>Titel</label>
                            <input type="text" name="accordion_titles[]" value="<?php echo esc_attr($acc['title']); ?>">
                        </div>
                        <button type="button" class="button produkt-remove-accordion">-</button>
                    </div>
                    <div class="produkt-form-group">
                        <?php wp_editor($acc['content'], 'accordion_content_' . $idx . '_edit', ['textarea_name' => 'accordion_contents[]', 'textarea_rows' => 3, 'media_buttons' => false]); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-accordion" class="button">+ Accordion hinzufügen</button>
        </div>

        </div><!-- end tab-features -->

        <div id="tab-tooltips" class="produkt-subtab-content">
            <div class="produkt-form-section">
                <h4>💬 Tooltips</h4>
                <div class="produkt-form-group">
                    <label>Mietdauer-Tooltip</label>
                    <textarea name="duration_tooltip" rows="3"><?php echo esc_textarea($edit_item->duration_tooltip); ?></textarea>
                </div>
                <div class="produkt-form-group">
                    <label>Zustand-Tooltip</label>
                    <textarea name="condition_tooltip" rows="4"><?php echo esc_textarea($edit_item->condition_tooltip); ?></textarea>
                </div>
                <div class="produkt-form-group">
                    <label><input type="checkbox" name="show_tooltips" value="1" <?php checked($edit_item->show_tooltips ?? 1, 1); ?>> Tooltips auf Produktseite anzeigen</label>
                </div>
            </div>
        </div><!-- end tab-tooltips -->

        <div id="tab-pricing" class="produkt-subtab-content">
            <div class="produkt-form-section">
                <h4>💲 Preiseinstellungen</h4>
                <div class="produkt-form-row">
                    <div class="produkt-form-group">
                        <label>Preis-Label</label>
                        <input type="text" name="price_label" value="<?php echo isset($edit_item->price_label) ? esc_attr($edit_item->price_label) : ''; ?>" placeholder="Monatlicher Mietpreis">
                    </div>
                    <div class="produkt-form-group">
                        <label>Preiszeitraum</label>
                        <select name="price_period">
                            <option value="month" <?php isset($edit_item->price_period) ? selected($edit_item->price_period, 'month') : ''; ?>>pro Monat</option>
                            <option value="one-time" <?php isset($edit_item->price_period) ? selected($edit_item->price_period, 'one-time') : ''; ?>>einmalig</option>
                        </select>
                    </div>
                    <div class="produkt-form-group">
                        <label><input type="checkbox" name="vat_included" value="1" <?php isset($edit_item->vat_included) ? checked($edit_item->vat_included, 1) : ''; ?>> Mit MwSt.</label>
                    </div>
                </div>
            </div>
        </div><!-- end tab-pricing -->

        <!-- Actions -->
        <div class="produkt-form-actions">
            <button type="submit" name="submit_category" class="button button-primary button-large">
                ✅ Änderungen speichern
            </button>
            <a href="<?php echo admin_url('admin.php?page=produkt-categories&tab=list'); ?>" class="button button-large">
                ❌ Abbrechen
           </a>
            <a href="<?php echo admin_url('admin.php?page=produkt-categories&delete=' . $edit_item->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>"
               class="button button-large produkt-delete-button"
               onclick="return confirm('Sind Sie sicher, dass Sie dieses Produkt löschen möchten?\n\n\"<?php echo esc_js($edit_item->name); ?>\" und alle zugehörigen Daten werden unwiderruflich gelöscht!')"
               style="margin-left: auto;">
                🗑️ Löschen
            </a>
        </div>
    </form>
</div>

<style>
.produkt-subtab-nav {
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    gap: 10px;
}
.produkt-subtab {
    padding: 8px 12px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-bottom: none;
    color: #666;
    border-radius: 6px 6px 0 0;
    text-decoration: none;
    cursor: pointer;
}
.produkt-subtab.active {
    background: var(--produkt-primary);
    color: var(--produkt-text);
    font-weight: 600;
}
.produkt-subtab-content {
    display: none;
}
.produkt-subtab-content.active {
    display: block;
}
</style>

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

    // Subtab switching
    document.querySelectorAll('.produkt-subtab').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            var target = this.getAttribute('data-tab');
            document.querySelectorAll('.produkt-subtab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.produkt-subtab-content').forEach(function(c) { c.classList.remove('active'); });
            this.classList.add('active');
            var content = document.getElementById('tab-' + target);
            if (content) content.classList.add('active');
        });
    });

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
