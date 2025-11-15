<?php
// Conditions List Tab Content
$variant_total = isset($variants) ? count($variants) : 0;
?>

<div class="produkt-conditions-list">
    <div class="produkt-list-header">
        <h3>🔄 Zustände für: <?php echo $current_category ? esc_html($current_category->name) : 'Unbekanntes Produkt'; ?></h3>
    </div>

    <?php if (empty($conditions)): ?>
        <div class="produkt-empty-state">
            <div class="produkt-empty-icon">🔄</div>
            <h4>Noch keine Zustände vorhanden</h4>
            <p>Legen Sie Ihren ersten Zustand für dieses Produkt an.</p>
            <a href="<?php echo admin_url('admin.php?page=produkt-conditions&category=' . $selected_category . '&tab=add'); ?>" class="button button-primary">
                ➕ Zustand erstellen
            </a>
        </div>
    <?php else: ?>
        <table class="activity-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Beschreibung</th>
                    <th>Preisanpassung</th>
                    <th>Verfügbarkeit</th>
                    <th>Sortierung</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conditions as $condition): ?>
                <?php
                    $modifier = round(floatval($condition->price_modifier) * 100, 2);
                    $available = isset($condition_variant_counts[$condition->id]) ? intval($condition_variant_counts[$condition->id]) : $variant_total;
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($condition->name); ?></strong>
                    </td>
                    <td>
                        <?php echo $condition->description ? esc_html($condition->description) : '<span style="color:#777;">–</span>'; ?>
                    </td>
                    <td>
                        <?php if ($modifier > 0): ?>
                            <span class="badge badge-danger">+<?php echo esc_html(number_format($modifier, 2, ',', '.')); ?>%</span>
                        <?php elseif ($modifier < 0): ?>
                            <span class="badge badge-success"><?php echo esc_html(number_format($modifier, 2, ',', '.')); ?>%</span>
                        <?php else: ?>
                            <span class="badge">±0%</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($variant_total > 0): ?>
                            <span class="badge badge-gray"><?php echo esc_html($available); ?> von <?php echo esc_html($variant_total); ?></span>
                        <?php else: ?>
                            <span class="badge">–</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html(intval($condition->sort_order)); ?></td>
                    <td>
                        <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='?page=produkt-conditions&category=<?php echo $selected_category; ?>&tab=edit&edit=<?php echo $condition->id; ?>'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                <path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/>
                                <path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/>
                            </svg>
                        </button>
                        <button type="button" class="icon-btn" onclick="if(confirm('Sind Sie sicher, dass Sie diesen Zustand löschen möchten?\n\n\"<?php echo esc_js($condition->name); ?>\" wird unwiderruflich gelöscht!')){window.location.href='?page=produkt-conditions&category=<?php echo $selected_category; ?>&delete=<?php echo $condition->id; ?>&fw_nonce=<?php echo wp_create_nonce('produkt_admin_action'); ?>';}" aria-label="Löschen">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                <path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/>
                                <path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/>
                            </svg>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
