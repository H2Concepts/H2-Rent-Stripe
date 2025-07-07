<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="produkt-admin-header-compact">
        <div class="produkt-admin-logo-compact">🏷️</div>
        <div class="produkt-admin-title-compact">
            <h1>Produkte verwalten</h1>
            <p>Produkte & SEO-Einstellungen</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="produkt-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=produkt-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Produkte</strong>
    </div>
    
    <!-- Tab Navigation -->
    <div class="produkt-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=produkt-categories&tab=list'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>">
            📋 Übersicht
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-categories&tab=add'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            ➕ Neues Produkt
        </a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=produkt-categories&tab=edit&edit=' . $edit_item->id); ?>" 
           class="produkt-tab <?php echo $active_tab === 'edit' ? 'active' : ''; ?>">
            ✏️ Bearbeiten
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Tab Content -->
    <div class="produkt-tab-content">
        <?php
        switch ($active_tab) {
            case 'add':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/categories-add-tab.php';
                break;
            case 'edit':
                if ($edit_item) {
                    include PRODUKT_PLUGIN_PATH . 'admin/tabs/categories-edit-tab.php';
                } else {
                    include PRODUKT_PLUGIN_PATH . 'admin/tabs/categories-list-tab.php';
                }
                break;
            case 'list':
            default:
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/categories-list-tab.php';
        }
        ?>
    </div>
</div>
