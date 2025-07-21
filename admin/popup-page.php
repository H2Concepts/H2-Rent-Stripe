<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <div class="produkt-admin-header-compact">
        <div class="produkt-admin-logo-compact">📣</div>
        <div class="produkt-admin-title-compact">
            <h1>Popup Einstellungen</h1>
            <p>Exit-Intent Popup konfigurieren</p>
        </div>
    </div>
    <div class="produkt-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=produkt-verleih'); ?>">Dashboard</a>
        <span>→</span>
        <strong>Popup</strong>
    </div>

    <div class="produkt-tab-content">
        <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/popup-tab.php'; ?>
    </div>
</div>
