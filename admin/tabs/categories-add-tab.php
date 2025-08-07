<?php
// Categories Add Tab Content
global $wpdb;

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

<div class="produkt-add-category">
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <button type="submit" name="submit_category" class="icon-btn categories-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
            </svg>
        </button>

        <div class="produkt-subtab-nav">
            <a href="#" class="produkt-subtab active" data-tab="general">Allgemein</a>
            <a href="#" class="produkt-subtab" data-tab="product">Produktseite</a>
            <a href="#" class="produkt-subtab" data-tab="features">Features</a>
            <a href="#" class="produkt-subtab" data-tab="filters">Filter</a>
            <a href="#" class="produkt-subtab" data-tab="inventory">Lagerverwaltung</a>
            <a href="#" class="produkt-subtab" data-tab="sorting">Sortierung</a>
        </div>

        <div id="tab-general" class="produkt-subtab-content active">
        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <h2>Grunddaten</h2>
                <p class="card-subline">Name und Shortcode</p>
                <div class="form-grid">
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

            <div class="dashboard-card">
                <h2>SEO-Einstellungen</h2>
                <p class="card-subline">Meta-Angaben</p>
                <div class="form-grid">
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

                <div class="produkt-form-group full-width">
                    <label>SEO-Beschreibung</label>
                    <textarea name="meta_description" rows="3" maxlength="160" placeholder="Beschreibung für Suchmaschinen (max. 160 Zeichen)"></textarea>
                    <div id="meta_description_counter" class="produkt-char-counter"></div>
                </div>
            </div>
        </div>
        </div><!-- end tab-general -->

        <div id="tab-product" class="produkt-subtab-content">
            <div class="dashboard-card">
                <h2>Seiteninhalte</h2>
                <p class="card-subline">Texte für die Produktseite</p>
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

            <div class="dashboard-card">
                <h2>Standard-Produktbild</h2>
                <p class="card-subline">Fallback-Bild</p>
                <div class="form-grid">
                    <div class="produkt-form-group full-width">
                        <label>Standard-Produktbild</label>
                        <div class="image-field-row">
                            <div id="default_image_preview" class="image-preview">
                                <span>Noch kein Bild vorhanden</span>
                            </div>
                            <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="default_image" aria-label="Bild auswählen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 26.2"><path d="M16,7c-3.9,0-7,3.1-7,7s3.1,7,7,7,7-3.1,7-7-3.1-7-7-7ZM16,19c-2.8,0-5-2.2-5-5s2.2-5,5-5,5,2.2,5,5-2.2,5-5,5ZM29,4h-4c-1,0-3-4-4-4h-10c-1.1,0-3.1,4-4,4H3c-1.7,0-3,1.3-3,3v16c0,1.7,1.3,3,3,3h26c1.7,0,3-1.3,3-3V7c0-1.7-1.3-3-3-3ZM30,22c0,1.1-.9,2-2,2H4c-1.1,0-2-.9-2-2v-14c0-1.1.9-2,2-2h4c.9,0,2.9-4,4-4h8c1,0,3,4,3.9,4h4.1c1.1,0,2,.9,2,2v14Z"/></svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="default_image" aria-label="Bild entfernen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="default_image" id="default_image" value="">
                        <small>Fallback-Bild wenn für Ausführungen kein spezifisches Bild hinterlegt ist</small>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h2>Content Blöcke</h2>
                <p class="card-subline">Abschnitte mit Text und Bild</p>
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

            <div class="dashboard-card">
                <h2>Details</h2>
                <p class="card-subline">Allgemeine Details</p>
                <div id="details-blocks-container">
                    <div class="produkt-page-block">
                        <div class="produkt-form-row">
                            <div class="produkt-form-group" style="flex:1;">
                                <label>Titel</label>
                                <input type="text" name="detail_block_titles[]">
                            </div>
                            <button type="button" class="button produkt-remove-detail-block">-</button>
                        </div>
                        <div class="produkt-form-group">
                            <label>Text</label>
                            <textarea name="detail_block_texts[]" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <button type="button" id="add-detail-block" class="button">+ Block hinzufügen</button>
            </div>

            <div class="dashboard-card">
                <h2>Technische Daten</h2>
                <p class="card-subline">Technische Informationen</p>
                <div id="tech-blocks-container">
                    <div class="produkt-page-block">
                        <div class="produkt-form-row">
                            <div class="produkt-form-group" style="flex:1;">
                                <label>Titel</label>
                                <input type="text" name="tech_block_titles[]">
                            </div>
                            <button type="button" class="button produkt-remove-tech-block">-</button>
                        </div>
                        <div class="produkt-form-group">
                            <label>Text</label>
                            <textarea name="tech_block_texts[]" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <button type="button" id="add-tech-block" class="button">+ Block hinzufügen</button>
            </div>

            <div class="dashboard-card">
                <h2>Lieferumfang</h2>
                <p class="card-subline">Im Paket enthalten</p>
                <div id="scope-blocks-container">
                    <div class="produkt-page-block">
                        <div class="produkt-form-row">
                            <div class="produkt-form-group" style="flex:1;">
                                <label>Titel</label>
                                <input type="text" name="scope_block_titles[]">
                            </div>
                            <button type="button" class="button produkt-remove-scope-block">-</button>
                        </div>
                        <div class="produkt-form-group">
                            <label>Text</label>
                            <textarea name="scope_block_texts[]" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <button type="button" id="add-scope-block" class="button">+ Block hinzufügen</button>
            </div>

            <div class="dashboard-card">
                <h2>Produktbewertung</h2>
                <p class="card-subline">Optionale Bewertung</p>
                <div class="produkt-form-group">
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="show_rating" value="1">
                        <span class="produkt-toggle-slider"></span>
                        <span>Produktbewertung anzeigen</span>
                    </label>
                </div>
                <div class="form-grid">
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
        </div><!-- end tab-product -->

        <div id="tab-features" class="produkt-subtab-content">
            <div class="dashboard-card">
                <h2>Features-Sektion</h2>
                <p class="card-subline">Bis zu vier Vorteile</p>
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

            <div class="dashboard-card">
                <h2>Accordion</h2>
                <p class="card-subline">Klappbare Informationen</p>
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
        </div><!-- end tab-features -->

        <div id="tab-filters" class="produkt-subtab-content">
            <div class="dashboard-card">
                <h2>Filter</h2>
                <p class="card-subline">Zuordnungen</p>
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
        </div><!-- end tab-filters -->

        <div id="tab-inventory" class="produkt-subtab-content">
            <div class="dashboard-card">
                <h2>Lagerverwaltung</h2>
                <p class="card-subline">Bestände und Extras</p>
                <p>Speichern Sie das Produkt, um die Lagerverwaltung zu aktivieren.</p>
            </div>
        </div><!-- end tab-inventory -->

        <div id="tab-sorting" class="produkt-subtab-content">
        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <h2>Sortierung</h2>
                <p class="card-subline">Reihenfolge und Kategorien</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Sortierung</label>
                        <input type="number" name="sort_order" min="0">
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
            </div>
        </div>
        </div><!-- end tab-sorting -->

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

    let detailBlockIndex = document.querySelectorAll('#details-blocks-container .produkt-page-block').length;
    document.getElementById('add-detail-block').addEventListener('click', function(e) {
        e.preventDefault();
        const div = document.createElement('div');
        div.className = 'produkt-page-block';
        div.innerHTML = '<div class="produkt-form-row">'
            + '<div class="produkt-form-group" style="flex:1;">'
            + '<label>Titel</label>'
            + '<input type="text" name="detail_block_titles[]" />'
            + '</div>'
            + '<button type="button" class="button produkt-remove-detail-block">-</button>'
            + '</div>'
            + '<div class="produkt-form-group"><label>Text</label>'
            + '<textarea name="detail_block_texts[]" rows="3"></textarea></div>';
        document.getElementById('details-blocks-container').appendChild(div);
        detailBlockIndex++;
    });
    document.getElementById('details-blocks-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('produkt-remove-detail-block')) {
            e.preventDefault();
            e.target.closest('.produkt-page-block').remove();
        }
    });

    let techBlockIndex = document.querySelectorAll('#tech-blocks-container .produkt-page-block').length;
    document.getElementById('add-tech-block').addEventListener('click', function(e) {
        e.preventDefault();
        const div = document.createElement('div');
        div.className = 'produkt-page-block';
        div.innerHTML = '<div class="produkt-form-row">'
            + '<div class="produkt-form-group" style="flex:1;">'
            + '<label>Titel</label>'
            + '<input type="text" name="tech_block_titles[]" />'
            + '</div>'
            + '<button type="button" class="button produkt-remove-tech-block">-</button>'
            + '</div>'
            + '<div class="produkt-form-group"><label>Text</label>'
            + '<textarea name="tech_block_texts[]" rows="3"></textarea></div>';
        document.getElementById('tech-blocks-container').appendChild(div);
        techBlockIndex++;
    });
    document.getElementById('tech-blocks-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('produkt-remove-tech-block')) {
            e.preventDefault();
            e.target.closest('.produkt-page-block').remove();
        }
    });

    let scopeBlockIndex = document.querySelectorAll('#scope-blocks-container .produkt-page-block').length;
    document.getElementById('add-scope-block').addEventListener('click', function(e) {
        e.preventDefault();
        const div = document.createElement('div');
        div.className = 'produkt-page-block';
        div.innerHTML = '<div class="produkt-form-row">'
            + '<div class="produkt-form-group" style="flex:1;">'
            + '<label>Titel</label>'
            + '<input type="text" name="scope_block_titles[]" />'
            + '</div>'
            + '<button type="button" class="button produkt-remove-scope-block">-</button>'
            + '</div>'
            + '<div class="produkt-form-group"><label>Text</label>'
            + '<textarea name="scope_block_texts[]" rows="3"></textarea></div>';
        document.getElementById('scope-blocks-container').appendChild(div);
        scopeBlockIndex++;
    });
    document.getElementById('scope-blocks-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('produkt-remove-scope-block')) {
            e.preventDefault();
            e.target.closest('.produkt-page-block').remove();
        }
    });

    // Inventory management popup logic
    document.querySelectorAll('.inventory-trigger').forEach(function(trig){
        trig.addEventListener('click', function(e){
            e.preventDefault();
            var id = this.dataset.variant || this.dataset.extra;
            var popup = document.getElementById('inv-popup-' + id);
            if (popup) {
                popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
            }
        });
    });
    document.querySelectorAll('.inventory-popup .inv-minus').forEach(function(btn){
        btn.addEventListener('click', function(){
            var target = document.getElementById(this.dataset.target);
            if (target) {
                target.value = Math.max(0, parseInt(target.value || 0) - 1);
                var id = this.dataset.variant || this.dataset.extra;
                if (id) updateAvail(id);
            }
        });
    });
    document.querySelectorAll('.inventory-popup .inv-plus').forEach(function(btn){
        btn.addEventListener('click', function(){
            var target = document.getElementById(this.dataset.target);
            if (target) {
                target.value = parseInt(target.value || 0) + 1;
                var id = this.dataset.variant || this.dataset.extra;
                if (id) updateAvail(id);
            }
        });
    });
    document.querySelectorAll('.inventory-popup input').forEach(function(inp){
        inp.addEventListener('input', function(){
            var id = this.id.replace(/^(avail|rent)-/, '');
            updateAvail(id);
        });
    });
    function updateAvail(id){
        var input = document.getElementById('avail-' + id);
        var span = document.querySelector('.inventory-trigger[data-variant="' + id + '"] .inventory-available-count, .inventory-trigger[data-extra="' + id + '"] .inventory-available-count');
        if (input && span) span.textContent = input.value;
    }

    function attachMediaButton(btn) {
        if (!btn) return;
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const field = document.getElementById(targetId);
            const preview = document.getElementById(targetId + '_preview');
            const frame = wp.media({ title: 'Bild auswählen', button: { text: 'Bild verwenden' }, multiple: false });
            frame.on('select', function() {
                const att = frame.state().get('selection').first().toJSON();
                if (field) field.value = att.url;
                if (preview) preview.innerHTML = '<img src="'+att.url+'" alt="">';
            });
            frame.open();
        });
    }
    document.querySelectorAll('.produkt-media-button').forEach(attachMediaButton);

    document.querySelectorAll('.produkt-remove-image').forEach(function(btn){
        btn.addEventListener('click', function(){
            const target = document.getElementById(this.dataset.target);
            const preview = document.getElementById(this.dataset.target + '_preview');
            if (target) target.value = '';
            if (preview) preview.innerHTML = '<span>Noch kein Bild vorhanden</span>';
        });
    });

    // Accordion fields are handled in admin-script.js
});
</script>

