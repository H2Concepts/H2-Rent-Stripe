<?php
use ProduktVerleih\Database;

if (!current_user_can('manage_options')) {
    return;
}

// Kategorie speichern
if (isset($_POST['save_category'])) {
    $name = sanitize_text_field($_POST['name']);
    $slug = sanitize_title($_POST['slug']);
    $description = sanitize_textarea_field($_POST['description']);
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    $id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

    global $wpdb;
    $table = $wpdb->prefix . 'produkt_product_categories';

    if ($id > 0) {
        $wpdb->update($table, [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'parent_id' => $parent_id ?: null
        ], ['id' => $id]);
    } else {
        $wpdb->insert($table, [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'parent_id' => $parent_id ?: null
        ]);
    }
}

// Kategorie löschen
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'produkt_product_categories', ['id' => $delete_id]);
    $wpdb->delete($wpdb->prefix . 'produkt_product_to_category', ['category_id' => $delete_id]);
}

global $wpdb;
$raw_cats = $wpdb->get_results(
    "SELECT c.*, COUNT(p.id) AS product_count
     FROM {$wpdb->prefix}produkt_product_categories c
     LEFT JOIN {$wpdb->prefix}produkt_product_to_category ptc ON c.id = ptc.category_id
     LEFT JOIN {$wpdb->prefix}produkt_categories p ON p.id = ptc.produkt_id
     GROUP BY c.id"
);
$categories = Database::get_product_categories_tree();
$counts = [];
foreach ($raw_cats as $r) { $counts[$r->id] = $r->product_count; }
foreach ($categories as $c) { $c->product_count = $counts[$c->id] ?? 0; }

// Statistiken für Info-Boxen
$category_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_product_categories WHERE parent_id IS NULL OR parent_id = 0");
$subcategory_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_product_categories WHERE parent_id IS NOT NULL AND parent_id != 0");
$total_category_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_product_categories");
$products_with_category = $wpdb->get_var("SELECT COUNT(DISTINCT produkt_id) FROM {$wpdb->prefix}produkt_product_to_category");

// Layouts verarbeiten
$layout_table = $wpdb->prefix . 'produkt_category_layouts';

if (isset($_POST['save_layout'])) {
    $name = sanitize_text_field($_POST['layout_name'] ?? '');
    $layout_type = intval($_POST['layout_type'] ?? 1);
    $border_radius = isset($_POST['border_radius']) ? 1 : 0;
    $cats = $_POST['layout_categories'] ?? [];
    $colors = $_POST['cat_color'] ?? [];
    $images = $_POST['cat_image'] ?? [];
    $items = [];
    foreach ($cats as $i => $cat_id) {
        $cid = intval($cat_id);
        if ($cid > 0) {
            $items[] = [
                'id' => $cid,
                'color' => sanitize_hex_color($colors[$i] ?? ''),
                'image' => esc_url_raw($images[$i] ?? ''),
            ];
        }
    }
    $cat_json = wp_json_encode($items);
    $layout_id = isset($_POST['layout_id']) ? intval($_POST['layout_id']) : 0;
    $shortcode = sanitize_title($_POST['layout_shortcode'] ?? '');
    if (empty($shortcode)) {
        $shortcode = sanitize_title($name);
    }
    if ($layout_id > 0) {
        $wpdb->update($layout_table, [
            'name' => $name,
            'layout_type' => $layout_type,
            'categories' => $cat_json,
            'border_radius' => $border_radius,
        ], ['id' => $layout_id]);
    } else {
        $base = $shortcode;
        $i = 1;
        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $layout_table WHERE shortcode = %s", $shortcode))) {
            $shortcode = $base . '-' . $i;
            $i++;
        }
        $wpdb->insert($layout_table, [
            'name' => $name,
            'layout_type' => $layout_type,
            'categories' => $cat_json,
            'border_radius' => $border_radius,
            'shortcode' => $shortcode,
        ]);
    }
}

if (isset($_GET['delete_layout'])) {
    $del = intval($_GET['delete_layout']);
    $wpdb->delete($layout_table, ['id' => $del]);
}

