<?php
// Extras Add Tab Content
?>

<div class="federwiegen-add-extra">
    <div class="federwiegen-form-header">
        <h3>➕ Neues Extra hinzufügen</h3>
        <p>Erstellen Sie ein neues Extra für die Kategorie "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>"</p>
    </div>
    
    <form method="post" action="" class="federwiegen-compact-form">
        <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
        <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
        
        <!-- Grunddaten -->
        <div class="federwiegen-form-section">
            <h4>📝 Grunddaten</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required placeholder="z.B. Himmel, Zubehör-Set">
                </div>
                <div class="federwiegen-form-group">
                    <label>Preis (€) *</label>
                    <input type="number" name="price" step="0.01" min="0" required placeholder="9.99">
                </div>
            </div>
        </div>
        
        <!-- Bild -->
        <div class="federwiegen-form-section">
            <h4>📸 Extra-Bild</h4>
            <div class="federwiegen-form-group">
                <label>Extra-Bild</label>
                <div class="federwiegen-upload-area">
                    <input type="url" name="image_url" id="image_url" placeholder="https://example.com/extra-bild.jpg">
                    <button type="button" class="button federwiegen-media-button" data-target="image_url">📁 Aus Mediathek wählen</button>
                </div>
                <small>Wird als Overlay über dem Hauptbild angezeigt (empfohlen: 400x400 Pixel)</small>
            </div>
        </div>
        
        <!-- Einstellungen -->
        <div class="federwiegen-form-section">
            <h4>⚙️ Einstellungen</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="federwiegen-form-actions">
            <button type="submit" name="submit" class="button button-primary button-large">
                ✅ Extra erstellen
            </button>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-extras&category=' . $selected_category . '&tab=list'); ?>" class="button button-large">
                ❌ Abbrechen
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // WordPress Media Library Integration
    document.querySelectorAll('.federwiegen-media-button').forEach(function(button) {
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
