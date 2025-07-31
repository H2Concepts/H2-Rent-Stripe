<?php
if (!defined('ABSPATH')) exit;

$order = $order_data ?? null;

if (empty($order) || !is_object($order)) {
    echo '<p>Fehler: Keine gültigen Auftragsdaten übergeben.</p>';
    return;
}

// Initialen erzeugen
$initials = '';
if (!empty($order->customer_name)) {
    $names = explode(' ', $order->customer_name);
    $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
}

// Prozent Mietdauer berechnen
$percent = 0;
if (!empty($sd) && !empty($ed)) {
    $start = strtotime($sd);
    $end = strtotime($ed);
    $today = time();
    $total = max(1, $end - $start);
    $elapsed = min(max(0, $today - $start), $total);
    $percent = floor(($elapsed / $total) * 100);
}

// Produkte ermitteln
$produkte = $order->produkte ?? [$order]; // fallback
?>

<div class="sidebar-wrapper">

    <!-- Header -->
    <div class="sidebar-header">
        <h2>Bestellübersicht</h2>
        <span class="order-id">#<?php echo esc_html($order->id ?? '–'); ?></span>
    </div>

    <!-- Kundeninfo -->
    <div class="customer-info">
        <div class="customer-avatar"><?php echo esc_html($initials); ?></div>
        <div class="customer-details">
            <strong><?php echo esc_html($order->customer_name ?? '–'); ?></strong>
            <div class="email"><?php echo esc_html($order->customer_email ?? '–'); ?></div>
        </div>
        <div class="customer-icons">
            <span class="icon">@</span>
            <span class="icon">📞</span>
            <span class="icon">⚙️</span>
        </div>
    </div>

    <!-- Kundendaten -->
    <div class="customer-contact">
        <h3>Kundendaten</h3>
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

    <!-- Mietzeitraum -->
    <div class="rental-period-box">
        <div class="badge-status">In Progress</div>
        <h3>Mietzeitraum</h3>
        <div class="rental-progress-number"><?php echo $percent; ?>%</div>
        <div class="rental-progress">
            <div class="bar">
                <div class="fill" style="width: <?php echo $percent; ?>%;"></div>
            </div>
        </div>
        <div class="rental-dates">
            <span>Abgeholt: <?php echo date_i18n('d. M', strtotime($sd)); ?></span>
            <span>Rückgabe: <?php echo date_i18n('d. M', strtotime($ed)); ?></span>
        </div>
    </div>

    <!-- Produktliste -->
    <div class="product-list">
        <h3>Produkte</h3>
        <?php foreach ($produkte as $p) : ?>
            <div class="product-item">
                <?php
                    $thumb_url = $p->image_url ?? $image_url ?? '';
                    if (!empty($thumb_url)) :
                ?>
                    <img class="product-thumb" src="<?php echo esc_url($thumb_url); ?>" alt="Produktbild">
                <?php endif; ?>

                <div class="product-details">
                    <strong><?php echo esc_html($p->produkt_name ?? '–'); ?></strong>
                    <?php if (!empty($p->variant_name)) : ?>
                        <div>Ausführung: <?php echo esc_html($p->variant_name); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($p->extra_names)) : ?>
                        <div>Extras: <?php echo esc_html($p->extra_names); ?></div>
                    <?php endif; ?>
                    <div>Miettage: <?php echo esc_html($p->dauer_text ?? '–'); ?></div>
                </div>

                <div class="product-price">
                    <?php echo number_format((float)$p->final_price, 2, ',', '.'); ?> €
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Gesamtpreis -->
    <div class="total-section">
        <p><strong>Gesamtpreis:</strong> <?php echo number_format((float)$order->final_price, 2, ',', '.'); ?> €</p>

        <?php if ($order->shipping_cost > 0 || !empty($order->shipping_name)) : ?>
            <p><strong>Versand:</strong>
                <?php echo esc_html($order->shipping_name ?: 'Versand'); ?>
                <?php if ($order->shipping_cost > 0) : ?>
                    – <?php echo number_format((float)$order->shipping_cost, 2, ',', '.'); ?> €
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
