<?php
// Variants Add Tab Content
$modus = get_option('produkt_betriebsmodus', 'miete');
?>

<div class="produkt-add-variant">
    <div class="produkt-form-header">
        <h3>➕ Neue Ausführung hinzufügen</h3>
        <p>Erstellen Sie eine neue Produktausführung für das Produkt "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>"</p>
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
                    <input type="text" name="name" required placeholder="z.B. Premium Produkt">
                </div>
                <?php if ($modus !== 'kauf' && $modus !== 'verkauf'): ?>
                <div class="produkt-form-group">
                    <label>Monatlicher Mietpreis *</label>
                    <input type="number" step="0.01" name="mietpreis_monatlich" required placeholder="29.90">
                </div>
                <?php else: ?>
                    <input type="hidden" name="mietpreis_monatlich" value="0">
                <?php endif; ?>
                <div class="produkt-form-group">
                    <label><?php echo ($modus === 'kauf' || $modus === 'verkauf') ? 'Preis pro Tag' : 'Einmaliger Verkaufspreis'; ?></label>
                    <input type="number" step="0.01" name="verkaufspreis_einmalig" placeholder="z.B. 199.00">
                </div>
            </div>
            
            <div class="produkt-form-group">
                <label>Beschreibung</label>
                <textarea name="description" rows="3" placeholder="Kurze Beschreibung der Ausführung..."></textarea>
            </div>
        </div>
        
        <!-- Verfügbarkeit -->
        <div class="produkt-form-section">
            <h4>📦 Verfügbarkeit</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="available" value="1" checked>
                        <span class="produkt-toggle-slider"></span>
                        <span>Verfügbar</span>
                    </label>
                </div>
                <div class="produkt-form-group">
                    <label>Verfügbarkeits-Hinweis</label>
                    <input type="text" name="availability_note" placeholder="z.B. 'Wieder verfügbar ab 15.03.2024'">
                </div>
                <div class="produkt-form-group">
                    <label>Lieferzeit-Text</label>
                    <input type="text" name="delivery_time" placeholder="z.B. 3-5 Werktagen" value="3-5 Werktagen">
                </div>
            </div>
        </div>
        
        <!-- Bilder -->
        <div class="produkt-form-section">
            <h4>📸 Produktbilder</h4>
            <p class="produkt-section-description">Fügen Sie bis zu 5 Bilder hinzu. Das erste Bild wird als Hauptbild verwendet.</p>
            
            <div class="produkt-images-grid">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="produkt-image-upload-compact">
                    <label><?php echo $i === 1 ? '🌟 Hauptbild' : 'Bild ' . $i; ?></label>
                    <div class="produkt-upload-area">
                        <input type="url" name="image_url_<?php echo $i; ?>" id="image_url_<?php echo $i; ?>" placeholder="Bild-URL eingeben...">
                        <button type="button" class="button produkt-media-button" data-target="image_url_<?php echo $i; ?>">📁</button>
                    </div>
                </div>
                <?php endfor; ?>
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
        
        <!-- Actions -->
        <div class="produkt-form-actions">
            <button type="submit" name="submit" class="button button-primary button-large">
                ✅ Ausführung erstellen
            </button>
            <a href="<?php echo admin_url('admin.php?page=produkt-variants&category=' . $selected_category . '&tab=list'); ?>" class="button button-large">
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
