<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="produkt-shop-archive produkt-container">
    <div class="produkt-shop-grid">
        <?php foreach ($categories as $cat): ?>
        <?php $url = home_url('/shop/' . sanitize_title($cat->product_title)); ?>
        <div class="produkt-shop-card">
            <?php if (!empty($cat->default_image)): ?>
                <img class="produkt-shop-image" src="<?php echo esc_url($cat->default_image); ?>" alt="<?php echo esc_attr($cat->product_title); ?>">
            <?php endif; ?>
            <h2><?php echo esc_html($cat->product_title); ?></h2>
            <?php if (!empty($cat->product_description)): ?>
                <p><?php echo esc_html(wp_trim_words($cat->product_description, 20)); ?></p>
            <?php endif; ?>
            <a class="produkt-shop-button" href="<?php echo esc_url($url); ?>">Jetzt ansehen</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
