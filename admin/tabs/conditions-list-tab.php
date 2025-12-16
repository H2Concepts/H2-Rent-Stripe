<?php
// Conditions List Tab Content
$variant_total = isset($variants) ? count($variants) : 0;
?>

<div class="produkt-conditions-list">
    <table class="activity-table">
        <thead>
            <tr>
                <th><?php echo esc_html__('Name', 'h2-rental-pro'); ?></th>
                <th><?php echo esc_html__('Beschreibung', 'h2-rental-pro'); ?></th>
                <th><?php echo esc_html__('Preisanpassung', 'h2-rental-pro'); ?></th>
                <th><?php echo esc_html__('Verfügbarkeit', 'h2-rental-pro'); ?></th>
                <th><?php echo esc_html__('Sortierung', 'h2-rental-pro'); ?></th>
                <th><?php echo esc_html__('Aktionen', 'h2-rental-pro'); ?></th>
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
                            <span
                                class="badge badge-danger">+<?php echo esc_html(number_format($modifier, 2, ',', '.')); ?>%</span>
                        <?php elseif ($modifier < 0): ?>
                            <span
                                class="badge badge-success"><?php echo esc_html(number_format($modifier, 2, ',', '.')); ?>%</span>
                        <?php else: ?>
                            <span class="badge">±0%</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        if ($variant_total > 0) {
                            echo esc_html(sprintf('%d / %d', $available, $variant_total));
                        } else {
                            echo '0';
                        }
                        ?>
                    </td>
                    <td><?php echo esc_html(intval($condition->sort_order)); ?></td>
                    <td>
                        <button type="button" class="icon-btn"
                            aria-label="<?php echo esc_attr__('Bearbeiten', 'h2-rental-pro'); ?>"
                            onclick="window.location.href='?page=produkt-conditions&category=<?php echo $selected_category; ?>&tab=edit&edit=<?php echo $condition->id; ?>'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                <path
                                    d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z" />
                                <path
                                    d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z" />
                            </svg>
                        </button>
                        <button type="button" class="icon-btn"
                            onclick="if(confirm('<?php echo esc_js(__('Bist du sicher, dass du löschen möchtest?', 'h2-rental-pro')); ?>')){window.location.href='?page=produkt-conditions&category=<?php echo $selected_category; ?>&delete=<?php echo $condition->id; ?>&fw_nonce=<?php echo wp_create_nonce('produkt_admin_action'); ?>';}"
                            aria-label="<?php echo esc_attr__('Löschen', 'h2-rental-pro'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                <path
                                    d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z" />
                                <path
                                    d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z" />
                            </svg>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>