<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_groups  = $wpdb->prefix . 'produkt_filter_groups';
$table_filters = $wpdb->prefix . 'produkt_filters';

if (!function_exists('pv_filters_redirect')) {
    function pv_filters_redirect($url) {
        if (!headers_sent()) {
            wp_safe_redirect($url);
        } else {
            echo '<script>window.location.href="' . esc_url($url) . '";</script>';
        }
        exit;
    }
}

// Handle group create/update
if (isset($_POST['save_group'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $name = sanitize_text_field($_POST['name']);
    $id   = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    if ($id) {
        $wpdb->update($table_groups, ['name' => $name], ['id' => $id]);
    } else {
        $wpdb->insert($table_groups, ['name' => $name]);
    }
    pv_filters_redirect(admin_url('admin.php?page=produkt-filters'));
}

// Handle group delete
if (isset($_GET['delete_group']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $gid = intval($_GET['delete_group']);
    $filter_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table_filters} WHERE group_id = %d", $gid));
    foreach ($filter_ids as $fid) {
        $wpdb->delete($wpdb->prefix . 'produkt_category_filters', ['filter_id' => $fid]);
    }
    $wpdb->delete($table_groups, ['id' => $gid]);
    $wpdb->delete($table_filters, ['group_id' => $gid]);
    pv_filters_redirect(admin_url('admin.php?page=produkt-filters'));
}

// Handle option create/update
if (isset($_POST['save_option'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $name = sanitize_text_field($_POST['option_name']);
    $gid  = intval($_POST['group_id']);
    $id   = isset($_POST['option_id']) ? intval($_POST['option_id']) : 0;
    if ($id) {
        $wpdb->update($table_filters, ['name' => $name], ['id' => $id]);
    } else {
        $wpdb->insert($table_filters, ['group_id' => $gid, 'name' => $name]);
    }
    pv_filters_redirect(admin_url('admin.php?page=produkt-filters&group=' . $gid));
}

// Handle option delete
if (isset($_GET['delete_option']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $fid = intval($_GET['delete_option']);
    $gid = intval($_GET['group'] ?? 0);
    $wpdb->delete($table_filters, ['id' => $fid]);
    $wpdb->delete($wpdb->prefix . 'produkt_category_filters', ['filter_id' => $fid]);
    pv_filters_redirect(admin_url('admin.php?page=produkt-filters' . ($gid ? '&group=' . $gid : '')));
}

$selected_group_id = isset($_GET['group']) ? intval($_GET['group']) : 0;
$add_group    = isset($_GET['add_group']);
$edit_group_id = isset($_GET['edit_group']) ? intval($_GET['edit_group']) : 0;
$add_option   = isset($_GET['add_option']);
$edit_option_id = isset($_GET['edit_option']) ? intval($_GET['edit_option']) : 0;

$groups = $wpdb->get_results("SELECT * FROM {$table_groups} ORDER BY name");
$current_group = $selected_group_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_groups} WHERE id = %d", $selected_group_id)) : null;
$options = $current_group ? $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_filters} WHERE group_id = %d ORDER BY name", $selected_group_id)) : [];
?>

<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Filter verwalten</p>

    <div class="dashboard-grid">
        <div class="dashboard-left">
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Alle Filter Kategorien</h2>
                        <p class="card-subline">Übersicht der Filtergruppen</p>
                    </div>
                    <button type="button" class="icon-btn add-category-btn" aria-label="Hinzufügen" onclick="window.location.href='<?php echo admin_url('admin.php?page=produkt-filters&add_group=1'); ?>'">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3"><path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/><path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/></svg>
                    </button>
                </div>
                <table class="activity-table">
                    <thead><tr><th>Titel</th><th>Aktion</th></tr></thead>
                    <tbody>
                        <?php if ($add_group): ?>
                            <tr>
                                <td colspan="2">
                                    <form method="post" class="inline-form">
                                        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
                                        <input type="text" name="name" required class="regular-text">
                                        <button type="submit" name="save_group" class="icon-btn" aria-label="Speichern">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3"><path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/><path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($groups as $g): ?>
                            <?php if ($edit_group_id === intval($g->id)): ?>
                                <tr>
                                    <td colspan="2">
                                        <form method="post" class="inline-form">
                                            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
                                            <input type="hidden" name="group_id" value="<?php echo $g->id; ?>">
                                            <input type="text" name="name" value="<?php echo esc_attr($g->name); ?>" required class="regular-text">
                                            <button type="submit" name="save_group" class="icon-btn" aria-label="Speichern">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3"><path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/><path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td><?php echo esc_html($g->name); ?></td>
                                    <td>
                                    <button type="button" class="icon-btn" aria-label="Optionen" onclick="window.location.href='<?php echo admin_url('admin.php?page=produkt-filters&group=' . $g->id); ?>'">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3"><path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/><path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/></svg>
                                    </button>
                                    <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='<?php echo admin_url('admin.php?page=produkt-filters&edit_group=' . $g->id); ?>'">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1"><path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/><path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/></svg>
                                    </button>
                                    <button type="button" class="icon-btn" aria-label="Löschen" onclick="if(confirm('Bist du sicher das du Löschen möchtest?')){window.location.href='<?php echo admin_url('admin.php?page=produkt-filters&delete_group=' . $g->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>';}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                                    </button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dashboard-right">
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Filteroptionen</h2>
                        <p class="card-subline">Fügen Sie Filter hinzu</p>
                    </div>
                    <?php if ($current_group): ?>
                        <button type="button" class="icon-btn add-category-btn" aria-label="Hinzufügen" onclick="window.location.href='<?php echo admin_url('admin.php?page=produkt-filters&group=' . $current_group->id . '&add_option=1'); ?>'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3"><path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/><path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/></svg>
                        </button>
                    <?php endif; ?>
                </div>
                <table class="activity-table">
                    <thead><tr><th>Name</th><th>Aktion</th></tr></thead>
                    <tbody>
                        <?php if ($current_group): ?>
                            <?php if ($add_option): ?>
                                <tr>
                                    <td colspan="2">
                                        <form method="post" class="inline-form">
                                            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
                                            <input type="hidden" name="group_id" value="<?php echo $current_group->id; ?>">
                                            <input type="text" name="option_name" required class="regular-text">
                                            <button type="submit" name="save_option" class="icon-btn" aria-label="Speichern">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3"><path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/><path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($options as $opt): ?>
                                <?php if ($edit_option_id === intval($opt->id)): ?>
                                    <tr>
                                        <td colspan="2">
                                            <form method="post" class="inline-form">
                                                <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
                                                <input type="hidden" name="group_id" value="<?php echo $current_group->id; ?>">
                                                <input type="hidden" name="option_id" value="<?php echo $opt->id; ?>">
                                                <input type="text" name="option_name" value="<?php echo esc_attr($opt->name); ?>" required class="regular-text">
                                                <button type="submit" name="save_option" class="icon-btn" aria-label="Speichern">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3"><path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/><path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/></svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td><?php echo esc_html($opt->name); ?></td>
                                        <td>
                                            <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='<?php echo admin_url('admin.php?page=produkt-filters&group=' . $current_group->id . '&edit_option=' . $opt->id); ?>'">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1"><path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/><path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/></svg>
                                            </button>
                                            <button type="button" class="icon-btn" aria-label="Löschen" onclick="if(confirm('Bist du sicher das du Löschen möchtest?')){window.location.href='<?php echo admin_url('admin.php?page=produkt-filters&group=' . $current_group->id . '&delete_option=' . $opt->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>';}">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2">Wählen Sie eine Filterkategorie</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
