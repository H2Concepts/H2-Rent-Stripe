<?php
// Variants List Tab Content
?>

<div class="produkt-variants-list">
    <div class="produkt-list-header">
        <h3>📋 Ausführungen für: <?php echo $current_category ? esc_html($current_category->name) : 'Unbekanntes Produkt'; ?></h3>
    </div>
    
    <?php if (empty($variants)): ?>
    <div class="produkt-empty-state">
        <div class="produkt-empty-icon">📦</div>
        <h4>Noch keine Ausführungen vorhanden</h4>
        <p>Erstellen Sie Ihre erste Produktausführung für dieses Produkt.</p>
        <a href="<?php echo admin_url('admin.php?page=produkt-variants&category=' . $selected_category . '&tab=add'); ?>" class="button button-primary">
            ➕ Erste Ausführung erstellen
        </a>
    </div>
    <?php else: ?>
    
    <div class="produkt-variants-grid">
        <?php foreach ($variants as $variant): ?>
        <div class="produkt-variant-card">
            <div class="produkt-variant-images">
                <?php 
                $image_count = 0;
                $main_image = '';
                for ($i = 1; $i <= 5; $i++): 
                    $image_field = 'image_url_' . $i;
                    $image_url = isset($variant->$image_field) ? $variant->$image_field : '';
                    if (!empty($image_url)): 
                        $image_count++;
                        if ($i === 1) $main_image = $image_url;
                    endif;
                endfor; 
                
                if (!empty($main_image)):
                ?>
                    <img src="<?php echo esc_url($main_image); ?>" class="produkt-variant-main-image" alt="<?php echo esc_attr($variant->name); ?>">
                    <?php if ($image_count > 1): ?>
                        <div class="produkt-image-count"><?php echo $image_count; ?> Bilder</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="produkt-variant-placeholder">
                        <span>📦</span>
                        <small>Kein Bild</small>
                    </div>
                <?php endif; ?>
                
                <!-- Status Badge -->
                <div class="produkt-variant-status">
                    <?php if ($variant->available ?? 1): ?>
                        <span class="produkt-status-badge available">✅ Verfügbar</span>
                    <?php else: ?>
                        <span class="produkt-status-badge unavailable">❌ Nicht verfügbar</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="produkt-variant-content">
                <h4><?php echo esc_html($variant->name); ?></h4>
                <?php if (isset($variant->active) && $variant->active == 0): ?>
                    <span class="badge badge-gray">Archiviert bei Stripe</span>
                <?php endif; ?>
                <p class="produkt-variant-description"><?php echo esc_html($variant->description); ?></p>
                
                <div class="produkt-variant-meta">
                    <?php
                        $price = 0;
                        $missing_price = false;
                        if (!empty($variant->stripe_price_id)) {
                            if (\ProduktVerleih\StripeService::price_exists($variant->stripe_price_id)) {
                                $p = \ProduktVerleih\StripeService::get_price_amount($variant->stripe_price_id);
                                if (!is_wp_error($p)) {
                                    $price = $p;
                                }
                            } else {
                                $missing_price = true;
                            }
                        }
                    ?>
                    <div class="produkt-variant-price">
                        <strong><?php echo number_format($price, 2, ',', '.'); ?>€</strong>
                        <small>/Monat</small>
                    </div>
                    <?php if ($missing_price): ?>
                        <span class="badge badge-warning">Preis fehlt bei Stripe</span>
                    <?php endif; ?>
                    
                    <div class="produkt-variant-info">
                        <small>Sortierung: <?php echo $variant->sort_order; ?></small>
                        <?php if (!($variant->available ?? 1) && !empty($variant->availability_note)): ?>
                            <small class="produkt-availability-note"><?php echo esc_html($variant->availability_note); ?></small>
                        <?php elseif (($variant->available ?? 1) && !empty($variant->delivery_time)): ?>
                            <small class="produkt-availability-note">Lieferzeit: <?php echo esc_html($variant->delivery_time); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="produkt-variant-actions">
                    <a href="<?php echo admin_url('admin.php?page=produkt-variants&category=' . $selected_category . '&tab=edit&edit=' . $variant->id); ?>" class="button button-small">
                        ✏️ Bearbeiten
                   </a>
                    <a href="<?php echo admin_url('admin.php?page=produkt-variants&category=' . $selected_category . '&delete=' . $variant->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>"
                       class="button button-small produkt-delete-button"
                       onclick="return confirm('Sind Sie sicher, dass Sie diese Ausführung löschen möchten?\n\n\"<?php echo esc_js($variant->name); ?>\" wird unwiderruflich gelöscht!')">
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
.produkt-variants-list {
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

.produkt-list-header h3 {
    margin: 0;
    color: #3c434a;
}

.produkt-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #eeeeee;
    border-radius: 8px;
    border: 2px dashed #dee2e6;
}

.produkt-empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.produkt-empty-state h4 {
    margin: 0 0 10px 0;
    color: #6c757d;
}

.produkt-empty-state p {
    margin: 0 0 20px 0;
    color: #6c757d;
}

.produkt-variants-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.produkt-variant-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.produkt-variant-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: var(--produkt-primary);
}

.produkt-variant-images {
    position: relative;
    height: 200px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.produkt-variant-main-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.produkt-variant-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #6c757d;
}

.produkt-variant-placeholder span {
    font-size: 3rem;
    opacity: 0.5;
}

.produkt-image-count {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.produkt-variant-status {
    position: absolute;
    top: 8px;
    left: 8px;
}

.produkt-status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.produkt-status-badge.available {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.produkt-status-badge.unavailable {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.produkt-variant-content {
    padding: 20px;
}

.produkt-variant-content h4 {
    margin: 0 0 8px 0;
    color: #2a372a;
    font-size: 1.1rem;
    font-weight: 600;
}

.produkt-variant-description {
    margin: 0 0 15px 0;
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.produkt-variant-meta {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    padding-top: 15px;
    border-top: 1px solid #f8f9fa;
}

.produkt-variant-price {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

.produkt-variant-price strong {
    color: var(--produkt-primary);
    font-size: 1.2rem;
}

.produkt-variant-price small {
    color: #6c757d;
    font-size: 0.8rem;
}

.produkt-variant-info {
    text-align: right;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.produkt-variant-info small {
    color: #6c757d;
    font-size: 0.8rem;
}

.produkt-availability-note {
    color: #dc3545 !important;
    font-weight: 500 !important;
}

.produkt-variant-actions {
    display: flex;
    gap: 8px;
}

.produkt-variant-actions .button {
    flex: 1;
    text-align: center;
    font-size: 0.85rem;
    padding: 6px 12px;
}

.produkt-delete-button {
    color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.produkt-delete-button:hover {
    background: #dc3545 !important;
    color: white !important;
}

@media (max-width: 768px) {
    .produkt-list-header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .produkt-variants-grid {
        grid-template-columns: 1fr;
    }
    
    .produkt-variant-actions {
        flex-direction: column;
    }
}
</style>
