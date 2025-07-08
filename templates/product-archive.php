<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="produkt-shop-archive produkt-container">
    <div class="produkt-shop-grid">
        <?php foreach ($categories as $cat): ?>
        <?php $url = home_url('/shop/' . sanitize_title($cat->product_title)); ?>
        <a class="produkt-shop-card" href="<?php echo esc_url($url); ?>">
            <?php if (!empty($cat->default_image)): ?>
                <img class="produkt-shop-image" src="<?php echo esc_url($cat->default_image); ?>" alt="<?php echo esc_attr($cat->product_title); ?>">
            <?php endif; ?>
            <h2><?php echo esc_html($cat->product_title); ?></h2>

            <?php if ($cat->show_rating && floatval($cat->rating_value) > 0): ?>
                <div class="produkt-card-rating">
                    <span class="produkt-star-rating small" style="--rating: <?php echo esc_attr(floatval($cat->rating_value)); ?>;"></span>
                    <span class="produkt-rating-number"><?php echo esc_html(number_format(floatval($cat->rating_value), 1, ',', '')); ?></span>
                </div>
            <?php endif; ?>

            <?php $price_info = $cat->price_info; ?>
            <?php if ($price_info): ?>
                <div class="produkt-card-price">
                    <?php echo ($cat->variant_count > 1 ? 'ab ' : '') . number_format($price_info['amount'], 2, ',', '.') . ' ' . esc_html($price_info['currency']); ?>
                </div>
            <?php endif; ?>

        </a>
        <?php endforeach; ?>
    </div>
</div>
