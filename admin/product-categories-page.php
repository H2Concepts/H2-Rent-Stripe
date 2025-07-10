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
    $id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

    global $wpdb;
    $table = $wpdb->prefix . 'produkt_product_categories';

    if ($id > 0) {
        $wpdb->update($table, [
            'name' => $name,
            'slug' => $slug,
            'description' => $description
        ], ['id' => $id]);
    } else {
        $wpdb->insert($table, [
            'name' => $name,
            'slug' => $slug,
            'description' => $description
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
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_product_categories ORDER BY name ASC");

// Wenn Bearbeiten
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}produkt_product_categories WHERE id = %d", $edit_id));
}
?>

<div class="wrap">
    <h1>Kategorien verwalten</h1>

    <h2><?= $edit_category ? 'Kategorie bearbeiten' : 'Neue Kategorie hinzufügen' ?></h2>

    <form method="post">
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
                <th><label for="description">Beschreibung</label></th>
                <td><textarea name="description" class="large-text"><?= esc_textarea($edit_category->description ?? '') ?></textarea></td>
            </tr>
        </table>
        <p><input type="submit" name="save_category" class="button-primary" value="Speichern"></p>
    </form>

    <h2>Bestehende Kategorien</h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= esc_html($cat->name) ?></td>
                    <td><?= esc_html($cat->slug) ?></td>
                    <td>
                        <a href="?page=produkt-kategorien&edit=<?= $cat->id ?>" class="button">Bearbeiten</a>
                        <a href="?page=produkt-kategorien&delete=<?= $cat->id ?>" class="button button-danger" onclick="return confirm('Wirklich löschen?')">Löschen</a>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
