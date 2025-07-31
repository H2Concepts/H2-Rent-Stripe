<?php
if (!defined('ABSPATH')) {
    exit;
}

use ProduktVerleih\Database;

global $wpdb;

// Latest 4 products
$recent_products = $wpdb->get_results(
    "SELECT id, name, default_image FROM {$wpdb->prefix}produkt_categories ORDER BY id DESC LIMIT 4"
);

// Variables provided by Admin::categories_page()
$categories        = $categories ?? [];
$product_categories = $product_categories ?? [];
$selected_prodcat  = $selected_prodcat ?? 0;
$search_term       = $search_term ?? '';
$active_tab        = $active_tab ?? 'list';
$edit_item         = $edit_item ?? null;
?>

<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting">Hallo, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Produkte verwalten</p>

<?php if ($active_tab === 'list'): ?>
    <div class="dashboard-grid">
        <div class="dashboard-left">
            <div class="dashboard-card">
                <h2>Neueste Produkte</h2>
                <p class="card-subline">Die zuletzt hinzugefügten Produkte</p>
                <div class="produkt-category-cards">
                    <?php foreach ($recent_products as $prod): ?>
                        <div class="produkt-category-card" style="position:relative;">
                            <div class="produkt-category-image">
                                <?php if (!empty($prod->default_image)): ?>
                                    <img src="<?php echo esc_url($prod->default_image); ?>" alt="<?php echo esc_attr($prod->name); ?>">
                                <?php else: ?>
                                    <div class="produkt-category-placeholder">
                                        <span>🏷️</span>
                                        <small>Kein Bild</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="prod-card-title" style="display:flex;justify-content:space-between;align-items:center;font-size:18px;">
                                <span><?php echo esc_html($prod->name); ?></span>
                                <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='?page=produkt-categories&tab=edit&edit=<?php echo $prod->id; ?>'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                    <path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/>
                                    <path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/>
                                </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="dashboard-right">
            <div class="dashboard-row">
                <div class="dashboard-card" style="flex:1;">
                    <h2>Filter</h2>
                    <form method="get" class="produkt-filter-form">
                        <input type="hidden" name="page" value="produkt-categories">
                        <select name="prodcat">
                            <option value="0">Alle Kategorien</option>
                            <?php foreach ($product_categories as $pc): ?>
                                <option value="<?php echo $pc->id; ?>" <?php selected($selected_prodcat, $pc->id); ?>><?php echo str_repeat('--', $pc->depth) . ' ' . esc_html($pc->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="s" placeholder="Produkt suchen..." value="<?php echo esc_attr($search_term); ?>">
                        <button type="submit" class="button">Filtern</button>
                    </form>
                </div>
                <div class="dashboard-card card-new-product" style="flex:1; position:relative;">
                    <h2>Neues Produkt</h2>
                    <p class="card-subline">Produkt erstellen</p>
                    <a href="<?php echo admin_url('admin.php?page=produkt-categories&tab=add'); ?>" class="icon-btn add-product-btn" style="position:absolute;bottom:15px;right:15px;" aria-label="Hinzufügen">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3">
                            <path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/>
                            <path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/>
                        </svg>
                    </a>
                </div>
            </div>
            <div class="dashboard-card">
                <h2>Alle Produkte</h2>
                <p class="card-subline">Übersicht Ihrer Produkte</p>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Bild</th>
                            <th>Name</th>
                            <th>Shortcode</th>
                            <th>Kategorien</th>
                            <th>SEO</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($cat->default_image)): ?>
                                        <img src="<?php echo esc_url($cat->default_image); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:4px;" alt="<?php echo esc_attr($cat->name); ?>">
                                    <?php else: ?>
                                        <div style="width:60px;height:60px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;">🏷️</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($cat->name); ?></td>
                                <td><code>[produkt_product category="<?php echo esc_html($cat->shortcode); ?>"]</code></td>
                                <td><?php echo esc_html($cat->categories ?: ''); ?></td>
                                <td><?php echo $cat->meta_title ? '✅' : '❌'; ?></td>
                                <td>
                                    <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='?page=produkt-categories&tab=edit&edit=<?php echo $cat->id; ?>'">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                            <path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/>
                                            <path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="icon-btn" onclick="if(confirm('Wirklich löschen?')){window.location.href='?page=produkt-categories&delete=<?php echo $cat->id; ?>&fw_nonce=<?php echo wp_create_nonce('produkt_admin_action'); ?>';}" aria-label="Löschen">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                            <path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/>
                                            <path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($active_tab === 'add'): ?>
    <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/categories-add-tab.php'; ?>
<?php elseif ($active_tab === 'edit' && $edit_item): ?>
    <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/categories-edit-tab.php'; ?>
<?php endif; ?>
</div>
