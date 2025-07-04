<?php
// Extras List Tab Content
?>

<div class="federwiegen-extras-list">
    <div class="federwiegen-list-header">
        <h3>🎁 Extras für: <?php echo $current_category ? esc_html($current_category->name) : 'Unbekannte Kategorie'; ?></h3>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-extras&category=' . $selected_category . '&tab=add'); ?>" class="button button-primary">
            ➕ Neues Extra hinzufügen
        </a>
    </div>
    
    <?php if (empty($extras)): ?>
    <div class="federwiegen-empty-state">
        <div class="federwiegen-empty-icon">🎁</div>
        <h4>Noch keine Extras vorhanden</h4>
        <p>Erstellen Sie Ihr erstes Extra für diese Kategorie.</p>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-extras&category=' . $selected_category . '&tab=add'); ?>" class="button button-primary">
            ➕ Erstes Extra erstellen
        </a>
    </div>
    <?php else: ?>
    
    <div class="federwiegen-extras-grid">
        <?php foreach ($extras as $extra): ?>
        <div class="federwiegen-extra-card">
            <div class="federwiegen-extra-image">
                <?php 
                $image_url = isset($extra->image_url) ? $extra->image_url : '';
                if (!empty($image_url)): 
                ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($extra->name); ?>">
                <?php else: ?>
                    <div class="federwiegen-extra-placeholder">
                        <span>🎁</span>
                        <small>Kein Bild</small>
                    </div>
                <?php endif; ?>
                
            </div>
            
            <div class="federwiegen-extra-content">
                <h4><?php echo esc_html($extra->name); ?></h4>
                
                <div class="federwiegen-extra-meta">
                    <div class="federwiegen-extra-price">
                        <strong><?php echo number_format($extra->price, 2, ',', '.'); ?>€</strong>
                        <small>/Monat</small>
                    </div>
                    
                    <div class="federwiegen-extra-info">
                        <small>Sortierung: <?php echo $extra->sort_order; ?></small>
                    </div>
                </div>
                
                <div class="federwiegen-extra-actions">
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-extras&category=' . $selected_category . '&tab=edit&edit=' . $extra->id); ?>" class="button button-small">
                        ✏️ Bearbeiten
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-extras&category=' . $selected_category . '&delete=' . $extra->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>"
                       class="button button-small federwiegen-delete-button"
                       onclick="return confirm('Sind Sie sicher, dass Sie dieses Extra löschen möchten?\n\n\"<?php echo esc_js($extra->name); ?>\" wird unwiderruflich gelöscht!')">
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
.federwiegen-extras-list {
    padding: 0;
}

.federwiegen-extras-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

/* Header layout matches categories/variants */
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

.federwiegen-extra-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.federwiegen-extra-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #5f7f5f;
}

.federwiegen-extra-image {
    position: relative;
    height: 150px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.federwiegen-extra-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.federwiegen-extra-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #6c757d;
}

.federwiegen-extra-placeholder span {
    font-size: 3rem;
    opacity: 0.5;
}

.federwiegen-extra-status {
    position: absolute;
    top: 8px;
    right: 8px;
}

.federwiegen-extra-content {
    padding: 20px;
}

.federwiegen-extra-content h4 {
    margin: 0 0 15px 0;
    color: #2a372a;
    font-size: 1.1rem;
    font-weight: 600;
}

.federwiegen-extra-meta {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    padding-top: 15px;
    border-top: 1px solid #f8f9fa;
}

.federwiegen-extra-price {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

.federwiegen-extra-price strong {
    color: #5f7f5f;
    font-size: 1.2rem;
}

.federwiegen-extra-price small {
    color: #6c757d;
    font-size: 0.8rem;
}

.federwiegen-extra-info {
    text-align: right;
}

.federwiegen-extra-info small {
    color: #6c757d;
    font-size: 0.8rem;
}

.federwiegen-extra-actions {
    display: flex;
    gap: 8px;
}

.federwiegen-extra-actions .button {
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
    .federwiegen-extras-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .federwiegen-extra-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .federwiegen-extras-grid {
        grid-template-columns: 1fr;
    }
}
</style>
