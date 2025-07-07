<?php
// Variants Add Tab Content
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
                <div class="produkt-form-group">
                    <label>Stripe Preis ID *</label>
                    <input type="text" name="stripe_price_id" required placeholder="price_123...">
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

<style>
.produkt-add-variant {
    max-width: 800px;
}

.produkt-form-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.produkt-form-header h3 {
    margin: 0 0 8px 0;
    color: #2a372a;
}

.produkt-form-header p {
    margin: 0;
    color: #6c757d;
}

.produkt-compact-form {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.produkt-form-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
}

.produkt-form-section h4 {
    margin: 0 0 15px 0;
    color: var(--produkt-primary);
    font-size: 1rem;
}

.produkt-section-description {
    margin: 0 0 15px 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.produkt-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.produkt-form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.produkt-form-group label {
    font-weight: 600;
    color: #3c434a;
    font-size: 0.9rem;
}

.produkt-form-group input,
.produkt-form-group textarea {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.9rem;
}

.produkt-form-group input:focus,
.produkt-form-group textarea:focus {
    border-color: var(--produkt-primary);
    box-shadow: 0 0 0 2px rgba(95, 127, 95, 0.1);
    outline: none;
}

.produkt-toggle-label {
    display: flex !important;
    flex-direction: row !important;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.produkt-images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.produkt-image-upload-compact {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.produkt-image-upload-compact label {
    font-weight: 600;
    color: #3c434a;
    font-size: 0.85rem;
}
.produkt-form-actions {
    display: flex;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.produkt-form-actions .button-large {
    padding: 12px 24px;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .produkt-form-row {
        grid-template-columns: 1fr;
    }
    
    .produkt-images-grid {
        grid-template-columns: 1fr;
    }
    
    .produkt-form-actions {
        flex-direction: column;
    }
}
</style>

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
