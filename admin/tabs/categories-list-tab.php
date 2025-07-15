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
                    <option value="<?php echo $pc->id; ?>" <?php selected($selected_prodcat, $pc->id); ?>><?php echo esc_html($pc->name); ?></option>
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

<style>
.produkt-categories-list {
    padding: 0;
}

.produkt-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.produkt-filter-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.produkt-filter-form select,
.produkt-filter-form input[type="text"] {
    padding: 6px 8px;
}

.produkt-list-header h3 {
    margin: 0;
    color: #3c434a;
}

.produkt-categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.produkt-category-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.produkt-category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: var(--produkt-primary);
}

.produkt-category-image {
    position: relative;
    height: 150px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.produkt-category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.produkt-category-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #6c757d;
}

.produkt-category-placeholder span {
    font-size: 3rem;
    opacity: 0.5;
}

.produkt-category-status {
    position: absolute;
    top: 8px;
    right: 8px;
}

.produkt-category-content {
    padding: 20px;
}

.produkt-category-content h4 {
    margin: 0 0 8px 0;
    color: #2a372a;
    font-size: 1.1rem;
    font-weight: 600;
}

.produkt-category-description {
    margin: 0 0 12px 0;
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.produkt-category-shortcode {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 8px;
    margin-bottom: 15px;
}

.produkt-category-shortcode code {
    background: none;
    padding: 0;
    font-size: 0.8rem;
    color: var(--produkt-primary);
    font-weight: 500;
}

.produkt-category-url {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 8px;
    margin-bottom: 15px;
}

.produkt-category-url code {
    background: none;
    padding: 0;
    font-size: 0.8rem;
    color: var(--produkt-primary);
    font-weight: 500;
}

.produkt-category-meta {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    padding-top: 15px;
    border-top: 1px solid #f8f9fa;
}

.produkt-category-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.produkt-category-info small {
    color: #6c757d;
    font-size: 0.8rem;
}

.produkt-category-shipping {
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.produkt-category-shipping strong {
    color: var(--produkt-primary);
    font-size: 1rem;
}

.produkt-category-shipping small {
    color: #6c757d;
    font-size: 0.8rem;
}

.produkt-category-shipping img {
    width: 32px;
    height: auto;
    margin-top: 4px;
}

.produkt-category-actions {
    display: flex;
    gap: 8px;
}

.produkt-category-actions .button {
    flex: 1;
    text-align: center;
    font-size: 0.85rem;
    padding: 6px 12px;
}

@media (max-width: 768px) {
    .produkt-list-header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .produkt-categories-grid {
        grid-template-columns: 1fr;
    }
    
    .produkt-category-actions {
        flex-direction: column;
    }
}
</style>
