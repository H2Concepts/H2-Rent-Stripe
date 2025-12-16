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

function produkt_settings_icon($slug, $active_tab)
{
    $svg = file_get_contents(PRODUKT_PLUGIN_PATH . 'assets/settings-icons/' . $slug . '.svg');
    $classes = $slug . '-icon';
    if ($active_tab === $slug) {
        $classes .= ' active';
    }
    return str_replace('<svg', '<svg class="' . $classes . '"', $svg);
}
?>

<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>,
        <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline"><?php echo esc_html__('Einstellungen verwalten', 'h2-rental-pro'); ?></p>

    <div class="settings-layout">
        <nav class="settings-menu">
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=branding'); ?>"
                class="<?php echo $active_tab === 'branding' ? 'active' : ''; ?>"
                aria-label="<?php echo esc_attr__('Branding', 'h2-rental-pro'); ?>"
                title="<?php echo esc_attr__('Branding', 'h2-rental-pro'); ?>">
                <?php echo produkt_settings_icon('branding', $active_tab); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=popup'); ?>"
                class="<?php echo $active_tab === 'popup' ? 'active' : ''; ?>"
                aria-label="<?php echo esc_attr__('Popup', 'h2-rental-pro'); ?>"
                title="<?php echo esc_attr__('Popup', 'h2-rental-pro'); ?>">
                <?php echo produkt_settings_icon('popup', $active_tab); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=stripe'); ?>"
                class="<?php echo $active_tab === 'stripe' ? 'active' : ''; ?>"
                aria-label="<?php echo esc_attr__('Stripe', 'h2-rental-pro'); ?>"
                title="<?php echo esc_attr__('Stripe', 'h2-rental-pro'); ?>">
                <?php echo produkt_settings_icon('stripe', $active_tab); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=buttons'); ?>"
                class="<?php echo $active_tab === 'buttons' ? 'active' : ''; ?>"
                aria-label="<?php echo esc_attr__('Buttons & Tooltips', 'h2-rental-pro'); ?>"
                title="<?php echo esc_attr__('Buttons & Tooltips', 'h2-rental-pro'); ?>">
                <?php echo produkt_settings_icon('buttons', $active_tab); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=email'); ?>"
                class="<?php echo $active_tab === 'email' ? 'active' : ''; ?>"
                aria-label="<?php echo esc_attr__('E-Mail Versand', 'h2-rental-pro'); ?>"
                title="<?php echo esc_attr__('E-Mail Versand', 'h2-rental-pro'); ?>">
                <?php echo produkt_settings_icon('email', $active_tab); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=notifications'); ?>"
                class="<?php echo $active_tab === 'notifications' ? 'active' : ''; ?>"
                aria-label="<?php echo esc_attr__('Benachrichtigungen', 'h2-rental-pro'); ?>"
                title="<?php echo esc_attr__('Benachrichtigungen', 'h2-rental-pro'); ?>">
                <?php echo produkt_settings_icon('notifications', $active_tab); ?>
            </a>
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