<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="abo-row">
    <div class="abo-box">
        <div class="abo-header">
            <img src="<?php echo esc_url(pv_get_variant_image_url($variant_id)); ?>" alt="">
            <h3>Abo-Übersicht</h3>
            <?php echo pv_get_subscription_status_badge($sub['status']); ?>
        </div>
        <p><strong>Produkt:</strong> <?php echo esc_html($produkt_name); ?></p>
        <p><strong>Gemietet seit:</strong> <?php echo esc_html($start_formatted); ?></p>
        <p><strong>Kündbar ab:</strong> <?php echo esc_html($kuendigbar_ab_date); ?></p>
        <?php if (!empty($sub['current_period_end'])) : ?>
            <p><strong>Läuft aktuell bis:</strong> <?php echo esc_html(date_i18n('d.m.Y', strtotime($sub['current_period_end']))); ?></p>
        <?php endif; ?>
        <?php if ($sub['cancel_at_period_end']) : ?>
            <p style="color:orange;"><strong>✅ Kündigung vorgemerkt zum <?php echo esc_html($period_end_date); ?>.</strong></p>
        <?php elseif ($is_extended) : ?>
            <p>📦 Abo läuft weiter. Nächster Abrechnungszeitraum bis: <?php echo esc_html($period_end_date); ?></p>
            <?php if ($can_cancel) : ?>
            <form method="post">
                <?php wp_nonce_field('cancel_subscription_action', 'cancel_subscription_nonce'); ?>
                <input type="hidden" name="subscription_id" value="<?php echo esc_attr($sub['subscription_id']); ?>">
                <button type="submit" name="cancel_subscription" style="background:#dc3545;color:white;border:none;padding:10px 20px;border-radius:5px;">Zum nächsten Laufzeitende kündigen</button>
            </form>
            <?php endif; ?>
        <?php elseif ($cancelable && $can_cancel) : ?>
            <form method="post">
                <?php wp_nonce_field('cancel_subscription_action', 'cancel_subscription_nonce'); ?>
                <input type="hidden" name="subscription_id" value="<?php echo esc_attr($sub['subscription_id']); ?>">
                <p style="margin-bottom:8px;">Sie können jetzt kündigen – die Kündigung wird zum Ende der Mindestlaufzeit wirksam (<?php echo esc_html(date_i18n('d.m.Y', $cancelable_ts)); ?>).</p>
                <button type="submit" name="cancel_subscription" style="background:#dc3545;color:white;border:none;padding:10px 20px;border-radius:5px;">Zum Laufzeitende kündigen</button>
            </form>
        <?php elseif (!$cancelable) : ?>
            <p style="color:#888;"><strong>⏳ Ihre Kündigung ist frühestens 14 Tage vor Ablauf der Mindestlaufzeit möglich (ab dem <?php echo esc_html($kuendigbar_ab_date); ?>).</strong></p>
        <?php endif; ?>
        <p class="abo-info">Nach Ablauf der Mindestlaufzeit verlängert sich das Abo automatisch monatlich. Sie können jederzeit zum Ende des laufenden Abrechnungszeitraums kündigen.</p>
    </div>
    <?php if ($order) : ?>
        <div class="image-box">
            <?php if ($image_url) : ?>
                <img src="<?php echo esc_url($image_url); ?>" alt="">
            <?php endif; ?>
        </div>
        <div class="order-box">
            <h3>Abo-Details</h3>
            <p><strong>Name:</strong> <?php echo esc_html($order->customer_name); ?></p>
            <p><strong>E-Mail:</strong> <?php echo esc_html($order->customer_email); ?></p>
            <p><strong>Adresse:</strong> <?php echo esc_html($address); ?></p>
            <p><strong>Preis pro Monat:</strong> <?php echo esc_html(number_format((float) $order->final_price, 2, ',', '.')); ?>€</p>
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
            <p><strong>Mietbeginn:</strong> <?php echo esc_html($start_formatted); ?></p>
        </div>
    <?php endif; ?>
</div>
