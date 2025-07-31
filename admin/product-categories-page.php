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

// Wenn Bearbeiten
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}produkt_product_categories WHERE id = %d", $edit_id));
}
?>

<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting">Hallo, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Kategorien verwalten</p>

    <div id="category-modal" class="modal-overlay" data-open="<?php echo $edit_category ? '1' : '0'; ?>">
        <div class="modal-content">
            <button type="button" class="modal-close">&times;</button>
            <h2><?php echo $edit_category ? 'Kategorie bearbeiten' : 'Neue Kategorie hinzufügen'; ?></h2>
            <form method="post" id="produkt-category-form" class="produkt-compact-form">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <input type="hidden" name="category_id" value="<?php echo esc_attr($edit_category->id ?? ''); ?>">
            <table class="form-table">
                <tr>
                    <th><label for="name">Name</label></th>
                    <td><input name="name" type="text" required value="<?php echo esc_attr($edit_category->name ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="slug">Slug</label></th>
                    <td><input name="slug" type="text" required value="<?php echo esc_attr($edit_category->slug ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="parent_id">Übergeordnete Kategorie</label></th>
                    <td>
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
                    </td>
                </tr>
                <tr>
                    <th><label for="description">Beschreibung</label></th>
                    <td><textarea name="description" class="large-text"><?php echo esc_textarea($edit_category->description ?? ''); ?></textarea></td>
                </tr>
            </table>
            <p><input type="submit" name="save_category" class="button-primary" value="Speichern"></p>
            </form>
        </div>
    </div>

    <div class="h2-rental-card card-category-list">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2>Bestehende Kategorien</h2>
            <button id="add-category-btn" class="button button-primary" style="margin-right:20px;">+ Hinzufügen</button>
        </div>
        <p class="card-subline">Was zuletzt passiert ist</p>
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
                            <a href="?page=produkt-kategorien&edit=<?php echo $cat->id; ?>" class="button button-primary">Bearbeiten</a>
                            <a href="?page=produkt-kategorien&delete=<?php echo $cat->id; ?>" class="button button-danger" onclick="return confirm('Wirklich löschen?')">Löschen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
