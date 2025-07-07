<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'branding';

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
        <div class="produkt-admin-logo-compact">⚙️</div>
        <div class="produkt-admin-title-compact">
            <h1>Einstellungen</h1>
            <p>Branding & Konfiguration</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="produkt-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=produkt-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Einstellungen</strong>
    </div>
    
    <!-- Tab Navigation -->
    <div class="produkt-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=branding'); ?>"
           class="produkt-tab <?php echo $active_tab === 'branding' ? 'active' : ''; ?>">
            🎨 Branding
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=popup'); ?>"
           class="produkt-tab <?php echo $active_tab === 'popup' ? 'active' : ''; ?>">
            📣 Popup
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=stripe'); ?>"
           class="produkt-tab <?php echo $active_tab === 'stripe' ? 'active' : ''; ?>">
            💳 Stripe Integration
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=pricing'); ?>"
           class="produkt-tab <?php echo $active_tab === 'pricing' ? 'active' : ''; ?>">
            💲 Preis-Einstellungen
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=debug'); ?>"
           class="produkt-tab <?php echo $active_tab === 'debug' ? 'active' : ''; ?>">
            🔧 Debug
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=notifications'); ?>"
           class="produkt-tab <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>">
            📧 Benachrichtigungen
        </a>
    </div>
    
    <!-- Tab Content -->
    <div class="produkt-tab-content">
        <?php
        switch ($active_tab) {
            case 'branding':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/branding-tab.php';
                break;
            case 'popup':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/popup-tab.php';
                break;
            case 'stripe':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/stripe-tab.php';
                break;
            case 'pricing':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/pricing-tab.php';
                break;
            case 'debug':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/debug-tab.php';
                break;
            case 'notifications':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/notifications-tab.php';
                break;
            default:
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/branding-tab.php';
        }
        ?>
    </div>
</div>