<?php
// Durations Add Tab Content
?>

<div class="produkt-add-duration">
    <div class="produkt-form-header">
        <h3><?php echo esc_html__('➕ Neue Mietdauer hinzufügen', 'h2-rental-pro'); ?></h3>
        <p><?php printf(esc_html__('Erstellen Sie eine neue Mietdauer für das Produkt "%s"', 'h2-rental-pro'), $current_category ? esc_html($current_category->name) : esc_html__('Unbekannt', 'h2-rental-pro')); ?></p>
    </div>
    
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
        
        <!-- Grunddaten -->
        <div class="produkt-form-section">
            <h4><?php echo esc_html__('📝 Grunddaten', 'h2-rental-pro'); ?></h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label><?php echo esc_html__('Name *', 'h2-rental-pro'); ?></label>
                    <input type="text" name="name" required placeholder="<?php echo esc_attr__('z.B. Flexible Abo, ab 2+, ab 6+', 'h2-rental-pro'); ?>">
                </div>
                <div class="produkt-form-group">
                    <label><?php echo esc_html__('Mindestmonate *', 'h2-rental-pro'); ?></label>
                    <input type="number" name="months_minimum" min="1" required placeholder="1">
                </div>
            </div>
            
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label class="produkt-toggle-label" for="show_badge" style="min-width:160px;">
                        <input type="checkbox" name="show_badge" id="show_badge" value="1">
                        <span class="produkt-toggle-slider"></span>
                        <span><?php echo esc_html__('Rabatt-Badge anzeigen', 'h2-rental-pro'); ?></span>
                    </label>
                </div>
                <div class="produkt-form-group">
                    <label><?php echo esc_html__('Sortierung', 'h2-rental-pro'); ?></label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
            </div>
        </div>

        <!-- Preise pro Variant -->
        <div class="produkt-form-section">
            <h4><?php echo esc_html__('💶 Monatlicher Preis pro Ausführung', 'h2-rental-pro'); ?></h4>
            <?php foreach ($variants as $variant): ?>
            <div class="produkt-form-group">
                <label><?php echo esc_html($variant->name); ?></label>
                <input type="number" step="0.01" name="variant_custom_price[<?php echo $variant->id; ?>]" placeholder="0.00">
                <small><?php echo esc_html__('Preis (monatlich in €)', 'h2-rental-pro'); ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        
        
        <!-- Actions -->
        <div class="produkt-form-actions">
            <button type="submit" name="submit" class="button button-primary button-large">
                <?php echo esc_html__('✅ Mietdauer erstellen', 'h2-rental-pro'); ?>
            </button>
            <a href="<?php echo admin_url('admin.php?page=produkt-durations&category=' . $selected_category . '&tab=list'); ?>" class="button button-large">
                <?php echo esc_html__('❌ Abbrechen', 'h2-rental-pro'); ?>
            </a>
        </div>
    </form>
</div>
