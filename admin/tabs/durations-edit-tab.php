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
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.7 90">
                <path fill-rule="evenodd" d="m39.8 24.3h-29.9l4.5 57.2v0.2c0 1.1 0.4 1.9 1 2.5 0.7 0.7 1.8 1.2 3.2 1.4h21.2s21.2 0 21.2 0c1.5-0.3 2.5-0.7 3.2-1.4 0.6-0.6 1-1.5 1-2.7v-0.1l4.5-57.2h-30zm-17.8 14.1c0-1.1 0.7-2 1.8-2.1 1.1 0 2 0.7 2.1 1.8l2.7 33.6c0 1.1-0.7 2-1.8 2.1-1.1 0-2-0.7-2.1-1.8l-2.7-33.6zm31.8-0.3c0-1.1 1-1.9 2.1-1.8 1.1 0 1.9 1 1.8 2.1l-2.7 33.6c0 1.1-1 1.9-2.1 1.8-1.1 0-1.9-1-1.8-2.1l2.7-33.6zm-15.9 0.1c0-1.1 0.9-1.9 1.9-1.9s1.9 0.9 1.9 1.9v33.6c0 1.1-0.9 1.9-1.9 1.9s-1.9-0.9-1.9-1.9v-33.6zm22.7-23.6h-53.1c-0.9 0-1.8 0.3-2.3 0.9-0.6 0.5-0.9 1.3-0.9 2v2.9h35.6s35.6 0 35.6 0v-2.9c0-0.8-0.4-1.5-0.9-2-0.6-0.6-1.4-0.9-2.3-0.9h-11.5zm-53.1-3.9h19.4v-0.8c0-2.4 0.5-4.6 1.3-6.2 1-2 2.5-3.3 4.4-3.3h14.6c1.8 0 3.4 1.3 4.4 3.3 0.8 1.6 1.3 3.8 1.3 6.2v0.8h19.4c1.9 0 3.7 0.8 5 2s2.1 2.9 2.1 4.8v4.9c0 1.1-0.9 1.9-1.9 1.9h-3.7l-4.6 57.5c0 2.1-0.8 3.9-2.1 5.2s-3 2.1-5.3 2.5h-0.5-21.3s-21.3 0-21.3 0h-0.3c-2.4-0.4-4.2-1.2-5.5-2.5s-2-3-2.1-5.2l-4.6-57.5h-3.7c-1.1 0-1.9-0.9-1.9-1.9v-4.9c0-1.9 0.8-3.6 2.1-4.8s3.1-2 5-2zm23.3 0h18.2v-0.8c0-1.8-0.3-3.4-0.9-4.5-0.3-0.7-0.7-1.1-0.9-1.1h-14.6c-0.2 0-0.6 0.4-0.9 1.1-0.6 1.1-0.9 2.7-0.9 4.5v0.8z"/>
            </svg>
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
