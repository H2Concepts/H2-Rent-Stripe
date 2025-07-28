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
        <?php $days = pv_get_order_rental_days($order); ?>
        <?php if ($days !== null) : ?>
            <p><strong>Miettage:</strong> <?php echo esc_html($days); ?></p>
        <?php elseif (!empty($order->dauer_text)) : ?>
            <p><strong>Miettage:</strong> <?php echo esc_html($order->dauer_text); ?></p>
        <?php endif; ?>
        <?php list($sd,$ed) = pv_get_order_period($order); ?>
        <?php if ($sd && $ed) : ?>
            <p><strong>Zeitraum:</strong> <?php echo esc_html(date_i18n('d.m.Y', strtotime($sd))); ?> - <?php echo esc_html(date_i18n('d.m.Y', strtotime($ed))); ?></p>
        <?php endif; ?>
    </div>
    <div class="order-box">
        <h3>Kundendaten</h3>
        <?php if (!empty($order->customer_name)) : ?>
            <p><strong>Name:</strong> <?php echo esc_html($order->customer_name); ?></p>
        <?php endif; ?>
        <?php if (!empty($order->customer_email)) : ?>
            <p><strong>E-Mail:</strong> <?php echo esc_html($order->customer_email); ?></p>
        <?php endif; ?>
        <?php if (!empty($order->customer_phone)) : ?>
            <p><strong>Telefon:</strong> <?php echo esc_html($order->customer_phone); ?></p>
        <?php endif; ?>
        <?php
        $address_parts = [];
        if (!empty($order->customer_street)) {
            $address_parts[] = $order->customer_street;
        }
        if (!empty($order->customer_postal) || !empty($order->customer_city)) {
            $address_parts[] = trim(($order->customer_postal ?: '') . ' ' . ($order->customer_city ?: ''));
        }
        if (!empty($order->customer_country)) {
            $address_parts[] = $order->customer_country;
        }
        $address = implode(', ', array_filter($address_parts));
        ?>
        <?php if ($address) : ?>
            <p><strong>Adresse:</strong> <?php echo esc_html($address); ?></p>
        <?php endif; ?>
    </div>
</div>