$layouts = $wpdb->get_results("SELECT * FROM $layout_table");

$edit_layout = null;
if (isset($_GET['edit_layout'])) {
    $edit_layout = $wpdb->get_row($wpdb->prepare("SELECT * FROM $layout_table WHERE id = %d", intval($_GET['edit_layout'])));
}

// Wenn Bearbeiten
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}produkt_product_categories WHERE id = %d", $edit_id));
}
?>

<div id="category-modal" class="modal-overlay" data-open="<?php echo $edit_category ? '1' : '0'; ?>">
    <div class="modal-content">
        <button type="button" class="modal-close">&times;</button>
        <h2><?php echo $edit_category ? 'Kategorie bearbeiten' : 'Neue Kategorie hinzufügen'; ?></h2>
        <form method="post" id="produkt-category-form" class="produkt-compact-form">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <input type="hidden" name="category_id" value="<?php echo esc_attr($edit_category->id ?? ''); ?>">
            <div class="form-grid">
                <div class="produkt-form-group">
                    <label for="name">Name</label>
                    <input name="name" type="text" required value="<?php echo esc_attr($edit_category->name ?? ''); ?>">
                </div>
                <div class="produkt-form-group">
                    <label for="slug">Slug</label>
                    <input name="slug" type="text" required value="<?php echo esc_attr($edit_category->slug ?? ''); ?>">
                </div>
                <div class="produkt-form-group">
                    <label for="parent_id">Übergeordnete Kategorie</label>
                    <select name="parent_id">
                        <option value="0">Keine</option>
                        <?php foreach ($categories as $cat_option): ?>
                            <?php if (!isset($edit_category->id) || $cat_option->id != $edit_category->id): ?>
                                <option value="<?php echo $cat_option->id; ?>" <?php echo (isset($edit_category->parent_id) && $edit_category->parent_id == $cat_option->id) ? 'selected' : ''; ?>>
                                    <?php echo str_repeat('--', $cat_option->depth) . ' ' . esc_html($cat_option->name); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="produkt-form-group full-width">
                    <label for="description">Beschreibung</label>
                    <textarea name="description" rows="3"><?php echo esc_textarea($edit_category->description ?? ''); ?></textarea>
                </div>
            </div>
            <p>
                <button type="submit" name="save_category" class="icon-btn" aria-label="Speichern">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                        <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                        <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
                    </svg>
                </button>
            </p>
        </form>
        </div>
</div>

