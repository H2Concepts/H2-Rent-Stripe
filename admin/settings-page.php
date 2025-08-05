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

<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Einstellungen verwalten</p>

    <div class="settings-layout">
        <nav class="settings-menu">
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=branding'); ?>" class="<?php echo $active_tab === 'branding' ? 'active' : ''; ?>" aria-label="Branding" title="Branding">🎨</a>
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=popup'); ?>" class="<?php echo $active_tab === 'popup' ? 'active' : ''; ?>" aria-label="Popup" title="Popup">📣</a>
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=stripe'); ?>" class="<?php echo $active_tab === 'stripe' ? 'active' : ''; ?>" aria-label="Stripe" title="Stripe">💳</a>
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=buttons'); ?>" class="<?php echo $active_tab === 'buttons' ? 'active' : ''; ?>" aria-label="Buttons & Tooltips" title="Buttons & Tooltips">🔘</a>
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=email'); ?>" class="<?php echo $active_tab === 'email' ? 'active' : ''; ?>" aria-label="E-Mail Versand" title="E-Mail Versand">✉️</a>
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=notifications'); ?>" class="<?php echo $active_tab === 'notifications' ? 'active' : ''; ?>" aria-label="Benachrichtigungen" title="Benachrichtigungen">📧</a>
        </nav>
        <div class="settings-content">
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
                case 'buttons':
                    include PRODUKT_PLUGIN_PATH . 'admin/tabs/buttons-tab.php';
                    break;
                case 'email':
                    include PRODUKT_PLUGIN_PATH . 'admin/tabs/email-tab.php';
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
</div>