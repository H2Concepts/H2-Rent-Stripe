<?php
// Categories Edit Tab Content

function produkt_category_icon($slug)
{
    $svg = file_get_contents(PRODUKT_PLUGIN_PATH . 'assets/settings-icons/' . $slug . '.svg');
    return str_replace('<svg', '<svg class="' . $slug . '-icon"', $svg);
}
?>

<div class="produkt-edit-category">
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
        <button type="submit" name="submit_category" class="icon-btn categories-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
            </svg>
        </button>
        <?php
        global $wpdb;
        $all_product_cats = \ProduktVerleih\Database::get_product_categories_tree();
        $selected_product_cats = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT category_id FROM {$wpdb->prefix}produkt_product_to_category WHERE produkt_id = %d",
                $edit_item->id
            )
        );
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
        $selected_filters = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT filter_id FROM {$wpdb->prefix}produkt_category_filters WHERE category_id = %d",
                $edit_item->id
            )
        );
        $modus = get_option('produkt_betriebsmodus', 'miete');
        ?>

        <div class="settings-layout">
            <nav class="settings-menu">
                <a href="#" class="produkt-subtab active" data-tab="general" aria-label="Allgemein" title="Allgemein">
                    <?php echo produkt_category_icon('general'); ?>
                </a>
                <a href="#" class="produkt-subtab" data-tab="product" aria-label="Produktseite" title="Produktseite">
                    <?php echo produkt_category_icon('product'); ?>
                </a>
                <a href="#" class="produkt-subtab" data-tab="features" aria-label="Features" title="Features">
                    <?php echo produkt_category_icon('features'); ?>
                </a>
                <a href="#" class="produkt-subtab" data-tab="filters" aria-label="Filter" title="Filter">
                    <?php echo produkt_category_icon('filters'); ?>
                </a>
                <a href="#" class="produkt-subtab" data-tab="inventory" aria-label="Lagerverwaltung" title="Lagerverwaltung">
                    <?php echo produkt_category_icon('inventory'); ?>
                </a>
                <a href="#" class="produkt-subtab" data-tab="sorting" aria-label="Sortierung" title="Sortierung">
                    <?php echo produkt_category_icon('sorting'); ?>
                </a>
            </nav>
            <div class="settings-content">
        <div id="tab-general" class="produkt-subtab-content active">
        <div class="produkt-form-sections">

        <!-- Grunddaten -->
        <div class="dashboard-card">
            <h2>Grunddaten</h2>
            <p class="card-subline">Name und Shortcode</p>
            <div class="form-grid">
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
        <div class="dashboard-card">
            <h2>SEO-Einstellungen</h2>
            <p class="card-subline">Meta-Angaben</p>
            <div class="form-grid">
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
            
            <div class="produkt-form-group full-width">
                <label>SEO-Beschreibung</label>
                <textarea name="meta_description" rows="3" maxlength="160"><?php echo esc_textarea($edit_item->meta_description ?? ''); ?></textarea>
                <div id="meta_description_counter" class="produkt-char-counter"></div>
            </div>
        </div>

        </div><!-- end produkt-form-sections -->

        </div><!-- end tab-general -->

        <div id="tab-product" class="produkt-subtab-content">
        <div class="produkt-form-sections">

        <!-- Seiteninhalte -->
        <div class="dashboard-card">
            <h2>Seiteninhalte</h2>
            <p class="card-subline">Texte für die Produktseite</p>
            
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
        <div class="dashboard-card">
            <h2>Standard-Produktbild</h2>
            <p class="card-subline">Fallback-Bild</p>
            <div class="form-grid">
                <div class="produkt-form-group full-width">
                    <label>Standard-Produktbild</label>
                    <div class="image-field-row">
                        <div id="default_image_preview" class="image-preview">
                            <?php if (!empty($edit_item->default_image)): ?>
                                <img src="<?php echo esc_url($edit_item->default_image); ?>" alt="">
                            <?php else: ?>
                                <span>Noch kein Bild vorhanden</span>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="default_image" aria-label="Bild auswählen">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 26.2"><path d="M16,7c-3.9,0-7,3.1-7,7s3.1,7,7,7,7-3.1,7-7-3.1-7-7-7ZM16,19c-2.8,0-5-2.2-5-5s2.2-5,5-5,5,2.2,5,5-2.2,5-5,5ZM29,4h-4c-1,0-3-4-4-4h-10c-1.1,0-3.1,4-4,4H3c-1.7,0-3,1.3-3,3v16c0,1.7,1.3,3,3,3h26c1.7,0,3-1.3,3-3V7c0-1.7-1.3-3-3-3ZM30,22c0,1.1-.9,2-2,2H4c-1.1,0-2-.9-2-2v-14c0-1.1.9-2,2-2h4c.9,0,2.9-4,4-4h8c1,0,3,4,3.9,4h4.1c1.1,0,2,.9,2,2v14Z"/></svg>
                        </button>
                        <button type="button" class="icon-btn produkt-remove-image" data-target="default_image" aria-label="Bild entfernen">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                        </button>
                    </div>
                    <input type="hidden" name="default_image" id="default_image" value="<?php echo esc_attr($edit_item->default_image); ?>">
                    <small>Fallback-Bild wenn für Ausführungen kein spezifisches Bild hinterlegt ist</small>
                </div>
            </div>
        </div>

        

        <!-- Content Blöcke -->
        <div class="dashboard-card">
            <h2>Content Blöcke</h2>
            <p class="card-subline">Abschnitte mit Text und Bild</p>
            <?php
                $page_blocks = !empty($edit_item->page_blocks) ? json_decode($edit_item->page_blocks, true) : [];
                if (!is_array($page_blocks) || empty($page_blocks)) {
                    $page_blocks = [['title' => '', 'text' => '', 'image' => '', 'alt' => '']];
                }
            ?>
            <div id="page-blocks-container" class="produkt-form-sections">
                <?php foreach ($page_blocks as $idx => $block): ?>
                <div class="dashboard-card produkt-page-block removable-block">
                    <button type="button" class="icon-btn icon-btn-remove produkt-remove-page-block" aria-label="Block entfernen">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32.2"><path fill-rule="evenodd" d="M16,0C7.2,0,0,7.2,0,16s7.2,16,16,16,16-7.2,16-16S24.8,0,16,0ZM16,30c-7.7,0-14-6.3-14-14S8.3,2,16,2s14,6.3,14,14-6.3,14-14,14ZM22,15h-12c-.6,0-1,.4-1,1s.4,1,1,1h12c.6,0,1-.4,1-1s-.4-1-1-1Z"/></svg>
                    </button>
                    <div class="produkt-form-row">
                        <div class="produkt-form-group" style="flex:1;">
                            <label>Titel</label>
                            <input type="text" name="page_block_titles[]" value="<?php echo esc_attr($block['title']); ?>">
                        </div>
                    </div>
                    <div class="produkt-form-group">
                        <label>Text</label>
                        <textarea name="page_block_texts[]" rows="3"><?php echo esc_textarea($block['text']); ?></textarea>
                    </div>
                    <div class="produkt-form-group">
                        <label>Bild</label>
                        <div class="image-field-row">
                            <div id="page_block_image_<?php echo $idx; ?>_preview" class="image-preview">
                                <?php if (!empty($block['image'])): ?>
                                    <img src="<?php echo esc_url($block['image']); ?>" alt="">
                                <?php else: ?>
                                    <span>Noch kein Bild vorhanden</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="page_block_image_<?php echo $idx; ?>" aria-label="Bild auswählen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 26.2"><path d="M16,7c-3.9,0-7,3.1-7,7s3.1,7,7,7,7-3.1,7-7-3.1-7-7-7ZM16,19c-2.8,0-5-2.2-5-5s2.2-5,5-5,5,2.2,5,5-2.2,5-5,5ZM29,4h-4c-1,0-3-4-4-4h-10c-1.1,0-3.1,4-4,4H3c-1.7,0-3,1.3-3,3v16c0,1.7,1.3,3,3,3h26c1.7,0,3-1.3,3-3V7c0-1.7-1.3-3-3-3ZM30,22c0,1.1-.9,2-2,2H4c-1.1,0-2-.9-2-2v-14c0-1.1.9-2,2-2h4c.9,0,2.9-4,4-4h8c1,0,3,4,3.9,4h4.1c1.1,0,2,.9,2,2v14Z"/></svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="page_block_image_<?php echo $idx; ?>" aria-label="Bild entfernen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="page_block_images[]" id="page_block_image_<?php echo $idx; ?>" value="<?php echo esc_attr($block['image']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Alt-Text</label>
                        <input type="text" name="page_block_alts[]" value="<?php echo esc_attr($block['alt'] ?? ''); ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-page-block" class="icon-btn add-category-btn" aria-label="Block hinzufügen">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3"><path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/><path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/></svg>
            </button>
        </div>

        <div class="dashboard-card">
            <h2>Details</h2>
            <p class="card-subline">Allgemeine Details</p>
            <?php
                $detail_blocks = !empty($edit_item->detail_blocks) ? json_decode($edit_item->detail_blocks, true) : [];
                if (!is_array($detail_blocks) || empty($detail_blocks)) {
                    $detail_blocks = [['title' => '', 'text' => '']];
                }
            ?>
            <div id="details-blocks-container" class="produkt-form-sections">
                <?php foreach ($detail_blocks as $idx => $block): ?>
                <div class="dashboard-card produkt-page-block removable-block">
                    <button type="button" class="icon-btn icon-btn-remove produkt-remove-detail-block" aria-label="Block entfernen">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32.2"><path fill-rule="evenodd" d="M16,0C7.2,0,0,7.2,0,16s7.2,16,16,16,16-7.2,16-16S24.8,0,16,0ZM16,30c-7.7,0-14-6.3-14-14S8.3,2,16,2s14,6.3,14,14-6.3,14-14,14ZM22,15h-12c-.6,0-1,.4-1,1s.4,1,1,1h12c.6,0,1-.4,1-1s-.4-1-1-1Z"/></svg>
                    </button>
                    <div class="produkt-form-row">
                        <div class="produkt-form-group" style="flex:1;">
                            <label>Titel</label>
                            <input type="text" name="detail_block_titles[]" value="<?php echo esc_attr($block['title']); ?>">
                        </div>
                    </div>
                    <div class="produkt-form-group">
                        <label>Text</label>
                        <textarea name="detail_block_texts[]" rows="3"><?php echo esc_textarea($block['text']); ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-detail-block" class="icon-btn add-category-btn" aria-label="Block hinzufügen">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3"><path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/><path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/></svg>
            </button>
        </div>

        <div class="dashboard-card">
            <h2>Technische Daten</h2>
            <p class="card-subline">Technische Informationen</p>
            <?php
                $tech_blocks = !empty($edit_item->tech_blocks) ? json_decode($edit_item->tech_blocks, true) : [];
                if (!is_array($tech_blocks) || empty($tech_blocks)) {
                    $tech_blocks = [['title' => '', 'text' => '']];
                }
            ?>
            <div id="tech-blocks-container" class="produkt-form-sections">
                <?php foreach ($tech_blocks as $idx => $block): ?>
                <div class="dashboard-card produkt-page-block removable-block">
                    <button type="button" class="icon-btn icon-btn-remove produkt-remove-tech-block" aria-label="Block entfernen">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32.2"><path fill-rule="evenodd" d="M16,0C7.2,0,0,7.2,0,16s7.2,16,16,16,16-7.2,16-16S24.8,0,16,0ZM16,30c-7.7,0-14-6.3-14-14S8.3,2,16,2s14,6.3,14,14-6.3,14-14,14ZM22,15h-12c-.6,0-1,.4-1,1s.4,1,1,1h12c.6,0,1-.4,1-1s-.4-1-1-1Z"/></svg>
                    </button>
                    <div class="produkt-form-row">
                        <div class="produkt-form-group" style="flex:1;">
                            <label>Titel</label>
                            <input type="text" name="tech_block_titles[]" value="<?php echo esc_attr($block['title']); ?>">
                        </div>
                    </div>
                    <div class="produkt-form-group">
                        <label>Text</label>
                        <textarea name="tech_block_texts[]" rows="3"><?php echo esc_textarea($block['text']); ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-tech-block" class="icon-btn add-category-btn" aria-label="Block hinzufügen">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3"><path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/><path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/></svg>
            </button>
        </div>

        <div class="dashboard-card">
            <h2>Lieferumfang</h2>
            <p class="card-subline">Im Paket enthalten</p>
            <?php
                $scope_blocks = !empty($edit_item->scope_blocks) ? json_decode($edit_item->scope_blocks, true) : [];
                if (!is_array($scope_blocks) || empty($scope_blocks)) {
                    $scope_blocks = [['title' => '', 'text' => '']];
                }
            ?>
            <div id="scope-blocks-container" class="produkt-form-sections">
                <?php foreach ($scope_blocks as $idx => $block): ?>
                <div class="dashboard-card produkt-page-block removable-block">
                    <button type="button" class="icon-btn icon-btn-remove produkt-remove-scope-block" aria-label="Block entfernen">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32.2"><path fill-rule="evenodd" d="M16,0C7.2,0,0,7.2,0,16s7.2,16,16,16,16-7.2,16-16S24.8,0,16,0ZM16,30c-7.7,0-14-6.3-14-14S8.3,2,16,2s14,6.3,14,14-6.3,14-14,14ZM22,15h-12c-.6,0-1,.4-1,1s.4,1,1,1h12c.6,0,1-.4,1-1s-.4-1-1-1Z"/></svg>
                    </button>
                    <div class="produkt-form-row">
                        <div class="produkt-form-group" style="flex:1;">
                            <label>Titel</label>
                            <input type="text" name="scope_block_titles[]" value="<?php echo esc_attr($block['title']); ?>">
                        </div>
                    </div>
                    <div class="produkt-form-group">
                        <label>Text</label>
                        <textarea name="scope_block_texts[]" rows="3"><?php echo esc_textarea($block['text']); ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-scope-block" class="icon-btn add-category-btn" aria-label="Block hinzufügen">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3"><path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/><path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/></svg>
            </button>
        </div>
        


        <!-- Produktbewertung -->
        <div class="dashboard-card">
            <div class="card-header-flex">
                <div>
                    <h2>Produktbewertung</h2>
                    <p class="card-subline">Optionale Bewertung</p>
                </div>
                <label class="produkt-toggle-label">
                    <input type="checkbox" name="show_rating" value="1" <?php checked($edit_item->show_rating ?? 0, 1); ?>>
                    <span class="produkt-toggle-slider"></span>
                    <span>Produktbewertung anzeigen</span>
                </label>
            </div>
            <div class="form-grid">
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
        </div>
        </div><!-- end tab-product -->


        <div id="tab-features" class="produkt-subtab-content">
        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Features-Sektion</h2>
                        <p class="card-subline">Bis zu vier Vorteile</p>
                    </div>
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="show_features" value="1" <?php checked($edit_item->show_features ?? 1, 1); ?>>
                        <span class="produkt-toggle-slider"></span>
                        <span>Features-Sektion anzeigen</span>
                    </label>
                </div>
                <div class="produkt-form-group">
                    <label>Features-Überschrift</label>
                    <input type="text" name="features_title" value="<?php echo esc_attr($edit_item->features_title); ?>" placeholder="z.B. Warum unser Produkt?">
                </div>
            </div>

            <div class="features-grid">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="dashboard-card">
                    <h3>Feature <?php echo $i; ?></h3>
                    <p class="card-subline">Titel, Icon &amp; Beschreibung</p>
                    <div class="produkt-form-group">
                        <label>Titel</label>
                        <input type="text" name="feature_<?php echo $i; ?>_title" value="<?php echo esc_attr($edit_item->{'feature_' . $i . '_title'}); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Icon-Bild</label>
                        <div class="image-field-row">
                            <div id="feature_<?php echo $i; ?>_icon_preview" class="image-preview">
                                <?php if (!empty($edit_item->{'feature_' . $i . '_icon'})): ?>
                                    <img src="<?php echo esc_url($edit_item->{'feature_' . $i . '_icon'}); ?>" alt="Feature <?php echo $i; ?> Icon">
                                <?php else: ?>
                                    <span>Noch kein Bild vorhanden</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="feature_<?php echo $i; ?>_icon" aria-label="Bild auswählen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 26.2"><path d="M16,7c-3.9,0-7,3.1-7,7s3.1,7,7,7,7-3.1,7-7-3.1-7-7-7ZM16,19c-2.8,0-5-2.2-5-5s2.2-5,5-5,5,2.2,5,5-2.2,5-5,5ZM29,4h-4c-1,0-3-4-4-4h-10c-1.1,0-3.1,4-4,4H3c-1.7,0-3,1.3-3,3v16c0,1.7,1.3,3,3,3h26c1.7,0,3-1.3,3-3V7c0-1.7-1.3-3-3-3ZM30,22c0,1.1-.9,2-2,2H4c-1.1,0-2-.9-2-2v-14c0-1.1.9-2,2-2h4c.9,0,2.9-4,4-4h8c1,0,3,4,3.9,4h4.1c1.1,0,2,.9,2,2v14Z"/></svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="feature_<?php echo $i; ?>_icon" aria-label="Bild entfernen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="feature_<?php echo $i; ?>_icon" id="feature_<?php echo $i; ?>_icon" value="<?php echo esc_attr($edit_item->{'feature_' . $i . '_icon'}); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Beschreibung</label>
                        <textarea name="feature_<?php echo $i; ?>_description" rows="2"><?php echo esc_textarea($edit_item->{'feature_' . $i . '_description'}); ?></textarea>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div class="dashboard-card">
                <h2>Accordion</h2>
                <p class="card-subline">Klappbare Informationen</p>
                <div id="accordion-container">
                    <?php
                    $accordion_data = !empty($edit_item->accordion_data) ? json_decode($edit_item->accordion_data, true) : [];
                    if (!is_array($accordion_data) || empty($accordion_data)) {
                        $accordion_data = [['title' => '', 'content' => '']];
                    }
                    foreach ($accordion_data as $idx => $acc): ?>
                    <div class="produkt-accordion-group removable-block">
                        <button type="button" class="icon-btn icon-btn-remove produkt-remove-accordion" aria-label="Accordion entfernen">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32.2"><path fill-rule="evenodd" d="M16,0C7.2,0,0,7.2,0,16s7.2,16,16,16,16-7.2,16-16S24.8,0,16,0ZM16,30c-7.7,0-14-6.3-14-14S8.3,2,16,2s14,6.3,14,14-6.3,14-14,14ZM22,15h-12c-.6,0-1,.4-1,1s.4,1,1,1h12c.6,0,1-.4,1-1s-.4-1-1-1Z"/></svg>
                        </button>
                        <div class="produkt-form-row">
                            <div class="produkt-form-group" style="flex:1;">
                                <label>Titel</label>
                                <input type="text" name="accordion_titles[]" value="<?php echo esc_attr($acc['title']); ?>">
                            </div>
                        </div>
                        <div class="produkt-form-group">
                            <?php wp_editor($acc['content'], 'accordion_content_' . $idx . '_edit', ['textarea_name' => 'accordion_contents[]', 'textarea_rows' => 3, 'media_buttons' => false]); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-accordion" class="icon-btn add-category-btn" aria-label="Accordion hinzufügen">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3"><path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/><path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/></svg>
            </button>
            </div>
        </div>

        </div><!-- end tab-features -->

        <div id="tab-filters" class="produkt-subtab-content">
        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Filter</h2>
                        <p class="card-subline">Filter für diese Kategorie</p>
                    </div>
                    <div class="produkt-filter-form product-search-bar">
                        <div class="search-input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="search-icon">
                                <path d="M10 2a8 8 0 105.3 14.1l4.3 4.3a1 1 0 101.4-1.4l-4.3-4.3A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/>
                            </svg>
                            <input type="text" id="filter-search" placeholder="Filter suchen...">
                        </div>
                    </div>
                </div>
            </div>

            <div id="filter-grid" class="filter-grid">
                <?php foreach ($filter_groups as $group): ?>
                <div class="dashboard-card">
                    <h3><?php echo esc_html($group->name); ?></h3>
                    <?php foreach ($filters_by_group[$group->id] as $f): ?>
                    <label class="produkt-filter-item">
                        <input type="checkbox" name="filters[]" value="<?php echo $f->id; ?>" <?php checked(in_array($f->id, $selected_filters)); ?>> <?php echo esc_html($f->name); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        </div><!-- end tab-filters -->

        <div id="tab-inventory" class="produkt-subtab-content">
        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <h2>Lagerverwaltung</h2>
                <p class="card-subline">Bestände verwalten</p>
                <?php
                    $variants = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d ORDER BY sort_order, name",
                            $edit_item->id
                        )
                    );
                ?>
                <?php if (empty($variants)): ?>
                    <p>Bitte zuerst eine Ausführung erstellen.</p>
                <?php else: ?>
                    <table class="activity-table produkt-inventory-table">
                        <thead>
                            <tr>
                                <th>Ausführung</th>
                                <th>Preis</th>
                                <th>Menge</th>
                                <th>SKU</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($variants as $v): ?>
                            <tr>
                                <td><?php echo esc_html($v->name); ?></td>
                                <?php
                                    $price_val = ($modus === 'kauf')
                                        ? $v->verkaufspreis_einmalig
                                        : $v->base_price;
                                ?>
                                <td><?php echo number_format((float)$price_val, 2, ',', '.'); ?>€</td>
                                <td class="inventory-cell">
                                    <div class="inventory-trigger" data-variant="<?php echo $v->id; ?>">
                                        <span class="inventory-available-count"><?php echo intval($v->stock_available); ?></span>
                                    </div>
                                    <div class="inventory-popup" id="inv-popup-<?php echo $v->id; ?>">
                                        <label>Verfügbar</label>
                                        <div class="quantity-control">
                                            <button type="button" class="inv-minus" data-target="avail-<?php echo $v->id; ?>" data-variant="<?php echo $v->id; ?>">-</button>
                                            <input type="number" id="avail-<?php echo $v->id; ?>" name="stock_available[<?php echo $v->id; ?>]" value="<?php echo intval($v->stock_available); ?>" min="0">
                                            <button type="button" class="inv-plus" data-target="avail-<?php echo $v->id; ?>" data-variant="<?php echo $v->id; ?>">+</button>
                                        </div>
                                        <label>In Vermietung</label>
                                        <div class="quantity-control">
                                            <button type="button" class="inv-minus" data-target="rent-<?php echo $v->id; ?>">-</button>
                                            <input type="number" id="rent-<?php echo $v->id; ?>" name="stock_rented[<?php echo $v->id; ?>]" value="<?php echo intval($v->stock_rented); ?>" min="0">
                                            <button type="button" class="inv-plus" data-target="rent-<?php echo $v->id; ?>">+</button>
                                        </div>
                                    </div>
                                </td>
                                <td><input type="text" name="sku[<?php echo $v->id; ?>]" value="<?php echo esc_attr($v->sku); ?>"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <h2>Extras</h2>
                <p class="card-subline">Bestände verwalten</p>
                <?php
                    $extras = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}produkt_extras WHERE category_id = %d ORDER BY sort_order, name",
                            $edit_item->id
                        )
                    );
                ?>
                <?php if (empty($extras)): ?>
                    <p>Bitte zuerst ein Extra erstellen.</p>
                <?php else: ?>
                    <table class="activity-table produkt-inventory-table">
                        <thead>
                            <tr>
                                <th>Extra</th>
                                <th>Preis</th>
                                <th>Menge</th>
                                <th>SKU</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($extras as $e): ?>
                            <tr>
                                <td><?php echo esc_html($e->name); ?></td>
                                <?php
                                    $display_price = (float) $e->price;
                                    $price_id = $modus === 'kauf'
                                        ? ($e->stripe_price_id_sale ?: ($e->stripe_price_id ?? ''))
                                        : ($e->stripe_price_id_rent ?: ($e->stripe_price_id ?? ''));
                                    if ($price_id) {
                                        $p = \ProduktVerleih\StripeService::get_price_amount($price_id);
                                        if (!is_wp_error($p)) {
                                            $display_price = $p;
                                        }
                                    }
                                ?>
                                <td><?php echo number_format((float) $display_price, 2, ',', '.'); ?>€</td>
                                <td class="inventory-cell">
                                    <div class="inventory-trigger" data-extra="<?php echo $e->id; ?>">
                                        <span class="inventory-available-count"><?php echo intval($e->stock_available); ?></span>
                                    </div>
                                    <div class="inventory-popup" id="inv-popup-<?php echo $e->id; ?>">
                                        <label>Verfügbar</label>
                                        <div class="quantity-control">
                                            <button type="button" class="inv-minus" data-target="avail-<?php echo $e->id; ?>" data-extra="<?php echo $e->id; ?>">-</button>
                                            <input type="number" id="avail-<?php echo $e->id; ?>" name="extra_stock_available[<?php echo $e->id; ?>]" value="<?php echo intval($e->stock_available); ?>" min="0">
                                            <button type="button" class="inv-plus" data-target="avail-<?php echo $e->id; ?>" data-extra="<?php echo $e->id; ?>">+</button>
                                        </div>
                                        <label>In Vermietung</label>
                                        <div class="quantity-control">
                                            <button type="button" class="inv-minus" data-target="rent-<?php echo $e->id; ?>">-</button>
                                            <input type="number" id="rent-<?php echo $e->id; ?>" name="extra_stock_rented[<?php echo $e->id; ?>]" value="<?php echo intval($e->stock_rented); ?>" min="0">
                                            <button type="button" class="inv-plus" data-target="rent-<?php echo $e->id; ?>">+</button>
                                        </div>
                                    </div>
                                </td>
                                <td><input type="text" name="extra_sku[<?php echo $e->id; ?>]" value="<?php echo esc_attr($e->sku); ?>"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        </div><!-- end tab-inventory -->

        <div id="tab-sorting" class="produkt-subtab-content">
        <div class="produkt-form-sections">
        <div class="dashboard-card">
            <h2>Sortierung</h2>
            <p class="card-subline">Reihenfolge festlegen</p>
            <div class="form-grid">
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_item->sort_order; ?>" min="0">
                </div>
            </div>
        </div>
        <div class="dashboard-card">
            <h2>Kategorien</h2>
            <p class="card-subline">Bitte Kategorie auswählen</p>
            <div class="category-accordion produkt-accordions">
                <?php $opened = false; $first_cat = true; foreach ($all_product_cats as $cat): if (!empty($cat->parent_id)) continue; ?>
                <?php $has_sel = false; foreach ($cat->children as $child){ if(in_array($child->id,$selected_product_cats)){ $has_sel = true; break; } } ?>
                <?php $active = (!$opened && ($has_sel || in_array($cat->id,$selected_product_cats) || $first_cat)) ? ' active' : ''; if($active) $opened = true; $first_cat = false; ?>
                <div class="produkt-accordion-item<?php echo $active; ?>">
                    <button type="button" class="produkt-accordion-header"><?php echo esc_html($cat->name); ?></button>
                    <div class="produkt-accordion-content">
                        <div class="category-tiles">
                            <?php foreach ($cat->children as $child): ?>
                            <div class="category-tile<?php echo in_array($child->id,$selected_product_cats) ? ' selected' : ''; ?>" data-id="<?php echo $child->id; ?>" data-parent="<?php echo $cat->id; ?>"><?php echo esc_html($child->name); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="selected-categories">
                <?php foreach ($selected_product_cats as $cid): ?>
                <input type="hidden" name="product_categories[]" value="<?php echo esc_attr($cid); ?>">
                <?php endforeach; ?>
            </div>
            <p class="description">Wählen Sie eine oder mehrere Kategorien für dieses Produkt.</p>
        </div>
        </div>
        </div><!-- end tab-sorting -->
            </div>
        </div>

        <!-- Actions -->
        <div class="produkt-form-actions">
            <a href="<?php echo admin_url('admin.php?page=produkt-categories&delete=' . $edit_item->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>"
               class="button button-large produkt-delete-button"
               onclick="return confirm('Sind Sie sicher, dass Sie dieses Produkt löschen möchten?\n\n\"<?php echo esc_js($edit_item->name); ?>\" und alle zugehörigen Daten werden unwiderruflich gelöscht!')">
                🗑️ Löschen
            </a>
        </div>
    </form>
