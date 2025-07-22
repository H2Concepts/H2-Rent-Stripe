<?php
// Durations Add Tab Content
?>

<div class="produkt-add-duration">
    <div class="produkt-form-header">
        <h3>➕ Neue Mietdauer hinzufügen</h3>
        <p>Erstellen Sie eine neue Mietdauer für das Produkt "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>"</p>
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
                <?php $modus = get_option('produkt_betriebsmodus', 'miete'); ?>
                <div class="produkt-form-group">
                    <label>Mindestmietdauer (<?php echo $modus === 'verkauf' ? 'Tage' : 'Monate'; ?>) *</label>
                    <input type="number" name="months_minimum" min="1" required placeholder="1">
                </div>
            </div>
            
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label class="produkt-toggle-label" for="show_badge" style="min-width:160px;">
                        <input type="checkbox" name="show_badge" id="show_badge" value="1">
                        <span class="produkt-toggle-slider"></span>
                        <span>Rabatt-Badge anzeigen</span>
                    </label>
                </div>
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
            </div>
        </div>

        <!-- Preise pro Variant -->
        <div class="produkt-form-section">
            <h4>💶 Monatlicher Preis pro Ausführung</h4>
            <?php foreach ($variants as $variant): ?>
            <div class="produkt-form-group">
                <label><?php echo esc_html($variant->name); ?></label>
                <input type="number" step="0.01" name="variant_custom_price[<?php echo $variant->id; ?>]" placeholder="0.00">
                <small>Preis (monatlich in €)</small>
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
