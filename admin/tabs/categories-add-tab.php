<?php
// Categories Add Tab Content
global $wpdb;
?>

<div class="produkt-add-category">
    <div class="produkt-form-header">
        <h3>➕ Neues Produkt hinzufügen</h3>
        <p>Erstellen Sie eine Produkt und Produktseite individuellen Einstellungen und Konfigurationen.</p>
    </div>
    
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <?php
        $all_product_cats = \ProduktVerleih\Database::get_product_categories_tree();
        $filter_groups = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_filter_groups ORDER BY name");
        $filters_by_group = [];
        foreach ($filter_groups as $g) {
            $filters_by_group[$g->id] = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}produkt_filters WHERE group_id = %d ORDER BY name",
                    $g->id
                )
            );
        }
        ?>
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
            </div>
        </div>
        
        <!-- SEO-Einstellungen -->
        <div class="produkt-form-section">
            <h4>🔍 SEO-Einstellungen</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>SEO-Titel</label>
                    <input type="text" name="meta_title" maxlength="60" placeholder="Optimiert für Suchmaschinen">
                    <small>Max. 60 Zeichen für Google <span id="meta_title_counter" class="produkt-char-counter"></span></small>
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
                <div id="meta_description_counter" class="produkt-char-counter"></div>
            </div>
        </div>
        
        <!-- Seiteninhalte -->
        <div class="produkt-form-section">
            <h4>📄 Seiteninhalte</h4>

            <div class="produkt-form-group">
                <label>Kurzbeschreibung <small>für Produktübersichtsseite</small></label>
                <textarea name="short_description" rows="2" placeholder="Kurzer Text unter dem Titel"></textarea>
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

        <!-- Content Blöcke -->
        <div class="produkt-form-section">
            <h4>Content Blöcke</h4>
            <div id="page-blocks-container">
                <div class="produkt-page-block">
                    <div class="produkt-form-row">
                        <div class="produkt-form-group" style="flex:1;">
                            <label>Titel</label>
                            <input type="text" name="page_block_titles[]">
                        </div>
                        <button type="button" class="button produkt-remove-page-block">-</button>
                    </div>
                    <div class="produkt-form-group">
                        <label>Text</label>
                        <textarea name="page_block_texts[]" rows="3"></textarea>
                    </div>
                    <div class="produkt-form-group">
                        <label>Bild</label>
                        <div class="produkt-upload-area">
                            <input type="url" name="page_block_images[]" id="page_block_image_0">
                            <button type="button" class="button produkt-media-button" data-target="page_block_image_0">📁</button>
                        </div>
                    </div>
                    <div class="produkt-form-group">
                        <label>Alt-Text</label>
                        <input type="text" name="page_block_alts[]">
                    </div>
                </div>
            </div>
            <button type="button" id="add-page-block" class="button">+ Block hinzufügen</button>
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
            
            <?php for ($i = 1; $i <= 4; $i++): ?>
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
        <div class="produkt-form-group">
            <label>Kategorien</label>
                <select name="product_categories[]" multiple style="width:100%; height:auto; min-height:100px;">
                    <?php foreach ($all_product_cats as $cat): ?>
                        <option value="<?php echo $cat->id; ?>">
                            <?php echo str_repeat('--', $cat->depth) . ' ' . esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Wählen Sie eine oder mehrere Kategorien für dieses Produkt.</p>
            </div>
        </div>

        <div class="produkt-form-section">
            <h4>🔎 Filter</h4>
            <input type="text" id="filter-search" placeholder="Filter suchen..." style="max-width:300px;width:100%;">
            <div id="filter-list" class="produkt-filter-list" style="margin-top:10px;">
                <?php foreach ($filter_groups as $group): ?>
                    <strong><?php echo esc_html($group->name); ?></strong><br>
                    <?php foreach ($filters_by_group[$group->id] as $f): ?>
                    <label class="produkt-filter-item" style="display:block;margin-bottom:4px;">
                        <input type="checkbox" name="filters[]" value="<?php echo $f->id; ?>"> <?php echo esc_html($f->name); ?>
                    </label>
                    <?php endforeach; ?>
                <?php endforeach; ?>
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

    const filterSearch = document.getElementById('filter-search');
    if (filterSearch) {
        filterSearch.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#filter-list .produkt-filter-item').forEach(function(el) {
                el.style.display = el.textContent.toLowerCase().indexOf(term) !== -1 ? 'block' : 'none';
            });
        });
    }

    let pageBlockIndex = document.querySelectorAll('#page-blocks-container .produkt-page-block').length;
    document.getElementById('add-page-block').addEventListener('click', function(e) {
        e.preventDefault();
        const id = 'page_block_image_' + pageBlockIndex;
        const div = document.createElement('div');
        div.className = 'produkt-page-block';
        div.innerHTML = '<div class="produkt-form-row">'
            + '<div class="produkt-form-group" style="flex:1;">'
            + '<label>Titel</label>'
            + '<input type="text" name="page_block_titles[]" />'
            + '</div>'
            + '<button type="button" class="button produkt-remove-page-block">-</button>'
            + '</div>'
            + '<div class="produkt-form-group"><label>Text</label>'
            + '<textarea name="page_block_texts[]" rows="3"></textarea></div>'
            + '<div class="produkt-form-group"><label>Bild</label>'
            + '<div class="produkt-upload-area">'
            + '<input type="url" name="page_block_images[]" id="' + id + '">' 
            + '<button type="button" class="button produkt-media-button" data-target="' + id + '">📁</button>'
            + '</div></div>'
            + '<div class="produkt-form-group"><label>Alt-Text</label>'
            + '<input type="text" name="page_block_alts[]"></div>';
        document.getElementById('page-blocks-container').appendChild(div);
        attachMediaButton(div.querySelector('.produkt-media-button'));
        pageBlockIndex++;
    });

    document.getElementById('page-blocks-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('produkt-remove-page-block')) {
            e.preventDefault();
            e.target.closest('.produkt-page-block').remove();
        }
    });

    function attachMediaButton(btn) {
        if (!btn) return;
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const field = document.getElementById(targetId);
            const frame = wp.media({ title: 'Bild auswählen', button: { text: 'Bild verwenden' }, multiple: false });
            frame.on('select', function() {
                const att = frame.state().get('selection').first().toJSON();
                field.value = att.url;
            });
            frame.open();
        });
    }
    document.querySelectorAll('.produkt-media-button').forEach(attachMediaButton);

    // Accordion fields are handled in admin-script.js
});
</script>
