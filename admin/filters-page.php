<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_groups  = $wpdb->prefix . 'produkt_filter_groups';
$table_filters = $wpdb->prefix . 'produkt_filters';

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_groups WHERE id = %d", intval($_GET['edit'])));
    if ($edit_item) {
        $active_tab = 'edit';
    }
}

if (isset($_POST['submit_group'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $name = sanitize_text_field($_POST['name']);
    $id   = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id) {
        $wpdb->update($table_groups, ['name' => $name], ['id' => $id]);
    } else {
        $wpdb->insert($table_groups, ['name' => $name]);
        $id = $wpdb->insert_id;
    }
    $active_tab = 'list';
}

if (isset($_POST['add_filter_item'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $name  = sanitize_text_field($_POST['filter_name']);
    $gid   = intval($_POST['group_id']);
    if ($name && $gid) {
        $wpdb->insert($table_filters, ['group_id' => $gid, 'name' => $name]);
    }
    $active_tab = 'edit';
}

if (isset($_POST['update_filter_item'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $name = sanitize_text_field($_POST['filter_name']);
    $fid  = intval($_POST['filter_id']);
    $wpdb->update($table_filters, ['name' => $name], ['id' => $fid]);
    $active_tab = 'edit';
}

if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $del_id = intval($_GET['delete']);
    $wpdb->delete($table_groups, ['id' => $del_id]);
    $filter_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM $table_filters WHERE group_id = %d", $del_id));
    foreach ($filter_ids as $fid) {
        $wpdb->delete($wpdb->prefix . 'produkt_category_filters', ['filter_id' => $fid]);
    }
    $wpdb->delete($table_filters, ['group_id' => $del_id]);
}

if (isset($_GET['delete_filter']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $fid = intval($_GET['delete_filter']);
    $wpdb->delete($table_filters, ['id' => $fid]);
    $wpdb->delete($wpdb->prefix . 'produkt_category_filters', ['filter_id' => $fid]);
    $active_tab = 'edit';
}

$groups  = $wpdb->get_results("SELECT * FROM $table_groups ORDER BY name");
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
              <h2>Neue Filtergruppe hinzufügen</h2>
              <form method="post">
                  <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
                  <table class="form-table">
                      <tr>
                          <th><label for="filter-name">Titel</label></th>
                          <td><input type="text" name="name" id="filter-name" class="regular-text" required></td>
                      </tr>
                  </table>
                  <?php submit_button('Hinzufügen', 'primary', 'submit_group'); ?>
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
                  <?php submit_button('Speichern', 'primary', 'submit_group'); ?>
              </form>
              <?php $group_filters = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_filters WHERE group_id = %d ORDER BY name", $edit_item->id)); ?>
              <h3>Filteroptionen</h3>
              <form method="post" style="margin-bottom:15px;">
                  <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
                  <input type="hidden" name="group_id" value="<?php echo $edit_item->id; ?>">
                  <input type="text" name="filter_name" class="regular-text" required>
                  <?php submit_button('Hinzufügen', 'secondary', 'add_filter_item', false); ?>
              </form>
              <table class="widefat striped">
                  <thead><tr><th>Name</th><th>Aktion</th></tr></thead>
                  <tbody>
                      <?php foreach ($group_filters as $f): ?>
                      <tr>
                          <td>
                              <form method="post">
                                  <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
                                  <input type="hidden" name="filter_id" value="<?php echo $f->id; ?>">
                                  <input type="text" name="filter_name" value="<?php echo esc_attr($f->name); ?>">
                                  <?php submit_button('Speichern', 'small', 'update_filter_item', false); ?>
                                  <a href="<?php echo admin_url('admin.php?page=produkt-filters&edit=' . $edit_item->id . '&delete_filter=' . $f->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>" onclick="return confirm('Löschen?');">Löschen</a>
                              </form>
                          </td>
                          <td></td>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          <?php else: ?>
              <h2>Alle Filter</h2>
              <table class="widefat striped">
                  <thead><tr><th>Titel</th><th>Aktion</th></tr></thead>
                  <tbody>
                    <?php foreach ($groups as $f): ?>
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
