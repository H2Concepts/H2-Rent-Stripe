<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="abo-row">
    <div class="order-box">
        <?php
            $nr          = !empty($order->order_number) ? $order->order_number : $order->id;
            $mode        = pv_get_order_mode((array) ($order ?? []), (int) ($order->id ?? 0));
            $is_rental   = ($mode === 'miete');
            $price_suffix = $is_rental ? '/Monat' : '';
        ?>
        <h3>Bestellung #<?php echo esc_html($nr); ?></h3>
        <p><strong>Datum:</strong> <?php echo esc_html(date_i18n('d.m.Y', strtotime($order->created_at))); ?></p>

        <?php $produkte = $order->produkte ?? [$order]; ?>
        <div class="order-product-list">
            <?php foreach ($produkte as $produkt) : ?>
                <div class="order-product-row">
                    <?php $thumb = $produkt->image_url ?? $image_url ?? ''; ?>
                    <?php if (!empty($thumb)) : ?>
                        <div class="order-product-thumbwrap">
                            <img class="order-product-thumb" src="<?php echo esc_url($thumb); ?>" alt="">
                        </div>
                    <?php endif; ?>
                    <div class="order-product-meta">
                        <p><strong>Produkt:</strong> <?php echo esc_html($produkt->produkt_name ?: ($order->category_name ?? $order->produkt_name)); ?></p>
                        <?php if (!empty($produkt->variant_name)) : ?>
                            <p><strong>Ausführung:</strong> <?php echo esc_html($produkt->variant_name); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($produkt->extra_names)) : ?>
                            <p><strong>Extras:</strong> <?php echo esc_html($produkt->extra_names); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($produkt->product_color_name)) : ?>
                            <p><strong>Farbe:</strong> <?php echo esc_html($produkt->product_color_name); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($produkt->frame_color_name)) : ?>
                            <p><strong>Gestellfarbe:</strong> <?php echo esc_html($produkt->frame_color_name); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($produkt->condition_name)) : ?>
                            <p><strong>Zustand:</strong> <?php echo esc_html($produkt->condition_name); ?></p>
                        <?php endif; ?>
                        <?php
                            $period_obj = (object) array_merge((array) $order, (array) $produkt);
                            list($sd, $ed) = pv_get_order_period($period_obj);
                        ?>
                        <?php if ($sd && $ed) : ?>
                            <p><strong>Zeitraum:</strong> <?php echo esc_html(date_i18n('d.m.Y', strtotime($sd))); ?> - <?php echo esc_html(date_i18n('d.m.Y', strtotime($ed))); ?></p>
                        <?php endif; ?>
                        <?php
                            $days = pv_get_order_rental_days($period_obj);
                            $duration_text = $produkt->duration_name ?? ($order->dauer_text ?? '');
                        ?>
                        <?php $duration_label = $is_rental ? 'Mindestlaufzeit' : 'Miettage'; ?>
                        <?php if ($days !== null) : ?>
                            <p><strong><?php echo esc_html($duration_label); ?>:</strong> <?php echo esc_html($days); ?></p>
                        <?php elseif (!empty($duration_text)) : ?>
                            <p><strong><?php echo esc_html($duration_label); ?>:</strong> <?php echo esc_html($duration_text); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($produkt->weekend_tariff)) : ?>
                            <p><strong>Hinweis:</strong> Wochenendtarif</p>
                        <?php endif; ?>
                    </div>
                    <div class="order-product-price">
                        <?php echo esc_html(number_format((float) ($produkt->final_price ?? 0), 2, ',', '.')); ?>€<?php echo esc_html($price_suffix); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="order-totals">
            <p><strong>Zwischensumme:</strong> <?php echo esc_html(number_format((float) $order->final_price, 2, ',', '.')); ?>€<?php echo esc_html($price_suffix); ?></p>
            <?php if (($order->shipping_cost ?? 0) > 0 || !empty($order->shipping_name)) : ?>
                <?php $shipping_label = pv_format_shipping_cost_label($order->shipping_cost ?? 0); ?>
                <p><strong>Versand:</strong> <?php echo esc_html($order->shipping_name ?: 'Versand'); ?> - <?php echo esc_html($shipping_label); ?></p>
            <?php endif; ?>
            <?php $total = (float) $order->final_price + (float) $order->shipping_cost; ?>
            <p><strong>Gesamtsumme:</strong> <?php echo esc_html(number_format($total, 2, ',', '.')); ?>€</p>
        </div>
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
