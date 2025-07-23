<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="abo-row">
    <div class="order-box">
        <?php if (!empty($image_url)) : ?>
            <img class="order-product-image" src="<?php echo esc_url($image_url); ?>" alt="">
        <?php endif; ?>
        <h3>Bestellung #<?php echo esc_html($order->id); ?></h3>
        <p><strong>Datum:</strong> <?php echo esc_html(date_i18n('d.m.Y', strtotime($order->created_at))); ?></p>
        <p><strong>Produkt:</strong> <?php echo esc_html($order->produkt_name); ?></p>
        <p><strong>Preis:</strong> <?php echo esc_html(number_format((float) $order->final_price, 2, ',', '.')); ?>€</p>
        <?php if ($order->shipping_cost > 0) : ?>
            <p><strong>Versand:</strong> <?php echo esc_html(number_format((float) $order->shipping_cost, 2, ',', '.')); ?>€</p>
        <?php endif; ?>
        <?php if (!empty($order->extra_text)) : ?>
            <p><strong>Extras:</strong> <?php echo esc_html($order->extra_text); ?></p>
        <?php endif; ?>
        <?php if (!empty($order->produktfarbe_text)) : ?>
            <p><strong>Farbe:</strong> <?php echo esc_html($order->produktfarbe_text); ?></p>
        <?php endif; ?>
        <?php if (!empty($order->gestellfarbe_text)) : ?>
            <p><strong>Gestellfarbe:</strong> <?php echo esc_html($order->gestellfarbe_text); ?></p>
        <?php endif; ?>
        <?php if (!empty($order->zustand_text)) : ?>
            <p><strong>Zustand:</strong> <?php echo esc_html($order->zustand_text); ?></p>
        <?php endif; ?>
<?php if (!empty($order->invoice_url) && $order->mode === 'kauf') : ?>
    <p>
        📄 <a href="<?php echo esc_url($order->invoice_url); ?>" target="_blank">
            Rechnung herunterladen
        </a>
    </p>
<?php endif; ?>
    </div>
</div>
