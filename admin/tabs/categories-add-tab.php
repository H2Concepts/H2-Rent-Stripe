<?php
// Categories Add Tab Content
?>

<div class="produkt-add-category">
    <div class="produkt-form-header">
        <h3>➕ Neue Kategorie hinzufügen</h3>
        <p>Erstellen Sie eine neue Produktkategorie mit SEO-Einstellungen und individueller Konfiguration.</p>
    </div>
    
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <!-- Grunddaten -->
        <div class="produkt-form-section">
            <h4>📝 Grunddaten</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Kategorie-Name *</label>
                    <input type="text" name="name" required placeholder="z.B. Nonomo Produkt">
                </div>
                <div class="produkt-form-group">
                    <label>Shortcode-Bezeichnung *</label>
                    <input type="text" name="shortcode" required pattern="[a-z0-9_-]+" placeholder="z.B. nonomo-premium">
                    <small>Nur Kleinbuchstaben, Zahlen, _ und -</small>
                </div>
            </div>
        </div>
        
        
        <!-- Bilder -->
        <div class="produkt-form-section">
            <h4>📸 Standard-Produktbild</h4>
            <div class="produkt-form-group">
                <label>Standard-Produktbild</label>
                <div class="produkt-upload-area">
                    <input type="url" name="default_image" id="default_image" placeholder="https://example.com/standard-bild.jpg">
                    <button type="button" class="button produkt-media-button" data-target="default_image">📁 Aus Mediathek wählen</button>
                </div>
                <small>Fallback-Bild wenn für Ausführungen kein spezifisches Bild hinterlegt ist</small>
            </div>
        </div>
        
        <!-- Features -->
        <div class="produkt-form-section">
            <h4>🌟 Features-Sektion</h4>
            <div class="produkt-form-group">
                <label><input type="checkbox" name="show_features" value="1" checked> Features-Sektion anzeigen</label>
            </div>
            <div class="produkt-form-group">
                <label>Features-Überschrift</label>
                <input type="text" name="features_title" placeholder="z.B. Warum unser Produkt?">
            </div>
            
            <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="produkt-feature-group">
                <h5>Feature <?php echo $i; ?></h5>
                <div class="produkt-form-row">
                    <div class="produkt-form-group">
                        <label>Titel</label>
                        <input type="text" name="feature_<?php echo $i; ?>_title" placeholder="z.B. Sicherheit First">
                    </div>
                    <div class="produkt-form-group">
                        <label>Icon-Bild</label>
                        <div class="produkt-upload-area">
                            <input type="url" name="feature_<?php echo $i; ?>_icon" id="feature_<?php echo $i; ?>_icon" placeholder="https://example.com/icon<?php echo $i; ?>.png">
                            <button type="button" class="button produkt-media-button" data-target="feature_<?php echo $i; ?>_icon">📁</button>
                        </div>
                    </div>
                </div>
                <div class="produkt-form-group">
                    <label>Beschreibung</label>
                    <textarea name="feature_<?php echo $i; ?>_description" rows="2" placeholder="Beschreibung für Feature <?php echo $i; ?>"></textarea>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        
        
        <!-- Einstellungen -->
        <div class="produkt-form-section">
            <h4>⚙️ Einstellungen</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" min="0">
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="produkt-form-actions">
            <button type="submit" name="submit_category" class="button button-primary button-large">
                ✅ Kategorie erstellen
            </button>
            <a href="<?php echo admin_url('admin.php?page=produkt-categories&tab=list'); ?>" class="button button-large">
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
    
    // Auto-generate shortcode from name
    const nameInput = document.querySelector('input[name="name"]');
    const shortcodeInput = document.querySelector('input[name="shortcode"]');
    
    if (nameInput && shortcodeInput) {
        nameInput.addEventListener('input', function() {
            if (!shortcodeInput.value) {
                const shortcode = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim();
                shortcodeInput.value = shortcode;
            }
        });
    }
});
</script>
