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

<div class="wrap" id="produkt-admin-product-categories">
    <div class="produkt-admin-card">
        <div class="produkt-admin-header-compact">
            <div class="produkt-admin-logo-compact">
                <span class="dashicons dashicons-category"></span>
            </div>
            <div class="produkt-admin-title-compact">
                <h1>Kategorien verwalten</h1>
                <p>Produkte in Kategorien organisieren</p>
            </div>
        </div>

        <h2><?= $edit_category ? 'Kategorie bearbeiten' : 'Neue Kategorie hinzufügen' ?></h2>

        <form method="post" id="produkt-category-form" class="produkt-compact-form">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <input type="hidden" name="category_id" value="<?= esc_attr($edit_category->id ?? '') ?>">
        <table class="form-table">
            <tr>
                <th><label for="name">Name</label></th>
                <td><input name="name" type="text" required value="<?= esc_attr($edit_category->name ?? '') ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="slug">Slug</label></th>
                <td><input name="slug" type="text" required value="<?= esc_attr($edit_category->slug ?? '') ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="parent_id">Übergeordnete Kategorie</label></th>
                <td>
                    <select name="parent_id">
                        <option value="0">Keine</option>
                        <?php foreach ($categories as $cat_option): ?>
                            <?php if (!isset($edit_category->id) || $cat_option->id != $edit_category->id): ?>
                                <option value="<?= $cat_option->id ?>" <?= isset($edit_category->parent_id) && $edit_category->parent_id == $cat_option->id ? 'selected' : '' ?>>
                                    <?= str_repeat('--', $cat_option->depth) . ' ' . esc_html($cat_option->name) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="description">Beschreibung</label></th>
                <td><textarea name="description" class="large-text"><?= esc_textarea($edit_category->description ?? '') ?></textarea></td>
            </tr>
        </table>
        <p><input type="submit" name="save_category" class="button-primary" value="Speichern"></p>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var nameField = document.querySelector('#produkt-category-form input[name="name"]');
        var slugField = document.querySelector('#produkt-category-form input[name="slug"]');
        if (!nameField || !slugField) return;

        var touched = false;
        slugField.addEventListener('input', function () { touched = true; });
        nameField.addEventListener('input', function () {
            if (touched && slugField.value) return;
            var slug = nameField.value.toLowerCase().trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-');
            slugField.value = slug;
        });
    });
    </script>

    <h2>Bestehende Kategorien</h2>
    <table class="widefat striped">
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
                    <td><?= str_repeat('--', $cat->depth) . ' ' . esc_html($cat->name) ?></td>
                    <td><?= esc_html($cat->slug) ?></td>
                    <td><?= intval($cat->product_count) ?></td>
                    <td>
                        <a href="?page=produkt-kategorien&edit=<?= $cat->id ?>" class="button">Bearbeiten</a>
                        <a href="?page=produkt-kategorien&delete=<?= $cat->id ?>" class="button button-danger" onclick="return confirm('Wirklich löschen?')">Löschen</a>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    </div>
</div>
