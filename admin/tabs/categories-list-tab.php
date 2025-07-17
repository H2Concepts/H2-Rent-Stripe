<?php
// Categories List Tab Content
?>

<div class="produkt-categories-list">
    <div class="produkt-list-header">
        <h3>📋 Alle Produkte</h3>
        <form method="get" action="" class="produkt-filter-form">
            <input type="hidden" name="page" value="produkt-categories">
            <input type="hidden" name="tab" value="list">
            <select name="prodcat">
                <option value="0">Alle Kategorien</option>
                <?php foreach ($product_categories as $pc): ?>
                    <option value="<?php echo $pc->id; ?>" <?php selected($selected_prodcat, $pc->id); ?>><?php echo str_repeat('--', $pc->depth) . ' ' . esc_html($pc->name); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="s" value="<?php echo esc_attr($search_term); ?>" placeholder="Produkt suchen...">
            <button type="submit" class="button">Filtern</button>
        </form>
    </div>
    
    <?php if (empty($categories)): ?>
    <div class="produkt-empty-state">
        <div class="produkt-empty-icon">🏷️</div>
        <h4>Noch keine Produkte vorhanden</h4>
        <p>Erstellen Sie Ihr erstes Produkt.</p>
        <a href="<?php echo admin_url('admin.php?page=produkt-categories&tab=add'); ?>" class="button button-primary">
            ➕ Erstes Produkt erstellen
        </a>
    </div>
    <?php else: ?>
    
    <div class="produkt-categories-grid">
        <?php foreach ($categories as $category): ?>
        <div class="produkt-category-card">
            <div class="produkt-category-image">
                <?php if (!empty($category->default_image)): ?>
                    <img src="<?php echo esc_url($category->default_image); ?>" alt="<?php echo esc_attr($category->name); ?>">
                <?php else: ?>
                    <div class="produkt-category-placeholder">
                        <span>🏷️</span>
                        <small>Kein Bild</small>
                    </div>
                <?php endif; ?>
                
            </div>
            
            <div class="produkt-category-content">
                <h4><?php echo esc_html($category->name); ?></h4>
                
                <div class="produkt-category-shortcode">
                    <code>[produkt_product category="<?php echo esc_html($category->shortcode); ?>"]</code>
                </div>

                <?php $product_url = home_url('/shop/produkt/' . sanitize_title($category->product_title)); ?>
                <div class="produkt-category-url">
                    <code><?php echo esc_url($product_url); ?></code>
                </div>

                <?php if (!empty($category->categories)): ?>
                <div class="produkt-category-tags">
                    <?php foreach (explode(',', $category->categories) as $cat_name): ?>
                        <span class="badge"><?php echo esc_html(trim($cat_name)); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="produkt-category-meta">
                    <div class="produkt-category-info">
                        <small>Sortierung: <?php echo $category->sort_order; ?></small>
                        <?php if (!empty($category->meta_title)): ?>
                            <small>SEO: ✅ Konfiguriert</small>
                        <?php else: ?>
                            <small>SEO: ❌ Nicht konfiguriert</small>
                        <?php endif; ?>
                    </div>
                    
                </div>
                
                <div class="produkt-category-actions">
                    <a href="<?php echo esc_url($product_url); ?>" class="button button-small" target="_blank">
                        🔍 Seite ansehen
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=produkt-categories&tab=edit&edit=' . $category->id); ?>" class="button button-small">
                        ✏️ Bearbeiten
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=produkt-categories&delete=' . $category->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>"
                       class="button button-small produkt-delete-button"
                       onclick="return confirm('Sind Sie sicher, dass Sie dieses Produkt löschen möchten?\n\n\"<?php echo esc_js($category->name); ?>\" und alle zugehörigen Daten werden unwiderruflich gelöscht!')">
                        🗑️ Löschen
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
</div>


