<?php
// Durations Add Tab Content
?>

<div class="produkt-add-duration">
    <div class="produkt-form-header">
        <h3>➕ Neue Mietdauer hinzufügen</h3>
        <p>Erstellen Sie eine neue Mietdauer für die Kategorie "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>"</p>
    </div>
    
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
        
        <!-- Grunddaten -->
        <div class="produkt-form-section">
            <h4>📝 Grunddaten</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required placeholder="z.B. Flexible Abo, ab 2+, ab 6+">
                </div>
                <div class="produkt-form-group">
                    <label>Mindestmonate *</label>
                    <input type="number" name="months_minimum" min="1" required placeholder="1">
                </div>
            </div>
            
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Rabatt (%)</label>
                    <input type="number" name="discount" step="0.01" min="0" max="100" placeholder="10">
                    <small>z.B. 10 für 10% Rabatt</small>
                </div>
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
            </div>
        </div>

        <!-- Price IDs per Variant -->
        <div class="produkt-form-section">
            <h4>💳 Preis IDs pro Ausführung</h4>
            <?php foreach ($variants as $variant): ?>
            <div class="produkt-form-group">
                <label><?php echo esc_html($variant->name); ?></label>
                <input type="text" name="variant_price_id[<?php echo $variant->id; ?>]" placeholder="<?php echo esc_attr($variant->stripe_price_id); ?>">
                <small>Leer lassen, um Standard zu verwenden</small>
            </div>
            <?php endforeach; ?>
        </div>
        
        
        <!-- Actions -->
        <div class="produkt-form-actions">
            <button type="submit" name="submit" class="button button-primary button-large">
                ✅ Mietdauer erstellen
            </button>
            <a href="<?php echo admin_url('admin.php?page=produkt-durations&category=' . $selected_category . '&tab=list'); ?>" class="button button-large">
                ❌ Abbrechen
            </a>
        </div>
    </form>
</div>
