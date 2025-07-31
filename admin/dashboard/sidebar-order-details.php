<?php
if (!defined('ABSPATH')) exit;

$order = $order_data ?? null;

if (empty($order) || !is_object($order)) {
    echo '<p>Fehler: Keine gültigen Auftragsdaten übergeben.</p>';
    return;
}
?>
<div class="order-box">
    <?php if (!empty($image_url)) : ?>
        <img class="order-product-image" src="<?php echo esc_url($image_url); ?>" alt="Produktbild">
    <?php endif; ?>

    <p><strong>Produkt:</strong> <?php echo esc_html($order->category_name ?? $order->produkt_name ?? '–'); ?></p>

    <?php if (!empty($order->variant_name)) : ?>
        <p><strong>Ausführung:</strong> <?php echo esc_html($order->variant_name); ?></p>
    <?php endif; ?>

    <p><strong>Extras:</strong> <?php echo esc_html($order->extra_names ?? '–'); ?></p>


    <?php if (!empty($order->produktfarbe_text)) : ?>
        <p><strong>Farbe:</strong> <?php echo esc_html($order->produktfarbe_text); ?></p>
    <?php endif; ?>

    <?php if (!empty($order->gestellfarbe_text)) : ?>
        <p><strong>Gestellfarbe:</strong> <?php echo esc_html($order->gestellfarbe_text); ?></p>
    <?php endif; ?>

    <?php if (!empty($order->condition_name)) : ?>
        <p><strong>Zustand:</strong> <?php echo esc_html($order->condition_name); ?></p>
    <?php endif; ?>

    <?php if (!empty($sd) && !empty($ed)) : ?>
        <p><strong>Zeitraum:</strong> <?php echo esc_html(date_i18n('d.m.Y', strtotime($sd))); ?> – <?php echo esc_html(date_i18n('d.m.Y', strtotime($ed))); ?></p>
    <?php endif; ?>

    <?php if ($days !== null) : ?>
        <p><strong>Miettage:</strong> <?php echo esc_html($days); ?></p>
    <?php elseif (!empty($order->dauer_text)) : ?>
        <p><strong>Miettage:</strong> <?php echo esc_html($order->dauer_text); ?></p>
    <?php endif; ?>

    <p><strong>Preis:</strong> <?php echo esc_html(number_format((float)$order->final_price, 2, ',', '.')); ?> €</p>

    <?php if ($order->shipping_cost > 0 || !empty($order->shipping_name)) : ?>
        <p><strong>Versand:</strong>
            <?php echo esc_html($order->shipping_name ?: 'Versand'); ?>
            <?php if ($order->shipping_cost > 0) : ?>
                – <?php echo esc_html(number_format((float)$order->shipping_cost, 2, ',', '.')); ?> €
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <hr>

    <p><strong>Kunde:</strong> <?php echo esc_html($order->customer_name ?? '–'); ?></p>

    <p><strong>E-Mail:</strong> <?php echo esc_html($order->customer_email ?? '–'); ?></p>

    <?php if (!empty($order->customer_phone)) : ?>
        <p><strong>Telefon:</strong> <?php echo esc_html($order->customer_phone); ?></p>
    <?php endif; ?>

    <?php if (!empty($order->customer_street)) : ?>
        <p><strong>Adresse:</strong>
            <?php
            echo esc_html($order->customer_street);
            if (!empty($order->customer_postal) || !empty($order->customer_city)) {
                echo ', ' . esc_html(trim($order->customer_postal . ' ' . $order->customer_city));
            }
            if (!empty($order->customer_country)) {
                echo ', ' . esc_html($order->customer_country);
            }
            ?>
        </p>
    <?php endif; ?>
</div>
