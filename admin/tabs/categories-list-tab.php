<?php
// Categories List Tab Content
?>

<div class="federwiegen-categories-list">
    <div class="federwiegen-list-header">
        <h3>📋 Alle Kategorien</h3>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&tab=add'); ?>" class="button button-primary">
            ➕ Neue Kategorie hinzufügen
        </a>
    </div>
    
    <?php if (empty($categories)): ?>
    <div class="federwiegen-empty-state">
        <div class="federwiegen-empty-icon">🏷️</div>
        <h4>Noch keine Kategorien vorhanden</h4>
        <p>Erstellen Sie Ihre erste Produktkategorie.</p>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&tab=add'); ?>" class="button button-primary">
            ➕ Erste Kategorie erstellen
        </a>
    </div>
    <?php else: ?>
    
    <div class="federwiegen-categories-grid">
        <?php foreach ($categories as $category): ?>
        <div class="federwiegen-category-card">
            <div class="federwiegen-category-image">
                <?php if (!empty($category->default_image)): ?>
                    <img src="<?php echo esc_url($category->default_image); ?>" alt="<?php echo esc_attr($category->name); ?>">
                <?php else: ?>
                    <div class="federwiegen-category-placeholder">
                        <span>🏷️</span>
                        <small>Kein Bild</small>
                    </div>
                <?php endif; ?>
                
            </div>
            
            <div class="federwiegen-category-content">
                <h4><?php echo esc_html($category->name); ?></h4>
                
                <div class="federwiegen-category-shortcode">
                    <code>[federwiegen_product category="<?php echo esc_html($category->shortcode); ?>"]</code>
                </div>
                
                <div class="federwiegen-category-meta">
                    <div class="federwiegen-category-info">
                        <small>Sortierung: <?php echo $category->sort_order; ?></small>
                        <?php if (!empty($category->meta_title)): ?>
                            <small>SEO: ✅ Konfiguriert</small>
                        <?php else: ?>
                            <small>SEO: ❌ Nicht konfiguriert</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="federwiegen-category-shipping">
                        <strong><?php echo number_format($category->shipping_cost ?? 0, 2, ',', '.'); ?>€</strong>
                        <small>Versand</small>
                        <?php if (!empty($category->shipping_provider)): ?>
                            <img src="<?php echo esc_url(FEDERWIEGEN_PLUGIN_URL . 'assets/shipping-icons/' . $category->shipping_provider . '.svg'); ?>" alt="<?php echo esc_attr($category->shipping_provider); ?>">
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="federwiegen-category-actions">
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&tab=edit&edit=' . $category->id); ?>" class="button button-small">
                        ✏️ Bearbeiten
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&delete=' . $category->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>"
                       class="button button-small federwiegen-delete-button"
                       onclick="return confirm('Sind Sie sicher, dass Sie diese Kategorie löschen möchten?\n\n\"<?php echo esc_js($category->name); ?>\" und alle zugehörigen Daten werden unwiderruflich gelöscht!')">
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
.federwiegen-categories-list {
    padding: 0;
}

.federwiegen-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.federwiegen-list-header h3 {
    margin: 0;
    color: #3c434a;
}

.federwiegen-categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.federwiegen-category-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.federwiegen-category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #5f7f5f;
}

.federwiegen-category-image {
    position: relative;
    height: 150px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.federwiegen-category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.federwiegen-category-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #6c757d;
}

.federwiegen-category-placeholder span {
    font-size: 3rem;
    opacity: 0.5;
}

.federwiegen-category-status {
    position: absolute;
    top: 8px;
    right: 8px;
}

.federwiegen-category-content {
    padding: 20px;
}

.federwiegen-category-content h4 {
    margin: 0 0 8px 0;
    color: #2a372a;
    font-size: 1.1rem;
    font-weight: 600;
}

.federwiegen-category-description {
    margin: 0 0 12px 0;
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.federwiegen-category-shortcode {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 8px;
    margin-bottom: 15px;
}

.federwiegen-category-shortcode code {
    background: none;
    padding: 0;
    font-size: 0.8rem;
    color: #5f7f5f;
    font-weight: 500;
}

.federwiegen-category-meta {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    padding-top: 15px;
    border-top: 1px solid #f8f9fa;
}

.federwiegen-category-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.federwiegen-category-info small {
    color: #6c757d;
    font-size: 0.8rem;
}

.federwiegen-category-shipping {
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.federwiegen-category-shipping strong {
    color: #5f7f5f;
    font-size: 1rem;
}

.federwiegen-category-shipping small {
    color: #6c757d;
    font-size: 0.8rem;
}

.federwiegen-category-shipping img {
    width: 32px;
    height: auto;
    margin-top: 4px;
}

.federwiegen-category-actions {
    display: flex;
    gap: 8px;
}

.federwiegen-category-actions .button {
    flex: 1;
    text-align: center;
    font-size: 0.85rem;
    padding: 6px 12px;
}

@media (max-width: 768px) {
    .federwiegen-list-header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .federwiegen-categories-grid {
        grid-template-columns: 1fr;
    }
    
    .federwiegen-category-actions {
        flex-direction: column;
    }
}
</style>