<div id="layout-modal" class="modal-overlay" data-open="<?php echo $edit_layout ? '1' : '0'; ?>">
    <div class="modal-content">
        <button type="button" class="modal-close">&times;</button>
        <h2><?php echo $edit_layout ? 'Layout bearbeiten' : 'Neues Layout'; ?></h2>
        <form method="post" class="produkt-compact-form">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <input type="hidden" name="layout_id" value="<?php echo esc_attr($edit_layout->id ?? ''); ?>">
            <input type="hidden" name="layout_shortcode" value="<?php echo esc_attr($edit_layout->shortcode ?? ''); ?>">
            <div class="form-grid">
                <div class="produkt-form-group">
                    <label for="layout_name">Name</label>
                    <input name="layout_name" type="text" required value="<?php echo esc_attr($edit_layout->name ?? ''); ?>">
                </div>
                <div class="produkt-form-group full-width">
                    <label>Kategorien</label>
                    <?php $existing_items = $edit_layout ? json_decode($edit_layout->categories, true) : []; ?>
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <?php $ex = $existing_items[$i] ?? []; ?>
                        <div class="layout-cat-row">
                            <select name="layout_categories[]">
                                <option value="">-- Kategorie wählen --</option>
                                <?php foreach ($categories as $cat_option): ?>
                                    <option value="<?php echo $cat_option->id; ?>" <?php selected($ex['id'] ?? '', $cat_option->id); ?>><?php echo esc_html($cat_option->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="color" name="cat_color[]" value="<?php echo esc_attr($ex['color'] ?? '#ffffff'); ?>">
                            <input type="text" name="cat_image[]" value="<?php echo esc_attr($ex['image'] ?? ''); ?>" placeholder="Bild URL">
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="produkt-form-group">
                    <label>Layout</label>
                    <div>
                        <label><input type="radio" name="layout_type" value="1" <?php echo (!isset($edit_layout->layout_type) || $edit_layout->layout_type == 1) ? 'checked' : ''; ?>> Layout 1</label>
                        <label><input type="radio" name="layout_type" value="2" <?php echo (isset($edit_layout->layout_type) && $edit_layout->layout_type == 2) ? 'checked' : ''; ?>> Layout 2</label>
                    </div>
                </div>
                <div class="produkt-form-group">
                    <label>Border Radius</label>
                    <div>
                        <label><input type="checkbox" name="border_radius" value="1" <?php echo (!empty($edit_layout->border_radius)) ? 'checked' : ''; ?>> 20px</label>
                    </div>
                </div>
            </div>
            <p>
                <button type="submit" name="save_layout" class="icon-btn" aria-label="Speichern">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                        <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                        <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
                    </svg>
                </button>
            </p>
        </form>
    </div>
</div>

<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Kategorien verwalten</p>

    <div class="product-info-grid cols-4">
        <div class="product-info-box bg-pastell-gelb">
            <span class="label">Kategorien</span>
            <strong class="value"><?php echo intval($category_count); ?></strong>
        </div>
        <div class="product-info-box bg-pastell-gruen">
            <span class="label">Subkategorien</span>
            <strong class="value"><?php echo intval($subcategory_count); ?></strong>
        </div>
        <div class="product-info-box bg-pastell-mint">
            <span class="label">Gesamt</span>
            <strong class="value"><?php echo intval($total_category_count); ?></strong>
        </div>
        <div class="product-info-box bg-pastell-orange">
            <span class="label">Produkte zugeordnet</span>
            <strong class="value"><?php echo intval($products_with_category); ?></strong>
        </div>
    </div>

    <div class="product-info-grid">
        <div class="h2-rental-card card-category-list">
            <div class="card-header-flex">
                <div>
                    <h2>Bestehende Kategorien</h2>
                    <p class="card-subline">Verwalten Sie Ihre Kategorien</p>
                </div>
                <button id="add-category-btn" type="button" class="icon-btn add-category-btn" aria-label="Hinzufügen">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3">
                        <path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/>
                        <path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/>
                    </svg>
                </button>
            </div>
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Produkte</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo str_repeat('--', $cat->depth) . ' ' . esc_html($cat->name); ?></td>
                            <td><?php echo esc_html($cat->slug); ?></td>
                            <td><?php echo intval($cat->product_count); ?></td>
                            <td>
                                <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='?page=produkt-kategorien&edit=<?php echo $cat->id; ?>'">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                        <path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/>
                                        <path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/>
                                    </svg>
                                </button>
                                <button type="button" class="icon-btn" onclick="if(confirm('Wirklich löschen?')){window.location.href='?page=produkt-kategorien&delete=<?php echo $cat->id; ?>';}" aria-label="Löschen">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                        <path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/>
                                        <path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="h2-rental-card card-layout-list">
            <div class="card-header-flex">
                <div>
                    <h2>Kategorie Layout</h2>
                    <p class="card-subline">Layouts für Ihre Homepage</p>
                </div>
                <button id="add-layout-btn" type="button" class="icon-btn add-category-btn" aria-label="Hinzufügen">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3">
                        <path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/>
                        <path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/>
                    </svg>
                </button>
            </div>
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Shortcode</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($layouts as $lay): ?>
                        <tr>
                            <td><?php echo esc_html($lay->name); ?></td>
                            <td><code>[produkt_category_layout id="<?php echo esc_html($lay->shortcode); ?>"]</code></td>
                            <td>
                                <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='?page=produkt-kategorien&edit_layout=<?php echo $lay->id; ?>'">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                        <path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/>
                                        <path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/>
                                    </svg>
                                </button>
                                <button type="button" class="icon-btn" onclick="if(confirm('Wirklich löschen?')){window.location.href='?page=produkt-kategorien&delete_layout=<?php echo $lay->id; ?>';}" aria-label="Löschen">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                        <path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/>
                                        <path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
