<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get all categories for dropdown
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order, name");

// Get selected category from URL parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : (isset($categories[0]) ? $categories[0]->id : 0);

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

// Get branding settings
$branding = array();
$branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}federwiegen_branding");
foreach ($branding_results as $result) {
    $branding[$result->setting_key] = $result->setting_value;
}
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="federwiegen-admin-header-compact">
        <div class="federwiegen-admin-logo-compact">📊</div>
        <div class="federwiegen-admin-title-compact">
            <h1>Analytics</h1>
            <p>Statistiken & Bestellungen</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="federwiegen-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Analytics</strong>
    </div>
    
    <!-- Category Selection -->
    <div class="federwiegen-category-selector">
        <form method="get" action="">
            <input type="hidden" name="page" value="federwiegen-analytics">
            <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">
            <label for="category-select"><strong>🏷️ Kategorie:</strong></label>
            <select name="category" id="category-select" onchange="this.form.submit()">
                <option value="0" <?php selected($selected_category, 0); ?>>Alle Kategorien</option>
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category->id; ?>" <?php selected($selected_category, $category->id); ?>>
                    <?php echo esc_html($category->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <noscript><input type="submit" value="Wechseln" class="button"></noscript>
        </form>
    </div>
    
    <!-- Tab Navigation -->
    <div class="federwiegen-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-analytics&category=' . $selected_category . '&tab=dashboard'); ?>"
           class="federwiegen-tab <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
            📊 Analytics Dashboard
        </a>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-analytics&category=' . $selected_category . '&tab=orders'); ?>"
           class="federwiegen-tab <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">
            📋 Bestellungen
        </a>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-analytics&category=' . $selected_category . '&tab=notifications'); ?>"
           class="federwiegen-tab <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>">
            📧 Benachrichtigungen
        </a>
    </div>
    
    <!-- Tab Content -->
    <div class="federwiegen-tab-content">
        <?php
        switch ($active_tab) {
            case 'dashboard':
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/analytics-dashboard-tab.php';
                break;
            case 'orders':
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/orders-tab.php';
                break;
            case 'notifications':
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/notifications-tab.php';
                break;
            default:
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/analytics-dashboard-tab.php';
        }
        ?>
    </div>
</div>