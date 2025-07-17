<?php
// Extras List Tab Content
?>

<div class="produkt-extras-list">
    <div class="produkt-list-header">
        <h3>🎁 Extras für: <?php echo $current_category ? esc_html($current_category->name) : 'Unbekanntes Produkt'; ?></h3>
    </div>
    
    <?php if (empty($extras)): ?>
    <div class="produkt-empty-state">
        <div class="produkt-empty-icon">🎁</div>
        <h4>Noch keine Extras vorhanden</h4>
        <p>Erstellen Sie Ihr erstes Extra für dieses Produkt.</p>
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
                <?php
                $archived = false;
                if (!empty($extra->stripe_product_id)) {
                    $archived = \ProduktVerleih\StripeService::is_product_archived_cached($extra->stripe_product_id);
                }
                ?>
                <?php if ($archived): ?>
                    <span class="badge badge-danger">⚠️ Produkt bei Stripe archiviert</span>
                <?php endif; ?>
                
                <div class="produkt-extra-meta">
                    <div class="produkt-extra-price">
                        <?php
                        $display_price = $extra->price;
                        $missing_price = false;
                        if (!empty($extra->stripe_price_id)) {
                            if (\ProduktVerleih\StripeService::price_exists($extra->stripe_price_id)) {
                                $p = \ProduktVerleih\StripeService::get_price_amount($extra->stripe_price_id);
                                if (!is_wp_error($p)) {
                                    $display_price = $p;
                                }
                            } else {
                                $missing_price = true;
                            }
                        }
                        if ($display_price > 0) {
                            echo '<strong>' . number_format($display_price, 2, ',', '.') . "€</strong>";
                        }
                        ?>
                    </div>
                    <?php if ($missing_price): ?>
                        <span class="badge badge-warning">Preis fehlt bei Stripe</span>
                    <?php endif; ?>
                    
                    <div class="produkt-extra-info">
                        <small>Sortierung: <?php echo $extra->sort_order; ?></small>
                    </div>
                </div>
                
                <div class="produkt-extra-actions">
                    <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&tab=edit&edit=' . $extra->id); ?>" class="button button-small">
                        ✏️ Bearbeiten
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&delete=' . $extra->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>"
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


