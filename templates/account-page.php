<?php
use ProduktVerleih\Database;

if (!defined('ABSPATH')) {
    exit;
}

$db = new Database();

?>
<div class="produkt-account-wrapper">
    <?php if (!is_user_logged_in()) : ?>
        <form method="post" class="produkt-account-email-form">
            <input type="email" name="email" placeholder="Ihre E-Mail" value="<?php echo esc_attr($email_value); ?>" required>
            <button type="submit" name="request_login_code">Login-Code anfordern</button>
        </form>
        <?php if ($show_code_form) : ?>
        <form method="post" class="produkt-account-code-form">
            <input type="hidden" name="email" value="<?php echo esc_attr($email_value); ?>">
            <input type="text" name="code" placeholder="6-stelliger Code" required>
            <button type="submit" name="verify_login_code">Einloggen</button>
        </form>
        <?php endif; ?>
    <?php else : ?>
        <?php if (!empty($subscriptions)) : ?>
            <h2>Ihre Abos</h2>
            <?php
            $orders = Database::get_orders_for_user(get_current_user_id());
            $order_map = [];
            foreach ($orders as $o) {
                $order_map[$o->subscription_id] = $o;
            }
            ?>
            <?php foreach ($subscriptions as $sub) : ?>
                <?php
                $order = $order_map[$sub['subscription_id']] ?? null;
                $product_name = $order->produkt_name ?? $sub['subscription_id'];
                $start_ts = strtotime($sub['start_date']);
                $start_formatted = date_i18n('d.m.Y', $start_ts);
                $laufzeit_in_monaten = 3;
                if ($order && !empty($order->duration_id)) {
                    global $wpdb;
                    $laufzeit_in_monaten = (int) $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT months_minimum FROM {$wpdb->prefix}produkt_durations WHERE id = %d",
                            $order->duration_id
                        )
                    );
                    if (!$laufzeit_in_monaten) {
                        $laufzeit_in_monaten = 3; // Fallback
                    }
                }
                $cancelable_ts            = strtotime("+{$laufzeit_in_monaten} months", $start_ts);
                $kuendigungsfenster_ts    = strtotime('-14 days', $cancelable_ts);
                $kuendigbar_ab_date       = date_i18n('d.m.Y', $kuendigungsfenster_ts);
                $cancelable               = time() >= $kuendigungsfenster_ts;
                $is_extended              = time() > $cancelable_ts;

                $period_end_ts   = null;
                $period_end_date = '';
                if (!empty($sub['current_period_end'])) {
                    $period_end_ts   = strtotime($sub['current_period_end']);
                    $period_end_date = date_i18n('d.m.Y', $period_end_ts);
                }

                $image_url = '';
                if ($order) {
                    global $wpdb;
                    if (!empty($order->variant_id)) {
                        $image_url = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT image_url_1 FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
                                $order->variant_id
                            )
                        );
                    }

                    if (empty($image_url) && !empty($order->category_id)) {
                        $image_url = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT default_image FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                                $order->category_id
                            )
                        );
                    }
                }

                $address = trim($order->customer_street . ', ' . $order->customer_postal . ' ' . $order->customer_city);
                ?>
                <div class="abo-wrapper">
                    <div class="abo-box">
                        <h3><?php echo esc_html($product_name); ?></h3>
                        <p><strong>Gemietet seit:</strong> <?php echo esc_html($start_formatted); ?></p>
                        <p><strong>Kündbar ab:</strong> <?php echo esc_html($kuendigbar_ab_date); ?></p>

                        <?php if ($sub['cancel_at_period_end']) : ?>
                            <p style="color:orange;"><strong>✅ Kündigung vorgemerkt zum <?php echo esc_html($period_end_date); ?>.</strong></p>
                        <?php elseif ($is_extended) : ?>
                            <p>📦 Abo läuft weiter. Nächster Abrechnungszeitraum bis: <?php echo esc_html($period_end_date); ?></p>
                            <form method="post">
                                <input type="hidden" name="cancel_subscription" value="<?php echo esc_attr($sub['subscription_id']); ?>">
                                <button type="submit" style="background:#dc3545;color:white;border:none;padding:10px 20px;border-radius:5px;">
                                    Zum nächsten Laufzeitende kündigen
                                </button>
                            </form>
                        <?php elseif ($cancelable) : ?>
                            <form method="post">
                                <input type="hidden" name="cancel_subscription" value="<?php echo esc_attr($sub['subscription_id']); ?>">
                                <p style="margin-bottom:8px;">Sie können jetzt kündigen – die Kündigung wird zum Ende der Mindestlaufzeit wirksam (<?php echo esc_html(date_i18n('d.m.Y', $cancelable_ts)); ?>).</p>
                                <button type="submit" style="background:#dc3545;color:white;border:none;padding:10px 20px;border-radius:5px;">
                                    Zum Laufzeitende kündigen
                                </button>
                            </form>
                        <?php else : ?>
                            <p style="color:#888;"><strong>⏳ Ihre Kündigung ist frühestens 14 Tage vor Ablauf der Mindestlaufzeit möglich (ab dem <?php echo esc_html($kuendigbar_ab_date); ?>).</strong></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($order) : ?>
                        <div class="order-box">
                            <?php if ($image_url) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="">
                            <?php endif; ?>
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
            <?php endforeach; ?>
        <?php else : ?>
            <p>Keine aktiven Abos.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
