<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'produkt_conditions';

// Get all categories for dropdown
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order, name");

// Get selected category from URL parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : (isset($categories[0]) ? $categories[0]->id : 1);

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

// Variants for toggle section
$variants = $wpdb->get_results($wpdb->prepare(
    "SELECT id, name FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d ORDER BY sort_order, name",
    $selected_category
));

// Handle form submissions
if (isset($_POST['submit'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $description = sanitize_textarea_field($_POST['description']);
    $price_modifier = floatval($_POST['price_modifier']) / 100; // Convert percentage to decimal
    $sort_order = intval($_POST['sort_order']);

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $result = $wpdb->update(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'description' => $description,
                'price_modifier' => $price_modifier,
                'sort_order' => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%s', '%f', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            $condition_id = intval($_POST['id']);
            echo '<div class="notice notice-success"><p>✅ Zustand erfolgreich aktualisiert!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Aktualisieren: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    } else {
        // Insert
        $result = $wpdb->insert(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'description' => $description,
                'price_modifier' => $price_modifier,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%s', '%f', '%d')
        );
        
        if ($result !== false) {
            $condition_id = $wpdb->insert_id;
            echo '<div class="notice notice-success"><p>✅ Zustand erfolgreich hinzugefügt!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }

    if (isset($condition_id)) {
        $variant_inputs = $_POST['variant_available'] ?? array();
        $table_variant_options = $wpdb->prefix . 'produkt_variant_options';
        $all_variants = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d",
            $category_id
        ));
        foreach ($all_variants as $v) {
            $available = isset($variant_inputs[$v->id]) ? 1 : 0;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_variant_options WHERE variant_id = %d AND option_type = 'condition' AND option_id = %d",
                $v->id,
                $condition_id
            ));
            if ($exists) {
                $wpdb->update($table_variant_options, ['available' => $available], ['id' => $exists], ['%d'], ['%d']);
            } else {
                $wpdb->insert($table_variant_options, [
                    'variant_id' => $v->id,
                    'option_type' => 'condition',
                    'option_id' => $condition_id,
                    'available' => $available
                ], ['%d','%s','%d','%d']);
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $result = $wpdb->delete($table_name, array('id' => intval($_GET['delete'])), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Zustand gelöscht!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
    }
}

// Get item for editing
$edit_item = null;
$variant_availability = array();
if (isset($_GET['edit'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit'])));
    if ($edit_item) {
        $selected_category = $edit_item->category_id;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT variant_id, available FROM {$wpdb->prefix}produkt_variant_options WHERE option_type = 'condition' AND option_id = %d",
            $edit_item->id
        ));
        $variant_availability = array();
        foreach ($rows as $row) {
            $variant_availability[$row->variant_id] = intval($row->available);
        }
    }
}

// Get current category info
$current_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}produkt_categories WHERE id = %d", $selected_category));

// Get all conditions for selected category
$conditions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, name", $selected_category));

$condition_variant_counts = array();
if (!empty($conditions)) {
    $condition_ids = wp_list_pluck($conditions, 'id');
    if (!empty($condition_ids)) {
        $condition_ids = array_map('intval', $condition_ids);
        $placeholders = implode(',', array_fill(0, count($condition_ids), '%d'));
        $sql = $wpdb->prepare(
            "SELECT option_id, SUM(available) AS available_count FROM {$wpdb->prefix}produkt_variant_options WHERE option_type = 'condition' AND option_id IN ($placeholders) GROUP BY option_id",
            ...$condition_ids
        );
        $rows = $wpdb->get_results($sql);
        foreach ($rows as $row) {
            $condition_variant_counts[intval($row->option_id)] = intval($row->available_count);
        }
    }
}

$subline_text = 'Produktzustände & Preisanpassungen verwalten.';
if ($active_tab === 'add') {
    $subline_text = 'Erstellen Sie einen neuen Zustand für das ausgewählte Produkt.';
} elseif ($active_tab === 'edit' && $edit_item) {
    $subline_text = 'Bearbeiten Sie den Zustand "' . esc_html($edit_item->name) . '".';
}
?>

<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline"><?php echo $subline_text; ?></p>

    <?php if ($active_tab === 'list'): ?>
    <div class="dashboard-grid">
        <div class="dashboard-left">
            <div class="dashboard-card card-product-selector">
                <h2>Produkt auswählen</h2>
                <p class="card-subline">Für welches Produkt möchten Sie Zustände verwalten?</p>
                <form method="get" action="" class="produkt-category-selector" style="background:none;border:none;padding:0;">
                    <input type="hidden" name="page" value="produkt-conditions">
                    <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">
                    <select name="category" id="category-select" onchange="this.form.submit()">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category->id; ?>" <?php selected($selected_category, $category->id); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><input type="submit" value="Wechseln" class="button"></noscript>
                </form>
                <?php if ($current_category): ?>
                <div class="selected-product-preview">
                    <?php if (!empty($current_category->default_image)): ?>
                        <img src="<?php echo esc_url($current_category->default_image); ?>" alt="<?php echo esc_attr($current_category->name); ?>">
                    <?php else: ?>
                        <div class="placeholder-icon">🔄</div>
                    <?php endif; ?>
                    <div class="tile-overlay"><span><?php echo esc_html($current_category->name); ?></span></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="dashboard-right">
            <div class="dashboard-row">
                <div class="dashboard-card card-new-product">
                    <h2>Neuer Zustand</h2>
                    <p class="card-subline">Zustand erstellen</p>
                    <a href="<?php echo admin_url('admin.php?page=produkt-conditions&category=' . $selected_category . '&tab=add'); ?>" class="icon-btn add-product-btn" aria-label="Hinzufügen">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3">
                            <path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/>
                            <path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/>
                        </svg>
                    </a>
                </div>
                <div class="dashboard-card card-quicknav">
                    <h2>Schnellnavigation</h2>
                    <p class="card-subline">Direkt zu wichtigen Listen</p>
                    <div class="quicknav-grid">
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-verleih">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🏠</div>
                                    <div class="quicknav-label">Dashboard</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-categories">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🧩</div>
                                    <div class="quicknav-label">Kategorien</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-products">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🏷️</div>
                                    <div class="quicknav-label">Produkte</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-variants&category=<?php echo $selected_category; ?>">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🧩</div>
                                    <div class="quicknav-label">Ausführungen</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Zustände</h2>
                        <p class="card-subline">Verfügbare Produktzustände</p>
                    </div>
                </div>
                <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/conditions-list-tab.php'; ?>
            </div>
        </div>
    </div>
    <?php elseif ($active_tab === 'add'): ?>
        <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/conditions-add-tab.php'; ?>
    <?php elseif ($active_tab === 'edit' && $edit_item): ?>
        <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/conditions-edit-tab.php'; ?>
    <?php else: ?>
        <div class="dashboard-grid">
            <div class="dashboard-left">
                <div class="dashboard-card card-product-selector">
                    <h2>Produkt auswählen</h2>
                    <p class="card-subline">Bitte wählen Sie ein Produkt aus, um Zustände zu verwalten.</p>
                    <form method="get" action="" class="produkt-category-selector" style="background:none;border:none;padding:0;">
                        <input type="hidden" name="page" value="produkt-conditions">
                        <input type="hidden" name="tab" value="list">
                        <select name="category" id="category-select" onchange="this.form.submit()">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category->id; ?>" <?php selected($selected_category, $category->id); ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <noscript><input type="submit" value="Wechseln" class="button"></noscript>
                    </form>
                    <?php if ($current_category): ?>
                    <div class="selected-product-preview">
                        <?php if (!empty($current_category->default_image)): ?>
                            <img src="<?php echo esc_url($current_category->default_image); ?>" alt="<?php echo esc_attr($current_category->name); ?>">
                        <?php else: ?>
                            <div class="placeholder-icon">🔄</div>
                        <?php endif; ?>
                        <div class="tile-overlay"><span><?php echo esc_html($current_category->name); ?></span></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dashboard-right">
                <div class="dashboard-card">
                    <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/conditions-list-tab.php'; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
