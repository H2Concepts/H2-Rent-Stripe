<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'federwiegen_extras';

// Get all categories for dropdown
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order, name");

// Get selected category from URL parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : (isset($categories[0]) ? $categories[0]->id : 1);

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

// Ensure image_url column exists
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'image_url'");
if (empty($column_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN image_url TEXT AFTER price");
}

// Ensure category_id column exists
$category_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'category_id'");
if (empty($category_column_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN category_id mediumint(9) DEFAULT 1 AFTER id");
}

// Handle form submissions
if (isset($_POST['submit'])) {
    \FederwiegenVerleih\Admin::verify_admin_action();
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $price = floatval($_POST['price']);
    $image_url = esc_url_raw($_POST['image_url']);
    $active = isset($_POST['active']) ? 1 : 0;
    $sort_order = intval($_POST['sort_order']);

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $result = $wpdb->update(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'price' => $price,
                'image_url' => $image_url,
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%f', '%s', '%d', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Extra erfolgreich aktualisiert!</p></div>';
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
                'price' => $price,
                'image_url' => $image_url,
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%f', '%s', '%d', '%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Extra erfolgreich hinzugefügt!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'federwiegen_admin_action')) {
    $result = $wpdb->delete($table_name, array('id' => intval($_GET['delete'])), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Extra gelöscht!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit'])));
    if ($edit_item) {
        $selected_category = $edit_item->category_id;
    }
}

// Get current category info
$current_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d", $selected_category));

// Get all extras for selected category
$extras = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, name", $selected_category));
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="federwiegen-admin-header-compact">
        <div class="federwiegen-admin-logo-compact">🎁</div>
        <div class="federwiegen-admin-title-compact">
            <h1>Extras verwalten</h1>
            <p>Zusatzoptionen mit Bildern</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="federwiegen-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Extras</strong>
    </div>
    
    <!-- Category Selection -->
    <div class="federwiegen-category-selector">
        <form method="get" action="">
            <input type="hidden" name="page" value="federwiegen-extras">
            <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">
            <label for="category-select"><strong>🏷️ Kategorie:</strong></label>
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
        <div class="federwiegen-category-info">
            <code>[federwiegen_product category="<?php echo esc_html($current_category->shortcode); ?>"]</code>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Tab Navigation -->
    <div class="federwiegen-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-extras&category=' . $selected_category . '&tab=list'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>">
            📋 Übersicht
        </a>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-extras&category=' . $selected_category . '&tab=add'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            ➕ Neues Extra
        </a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-extras&category=' . $selected_category . '&tab=edit&edit=' . $edit_item->id); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'edit' ? 'active' : ''; ?>">
            ✏️ Bearbeiten
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Tab Content -->
    <div class="federwiegen-tab-content">
        <?php
        switch ($active_tab) {
            case 'add':
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/extras-add-tab.php';
                break;
            case 'edit':
                if ($edit_item) {
                    include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/extras-edit-tab.php';
                } else {
                    include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/extras-list-tab.php';
                }
                break;
            case 'list':
            default:
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/extras-list-tab.php';
        }
        ?>
    </div>
</div>
