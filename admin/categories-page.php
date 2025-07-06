<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="federwiegen-admin-header-compact">
        <div class="federwiegen-admin-logo-compact">🏷️</div>
        <div class="federwiegen-admin-title-compact">
            <h1>Kategorien verwalten</h1>
            <p>Produktkategorien & SEO-Einstellungen</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="federwiegen-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Kategorien</strong>
    </div>
    
    <!-- Tab Navigation -->
    <div class="federwiegen-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&tab=list'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>">
            📋 Übersicht
        </a>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&tab=add'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            ➕ Neue Kategorie
        </a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&tab=edit&edit=' . $edit_item->id); ?>" 
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
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/categories-add-tab.php';
                break;
            case 'edit':
                if ($edit_item) {
                    include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/categories-edit-tab.php';
                } else {
                    include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/categories-list-tab.php';
                }
                break;
            case 'list':
            default:
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/categories-list-tab.php';
        }
        ?>
    </div>
</div>
