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
        <?php
        global $wpdb;
        $all_product_cats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_product_categories ORDER BY name ASC");
        $selected_product_cats = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT category_id FROM {$wpdb->prefix}produkt_product_to_category WHERE produkt_id = %d",
                $edit_item->id
            )
        );
        ?>

        <div class="produkt-subtab-nav">
            <a href="#" class="produkt-subtab active" data-tab="general">Allgemein</a>
            <a href="#" class="produkt-subtab" data-tab="product">Produktseite</a>
            <a href="#" class="produkt-subtab" data-tab="features">Features</a>
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
                    <small>Max. 60 Zeichen für Google <span id="meta_title_counter" class="produkt-char-counter"></span></small>
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
                <div id="meta_description_counter" class="produkt-char-counter"></div>
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
            <div class="produkt-form-group">
                <label>Kategorien</label>
                <select name="product_categories[]" multiple style="width:100%; height:auto; min-height:100px;">
                    <?php foreach ($all_product_cats as $cat): ?>
                        <option value="<?php echo $cat->id; ?>" <?php echo in_array($cat->id, $selected_product_cats) ? 'selected' : ''; ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Wählen Sie eine oder mehrere Kategorien für dieses Produkt.</p>
            </div>
        </div>

        </div><!-- end tab-general -->

        <div id="tab-product" class="produkt-subtab-content">

        <!-- Seiteninhalte -->
        <div class="produkt-form-section">
            <h4>📄 Seiteninhalte</h4>
            
            <div class="produkt-form-group">
                <label>Kurzbeschreibung <small>für Produktübersichtsseite</small></label>
                <textarea name="short_description" rows="2"><?php echo esc_textarea($edit_item->short_description ?? ''); ?></textarea>
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

            <?php for ($i = 1; $i <= 4; $i++): ?>
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

    // Auto-generate shortcode from name
    const nameInput = document.querySelector('input[name="name"]');
    const shortcodeInput = document.querySelector('input[name="shortcode"]');
    let manualShortcode = false;
    if (shortcodeInput) {
        shortcodeInput.addEventListener('input', function() { manualShortcode = true; });
    }
    if (nameInput && shortcodeInput) {
        nameInput.addEventListener('input', function() {
            if (!manualShortcode) {
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

    function updateCharCounter(input, counter, min, max) {
        const len = input.value.length;
        counter.textContent = len + ' Zeichen';
        let cls = 'warning';
        if (len > max) { cls = 'error'; }
        else if (len >= min) { cls = 'ok'; }
        counter.className = 'produkt-char-counter ' + cls;
    }

    const mtInput = document.querySelector('input[name="meta_title"]');
    const mtCounter = document.getElementById('meta_title_counter');
    if (mtInput && mtCounter) {
        updateCharCounter(mtInput, mtCounter, 50, 60);
        mtInput.addEventListener('input', () => updateCharCounter(mtInput, mtCounter, 50, 60));
    }
    const mdInput = document.querySelector('textarea[name="meta_description"]');
    const mdCounter = document.getElementById('meta_description_counter');
    if (mdInput && mdCounter) {
        updateCharCounter(mdInput, mdCounter, 150, 160);
        mdInput.addEventListener('input', () => updateCharCounter(mdInput, mdCounter, 150, 160));
    }

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

    // Accordion fields are handled in admin-script.js
});
</script>
