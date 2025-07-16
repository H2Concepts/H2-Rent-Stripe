<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Hard delete a product if requested
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $id = intval($_GET['delete']);

    // Optional: verknüpfte Daten wie Bilder, Varianten etc. löschen

    $wpdb->delete($wpdb->prefix . 'produkt_categories', ['id' => $id], ['%d']);
    $wpdb->delete($wpdb->prefix . 'produkt_product_to_category', ['produkt_id' => $id], ['%d']);

    echo '<div class="notice notice-success"><p>✅ Produkt gelöscht!</p></div>';
}

// Get all categories for dropdown
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order, name");

// Get selected category from URL parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : (isset($categories[0]) ? $categories[0]->id : 1);

// Get current category info
$current_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}produkt_categories WHERE id = %d", $selected_category));

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'variants';

// Get branding settings
$branding = array();
$branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}produkt_branding");
foreach ($branding_results as $result) {
    $branding[$result->setting_key] = $result->setting_value;
}
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="produkt-admin-header-compact">
        <div class="produkt-admin-logo-compact">📦</div>
        <div class="produkt-admin-title-compact">
            <h1>Produkte verwalten</h1>
            <p>Ausführungen, Extras, Farben & Zustände</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="produkt-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=produkt-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Produkte</strong>
    </div>
    
    <!-- Category Selection -->
    <div class="produkt-category-selector">
        <form method="get" action="">
            <input type="hidden" name="page" value="produkt-products">
            <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">
            <label for="category-select"><strong>🏷️ Produkt:</strong></label>
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
        <div class="produkt-category-info">
            <code>[produkt_product category="<?php echo esc_html($current_category->shortcode); ?>"]</code>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Tab Navigation -->
    <div class="produkt-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=variants'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'variants' ? 'active' : ''; ?>">
            🖼️ Ausführungen
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=extras'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'extras' ? 'active' : ''; ?>">
            🎁 Extras
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=conditions'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'conditions' ? 'active' : ''; ?>">
            🔄 Zustände
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=colors'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'colors' ? 'active' : ''; ?>">
            🎨 Farben
        </a>
    </div>
    
    <!-- Tab Content -->
    <div class="produkt-tab-content">
        <?php
        switch ($active_tab) {
            case 'variants':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/variants-tab.php';
                break;
            case 'extras':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/extras-tab.php';
                break;
            case 'conditions':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/conditions-tab.php';
                break;
            case 'colors':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/colors-tab.php';
                break;
            default:
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/variants-tab.php';
        }
        ?>
    </div>
</div>