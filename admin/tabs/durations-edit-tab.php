<?php
// Durations Edit Tab Content
?>

<div class="federwiegen-edit-duration">
    <div class="federwiegen-form-header">
        <h3>✏️ Mietdauer bearbeiten</h3>
        <p>Bearbeiten Sie die Mietdauer "<?php echo esc_html($edit_item->name); ?>" für die Kategorie "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>"</p>
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
                    <label>Mindestmonate *</label>
                    <input type="number" name="months_minimum" value="<?php echo $edit_item->months_minimum; ?>" min="1" required>
                </div>
            </div>
            
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Rabatt (%)</label>
                    <input type="number" name="discount" value="<?php echo ($edit_item->discount * 100); ?>" step="0.01" min="0" max="100">
                    <small>z.B. 10 für 10% Rabatt</small>
                </div>
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
            <a href="<?php echo admin_url('admin.php?page=federwiegen-durations&category=' . $selected_category . '&tab=list'); ?>" class="button button-large">
                ❌ Abbrechen
            </a>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-durations&category=' . $selected_category . '&delete=' . $edit_item->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>"
               class="button button-large federwiegen-delete-button"
               onclick="return confirm('Sind Sie sicher, dass Sie diese Mietdauer löschen möchten?\n\n\"<?php echo esc_js($edit_item->name); ?>\" wird unwiderruflich gelöscht!')"
               style="margin-left: auto;">
                🗑️ Löschen
            </a>
        </div>
    </form>
</div>
