<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <div class="federwiegen-admin-header-compact">
        <div class="federwiegen-admin-logo-compact">📣</div>
        <div class="federwiegen-admin-title-compact">
            <h1>Popup Einstellungen</h1>
            <p>Exit-Intent Popup konfigurieren</p>
        </div>
    </div>
    <div class="federwiegen-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>">Dashboard</a>
        <span>→</span>
        <strong>Popup</strong>
    </div>

    <div class="federwiegen-tab-content">
        <?php include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/popup-tab.php'; ?>
    </div>
</div>
