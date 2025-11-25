<?php
if (!defined('ABSPATH')) { exit; }

use ProduktVerleih\StripeService;

/**
 * Get the lowest Stripe price for all variants and durations in a category.
 *
 * @param int $category_id Category ID.
 * @return array{amount: ?float, price_id: ?string, count: int}
 */
function pv_get_lowest_stripe_price_by_category($category_id) {
    global $wpdb;

    $variant_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d",
            $category_id
        )
    );

    $duration_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}produkt_durations WHERE category_id = %d",
            $category_id
        )
    );

    $price_data = StripeService::get_lowest_price_with_durations($variant_ids, $duration_ids);
    $fallback_amount = pv_get_lowest_rental_amount($variant_ids, $duration_ids);

    if ($fallback_amount !== null) {
        if (!is_array($price_data)) {
            $price_data = ['amount' => $fallback_amount, 'price_id' => ''];
        } elseif (!isset($price_data['amount']) || (float) $price_data['amount'] <= 0 || $fallback_amount < (float) $price_data['amount']) {
            $price_data['amount'] = $fallback_amount;
        }
    }

    $duration_count = count($duration_ids);
    $price_count = 0;
    if (!empty($variant_ids) && !empty($duration_ids)) {
        $placeholders_variant  = implode(',', array_fill(0, count($variant_ids), '%d'));
        $placeholders_duration = implode(',', array_fill(0, count($duration_ids), '%d'));
        $count_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_duration_prices
             WHERE variant_id IN ($placeholders_variant)
               AND duration_id IN ($placeholders_duration)",
            array_merge($variant_ids, $duration_ids)
        );
        $price_count = (int) $wpdb->get_var($count_query);
    }

    $amount = isset($price_data['amount']) ? (float) $price_data['amount'] : null;
    if ($amount !== null && $amount <= 0) {
        $amount = null;
    }

    return [
        'amount'         => $amount,
        'price_id'       => $price_data['price_id'] ?? null,
        'count'          => $price_count,
        'duration_count' => $duration_count,
        'mode'           => get_option('produkt_betriebsmodus', 'miete'),
    ];
}

/**
 * Find the lowest available rental amount for given variants and durations.
 *
 * @param array $variant_ids
 * @param array $duration_ids
 * @return float|null
 */
function pv_get_lowest_rental_amount($variant_ids, $duration_ids) {
    global $wpdb;

    if (empty($variant_ids)) {
        return null;
    }

    $lowest = null;

    if (!empty($duration_ids)) {
        $placeholders_v = implode(',', array_fill(0, count($variant_ids), '%d'));
        $placeholders_d = implode(',', array_fill(0, count($duration_ids), '%d'));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT stripe_price_id, custom_price FROM {$wpdb->prefix}produkt_duration_prices
                 WHERE variant_id IN ($placeholders_v)
                   AND duration_id IN ($placeholders_d)",
                array_merge($variant_ids, $duration_ids)
            )
        );

        foreach ($rows as $row) {
            $custom = ($row->custom_price !== null) ? (float) $row->custom_price : 0.0;
            if ($custom > 0 && ($lowest === null || $custom < $lowest)) {
                $lowest = $custom;
            }

            if (!empty($row->stripe_price_id)) {
                $amount = StripeService::get_price_amount($row->stripe_price_id);
                if (!is_wp_error($amount) && $amount > 0 && ($lowest === null || $amount < $lowest)) {
                    $lowest = $amount;
                }
            }
        }
    }

    if ($lowest === null) {
        $placeholders_v = implode(',', array_fill(0, count($variant_ids), '%d'));
        $stripe_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT stripe_price_id FROM {$wpdb->prefix}produkt_variants WHERE id IN ($placeholders_v)",
                $variant_ids
            )
        );

        foreach ($stripe_ids as $pid) {
            if (!$pid) { continue; }
            $amount = StripeService::get_price_amount($pid);
            if (!is_wp_error($amount) && $amount > 0 && ($lowest === null || $amount < $lowest)) {
                $lowest = $amount;
            }
        }
    }

    return $lowest;
}

/**
 * Format a price label based on price data.
 *
 * @param array|null $price_data Price data array from pv_get_lowest_stripe_price_by_category.
 * @return string Formatted price string.
 */
