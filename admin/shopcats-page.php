<?php
if (!defined('ABSPATH')) { exit; }

global $wpdb;
$table = $wpdb->prefix . 'produkt_shopcats';

// Handle add
if (isset($_POST['add_shopcat'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $name = sanitize_text_field($_POST['name']);
    $slug = sanitize_title($_POST['slug']);
    if ($name && $slug) {
        $wpdb->insert($table, ['name' => $name, 'slug' => $slug], ['%s', '%s']);
        echo '<div class="notice notice-success"><p>✅ Kategorie hinzugefügt!</p></div>';
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $wpdb->delete($table, ['id' => intval($_GET['delete'])], ['%d']);
    echo '<div class="notice notice-success"><p>✅ Kategorie gelöscht!</p></div>';
}

$shopcats = $wpdb->get_results("SELECT * FROM $table ORDER BY name");
?>
<div class="wrap">
    <h1>Produkt-Kategorien</h1>
    <form method="post" action="" style="margin-bottom:20px;">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="text" name="name" placeholder="Name" required>
        <input type="text" name="slug" placeholder="Slug" pattern="[a-z0-9_-]+" required>
        <button type="submit" name="add_shopcat" class="button button-primary">Hinzufügen</button>
    </form>
    <table class="widefat">
        <thead><tr><th>Name</th><th>Slug</th><th>Aktionen</th></tr></thead>
        <tbody>
        <?php foreach ($shopcats as $cat): ?>
            <tr>
                <td><?php echo esc_html($cat->name); ?></td>
                <td><?php echo esc_html($cat->slug); ?></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=produkt-shopcats&delete=' . $cat->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>" class="button">Löschen</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
