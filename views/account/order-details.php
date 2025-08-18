<?php
if (!defined('ABSPATH')) {
    exit;
}

$nr = !empty($order->order_number) ? $order->order_number : $order->id;
$items = [];
if (!empty($order->client_info)) {
    $ci = json_decode($order->client_info, true);
    if (!empty($ci['cart_items']) && is_array($ci['cart_items'])) {
        $items = $ci['cart_items'];
    }
}
?>
<div class="order-box customer-order-box">
    <div class="customer-order-meta">
        <p><strong>Bestellnummer:</strong> #<?php echo esc_html($nr); ?></p>
        <?php if (!empty($order->customer_phone)) : ?>
            <p><strong>Telefon:</strong> <?php echo esc_html($order->customer_phone); ?></p>
        <?php endif; ?>
        <?php if (!empty($order->invoice_url)) : ?>
            <p><strong>Rechnung:</strong> <a href="<?php echo esc_url($order->invoice_url); ?>" target="_blank" rel="noopener">Download</a></p>
        <?php endif; ?>
        <?php if (!empty($order->shipping_name)) : ?>
            <p><strong>Versand:</strong> <?php echo esc_html($order->shipping_name); ?><?php if ($order->shipping_cost > 0) : ?> – <?php echo esc_html(number_format((float)$order->shipping_cost, 2, ',', '.')); ?>€<?php endif; ?></p>
        <?php endif; ?>
    </div>
    <div class="customer-order-items">
        <?php if ($items) : ?>
            <?php foreach ($items as $it) : $meta = $it['metadata'] ?? []; $img = $it['image_url'] ?? pv_get_image_url_by_variant_or_category(intval($it['variant_id'] ?? 0), intval($it['category_id'] ?? 0)); ?>
                <div class="customer-order-item">
                    <?php if ($img) : ?><img src="<?php echo esc_url($img); ?>" class="customer-order-thumb" alt=""><?php endif; ?>
                    <div class="customer-order-info">
                        <p class="customer-order-name"><?php echo esc_html($meta['produkt'] ?? 'Produkt'); ?></p>
                        <?php if (!empty($meta['zustand'])) : ?><p>Ausführung: <?php echo esc_html($meta['zustand']); ?></p><?php endif; ?>
                        <?php if (!empty($meta['extra'])) : ?><p>Extras: <?php echo esc_html($meta['extra']); ?></p><?php endif; ?>
                        <?php if (!empty($meta['produktfarbe'])) : ?><p>Farbe: <?php echo esc_html($meta['produktfarbe']); ?></p><?php endif; ?>
                        <?php if (!empty($meta['gestellfarbe'])) : ?><p>Gestellfarbe: <?php echo esc_html($meta['gestellfarbe']); ?></p><?php endif; ?>
                        <?php if (!empty($it['start_date']) && !empty($it['end_date'])) : ?>
                            <p>Mietzeitraum: <?php echo esc_html(date_i18n('d.m.Y', strtotime($it['start_date']))); ?> - <?php echo esc_html(date_i18n('d.m.Y', strtotime($it['end_date']))); ?></p>
                        <?php elseif (!empty($meta['dauer_name'])) : ?>
                            <p>Mietdauer: <?php echo esc_html($meta['dauer_name']); ?></p>
                        <?php endif; ?>
                        <p class="customer-order-price"><?php echo esc_html(number_format((float)($it['final_price'] ?? 0), 2, ',', '.')); ?>€</p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <?php $img = pv_get_order_image($order); ?>
            <div class="customer-order-item">
                <?php if ($img) : ?><img src="<?php echo esc_url($img); ?>" class="customer-order-thumb" alt=""><?php endif; ?>
                <div class="customer-order-info">
                    <p class="customer-order-name"><?php echo esc_html($order->produkt_name ?: $order->variant_name); ?></p>
                    <?php if (!empty($order->extra_names)) : ?><p>Extras: <?php echo esc_html($order->extra_names); ?></p><?php endif; ?>
                    <?php if (!empty($order->produktfarbe_text)) : ?><p>Farbe: <?php echo esc_html($order->produktfarbe_text); ?></p><?php endif; ?>
                    <?php if (!empty($order->gestellfarbe_text)) : ?><p>Gestellfarbe: <?php echo esc_html($order->gestellfarbe_text); ?></p><?php endif; ?>
                    <?php if (!empty($order->zustand_text)) : ?><p>Ausführung: <?php echo esc_html($order->zustand_text); ?></p><?php endif; ?>
                    <?php list($sd,$ed) = pv_get_order_period($order); if ($sd && $ed) : ?>
                        <p>Mietzeitraum: <?php echo esc_html(date_i18n('d.m.Y', strtotime($sd))); ?> - <?php echo esc_html(date_i18n('d.m.Y', strtotime($ed))); ?></p>
                    <?php endif; ?>
                    <p class="customer-order-price"><?php echo esc_html(number_format((float)$order->final_price, 2, ',', '.')); ?>€</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="customer-order-total">
        <p><strong>Gesamtpreis:</strong> <?php echo esc_html(number_format((float)$order->final_price, 2, ',', '.')); ?>€</p>
        <?php if ($order->shipping_cost > 0) : ?>
            <p><strong>Versand:</strong> <?php echo esc_html(number_format((float)$order->shipping_cost, 2, ',', '.')); ?>€</p>
        <?php endif; ?>
    </div>
</div>