</div>

<style>
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
            document.querySelectorAll('.produkt-subtab').forEach(function(t) {
                t.classList.remove('active');
                var svg = t.querySelector('svg');
                if (svg) svg.classList.remove('activ');
            });
            document.querySelectorAll('.produkt-subtab-content').forEach(function(c) { c.classList.remove('active'); });
            this.classList.add('active');
            var svgActive = this.querySelector('svg');
            if (svgActive) svgActive.classList.add('activ');
            var content = document.getElementById('tab-' + target);
            if (content) {
                content.classList.add('active');
                if (target === 'sorting') {
                    content.querySelectorAll('.produkt-accordion-item.active .produkt-accordion-content').forEach(function(c) {
                        c.style.maxHeight = c.scrollHeight + 'px';
                    });
                }
            }
        });
    });

    document.querySelectorAll('.produkt-subtab.active svg').forEach(function(svg) {
        svg.classList.add('activ');
    });

    const filterSearch = document.getElementById('filter-search');
    if (filterSearch) {
        filterSearch.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#filter-grid .produkt-filter-item').forEach(function(el) {
                el.style.display = el.textContent.toLowerCase().indexOf(term) !== -1 ? 'block' : 'none';
            });
        });
    }

    let pageBlockIndex = document.querySelectorAll('#page-blocks-container .produkt-page-block').length;
    document.getElementById('add-page-block').addEventListener('click', function(e) {
        e.preventDefault();
        const id = 'page_block_image_' + pageBlockIndex;
        const div = document.createElement('div');
        div.className = 'dashboard-card produkt-page-block removable-block';
        div.innerHTML = '<button type="button" class="icon-btn icon-btn-remove produkt-remove-page-block" aria-label="Block entfernen"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32.2"><path fill-rule="evenodd" d="M16,0C7.2,0,0,7.2,0,16s7.2,16,16,16,16-7.2,16-16S24.8,0,16,0ZM16,30c-7.7,0-14-6.3-14-14S8.3,2,16,2s14,6.3,14,14-6.3,14-14,14ZM22,15h-12c-.6,0-1,.4-1,1s.4,1,1,1h12c.6,0,1-.4,1-1s-.4-1-1-1Z"/></svg></button>'
            + '<div class="produkt-form-row">'
            + '<div class="produkt-form-group" style="flex:1;">'
            + '<label>Titel</label>'
            + '<input type="text" name="page_block_titles[]" />'
            + '</div>'
            + '</div>'
            + '<div class="produkt-form-group"><label>Text</label>'
            + '<textarea name="page_block_texts[]" rows="3"></textarea></div>'
            + '<div class="produkt-form-group"><label>Bild</label>'
            + '<div class="image-field-row">'
            + '<div id="' + id + '_preview" class="image-preview"><span>Noch kein Bild vorhanden</span></div>'
            + '<button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="' + id + '" aria-label="Bild auswählen"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 26.2"><path d="M16,7c-3.9,0-7,3.1-7,7s3.1,7,7,7,7-3.1,7-7-3.1-7-7-7ZM16,19c-2.8,0-5-2.2-5-5s2.2-5,5-5,5,2.2,5,5-2.2,5-5,5ZM29,4h-4c-1,0-3-4-4-4h-10c-1.1,0-3.1,4-4,4H3c-1.7,0-3,1.3-3,3v16c0,1.7,1.3,3,3,3h26c1.7,0,3-1.3,3-3V7c0-1.7-1.3-3-3-3ZM30,22c0,1.1-.9,2-2,2H4c-1.1,0-2-.9-2-2v-14c0-1.1.9-2,2-2h4c.9,0,2.9-4,4-4h8c1,0,3,4,3.9,4h4.1c1.1,0,2,.9,2,2v14Z"/></svg></button>'
            + '<button type="button" class="icon-btn produkt-remove-image" data-target="' + id + '" aria-label="Bild entfernen"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg></button>'
            + '</div>'
            + '<input type="hidden" name="page_block_images[]" id="' + id + '" value="">'
            + '</div>'
            + '<div class="produkt-form-group"><label>Alt-Text</label>'
            + '<input type="text" name="page_block_alts[]"></div>';
        document.getElementById('page-blocks-container').appendChild(div);
        attachMediaButton(div.querySelector('.produkt-media-button'));
        attachRemoveImage(div.querySelector('.produkt-remove-image'));
        pageBlockIndex++;
    });

    document.getElementById('page-blocks-container').addEventListener('click', function(e) {
        const btn = e.target.closest('.produkt-remove-page-block');
        if (btn) {
            e.preventDefault();
            btn.closest('.produkt-page-block').remove();
        }
    });

    let detailBlockIndex = document.querySelectorAll('#details-blocks-container .produkt-page-block').length;
    document.getElementById('add-detail-block').addEventListener('click', function(e) {
        e.preventDefault();
        const div = document.createElement('div');
        div.className = 'dashboard-card produkt-page-block removable-block';
        div.innerHTML = '<button type="button" class="icon-btn icon-btn-remove produkt-remove-detail-block" aria-label="Block entfernen"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32.2"><path fill-rule="evenodd" d="M16,0C7.2,0,0,7.2,0,16s7.2,16,16,16,16-7.2,16-16S24.8,0,16,0ZM16,30c-7.7,0-14-6.3-14-14S8.3,2,16,2s14,6.3,14,14-6.3,14-14,14ZM22,15h-12c-.6,0-1,.4-1,1s.4,1,1,1h12c.6,0,1-.4,1-1s-.4-1-1-1Z"/></svg></button>'
            + '<div class="produkt-form-row">'
            + '<div class="produkt-form-group" style="flex:1;">'
            + '<label>Titel</label>'
            + '<input type="text" name="detail_block_titles[]" />'
            + '</div>'
            + '</div>'
            + '<div class="produkt-form-group"><label>Text</label>'
            + '<textarea name="detail_block_texts[]" rows="3"></textarea></div>';
        document.getElementById('details-blocks-container').appendChild(div);
        detailBlockIndex++;
    });
    document.getElementById('details-blocks-container').addEventListener('click', function(e) {
        const btn = e.target.closest('.produkt-remove-detail-block');
        if (btn) {
            e.preventDefault();
            btn.closest('.produkt-page-block').remove();
        }
    });

    let techBlockIndex = document.querySelectorAll('#tech-blocks-container .produkt-page-block').length;
    document.getElementById('add-tech-block').addEventListener('click', function(e) {
        e.preventDefault();
        const div = document.createElement('div');
        div.className = 'dashboard-card produkt-page-block removable-block';
        div.innerHTML = '<button type="button" class="icon-btn icon-btn-remove produkt-remove-tech-block" aria-label="Block entfernen"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32.2"><path fill-rule="evenodd" d="M16,0C7.2,0,0,7.2,0,16s7.2,16,16,16,16-7.2,16-16S24.8,0,16,0ZM16,30c-7.7,0-14-6.3-14-14S8.3,2,16,2s14,6.3,14,14-6.3,14-14,14ZM22,15h-12c-.6,0-1,.4-1,1s.4,1,1,1h12c.6,0,1-.4,1-1s-.4-1-1-1Z"/></svg></button>'
            + '<div class="produkt-form-row">'
            + '<div class="produkt-form-group" style="flex:1;">'
            + '<label>Titel</label>'
            + '<input type="text" name="tech_block_titles[]" />'
            + '</div>'
            + '</div>'
            + '<div class="produkt-form-group"><label>Text</label>'
            + '<textarea name="tech_block_texts[]" rows="3"></textarea></div>';
        document.getElementById('tech-blocks-container').appendChild(div);
        techBlockIndex++;
    });
    document.getElementById('tech-blocks-container').addEventListener('click', function(e) {
        const btn = e.target.closest('.produkt-remove-tech-block');
        if (btn) {
            e.preventDefault();
            btn.closest('.produkt-page-block').remove();
        }
    });

    let scopeBlockIndex = document.querySelectorAll('#scope-blocks-container .produkt-page-block').length;
    document.getElementById('add-scope-block').addEventListener('click', function(e) {
        e.preventDefault();
        const div = document.createElement('div');
        div.className = 'dashboard-card produkt-page-block removable-block';
        div.innerHTML = '<button type="button" class="icon-btn icon-btn-remove produkt-remove-scope-block" aria-label="Block entfernen"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32.2"><path fill-rule="evenodd" d="M16,0C7.2,0,0,7.2,0,16s7.2,16,16,16,16-7.2,16-16S24.8,0,16,0ZM16,30c-7.7,0-14-6.3-14-14S8.3,2,16,2s14,6.3,14,14-6.3,14-14,14ZM22,15h-12c-.6,0-1,.4-1,1s.4,1,1,1h12c.6,0,1-.4,1-1s-.4-1-1-1Z"/></svg></button>'
            + '<div class="produkt-form-row">'
            + '<div class="produkt-form-group" style="flex:1;">'
            + '<label>Titel</label>'
            + '<input type="text" name="scope_block_titles[]" />'
            + '</div>'
            + '</div>'
            + '<div class="produkt-form-group"><label>Text</label>'
            + '<textarea name="scope_block_texts[]" rows="3"></textarea></div>';
        document.getElementById('scope-blocks-container').appendChild(div);
        scopeBlockIndex++;
    });
    document.getElementById('scope-blocks-container').addEventListener('click', function(e) {
        const btn = e.target.closest('.produkt-remove-scope-block');
        if (btn) {
            e.preventDefault();
            btn.closest('.produkt-page-block').remove();
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
    function attachRemoveImage(btn){
        if (!btn) return;
        btn.addEventListener('click', function(){
            const target = document.getElementById(this.dataset.target);
            const preview = document.getElementById(this.dataset.target + '_preview');
            if (target) target.value = '';
            if (preview) preview.innerHTML = '<span>Noch kein Bild vorhanden</span>';
        });
    }
    document.querySelectorAll('.produkt-media-button').forEach(attachMediaButton);
    document.querySelectorAll('.produkt-remove-image').forEach(attachRemoveImage);

    // Accordion fields are handled in admin-script.js
});
</script>
