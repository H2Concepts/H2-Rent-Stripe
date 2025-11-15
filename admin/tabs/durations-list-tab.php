<?php
// Durations List Tab Content
?>

<div class="produkt-durations-list">

    <?php if (empty($durations)): ?>
        <div class="produkt-empty-state">
            <div class="produkt-empty-icon">⏰</div>
            <h4>Noch keine Mietdauern vorhanden</h4>
            <p>Erstellen Sie Ihre erste Mietdauer für dieses Produkt.</p>
            <button type="button" class="button button-primary js-open-duration-modal">
                ➕ Erste Mietdauer erstellen
            </button>
        </div>
    <?php else: ?>
        <table class="activity-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Mindestlaufzeit</th>
                    <th>Badges</th>
                    <th>Sortierung</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($durations as $duration):
                    $popular_gradient_start = function_exists('pv_normalize_hex_color_value')
                        ? pv_normalize_hex_color_value($duration->popular_gradient_start ?? '', '#ff8a3d')
                        : (sanitize_hex_color($duration->popular_gradient_start ?? '') ?: '#ff8a3d');
                    $popular_gradient_end = function_exists('pv_normalize_hex_color_value')
                        ? pv_normalize_hex_color_value($duration->popular_gradient_end ?? '', '#ff5b0f')
                        : (sanitize_hex_color($duration->popular_gradient_end ?? '') ?: '#ff5b0f');
                    $popular_text_color = function_exists('pv_normalize_hex_color_value')
                        ? pv_normalize_hex_color_value($duration->popular_text_color ?? '', '#ffffff')
                        : (sanitize_hex_color($duration->popular_text_color ?? '') ?: '#ffffff');
                    $popular_style = sprintf(
                        '--popular-gradient-start:%1$s; --popular-gradient-end:%2$s; --popular-text-color:%3$s;',
                        $popular_gradient_start,
                        $popular_gradient_end,
                        $popular_text_color
                    );

                    $is_archived = \ProduktVerleih\StripeService::is_price_archived_cached($duration->stripe_price_id);
                    $product_archived = false;
                    if (!empty($duration->stripe_product_id)) {
                        $product_archived = \ProduktVerleih\StripeService::is_product_archived_cached($duration->stripe_product_id);
                    }
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($duration->name); ?></strong><br>
                        <?php if ($is_archived): ?>
                            <span class="badge badge-warning">Archivierter oder ungültiger Stripe-Preis</span>
                        <?php endif; ?>
                        <?php if ($product_archived): ?>
                            <span class="badge badge-danger">⚠️ Produkt bei Stripe archiviert</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo intval($duration->months_minimum); ?> Monat<?php echo $duration->months_minimum > 1 ? 'e' : ''; ?>
                    </td>
                    <td>
                        <?php if ($duration->show_badge || !empty($duration->show_popular)): ?>
                            <div class="produkt-duration-badges">
                                <?php if ($duration->show_badge): ?>
                                    <span class="produkt-discount-badge">Rabatt-Badge</span>
                                <?php endif; ?>
                                <?php if (!empty($duration->show_popular)): ?>
                                    <span class="badge badge-popular" style="<?php echo esc_attr($popular_style); ?>">beliebt</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            –
                        <?php endif; ?>
                    </td>
                    <td><?php echo intval($duration->sort_order); ?></td>
                    <td>
                        <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='?page=produkt-durations&category=<?php echo $selected_category; ?>&tab=edit&edit=<?php echo $duration->id; ?>'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                <path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/>
                                <path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/>
                            </svg>
                        </button>
                        <button type="button" class="icon-btn" onclick="if(confirm('Sind Sie sicher, dass Sie diese Mietdauer löschen möchten?\n\n\"<?php echo esc_js($duration->name); ?>\" wird unwiderruflich gelöscht!')){window.location.href='?page=produkt-durations&category=<?php echo $selected_category; ?>&delete=<?php echo $duration->id; ?>&fw_nonce=<?php echo wp_create_nonce('produkt_admin_action'); ?>';}" aria-label="Löschen">
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


