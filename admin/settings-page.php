<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'branding';

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
        <div class="federwiegen-admin-logo-compact">⚙️</div>
        <div class="federwiegen-admin-title-compact">
            <h1>Einstellungen</h1>
            <p>Branding & Konfiguration</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="federwiegen-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Einstellungen</strong>
    </div>
    
    <!-- Tab Navigation -->
    <div class="federwiegen-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-settings&tab=branding'); ?>"
           class="federwiegen-tab <?php echo $active_tab === 'branding' ? 'active' : ''; ?>">
            🎨 Branding
        </a>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-settings&tab=popup'); ?>"
           class="federwiegen-tab <?php echo $active_tab === 'popup' ? 'active' : ''; ?>">
            📣 Popup
        </a>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-settings&tab=debug'); ?>"
           class="federwiegen-tab <?php echo $active_tab === 'debug' ? 'active' : ''; ?>">
            🔧 Debug
        </a>
    </div>
    
    <!-- Tab Content -->
    <div class="federwiegen-tab-content">
        <?php
        switch ($active_tab) {
            case 'branding':
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/branding-tab.php';
                break;
            case 'popup':
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/popup-tab.php';
                break;
            case 'debug':
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/debug-tab.php';
                break;
            default:
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/branding-tab.php';
        }
        ?>
    </div>
</div>