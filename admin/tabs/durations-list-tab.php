<?php
// Durations List Tab Content
?>

<div class="federwiegen-durations-list">
    <div class="federwiegen-list-header">
        <h3>⏰ Mietdauern für: <?php echo $current_category ? esc_html($current_category->name) : 'Unbekannte Kategorie'; ?></h3>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-durations&category=' . $selected_category . '&tab=add'); ?>" class="button button-primary">
            ➕ Neue Mietdauer hinzufügen
        </a>
    </div>
    
    <?php if (empty($durations)): ?>
    <div class="federwiegen-empty-state">
        <div class="federwiegen-empty-icon">⏰</div>
        <h4>Noch keine Mietdauern vorhanden</h4>
        <p>Erstellen Sie Ihre erste Mietdauer für diese Kategorie.</p>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-durations&category=' . $selected_category . '&tab=add'); ?>" class="button button-primary">
            ➕ Erste Mietdauer erstellen
        </a>
    </div>
    <?php else: ?>
    
    <div class="federwiegen-durations-grid">
        <?php foreach ($durations as $duration): ?>
        <div class="federwiegen-duration-card">
            <div class="federwiegen-duration-header">
                <h4><?php echo esc_html($duration->name); ?></h4>
                <?php if ($duration->discount > 0): ?>
                    <span class="federwiegen-discount-badge">-<?php echo round($duration->discount * 100); ?>%</span>
                <?php endif; ?>
            </div>
            
            <div class="federwiegen-duration-content">
                <div class="federwiegen-duration-info">
                    <div class="federwiegen-duration-months">
                        <strong><?php echo $duration->months_minimum; ?></strong>
                        <small>Monat<?php echo $duration->months_minimum > 1 ? 'e' : ''; ?> Mindestlaufzeit</small>
                    </div>
                    
                    <?php if ($duration->discount > 0): ?>
                    <div class="federwiegen-duration-savings">
                        <span class="federwiegen-savings-text">
                            <?php echo round($duration->discount * 100); ?>% Rabatt
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="federwiegen-duration-meta">
                    <div class="federwiegen-duration-details">
                        <small>Sortierung: <?php echo $duration->sort_order; ?></small>
                    </div>
                    
                </div>
                
                <div class="federwiegen-duration-actions">
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-durations&category=' . $selected_category . '&tab=edit&edit=' . $duration->id); ?>" class="button button-small">
                        ✏️ Bearbeiten
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-durations&category=' . $selected_category . '&delete=' . $duration->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>"
                       class="button button-small federwiegen-delete-button"
                       onclick="return confirm('Sind Sie sicher, dass Sie diese Mietdauer löschen möchten?\n\n\"<?php echo esc_js($duration->name); ?>\" wird unwiderruflich gelöscht!')">
                        🗑️ Löschen
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
</div>

<style>
.federwiegen-durations-list {
    padding: 0;
}

/* Header layout matches categories/variants */
.federwiegen-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.federwiegen-list-header h3 {
    margin: 0;
    color: #3c434a;
}

.federwiegen-durations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.federwiegen-duration-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.federwiegen-duration-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #5f7f5f;
}

.federwiegen-duration-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.federwiegen-duration-header h4 {
    margin: 0;
    color: #2a372a;
    font-size: 1.1rem;
    font-weight: 600;
}

.federwiegen-discount-badge {
    background: #d4edda;
    color: #155724;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    border: 1px solid #c3e6cb;
}

.federwiegen-duration-content {
    padding: 20px;
}

.federwiegen-duration-info {
    margin-bottom: 20px;
}

.federwiegen-duration-months {
    text-align: center;
    margin-bottom: 15px;
}

.federwiegen-duration-months strong {
    display: block;
    font-size: 2rem;
    color: #5f7f5f;
    font-weight: 700;
}

.federwiegen-duration-months small {
    color: #6c757d;
    font-size: 0.9rem;
}

.federwiegen-duration-savings {
    text-align: center;
    padding: 10px;
    background: #d4edda;
    border-radius: 6px;
    border: 1px solid #c3e6cb;
}

.federwiegen-savings-text {
    color: #155724;
    font-weight: 600;
    font-size: 0.9rem;
}

.federwiegen-duration-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-top: 15px;
    border-top: 1px solid #f8f9fa;
}

.federwiegen-duration-details small {
    color: #6c757d;
    font-size: 0.8rem;
}

.federwiegen-duration-actions {
    display: flex;
    gap: 8px;
}

.federwiegen-duration-actions .button {
    flex: 1;
    text-align: center;
    font-size: 0.85rem;
    padding: 6px 12px;
}

@media (max-width: 768px) {
    .federwiegen-list-header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    .federwiegen-durations-grid {
        grid-template-columns: 1fr;
    }
    
    .federwiegen-duration-actions {
        flex-direction: column;
    }
}
</style>
