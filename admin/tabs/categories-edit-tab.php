<?php
// Categories Edit Tab Content
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
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
$all_product_cats = \ProduktVerleih\Database::get_product_categories_tree();
$selected_product_cats = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT category_id FROM {$wpdb->prefix}produkt_product_to_category WHERE produkt_id = %d",
        $edit_item->id
    )
);
$modus = get_option('produkt_betriebsmodus', 'miete');
?>

<div class="produkt-edit-category">
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
        <button type="submit" name="submit" class="icon-btn categories-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3"><path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/><path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/></svg>
        </button>

        <div class="settings-layout">
            <nav class="settings-menu category-edit-menu">
                <a href="#" class="active" data-tab="general" aria-label="Allgemein">
                    <svg class="general-icon active" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="style=linear"><g id="edit"><path id="vector" d="M18.4101 3.6512L20.5315 5.77252C21.4101 6.6512 21.4101 8.07582 20.5315 8.9545L9.54019 19.9458C9.17774 20.3082 8.70239 20.536 8.19281 20.5915L4.57509 20.9856C3.78097 21.072 3.11061 20.4017 3.1971 19.6076L3.59111 15.9898C3.64661 15.4803 3.87444 15.0049 4.23689 14.6425L3.70656 14.1121L4.23689 14.6425L15.2282 3.6512C16.1068 2.77252 17.5315 2.77252 18.4101 3.6512Z" stroke="#000000" stroke-width="1.5"/><path id="vector_2" d="M15.2282 3.6512C16.1068 2.77252 17.5315 2.77252 18.4101 3.6512L20.5315 5.77252C21.4101 6.6512 21.4101 8.07582 20.5315 8.9545L18.7283 10.7576L13.425 5.45432L15.2282 3.6512Z" stroke="#000000" stroke-width="1.5"/></g></g></svg>
                </a>
                <a href="#" data-tab="product" aria-label="Produktseite">
                    <svg class="product-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="style=linear"><g id="document"><path id="rec" d="M3 7C3 4.23858 5.23858 2 8 2H16C18.7614 2 21 4.23858 21 7V17C21 19.7614 18.7614 22 16 22H8C5.23858 22 3 19.7614 3 17V7Z" stroke="#000000" stroke-width="1.5"/><path id="line" d="M8 8.2002H16" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path id="line_2" d="M8 12.2002H16" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path id="line_3" d="M9 16.2002H15" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></g></g></svg>
                </a>
                <a href="#" data-tab="features" aria-label="Features">
                    <svg class="features-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="style=linear"><g id="heart"><path id="vector" d="M12.62 20.8101C12.28 20.9301 11.72 20.9301 11.38 20.8101C8.48 19.8201 2 15.6901 2 8.6901C2 5.6001 4.49 3.1001 7.56 3.1001C9.38 3.1001 10.99 3.9801 12 5.3401C13.01 3.9801 14.63 3.1001 16.44 3.1001C19.51 3.1001 22 5.6001 22 8.6901C22 15.6901 15.52 19.8201 12.62 20.8101Z" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></g></g></svg>
                </a>
                <a href="#" data-tab="filters" aria-label="Filter">
                    <svg class="filter-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="style=linear"><g id="filter"><path id="vector" d="M18 2H6C4.34315 2 3 3.34315 3 5V6.54417C3 7.43969 3.40007 8.28839 4.09085 8.85829L7.36369 11.5584C8.05448 12.1283 8.45455 12.977 8.45455 13.8725V20.124C8.45455 21.7487 10.2893 22.6955 11.6135 21.754L14.7044 19.5563C15.232 19.1812 15.5455 18.5738 15.5455 17.9263V13.8725C15.5455 12.977 15.9455 12.1283 16.6363 11.5584L19.9091 8.85829C20.5999 8.28839 21 7.43969 21 6.54417V5C21 3.34315 19.6569 2 18 2Z" stroke="#000000" stroke-width="1.5"/></g></g></svg>
                </a>
                <a href="#" data-tab="inventory" aria-label="Lagerverwaltung">
                    <svg class="inventory-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="style=linear" clip-path="url(#clip0)"><g id="home-smile"><path id="vector" d="M19 23H5C3.34315 23 2 21.6569 2 20V11.563C2 10.4094 2.49808 9.31192 3.36639 8.55236L10.0248 2.72784C11.1558 1.7385 12.8442 1.73851 13.9752 2.72784L20.6336 8.55236C21.5019 9.31192 22 10.4094 22 11.563V20C22 21.6569 20.6569 23 19 23Z" stroke="#000000" stroke-width="1.5" stroke-linecap="round"/><path id="vector_2" d="M7.48433 15.1468C7.89041 16.001 8.53042 16.7224 9.33006 17.2275C10.1297 17.7325 11.0562 18.0004 12.002 18C12.9477 17.9996 13.874 17.731 14.6732 17.2254C15.4725 16.7197 16.1119 15.9977 16.5173 15.1433" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></g></g><defs><clipPath id="clip0"><rect width="24" height="24" fill="white" transform="translate(0 24) rotate(-90)"/></clipPath></defs></svg>
                </a>
            </nav>
            <div class="settings-content">
                <div id="tab-general" class="produkt-subtab-content active">
                    <div class="produkt-form-sections">
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
                                    <input type="text" name="shortcode" value="<?php echo esc_attr($edit_item->shortcode); ?>" pattern="[a-z0-9_-]+" required>
                                    <small>Nur Kleinbuchstaben, Zahlen, _ und -</small>
                                </div>
                            </div>
                        </div>

        <div class="dashboard-card">
            <h2>Produktbild</h2>
            <p class="card-subline">Vorschau</p>
            <div class="form-grid">
                <div class="produkt-form-group full-width">
                    <label>Standard-Produktbild</label>
                    <div class="image-field-row">
                        <div id="default_image_preview" class="image-preview">
                            <?php if (!empty($edit_item->default_image)): ?>
                            <img src="<?php echo esc_url($edit_item->default_image); ?>" alt="">
                            <?php else: ?><span>Noch kein Bild vorhanden</span><?php endif; ?>
                        </div>
                        <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="default_image" aria-label="Bild auswählen">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 26.2"><path d="M16,7c-3.9,0-7,3.1-7,7s3.1,7,7,7,7-3.1,7-7-3.1-7-7-7ZM16,19c-2.8,0-5-2.2-5-5s2.2-5,5-5,5,2.2,5,5-2.2,5-5,5ZM29,4h-4c-1,0-3-4-4-4h-10c-1.1,0-3.1,4-4,4H3c-1.7,0-3,1.3-3,3v16c0,1.7,1.3,3,3,3h26c1.7,0,3-1.3,3-3V7c0-1.7-1.3-3-3-3ZM30,22c0,1.1-.9,2-2,2H4c-1.1,0-2-.9-2-2v-14c0-1.1.9-2,2-2h4c.9,0,2.9-4,4-4h8c1,0,3,4,3.9,4h4.1c1.1,0,2,.9,2,2v14Z"/></svg>
                        </button>
                        <button type="button" class="icon-btn produkt-remove-image" data-target="default_image" aria-label="Bild entfernen">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8Z"/></svg>
                        </button>
                    </div>
                    <input type="hidden" name="default_image" id="default_image" value="<?php echo esc_attr($edit_item->default_image); ?>">
                    <small>Fallback-Bild wenn kein spezifisches Bild vorhanden ist</small>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <h2>Produktbewertungen</h2>
            <p class="card-subline">Anzeige</p>
            <div class="form-grid">
                <div class="produkt-form-group full-width">
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="show_reviews" value="1" <?php checked($edit_item->show_reviews ?? 0, 1); ?>>
                        <span class="produkt-toggle-slider"></span>
                        <span>Produktbewertungen anzeigen</span>
                    </label>
                </div>
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

        <div class="dashboard-card">
            <h2>SEO-Einstellungen</h2>
            <p class="card-subline">Meta-Tags</p>
            <div class="form-grid">
                <div class="produkt-form-group">
                    <label>SEO-Titel</label>
                    <input type="text" name="meta_title" value="<?php echo esc_attr($edit_item->meta_title ?? ''); ?>" maxlength="60">
                    <small>Max. 60 Zeichen für Google <span id="meta_title_counter" class="produkt-char-counter"></span></small>
                </div>
                <div class="produkt-form-group full-width">
                    <label>SEO-Beschreibung</label>
                    <textarea name="meta_description" rows="2" maxlength="160"><?php echo esc_textarea($edit_item->meta_description ?? ''); ?></textarea>
                    <small>Max. 160 Zeichen <span id="meta_description_counter" class="produkt-char-counter"></span></small>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <h2>Sortierung &amp; Kategorien</h2>
            <p class="card-subline">Reihenfolge und Zuordnung</p>
            <div class="form-grid">
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo esc_attr($edit_item->sort_order); ?>" min="0">
                </div>
                <div class="produkt-form-group full-width">
                    <label>Kategorien</label>
                    <select name="product_categories[]" multiple style="width:100%; min-height:100px;">
                        <?php foreach ($all_product_cats as $cat): ?>
                        <option value="<?php echo $cat->id; ?>" <?php echo in_array($cat->id, $selected_product_cats) ? 'selected' : ''; ?>>
                            <?php echo str_repeat('--', $cat->depth) . ' ' . esc_html($cat->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
                    </div>
                </div>

                <div id="tab-product" class="produkt-subtab-content">
                    <div class="produkt-form-sections">
                        <div class="dashboard-card">
                            <h2>Produktseite</h2>
                            <p class="card-subline">Inhalt</p>
                            <div class="form-grid">
                                <div class="produkt-form-group full-width">
                                    <label>Kurzbeschreibung</label>
                                    <textarea name="short_description" rows="2"><?php echo esc_textarea($edit_item->short_description ?? ''); ?></textarea>
                                </div>
                                <div class="produkt-form-group full-width">
                                    <label>Produktbeschreibung *</label>
                                    <?php wp_editor($edit_item->product_description ?? '', 'category_product_description_edit', ['textarea_name' => 'product_description', 'textarea_rows' => 5, 'media_buttons' => false]); ?>
                                </div>
                            </div>
                        </div>

        <div class="dashboard-card">
            <h2>Einstellungen</h2>
            <p class="card-subline">Layout-Auswahl</p>
            <div class="form-grid">
                <div class="produkt-form-group">
                    <label>Layout-Stil</label>
                    <select name="layout_style">
                        <option value="default" <?php selected($edit_item->layout_style ?? 'default', 'default'); ?>>Standard (Horizontal)</option>
                        <option value="grid" <?php selected($edit_item->layout_style ?? 'default', 'grid'); ?>>Grid (Karten-Layout)</option>
                        <option value="list" <?php selected($edit_item->layout_style ?? 'default', 'list'); ?>>Liste (Vertikal)</option>
                    </select>
                </div>
            </div>
        </div>

        <?php
            $page_blocks = !empty($edit_item->page_blocks) ? json_decode($edit_item->page_blocks, true) : [];
            if (!is_array($page_blocks) || empty($page_blocks)) {
                $page_blocks = [['title' => '', 'text' => '', 'image' => '', 'alt' => '']];
            }
        ?>
        <div class="dashboard-card">
            <h2>Content Blöcke</h2>
            <p class="card-subline">Text &amp; Bild</p>
            <div id="page-blocks-container">
                <?php foreach ($page_blocks as $idx => $block): ?>
                <div class="produkt-page-block">
                    <div class="form-grid">
                        <div class="produkt-form-group" style="flex:1;">
                            <label>Titel</label>
                            <input type="text" name="page_block_titles[]" value="<?php echo esc_attr($block['title']); ?>">
                        </div>
                        <button type="button" class="button produkt-remove-page-block">-</button>
                    </div>
                    <div class="produkt-form-group">
                        <label>Text</label>
                        <textarea name="page_block_texts[]" rows="3"><?php echo esc_textarea($block['text']); ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-page-block" class="button">+ Block hinzufügen</button>
        </div>

        <?php
            $detail_blocks = !empty($edit_item->detail_blocks) ? json_decode($edit_item->detail_blocks, true) : [];
            if (!is_array($detail_blocks) || empty($detail_blocks)) {
                $detail_blocks = [['title' => '', 'text' => '']];
            }
        ?>
        <div class="dashboard-card">
            <h2>Details</h2>
            <p class="card-subline">Auflistung</p>
            <div id="details-blocks-container">
                <?php foreach ($detail_blocks as $idx => $block): ?>
                <div class="produkt-page-block">
                    <div class="form-grid">
                        <div class="produkt-form-group" style="flex:1;">
                            <label>Titel</label>
                            <input type="text" name="detail_block_titles[]" value="<?php echo esc_attr($block['title']); ?>">
                        </div>
                        <button type="button" class="button produkt-remove-detail-block">-</button>
                    </div>
                    <div class="produkt-form-group">
                        <label>Text</label>
                        <textarea name="detail_block_texts[]" rows="3"><?php echo esc_textarea($block['text']); ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-detail-block" class="button">+ Block hinzufügen</button>
        </div>

        <?php
            $tech_blocks = !empty($edit_item->tech_blocks) ? json_decode($edit_item->tech_blocks, true) : [];
            if (!is_array($tech_blocks) || empty($tech_blocks)) {
                $tech_blocks = [['title' => '', 'text' => '']];
            }
        ?>
        <div class="dashboard-card">
            <h2>Technische Daten</h2>
            <p class="card-subline">Informationen</p>
            <div id="tech-blocks-container">
                <?php foreach ($tech_blocks as $idx => $block): ?>
                <div class="produkt-page-block">
                    <div class="form-grid">
                        <div class="produkt-form-group" style="flex:1;">
                            <label>Titel</label>
                            <input type="text" name="tech_block_titles[]" value="<?php echo esc_attr($block['title']); ?>">
                        </div>
                        <button type="button" class="button produkt-remove-tech-block">-</button>
                    </div>
                    <div class="produkt-form-group">
                        <label>Text</label>
                        <textarea name="tech_block_texts[]" rows="3"><?php echo esc_textarea($block['text']); ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-tech-block" class="button">+ Block hinzufügen</button>
        </div>

        <?php
            $scope_blocks = !empty($edit_item->scope_blocks) ? json_decode($edit_item->scope_blocks, true) : [];
            if (!is_array($scope_blocks) || empty($scope_blocks)) {
                $scope_blocks = [['title' => '', 'text' => '']];
            }
        ?>
        <div class="dashboard-card">
            <h2>Lieferumfang</h2>
            <p class="card-subline">Inhalt</p>
            <div id="scope-blocks-container">
                <?php foreach ($scope_blocks as $idx => $block): ?>
                <div class="produkt-page-block">
                    <div class="form-grid">
                        <div class="produkt-form-group" style="flex:1;">
                            <label>Titel</label>
                            <input type="text" name="scope_block_titles[]" value="<?php echo esc_attr($block['title']); ?>">
                        </div>
                        <button type="button" class="button produkt-remove-scope-block">-</button>
                    </div>
                    <div class="produkt-form-group">
                        <label>Text</label>
                        <textarea name="scope_block_texts[]" rows="3"><?php echo esc_textarea($block['text']); ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-scope-block" class="button">+ Block hinzufügen</button>
        </div>

        <?php
            $accordion_data = !empty($edit_item->accordion_data) ? json_decode($edit_item->accordion_data, true) : [];
            if (!is_array($accordion_data) || empty($accordion_data)) {
                $accordion_data = [['title' => '', 'content' => '']];
            }
        ?>
        <div class="dashboard-card">
            <h2>Accordion</h2>
            <p class="card-subline">Bereiche</p>
            <div id="accordion-container">
                <?php foreach ($accordion_data as $idx => $acc): ?>
                <div class="produkt-accordion-group">
                    <div class="form-grid">
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
                    </div>
                </div>

                <div id="tab-features" class="produkt-subtab-content">
                    <div class="produkt-form-sections">
                    <div class="dashboard-card">
                        <h2>Features-Sektion</h2>
                        <p class="card-subline">Anzeige</p>
                        <div class="form-grid">
                            <div class="produkt-form-group">
                                <label class="produkt-toggle-label">
                                    <input type="checkbox" name="show_features" value="1" <?php checked($edit_item->show_features ?? 1, 1); ?>>
                                    <span class="produkt-toggle-slider"></span>
                                    <span>Features-Sektion anzeigen</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="feature-cards-grid">
                        <?php for ($i = 1; $i <= 4; $i++): $icon = $edit_item->{'feature_'.$i.'_icon'} ?? ''; $desc = $edit_item->{'feature_'.$i.'_description'} ?? ''; ?>
                        <div class="dashboard-card">
                            <h2>Feature <?php echo $i; ?></h2>
                            <p class="card-subline">Bild &amp; Text</p>
                            <div class="form-grid">
                                <div class="produkt-form-group full-width">
                                    <label>Bild</label>
                                    <div class="image-field-row">
                                        <div id="feature_<?php echo $i; ?>_icon_preview" class="image-preview">
                                            <?php if (!empty($icon)): ?>
                                            <img src="<?php echo esc_url($icon); ?>" alt="">
                                            <?php else: ?><span>Noch kein Bild vorhanden</span><?php endif; ?>
                                        </div>
                                        <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="feature_<?php echo $i; ?>_icon" aria-label="Bild auswählen">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 26.2"><path d="M16,7c-3.9,0-7,3.1-7,7s3.1,7,7,7,7-3.1,7-7-3.1-7-7-7ZM16,19c-2.8,0-5-2.2-5-5s2.2-5,5-5,5,2.2,5,5-2.2,5-5,5ZM29,4h-4c-1,0-3-4-4-4h-10c-1.1,0-3.1,4-4,4H3c-1.7,0-3,1.3-3,3v16c0,1.7,1.3,3,3,3h26c1.7,0,3-1.3,3-3V7c0-1.7-1.3-3-3-3ZM30,22c0,1.1-.9,2-2,2H4c-1.1,0-2-.9-2-2v-14c0-1.1.9-2,2-2h4c.9,0,2.9-4,4-4h8c1,0,3,4,3.9,4h4.1c1.1,0,2,.9,2,2v14Z"/></svg>
                                        </button>
                                        <button type="button" class="icon-btn produkt-remove-image" data-target="feature_<?php echo $i; ?>_icon" aria-label="Bild entfernen">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8Z"/></svg>
                                        </button>
                                    </div>
                                    <input type="hidden" name="feature_<?php echo $i; ?>_icon" id="feature_<?php echo $i; ?>_icon" value="<?php echo esc_attr($icon); ?>">
                                </div>
                                <div class="produkt-form-group full-width">
                                    <label>Beschreibung</label>
                                    <textarea name="feature_<?php echo $i; ?>_description" rows="2"><?php echo esc_textarea($desc); ?></textarea>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    </div>
                </div>

                <div id="tab-filters" class="produkt-subtab-content">
                    <div class="produkt-form-sections">
                    <div class="dashboard-card">
                        <div class="card-header-flex">
                            <div>
                                <h2>Filter</h2>
                                <p class="card-subline">Dem Produkt zuordnen</p>
                            </div>
                            <form class="produkt-filter-form product-search-bar" onsubmit="return false;">
                                <div class="search-input-wrapper">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="search-icon"><path d="M10 2a8 8 0 105.3 14.1l4.3 4.3a1 1 0 101.4-1.4l-4.3-4.3A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/></svg>
                                    <input type="text" id="filter-search" placeholder="Filter suchen">
                                </div>
                            </form>
                        </div>
                        <div id="filter-list" class="produkt-form-group">
                            <?php foreach ($filter_groups as $g): ?>
                                <h4><?php echo esc_html($g->name); ?></h4>
                                <?php foreach ($filters_by_group[$g->id] as $f): ?>
                                <label class="produkt-filter-item">
                                    <input type="checkbox" name="filters[]" value="<?php echo $f->id; ?>" <?php checked(in_array($f->id, $selected_filters)); ?>>
                                    <span><?php echo esc_html($f->name); ?></span>
                                </label>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    </div>
                </div>

                <div id="tab-inventory" class="produkt-subtab-content">
                    <div class="produkt-form-sections">
                        <div class="dashboard-card">
                            <h2>Lagerverwaltung</h2>
                            <p class="card-subline">Bestände</p>
                            <?php
                                $variants = $wpdb->get_results(
                                    $wpdb->prepare(
                                        "SELECT * FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d ORDER BY sort_order, name",
                                        $edit_item->id
                                    )
                                );
                                $extras = $wpdb->get_results(
                                    $wpdb->prepare(
                                        "SELECT * FROM {$wpdb->prefix}produkt_extras WHERE category_id = %d ORDER BY sort_order, name",
                                        $edit_item->id
                                    )
                                );
                            ?>
                            <?php if (empty($variants) && empty($extras)): ?>
                            <p>Keine Lagerverwaltung verfügbar.</p>
                            <?php else: ?>
                            <?php if (!empty($variants)): ?>
                            <h3>Ausführungen</h3>
                            <table class="activity-table">
                                <thead>
                                    <tr><th>Ausführung</th><th>Preis</th><th>Verfügbar</th><th>In Vermietung</th><th>SKU</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($variants as $v): ?>
                                    <tr>
                                        <td><?php echo esc_html($v->name); ?></td>
                                        <?php $price_val = ($modus === 'kauf') ? $v->verkaufspreis_einmalig : $v->base_price; ?>
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
                                                    <button type="button" class="inv-minus" data-target="rent-<?php echo $v->id; ?>" data-variant="<?php echo $v->id; ?>">-</button>
                                                    <input type="number" id="rent-<?php echo $v->id; ?>" name="stock_rented[<?php echo $v->id; ?>]" value="<?php echo intval($v->stock_rented); ?>" min="0">
                                                    <button type="button" class="inv-plus" data-target="rent-<?php echo $v->id; ?>" data-variant="<?php echo $v->id; ?>">+</button>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="inventory-cell">
                                            <div class="inventory-trigger" data-variant="<?php echo $v->id; ?>">
                                                <span class="inventory-rented-count"><?php echo intval($v->stock_rented); ?></span>
                                            </div>
                                        </td>
                                        <td><input type="text" name="sku[<?php echo $v->id; ?>]" value="<?php echo esc_attr($v->sku); ?>"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                            <?php if (!empty($extras)): ?>
                            <h3>Extras</h3>
                            <table class="activity-table">
                                <thead>
                                    <tr><th>Extra</th><th>Preis</th><th>Verfügbar</th><th>In Vermietung</th><th>SKU</th></tr>
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
                                                if (!is_wp_error($p)) { $display_price = $p; }
                                            }
                                        ?>
                                        <td><?php echo number_format((float)$display_price, 2, ',', '.'); ?>€</td>
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
                <button type="button" class="inv-minus" data-target="rent-<?php echo $e->id; ?>" data-extra="<?php echo $e->id; ?>">-</button>
                <input type="number" id="rent-<?php echo $e->id; ?>" name="extra_stock_rented[<?php echo $e->id; ?>]" value="<?php echo intval($e->stock_rented); ?>" min="0">
                <button type="button" class="inv-plus" data-target="rent-<?php echo $e->id; ?>" data-extra="<?php echo $e->id; ?>">+</button>
            </div>
        </div>
                                        </td>
                                        <td class="inventory-cell">
                                            <div class="inventory-trigger" data-extra="<?php echo $e->id; ?>">
                                                <span class="inventory-rented-count"><?php echo intval($e->stock_rented); ?></span>
                                            </div>
                                        </td>
                                        <td><input type="text" name="extra_sku[<?php echo $e->id; ?>]" value="<?php echo esc_attr($e->sku); ?>"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.category-edit-menu a').forEach(function(tab){
        tab.addEventListener('click', function(e){
            e.preventDefault();
            const target = this.dataset.tab;
            document.querySelectorAll('.category-edit-menu a').forEach(a=>{a.classList.remove('active'); a.querySelector('svg').classList.remove('active');});
            document.querySelectorAll('.settings-content .produkt-subtab-content').forEach(c=>c.classList.remove('active'));
            this.classList.add('active');
            this.querySelector('svg').classList.add('active');
            const content = document.getElementById('tab-'+target);
            if(content) content.classList.add('active');
        });
    });
    document.querySelectorAll('.produkt-media-button').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const target = document.getElementById(this.dataset.target);
            const preview = document.getElementById(this.dataset.target + '_preview');
            const frame = wp.media({title: 'Bild auswählen', button:{text:'Bild verwenden'}, multiple:false});
            frame.on('select', function(){
                const attachment = frame.state().get('selection').first().toJSON();
                if (target) target.value = attachment.url;
                if (preview) preview.innerHTML = '<img src="'+attachment.url+'" alt="">';
            });
            frame.open();
        });
    });
    document.querySelectorAll('.produkt-remove-image').forEach(function(btn){
        btn.addEventListener('click', function(){
            const target = document.getElementById(this.dataset.target);
            const preview = document.getElementById(this.dataset.target + '_preview');
            if (target) target.value = '';
            if (preview) preview.innerHTML = '<span>Noch kein Bild vorhanden</span>';
        });
    });
    const filterSearch = document.getElementById('filter-search');
    if (filterSearch) {
        filterSearch.addEventListener('input', function(){
            const term = this.value.toLowerCase();
            document.querySelectorAll('#filter-list .produkt-filter-item').forEach(function(el){
                el.style.display = el.textContent.toLowerCase().includes(term) ? 'block' : 'none';
            });
        });
    }
});
</script>