function pv_format_price_label($price_data) {
    if (!$price_data || !isset($price_data['amount'])) {
        return 'Preis auf Anfrage';
    }

    $mode = $price_data['mode'] ?? get_option('produkt_betriebsmodus', 'miete');
    $duration_count = (int) ($price_data['duration_count'] ?? 0);
    $formatted = number_format((float) $price_data['amount'], 2, ',', '.');

    if ($mode === 'miete' && $duration_count >= 2 && $price_data['amount'] > 0) {
        return 'ab ' . $formatted . '€';
    }

    if (($price_data['count'] ?? 0) > 1) {
        return 'ab ' . $formatted . '€';
    }

    return $formatted . '€';
}

/**
 * Render content blocks at a specific position and return how many were output.
 *
 * @param int $index Position index to render.
 * @param array $desktop_blocks Blocks keyed by position for desktop.
 * @param array $mobile_blocks Blocks keyed by position for mobile.
 * @return int Number of blocks rendered.
 */
function pv_render_content_blocks($index, &$desktop_blocks, &$mobile_blocks) {
    $desktop_count = 0;
    if (isset($desktop_blocks[$index])) {
        foreach ($desktop_blocks[$index] as $block) {
            if (($block->style ?? 'wide') === 'compact') {
                ?>
                <div class="shop-product-item desktop-only content-block-compact"<?php if (!empty($block->background_color)): ?> style="background-color: <?php echo esc_attr($block->background_color); ?>; --block-bg: <?php echo esc_attr($block->background_color); ?>"<?php endif; ?>>
                    <a href="<?php echo esc_url($block->button_url); ?>">
                        <div class="shop-product-image">
                            <?php if (!empty($block->image_url)): ?>
                                <img src="<?php echo esc_url($block->image_url); ?>" alt="<?php echo esc_attr($block->title); ?>">
                            <?php endif; ?>
                            <?php if (!empty($block->badge_text)): ?>
                                <span class="content-block-badge"><?php echo esc_html($block->badge_text); ?></span>
                            <?php endif; ?>
                        </div>
                        <svg class="content-block-wave" viewBox="0 0 1440 320" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M0,160L17.1,170.7C34.3,181,69,203,103,181.3C137.1,160,171,96,206,64C240,32,274,32,309,74.7C342.9,117,377,203,411,240C445.7,277,480,267,514,218.7C548.6,171,583,85,617,64C651.4,43,686,85,720,117.3C754.3,149,789,171,823,154.7C857.1,139,891,85,926,53.3C960,21,994,11,1029,42.7C1062.9,75,1097,149,1131,165.3C1165.7,181,1200,139,1234,154.7C1268.6,171,1303,245,1337,234.7C1371.4,224,1406,128,1423,80L1440,32L1440,320L1422.9,320C1405.7,320,1371,320,1337,320C1302.9,320,1269,320,1234,320C1200,320,1166,320,1131,320C1097.1,320,1063,320,1029,320C994.3,320,960,320,926,320C891.4,320,857,320,823,320C788.6,320,754,320,720,320C685.7,320,651,320,617,320C582.9,320,549,320,514,320C480,320,446,320,411,320C377.1,320,343,320,309,320C274.3,320,240,320,206,320C171.4,320,137,320,103,320C68.6,320,34,320,17,320L0,320Z"/>
                        </svg>
                        <h3 class="shop-product-title"><?php echo esc_html($block->title); ?></h3>
                        <div class="shop-product-shortdesc"><?php echo wpautop($block->content); ?></div>
                    </a>
                </div>
                <?php
            } else {
                ?>
                <div class="content-block desktop-only"<?php if (!empty($block->background_color)): ?> style="background-color: <?php echo esc_attr($block->background_color); ?>"<?php endif; ?>>
                    <div class="content-block-text">
                        <?php if (!empty($block->badge_text)): ?>
                            <span class="content-block-badge"><?php echo esc_html($block->badge_text); ?></span>
                        <?php endif; ?>
                        <h3 class="content-block-title"><?php echo esc_html($block->title); ?></h3>
                        <div class="content-block-description">
                            <?php echo wpautop($block->content); ?>
                        </div>
                        <?php if (!empty($block->button_text) && !empty($block->button_url)): ?>
                            <a class="content-block-button" href="<?php echo esc_url($block->button_url); ?>"><?php echo esc_html($block->button_text); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="content-block-image"<?php if (!empty($block->image_url)): ?> style="background-image:url('<?php echo esc_url($block->image_url); ?>')"<?php endif; ?>></div>
                </div>
                <?php
            }
            $desktop_count++;
        }
        unset($desktop_blocks[$index]);
    }

    $mobile_count = 0;
    if (isset($mobile_blocks[$index])) {
        foreach ($mobile_blocks[$index] as $block) {
            if (($block->style ?? 'wide') === 'compact') {
                ?>
                <div class="shop-product-item mobile-only content-block-compact"<?php if (!empty($block->background_color)): ?> style="background-color: <?php echo esc_attr($block->background_color); ?>; --block-bg: <?php echo esc_attr($block->background_color); ?>"<?php endif; ?>>
                    <a href="<?php echo esc_url($block->button_url); ?>">
                        <div class="shop-product-image">
                            <?php if (!empty($block->image_url)): ?>
                                <img src="<?php echo esc_url($block->image_url); ?>" alt="<?php echo esc_attr($block->title); ?>">
                            <?php endif; ?>
                            <?php if (!empty($block->badge_text)): ?>
                                <span class="content-block-badge"><?php echo esc_html($block->badge_text); ?></span>
                            <?php endif; ?>
                        </div>
                        <svg class="content-block-wave" viewBox="0 0 1440 320" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M0,160L17.1,170.7C34.3,181,69,203,103,181.3C137.1,160,171,96,206,64C240,32,274,32,309,74.7C342.9,117,377,203,411,240C445.7,277,480,267,514,218.7C548.6,171,583,85,617,64C651.4,43,686,85,720,117.3C754.3,149,789,171,823,154.7C857.1,139,891,85,926,53.3C960,21,994,11,1029,42.7C1062.9,75,1097,149,1131,165.3C1165.7,181,1200,139,1234,154.7C1268.6,171,1303,245,1337,234.7C1371.4,224,1406,128,1423,80L1440,32L1440,320L1422.9,320C1405.7,320,1371,320,1337,320C1302.9,320,1269,320,1234,320C1200,320,1166,320,1131,320C1097.1,320,1063,320,1029,320C994.3,320,960,320,926,320C891.4,320,857,320,823,320C788.6,320,754,320,720,320C685.7,320,651,320,617,320C582.9,320,549,320,514,320C480,320,446,320,411,320C377.1,320,343,320,309,320C274.3,320,240,320,206,320C171.4,320,137,320,103,320C68.6,320,34,320,17,320L0,320Z"/>
                        </svg>
                        <h3 class="shop-product-title"><?php echo esc_html($block->title); ?></h3>
                        <div class="shop-product-shortdesc"><?php echo wpautop($block->content); ?></div>
                    </a>
                </div>
                <?php
            } else {
                ?>
                <div class="content-block mobile-only"<?php if (!empty($block->background_color)): ?> style="background-color: <?php echo esc_attr($block->background_color); ?>"<?php endif; ?>>
                    <div class="content-block-text">
                        <?php if (!empty($block->badge_text)): ?>
                            <span class="content-block-badge"><?php echo esc_html($block->badge_text); ?></span>
                        <?php endif; ?>
                        <h3 class="content-block-title"><?php echo esc_html($block->title); ?></h3>
                        <div class="content-block-description">
                            <?php echo wpautop($block->content); ?>
                        </div>
                        <?php if (!empty($block->button_text) && !empty($block->button_url)): ?>
                            <a class="content-block-button" href="<?php echo esc_url($block->button_url); ?>"><?php echo esc_html($block->button_text); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="content-block-image"<?php if (!empty($block->image_url)): ?> style="background-image:url('<?php echo esc_url($block->image_url); ?>')"<?php endif; ?>></div>
                </div>
                <?php
            }
            $mobile_count++;
        }
        unset($mobile_blocks[$index]);
    }

    return $desktop_count;
}

