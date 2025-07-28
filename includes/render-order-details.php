<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="order-box">
    <?php if (!empty($image_url)) : ?>
        <img class="order-product-image" src="<?php echo esc_url($image_url); ?>" alt="">
    <?php endif; ?>
    <p><strong>Produkt:</strong> <?php echo esc_html($order->category_name); ?></p>
    <?php if (!empty($order->variant_name)) : ?>
        <p><strong>Ausführung:</strong> <?php echo esc_html($order->variant_name); ?></p>
    <?php endif; ?>
    <?php if (!empty($order->extra_names)) : ?>
        <p><strong>Extras:</strong> <?php echo esc_html($order->extra_names); ?></p>
    <?php endif; ?>
    <?php if (!empty($order->produktfarbe_text)) : ?>
        <p><strong>Farbe:</strong> <?php echo esc_html($order->produktfarbe_text); ?></p>
    <?php endif; ?>
    <?php if (!empty($order->gestellfarbe_text)) : ?>
        <p><strong>Gestellfarbe:</strong> <?php echo esc_html($order->gestellfarbe_text); ?></p>
    <?php endif; ?>
    <?php if (!empty($order->condition_name)) : ?>
        <p><strong>Zustand:</strong> <?php echo esc_html($order->condition_name); ?></p>
    <?php endif; ?>
    <?php list($sd,$ed) = pv_get_order_period($order); ?>
    <?php if ($sd && $ed) : ?>
        <p><strong>Zeitraum:</strong> <?php echo esc_html(date_i18n('d.m.Y', strtotime($sd))); ?> - <?php echo esc_html(date_i18n('d.m.Y', strtotime($ed))); ?></p>
    <?php endif; ?>
    <?php $days = pv_get_order_rental_days($order); ?>
    <?php if ($days !== null) : ?>
        <p><strong>Miettage:</strong> <?php echo esc_html($days); ?></p>
    <?php elseif (!empty($order->dauer_text)) : ?>
        <p><strong>Miettage:</strong> <?php echo esc_html($order->dauer_text); ?></p>
    <?php endif; ?>
    <p><strong>Preis:</strong> <?php echo esc_html(number_format((float) $order->final_price, 2, ',', '.')); ?>€</p>
    <?php if ($order->shipping_cost > 0 || !empty($order->shipping_name)) : ?>
        <p><strong>Versand:</strong> <?php echo esc_html($order->shipping_name ?: 'Versand'); ?> <?php if ($order->shipping_cost > 0) : ?>- <?php echo esc_html(number_format((float) $order->shipping_cost, 2, ',', '.')); ?>€<?php endif; ?></p>
    <?php endif; ?>
</div>
