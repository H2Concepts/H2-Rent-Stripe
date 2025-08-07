<?php
// Durations Edit Tab Content
?>

<?php
    $price_rows = $wpdb->get_results($wpdb->prepare("SELECT variant_id, custom_price, stripe_archived FROM $table_prices WHERE duration_id = %d", $edit_item->id), OBJECT_K);
    $duration_prices = array();
    if ($price_rows) {
        foreach ($price_rows as $pid => $obj) {
            $duration_prices[$pid] = [
                'custom_price'    => $obj->custom_price,
                'stripe_archived' => $obj->stripe_archived,
            ];
        }
    }
?>

<div class="produkt-edit-duration">
    <div class="produkt-form-header">
        <h3>✏️ Mietdauer bearbeiten</h3>
        <p>Bearbeiten Sie die Mietdauer "<?php echo esc_html($edit_item->name); ?>" für das Produkt "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>"</p>
    </div>
    
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
        <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
        <button type="submit" name="submit" class="icon-btn durations-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3"><path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/><path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/></svg>
        </button>
        <a href="<?php echo admin_url('admin.php?page=produkt-durations&category=' . $selected_category . '&delete=' . $edit_item->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>"
           class="icon-btn durations-delete-btn"
           aria-label="Löschen"
           onclick="return confirm('Sind Sie sicher, dass Sie diese Mietdauer löschen möchten?\n\n\"<?php echo esc_js($edit_item->name); ?>\" wird unwiderruflich gelöscht!')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
        </a>
        
        <!-- Grunddaten -->
        <div class="produkt-form-section">
            <h4>📝 Grunddaten</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?php echo esc_attr($edit_item->name); ?>" required>
                </div>
                <div class="produkt-form-group">
                    <label>Mindestmonate *</label>
                    <input type="number" name="months_minimum" value="<?php echo $edit_item->months_minimum; ?>" min="1" required>
                </div>
            </div>
            
        <div class="produkt-form-row">
            <div class="produkt-form-group">
                <label class="produkt-toggle-label" for="show_badge" style="min-width:160px;">
                    <input type="checkbox" name="show_badge" id="show_badge" value="1" <?php checked($edit_item->show_badge ?? 0, 1); ?>>
                    <span class="produkt-toggle-slider"></span>
                    <span>Rabatt-Badge anzeigen</span>
                </label>
            </div>
            <div class="produkt-form-group">
                <label>Sortierung</label>
                <input type="number" name="sort_order" value="<?php echo $edit_item->sort_order; ?>" min="0">
            </div>
        </div>
    </div>

        <!-- Preise pro Variant -->
        <div class="produkt-form-section">
            <h4>💶 Monatlicher Preis pro Ausführung</h4>
            <?php foreach ($variants as $variant): ?>
            <div class="produkt-form-group">
                <label><?php echo esc_html($variant->name); ?></label>
                <input type="number" step="0.01" name="variant_custom_price[<?php echo $variant->id; ?>]" value="<?php echo esc_attr($duration_prices[$variant->id]['custom_price'] ?? ''); ?>">
                <small>Preis (monatlich in €)</small>
                <?php
                $archived = false;
                $price_id = $duration_prices[$variant->id]['stripe_price_id'] ?? '';
                if ($price_id) {
                    $archived = \ProduktVerleih\StripeService::is_price_archived_cached($price_id);
                } elseif (!empty($duration_prices[$variant->id]['stripe_archived'])) {
                    $archived = true;
                }
                $product_archived = false;
                if (!empty($variant->stripe_product_id)) {
                    $product_archived = \ProduktVerleih\StripeService::is_product_archived_cached($variant->stripe_product_id);
                }
                ?>
                <?php if ($archived): ?>
                    <span class="badge badge-gray">Archivierter Stripe-Preis</span>
                <?php endif; ?>
                <?php if ($product_archived): ?>
                    <span class="badge badge-danger">⚠️ Produkt bei Stripe archiviert</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        
        <!-- Actions -->
        
    </form>
</div>
