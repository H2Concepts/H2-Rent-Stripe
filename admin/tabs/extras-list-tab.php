<?php
// Extras List Tab Content
?>

<div class="produkt-extras-list">
    <div class="produkt-list-header">
        <h3>🎁 Extras für: <?php echo $current_category ? esc_html($current_category->name) : 'Unbekannte Kategorie'; ?></h3>
        <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&tab=add'); ?>" class="button button-primary">
            ➕ Neues Extra hinzufügen
        </a>
    </div>
    
    <?php if (empty($extras)): ?>
    <div class="produkt-empty-state">
        <div class="produkt-empty-icon">🎁</div>
        <h4>Noch keine Extras vorhanden</h4>
        <p>Erstellen Sie Ihr erstes Extra für diese Kategorie.</p>
        <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&tab=add'); ?>" class="button button-primary">
            ➕ Erstes Extra erstellen
        </a>
    </div>
    <?php else: ?>
    
    <div class="produkt-extras-grid">
        <?php foreach ($extras as $extra): ?>
        <div class="produkt-extra-card">
            <div class="produkt-extra-image">
                <?php 
                $image_url = isset($extra->image_url) ? $extra->image_url : '';
                if (!empty($image_url)): 
                ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($extra->name); ?>">
                <?php else: ?>
                    <div class="produkt-extra-placeholder">
                        <span>🎁</span>
                        <small>Kein Bild</small>
                    </div>
                <?php endif; ?>
                
            </div>
            
            <div class="produkt-extra-content">
                <h4><?php echo esc_html($extra->name); ?></h4>
                
                <div class="produkt-extra-meta">
                    <div class="produkt-extra-price">
                        <?php if (!empty($extra->stripe_price_id)) {
                            $p = \FederwiegenVerleih\StripeService::get_price_amount($extra->stripe_price_id);
                            if (!is_wp_error($p)) {
                                echo '<strong>' . number_format($p, 2, ',', '.') . "€</strong><small>/Monat</small>";
                            }
                        } ?>
                    </div>
                    
                    <div class="produkt-extra-info">
                        <small>Sortierung: <?php echo $extra->sort_order; ?></small>
                    </div>
                </div>
                
                <div class="produkt-extra-actions">
                    <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&tab=edit&edit=' . $extra->id); ?>" class="button button-small">
                        ✏️ Bearbeiten
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&delete=' . $extra->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>"
                       class="button button-small produkt-delete-button"
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
.produkt-extras-list {
    padding: 0;
}

.produkt-extras-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

/* Header layout matches categories/variants */
.produkt-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.produkt-list-header h3 {
    margin: 0;
    color: #3c434a;
}

.produkt-extra-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.produkt-extra-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: var(--produkt-primary);
}

.produkt-extra-image {
    position: relative;
    height: 150px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.produkt-extra-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.produkt-extra-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #6c757d;
}

.produkt-extra-placeholder span {
    font-size: 3rem;
    opacity: 0.5;
}

.produkt-extra-status {
    position: absolute;
    top: 8px;
    right: 8px;
}

.produkt-extra-content {
    padding: 20px;
}

.produkt-extra-content h4 {
    margin: 0 0 15px 0;
    color: #2a372a;
    font-size: 1.1rem;
    font-weight: 600;
}

.produkt-extra-meta {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    padding-top: 15px;
    border-top: 1px solid #f8f9fa;
}

.produkt-extra-price {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

.produkt-extra-price strong {
    color: var(--produkt-primary);
    font-size: 1.2rem;
}

.produkt-extra-price small {
    color: #6c757d;
    font-size: 0.8rem;
}

.produkt-extra-info {
    text-align: right;
}

.produkt-extra-info small {
    color: #6c757d;
    font-size: 0.8rem;
}

.produkt-extra-actions {
    display: flex;
    gap: 8px;
}

.produkt-extra-actions .button {
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
    .produkt-extras-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .produkt-extra-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .produkt-extras-grid {
        grid-template-columns: 1fr;
    }
}
</style>
