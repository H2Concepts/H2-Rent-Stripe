<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$search      = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$customer_id = isset($_GET['customer']) ? intval($_GET['customer']) : 0;

$branding = [];
$results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}produkt_branding");
foreach ($results as $r) {
    $branding[$r->setting_key] = $r->setting_value;
}
?>
<div class="wrap">
    <div class="produkt-admin-header">
        <div class="produkt-admin-logo">👤</div>
        <div class="produkt-admin-title">
            <h1>Kunden</h1>
            <p>Verwaltung registrierter Kunden</p>
        </div>
    </div>

    <?php if (!$customer_id): ?>
    <form method="get" action="" style="margin:20px 0;">
        <input type="hidden" name="page" value="produkt-customers">
        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Nach Namen suchen">
        <input type="submit" class="button" value="Suchen">
    </form>

    <?php
        $args = [
            'role'    => 'kunde',
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ];
        if ($search) {
            $args['search']         = '*' . $search . '*';
            $args['search_columns'] = ['user_nicename', 'user_email', 'display_name'];
        }
        $users = get_users($args);
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>E-Mail</th>
                <th>Telefon</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <?php
                $first = get_user_meta($u->ID, 'first_name', true);
                $last  = get_user_meta($u->ID, 'last_name', true);
                $phone = get_user_meta($u->ID, 'phone', true);
                if (!$first && !$last) {
                    $order = $wpdb->get_row($wpdb->prepare(
                        "SELECT customer_name, customer_phone FROM {$wpdb->prefix}produkt_orders WHERE customer_email = %s ORDER BY created_at DESC LIMIT 1",
                        $u->user_email
                    ));
                    if ($order) {
                        $parts = explode(' ', $order->customer_name, 2);
                        $first = $first ?: ($parts[0] ?? '');
                        $last  = $last ?: ($parts[1] ?? '');
                        if (!$phone) {
                            $phone = $order->customer_phone;
                        }
                    }
                }
                $name  = trim($first . ' ' . $last);
                if (!$name) { $name = $u->display_name; }
            ?>
            <tr>
                <td><?php echo esc_html($name); ?></td>
                <td><?php echo esc_html($u->user_email); ?></td>
                <td><?php echo esc_html($phone ?: '–'); ?></td>
                <td><a href="<?php echo admin_url('admin.php?page=produkt-customers&customer=' . $u->ID); ?>" class="button">Details</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php else: ?>
    <?php
        $user = get_user_by('ID', $customer_id);
        if (!$user) {
            echo '<p>Kunde nicht gefunden.</p></div>';
            return;
        }
        $first = get_user_meta($user->ID, 'first_name', true);
        $last  = get_user_meta($user->ID, 'last_name', true);
        $phone = get_user_meta($user->ID, 'phone', true);
        if (!$first && !$last) {
            $latest = $wpdb->get_row($wpdb->prepare(
                "SELECT customer_name, customer_phone FROM {$wpdb->prefix}produkt_orders WHERE customer_email = %s ORDER BY created_at DESC LIMIT 1",
                $user->user_email
            ));
            if ($latest) {
                $parts = explode(' ', $latest->customer_name, 2);
                $first = $first ?: ($parts[0] ?? '');
                $last  = $last ?: ($parts[1] ?? '');
                if (!$phone) {
                    $phone = $latest->customer_phone;
                }
            }
        }
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}produkt_orders WHERE customer_email = %s ORDER BY created_at DESC",
            $user->user_email
        ));
    ?>
    <p><a href="<?php echo admin_url('admin.php?page=produkt-customers'); ?>" class="button">&larr; Zurück</a></p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div>
            <h2>Kunden Details</h2>
            <p><strong>Vorname:</strong> <?php echo esc_html($first); ?></p>
            <p><strong>Nachname:</strong> <?php echo esc_html($last); ?></p>
            <p><strong>E-Mail:</strong> <?php echo esc_html($user->user_email); ?></p>
            <p><strong>Telefon:</strong> <?php echo esc_html($phone ?: '–'); ?></p>
            <?php
                $addr_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT street, postal_code, city, country FROM {$wpdb->prefix}produkt_customers WHERE email = %s",
                    $user->user_email
                ));
                $addr = '';
                if ($addr_row) {
                    $addr = trim($addr_row->street . ', ' . $addr_row->postal_code . ' ' . $addr_row->city);
                    if ($addr_row->country) {
                        $addr .= ', ' . $addr_row->country;
                    }
                }
            ?>
            <h3>Versandadresse</h3>
            <p><?php echo esc_html($addr); ?></p>
            <h3>Rechnungsadresse</h3>
            <p><?php echo esc_html($addr); ?></p>

            <h2>Bestellungen</h2>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Produkttyp</th>
                        <th>Preis Gesamt</th>
                        <th>Status</th>
                        <th>Datum</th>
                        <th>Zeitraum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>#<?php echo $o->id; ?></td>
                        <td><?php echo esc_html($o->produkt_name); ?></td>
                        <td><?php echo number_format((float)$o->final_price, 2, ',', '.'); ?>€</td>
                        <td><?php echo esc_html($o->status); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($o->created_at)); ?></td>
                        <td>
                            <?php list($sd,$ed) = pv_get_order_period($o); ?>
                            <?php if ($sd && $ed): ?>
                                <?php echo date('d.m.Y', strtotime($sd)); ?> - <?php echo date('d.m.Y', strtotime($ed)); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div>
            <?php foreach ($orders as $o): ?>
            <div style="margin-bottom:20px;padding:10px;border:1px solid #ddd;background:#fff;">
                <h3>Gekauft - Bestellung #<?php echo $o->id; ?></h3>
                <p><strong>Produkt:</strong> <?php echo esc_html($o->produkt_name); ?></p>
                <?php if ($o->extra_text): ?>
                <p><strong>Extras:</strong> <?php echo esc_html($o->extra_text); ?></p>
                <?php endif; ?>
                <?php if ($o->dauer_text): ?>
                <p><strong>Dauer:</strong> <?php echo esc_html($o->dauer_text); ?></p>
                <?php endif; ?>
                <?php list($sd,$ed) = pv_get_order_period($o); ?>
                <?php if ($sd && $ed): ?>
                <p><strong>Zeitraum:</strong> <?php echo date('d.m.Y', strtotime($sd)); ?> - <?php echo date('d.m.Y', strtotime($ed)); ?></p>
                <?php endif; ?>
                <p><strong>Preis:</strong> <?php echo number_format((float)$o->final_price, 2, ',', '.'); ?>€</p>
                <?php
                    $addr_o = trim($o->customer_street . ', ' . $o->customer_postal . ' ' . $o->customer_city);
                    if (!empty($o->customer_country)) {
                        $addr_o .= ', ' . $o->customer_country;
                    }
                ?>
                <h4>Versandadresse</h4>
                <p><?php echo esc_html($addr_o); ?></p>
                <h4>Rechnungsadresse</h4>
                <p><?php echo esc_html($addr_o); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
