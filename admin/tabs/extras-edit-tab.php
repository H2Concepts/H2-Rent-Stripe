<?php
// Extras Edit Tab Content
?>

<div class="federwiegen-edit-extra">
    <div class="federwiegen-form-header">
        <h3>✏️ Extra bearbeiten</h3>
        <p>Bearbeiten Sie das Extra "<?php echo esc_html($edit_item->name); ?>" für die Kategorie "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>"</p>
    </div>
    
    <form method="post" action="" class="federwiegen-compact-form">
        <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
        <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
        <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
        
        <!-- Grunddaten -->
        <div class="federwiegen-form-section">
            <h4>📝 Grunddaten</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?php echo esc_attr($edit_item->name); ?>" required>
                </div>
                <div class="federwiegen-form-group">
                    <label>Preis (€) *</label>
                    <input type="number" name="price" value="<?php echo $edit_item->price; ?>" step="0.01" min="0" required>
                </div>
            </div>
        </div>
        
        <!-- Bild -->
        <div class="federwiegen-form-section">
            <h4>📸 Extra-Bild</h4>
            <div class="federwiegen-form-group">
                <label>Extra-Bild</label>
                <div class="federwiegen-upload-area">
                    <input type="url" name="image_url" id="image_url" value="<?php echo esc_attr($edit_item->image_url ?? ''); ?>">
                    <button type="button" class="button federwiegen-media-button" data-target="image_url">📁 Aus Mediathek wählen</button>
                </div>
                <small>Wird als Overlay über dem Hauptbild angezeigt (empfohlen: 400x400 Pixel)</small>
                
                <?php if (!empty($edit_item->image_url)): ?>
                <div class="federwiegen-image-preview">
                    <img src="<?php echo esc_url($edit_item->image_url); ?>" alt="Extra-Bild">
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Einstellungen -->
        <div class="federwiegen-form-section">
            <h4>⚙️ Einstellungen</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_item->sort_order; ?>" min="0">
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="federwiegen-form-actions">
            <button type="submit" name="submit" class="button button-primary button-large">
                ✅ Änderungen speichern
            </button>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-extras&category=' . $selected_category . '&tab=list'); ?>" class="button button-large">
                ❌ Abbrechen
            </a>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-extras&category=' . $selected_category . '&delete=' . $edit_item->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>"
               class="button button-large federwiegen-delete-button"
               onclick="return confirm('Sind Sie sicher, dass Sie dieses Extra löschen möchten?\n\n\"<?php echo esc_js($edit_item->name); ?>\" wird unwiderruflich gelöscht!')"
               style="margin-left: auto;">
                🗑️ Löschen
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
