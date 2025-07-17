<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'produkt_filters';

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['edit'])));
    if ($edit_item) {
        $active_tab = 'edit';
    }
}

if (isset($_POST['submit_filter'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $name = sanitize_text_field($_POST['name']);
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id) {
        $wpdb->update($table, ['name' => $name], ['id' => $id]);
    } else {
        $wpdb->insert($table, ['name' => $name]);
        $id = $wpdb->insert_id;
    }
    $active_tab = 'list';
}

if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $del_id = intval($_GET['delete']);
    $wpdb->delete($table, ['id' => $del_id]);
    $wpdb->delete($wpdb->prefix . 'produkt_category_filters', ['filter_id' => $del_id]);
}

$filters = $wpdb->get_results("SELECT * FROM $table ORDER BY name");
?>
<div class="wrap">
    <h1>Filter verwalten</h1>
    <nav class="produkt-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=produkt-filters&tab=list'); ?>" class="produkt-tab <?php echo $active_tab==='list'?'active':''; ?>">Übersicht</a>
        <a href="<?php echo admin_url('admin.php?page=produkt-filters&tab=add'); ?>" class="produkt-tab <?php echo $active_tab==='add'?'active':''; ?>">Neu</a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=produkt-filters&tab=edit&edit=' . $edit_item->id); ?>" class="produkt-tab <?php echo $active_tab==='edit'?'active':''; ?>">Bearbeiten</a>
        <?php endif; ?>
    </nav>
    <div class="produkt-tab-content">
        <?php if ($active_tab === 'add'): ?>
            <h2>Neuen Filter hinzufügen</h2>
            <form method="post">
                <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="filter-name">Titel</label></th>
                        <td><input type="text" name="name" id="filter-name" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button('Hinzufügen', 'primary', 'submit_filter'); ?>
            </form>
        <?php elseif ($active_tab === 'edit' && $edit_item): ?>
            <h2>Filter bearbeiten</h2>
            <form method="post">
                <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
                <input type="hidden" name="id" value="<?php echo $edit_item->id; ?>">
                <table class="form-table">
                    <tr>
                        <th><label for="filter-name">Titel</label></th>
                        <td><input type="text" name="name" id="filter-name" value="<?php echo esc_attr($edit_item->name); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button('Speichern', 'primary', 'submit_filter'); ?>
            </form>
        <?php else: ?>
            <h2>Alle Filter</h2>
            <table class="widefat striped">
                <thead><tr><th>Titel</th><th>Aktion</th></tr></thead>
                <tbody>
                    <?php foreach ($filters as $f): ?>
                    <tr>
                        <td><?php echo esc_html($f->name); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=produkt-filters&edit=' . $f->id); ?>">Bearbeiten</a> |
                            <a href="<?php echo admin_url('admin.php?page=produkt-filters&delete=' . $f->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>" onclick="return confirm('Sind Sie sicher?');">Löschen</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
