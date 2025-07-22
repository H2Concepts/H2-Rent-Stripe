<?php
// Variants List Tab Content
$modus = get_option('produkt_betriebsmodus', 'miete');
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
                <?php
                $archived = false;
                if (!empty($variant->stripe_product_id)) {
                    $archived = \ProduktVerleih\StripeService::is_product_archived_cached($variant->stripe_product_id);
                }
                ?>
                <?php if ($archived): ?>
                    <span class="badge badge-danger">⚠️ Produkt bei Stripe archiviert</span>
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
                    <?php if ($modus !== 'kauf'): ?>
                    <div class="produkt-variant-price">
                        <strong><?php echo number_format($price, 2, ',', '.'); ?>€</strong>
                        <small>/Monat</small>
                    </div>
                    <?php else: ?>
                    <div class="produkt-variant-sale-price">
                        <strong><?php echo number_format($variant->verkaufspreis_einmalig, 2, ',', '.'); ?>€</strong>
                    </div>
                    <?php endif; ?>
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


