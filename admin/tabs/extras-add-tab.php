<?php
// Extras Add Tab Content
?>

<div class="produkt-add-extra">
    <div class="produkt-form-header">
        <h3>➕ Neues Extra hinzufügen</h3>
        <p>Erstellen Sie ein neues Extra für das Produkt "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>"</p>
    </div>
    
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
        
        <!-- Grunddaten -->
        <div class="produkt-form-section">
            <h4>📝 Grunddaten</h4>
            <?php $modus = get_option('produkt_betriebsmodus', 'miete'); ?>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required placeholder="z.B. Himmel, Zubehör-Set">
                </div>
                <div class="produkt-form-group">
                    <label>Preis (EUR)<?php echo $modus === 'kauf' ? '' : ' *'; ?></label>
                    <input type="number" step="0.01" name="price" placeholder="0.00" <?php echo $modus === 'kauf' ? '' : 'required'; ?>>
                </div>
                <?php if ($modus === 'kauf'): ?>
                <div class="produkt-form-group">
                    <label>Einmalpreis (EUR) *</label>
                    <input type="number" step="0.01" name="sale_price" placeholder="0.00" required>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Bild -->
        <div class="produkt-form-section">
            <h4>📸 Extra-Bild</h4>
            <div class="produkt-form-group">
                <label>Extra-Bild</label>
                <div class="produkt-upload-area">
                    <input type="url" name="image_url" id="image_url" placeholder="https://example.com/extra-bild.jpg">
                    <button type="button" class="button produkt-media-button" data-target="image_url">📁 Aus Mediathek wählen</button>
                </div>
                <small>Wird als Overlay über dem Hauptbild angezeigt (empfohlen: 400x400 Pixel)</small>
            </div>
        </div>
        
        <!-- Einstellungen -->
        <div class="produkt-form-section">
            <h4>⚙️ Einstellungen</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
            </div>
        </div>

        <?php if (!empty($variants)): ?>
        <div class="produkt-form-section">
            <h4>🚀 Verfügbarkeit je Ausführung</h4>
            <div class="produkt-form-row" style="flex-wrap:wrap;gap:15px;">
                <?php foreach ($variants as $v): ?>
                <label class="produkt-toggle-label" style="min-width:160px;">
                    <input type="checkbox" name="variant_available[<?php echo $v->id; ?>]" value="1" checked>
                    <span class="produkt-toggle-slider"></span>
                    <span><?php echo esc_html($v->name); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="produkt-form-actions">
            <button type="submit" name="submit" class="button button-primary button-large">
                ✅ Extra erstellen
            </button>
            <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&tab=list'); ?>" class="button button-large">
                ❌ Abbrechen
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // WordPress Media Library Integration
    document.querySelectorAll('.produkt-media-button').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            
            if (!targetInput) return;
            
            const mediaUploader = wp.media({
                title: 'Bild auswählen',
                button: {
                    text: 'Bild verwenden'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                targetInput.value = attachment.url;
            });
            
            mediaUploader.open();
        });
    });
});
</script>
