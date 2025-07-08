<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="produkt-shop-archive produkt-container">
    <?php if (!empty($current_group)): ?>
        <h1 class="produkt-group-title"><?php echo esc_html($current_group->name); ?></h1>
    <?php endif; ?>
    <div class="produkt-shop-grid">
        <?php foreach ($categories as $cat): ?>
        <?php
            $path = '/shop/';
            $group_slug = '';
            if (!empty($current_group)) {
                $group_slug = sanitize_title($current_group->slug ?: $current_group->name);
            } elseif (!empty($cat->group_id)) {
                $group_slug = $wpdb->get_var($wpdb->prepare(
                    "SELECT slug FROM {$wpdb->prefix}produkt_groups WHERE id = %d",
                    $cat->group_id
                ));
            }
            if (!empty($group_slug)) {
                $path .= $group_slug . '/';
            }
            $path .= sanitize_title($cat->shortcode ?: $cat->product_title);
            $url = home_url($path);
        ?>
        <?php
            $price = $wpdb->get_var($wpdb->prepare(
                "SELECT base_price FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d ORDER BY sort_order LIMIT 1",
                $cat->id
            ));
        ?>
        <a class="produkt-shop-card-link" href="<?php echo esc_url($url); ?>">
            <div class="produkt-shop-card">
                <?php if (!empty($cat->default_image)): ?>
                    <img class="produkt-shop-image" src="<?php echo esc_url($cat->default_image); ?>" alt="<?php echo esc_attr($cat->product_title); ?>">
                <?php endif; ?>
                <h2><?php echo esc_html($cat->product_title); ?></h2>
                <?php if ($cat->show_rating && floatval($cat->rating_value) > 0): ?>
                    <?php $display = number_format(floatval($cat->rating_value), 1, ',', ''); ?>
                    <div class="produkt-rating">
                        <span class="produkt-rating-number"><?php echo esc_html($display); ?></span>
                        <span class="produkt-star-rating produkt-star-black" style="--rating: <?php echo esc_attr($cat->rating_value); ?>;"></span>
                    </div>
                <?php endif; ?>
                <?php if ($price !== null): ?>
                    <div class="produkt-card-price"><?php echo number_format((float)$price, 2, ',', '.'); ?>€</div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
