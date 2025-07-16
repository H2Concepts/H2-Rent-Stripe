<?php
// Durations List Tab Content
?>

<div class="produkt-durations-list">
    <div class="produkt-list-header">
        <h3>⏰ Mietdauern für: <?php echo $current_category ? esc_html($current_category->name) : 'Unbekanntes Produkt'; ?></h3>
    </div>
    
    <?php if (empty($durations)): ?>
    <div class="produkt-empty-state">
        <div class="produkt-empty-icon">⏰</div>
        <h4>Noch keine Mietdauern vorhanden</h4>
        <p>Erstellen Sie Ihre erste Mietdauer für dieses Produkt.</p>
        <a href="<?php echo admin_url('admin.php?page=produkt-durations&category=' . $selected_category . '&tab=add'); ?>" class="button button-primary">
            ➕ Erste Mietdauer erstellen
        </a>
    </div>
    <?php else: ?>
    
    <div class="produkt-durations-grid">
        <?php foreach ($durations as $duration): ?>
        <div class="produkt-duration-card">
            <div class="produkt-duration-header">
                <h4><?php echo esc_html($duration->name); ?></h4>
                <?php
                $is_archived = \ProduktVerleih\StripeService::is_price_archived($duration->stripe_price_id);
                if ($is_archived): ?>
                    <span class="badge badge-warning">Archivierter oder ungültiger Stripe-Preis</span>
                <?php endif; ?>
                <?php
                $product_archived = false;
                if (!empty($duration->stripe_product_id)) {
                    $product_archived = \ProduktVerleih\StripeService::is_product_archived($duration->stripe_product_id);
                }
                if ($product_archived): ?>
                    <span class="badge badge-danger">⚠️ Produkt bei Stripe archiviert</span>
                <?php endif; ?>
                <?php if ($duration->show_badge): ?>
                    <span class="produkt-discount-badge">Badge</span>
                <?php endif; ?>
            </div>
            
            <div class="produkt-duration-content">
                <div class="produkt-duration-info">
                    <div class="produkt-duration-months">
                        <strong><?php echo $duration->months_minimum; ?></strong>
                        <small>Monat<?php echo $duration->months_minimum > 1 ? 'e' : ''; ?> Mindestlaufzeit</small>
                    </div>
                    
                    <?php if ($duration->show_badge): ?>
                    <div class="produkt-duration-savings">
                        <span class="produkt-savings-text">Badge aktiv</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="produkt-duration-meta">
                    <div class="produkt-duration-details">
                        <small>Sortierung: <?php echo $duration->sort_order; ?></small>
                    </div>
                    
                </div>
                
                <div class="produkt-duration-actions">
                    <a href="<?php echo admin_url('admin.php?page=produkt-durations&category=' . $selected_category . '&tab=edit&edit=' . $duration->id); ?>" class="button button-small">
                        ✏️ Bearbeiten
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=produkt-durations&category=' . $selected_category . '&delete=' . $duration->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>"
                       class="button button-small produkt-delete-button"
                       onclick="return confirm('Sind Sie sicher, dass Sie diese Mietdauer löschen möchten?\n\n\"<?php echo esc_js($duration->name); ?>\" wird unwiderruflich gelöscht!')">
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
.produkt-durations-list {
    padding: 0;
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

.produkt-durations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.produkt-duration-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.produkt-duration-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: var(--produkt-primary);
}

.produkt-duration-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.produkt-duration-header h4 {
    margin: 0;
    color: #2a372a;
    font-size: 1.1rem;
    font-weight: 600;
}

.produkt-discount-badge {
    background: #d4edda;
    color: #155724;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    border: 1px solid #c3e6cb;
}

.produkt-duration-content {
    padding: 20px;
}

.produkt-duration-info {
    margin-bottom: 20px;
}

.produkt-duration-months {
    text-align: center;
    margin-bottom: 15px;
}

.produkt-duration-months strong {
    display: block;
    margin-bottom: 1rem;
    font-size: 2rem;
    color: var(--produkt-primary);
    font-weight: 700;
}

.produkt-duration-months small {
    color: #6c757d;
    font-size: 0.9rem;
}

.produkt-duration-savings {
    text-align: center;
    padding: 10px;
    background: #d4edda;
    border-radius: 6px;
    border: 1px solid #c3e6cb;
}

.produkt-savings-text {
    color: #155724;
    font-weight: 600;
    font-size: 0.9rem;
}

.produkt-duration-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-top: 15px;
    border-top: 1px solid #f8f9fa;
}

.produkt-duration-details small {
    color: #6c757d;
    font-size: 0.8rem;
}

.produkt-duration-actions {
    display: flex;
    gap: 8px;
}

.produkt-duration-actions .button {
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
    .produkt-durations-grid {
        grid-template-columns: 1fr;
    }
    
    .produkt-duration-actions {
        flex-direction: column;
    }
}
</style>
