<?php
// Variants Edit Tab Content
?>

<div class="federwiegen-edit-variant">
    <div class="federwiegen-form-header">
        <h3>✏️ Ausführung bearbeiten</h3>
        <p>Bearbeiten Sie die Ausführung "<?php echo esc_html($edit_item->name); ?>" für die Kategorie "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>"</p>
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
                    <label>Grundpreis (€) *</label>
                    <input type="number" name="base_price" value="<?php echo $edit_item->base_price; ?>" step="0.01" min="0" required>
                </div>
                <div class="federwiegen-form-group">
                    <label>Preis ab (€)</label>
                    <input type="number" name="price_from" value="<?php echo $edit_item->price_from; ?>" step="0.01" min="0">
                </div>
            </div>
            
            <div class="federwiegen-form-group">
                <label>Beschreibung</label>
                <textarea name="description" rows="3"><?php echo esc_textarea($edit_item->description); ?></textarea>
            </div>
        </div>
        
        <!-- Verfügbarkeit -->
        <div class="federwiegen-form-section">
            <h4>📦 Verfügbarkeit</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label class="federwiegen-toggle-label">
                        <input type="checkbox" name="available" value="1" <?php echo ($edit_item->available ?? 1) ? 'checked' : ''; ?>>
                        <span class="federwiegen-toggle-slider"></span>
                        <span>Verfügbar</span>
                    </label>
                </div>
                <div class="federwiegen-form-group">
                    <label>Verfügbarkeits-Hinweis</label>
                    <input type="text" name="availability_note" value="<?php echo esc_attr($edit_item->availability_note ?? ''); ?>">
                </div>
                <div class="federwiegen-form-group">
                    <label>Lieferzeit-Text</label>
                    <input type="text" name="delivery_time" value="<?php echo esc_attr($edit_item->delivery_time ?? '3-5 Werktagen'); ?>">
                </div>
            </div>
        </div>
        
        <!-- Bilder -->
        <div class="federwiegen-form-section">
            <h4>📸 Produktbilder</h4>
            <p class="federwiegen-section-description">Bearbeiten Sie die Bilder für diese Ausführung. Das erste Bild wird als Hauptbild verwendet.</p>
            
            <div class="federwiegen-images-grid">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="federwiegen-image-upload-compact">
                    <label><?php echo $i === 1 ? '🌟 Hauptbild' : 'Bild ' . $i; ?></label>
                    <div class="federwiegen-upload-area">
                        <input type="url" name="image_url_<?php echo $i; ?>" id="image_url_<?php echo $i; ?>" value="<?php echo esc_attr($edit_item->{'image_url_' . $i} ?? ''); ?>">
                        <button type="button" class="button federwiegen-media-button" data-target="image_url_<?php echo $i; ?>">📁</button>
                    </div>
                    
                    <?php if (!empty($edit_item->{'image_url_' . $i})): ?>
                    <div class="federwiegen-image-preview">
                        <img src="<?php echo esc_url($edit_item->{'image_url_' . $i}); ?>" alt="Bild <?php echo $i; ?>">
                    </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
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
            <a href="<?php echo admin_url('admin.php?page=federwiegen-variants&category=' . $selected_category . '&tab=list'); ?>" class="button button-large">
                ❌ Abbrechen
            </a>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-variants&category=' . $selected_category . '&delete=' . $edit_item->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>"
               class="button button-large federwiegen-delete-button"
               onclick="return confirm('Sind Sie sicher, dass Sie diese Ausführung löschen möchten?\n\n\"<?php echo esc_js($edit_item->name); ?>\" wird unwiderruflich gelöscht!')"
               style="margin-left: auto;">
                🗑️ Löschen
            </a>
        </div>
    </form>
</div>

<style>
.federwiegen-edit-variant {
    max-width: 800px;
}

.federwiegen-image-preview {
    margin-top: 8px;
}

.federwiegen-image-preview img {
    width: 100%;
    max-width: 120px;
    height: 80px;
    object-fit: cover;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.federwiegen-delete-button {
    color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.federwiegen-delete-button:hover {
    background: #dc3545 !important;
    color: white !important;
}
</style>

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
