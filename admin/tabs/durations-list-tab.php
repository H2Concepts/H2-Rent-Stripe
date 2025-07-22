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
                $is_archived = \ProduktVerleih\StripeService::is_price_archived_cached($duration->stripe_price_id);
                if ($is_archived): ?>
                    <span class="badge badge-warning">Archivierter oder ungültiger Stripe-Preis</span>
                <?php endif; ?>
                <?php
                $product_archived = false;
                if (!empty($duration->stripe_product_id)) {
                    $product_archived = \ProduktVerleih\StripeService::is_product_archived_cached($duration->stripe_product_id);
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


