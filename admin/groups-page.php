<?php
if (!defined('ABSPATH')) { exit; }

global $wpdb;
$table = $wpdb->prefix . 'produkt_groups';

if (isset($_POST['add_group'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $name = sanitize_text_field($_POST['name']);
    $slug = sanitize_title($_POST['slug']);
    if ($name && $slug) {
        $wpdb->insert($table, [ 'name' => $name, 'slug' => $slug ]);
        echo '<div class="notice notice-success"><p>✅ Kategorie gespeichert.</p></div>';
    }
}

if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $wpdb->delete($table, ['id' => intval($_GET['delete'])]);
    echo '<div class="notice notice-success"><p>✅ Kategorie gelöscht.</p></div>';
}

$groups = $wpdb->get_results("SELECT * FROM $table ORDER BY name");
?>
<div class="wrap">
    <h1>Kategorien</h1>
    <form method="post" style="margin-bottom:20px;">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="text" name="name" placeholder="Name" required>
        <input type="text" name="slug" placeholder="Slug" required>
        <button type="submit" name="add_group" class="button button-primary">Hinzufügen</button>
    </form>
    <table class="widefat striped">
        <thead><tr><th>Name</th><th>Slug</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($groups as $g): ?>
            <tr>
                <td><?php echo esc_html($g->name); ?></td>
                <td><?php echo esc_html($g->slug); ?></td>
                <td><a class="button" href="<?php echo admin_url('admin.php?page=produkt-groups&delete=' . $g->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>">Löschen</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
