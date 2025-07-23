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
        $table = $wpdb->prefix . 'produkt_customers';
        $sql = "SELECT * FROM $table";
        if ($search) {
            $sql .= $wpdb->prepare(" WHERE name LIKE %s OR email LIKE %s", "%$search%", "%$search%");
        }
        $sql .= " ORDER BY name ASC";
        $customers = $wpdb->get_results($sql);
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
            <?php foreach ($customers as $c): ?>
            <tr>
                <td><?php echo esc_html($c->name); ?></td>
                <td><?php echo esc_html($c->email); ?></td>
                <td><?php echo esc_html($c->phone ?: '–'); ?></td>
                <td><a href="<?php echo admin_url('admin.php?page=produkt-customers&customer=' . $c->id); ?>" class="button">Details</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php else: ?>
    <?php
        $table = $wpdb->prefix . 'produkt_customers';
        $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $customer_id));
        if (!$customer) {
            echo '<p>Kunde nicht gefunden.</p></div>';
            return;
        }
        $parts = explode(' ', $customer->name, 2);
        $first = $parts[0] ?? '';
        $last  = $parts[1] ?? '';
        $phone = $customer->phone;
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}produkt_orders WHERE customer_email = %s ORDER BY created_at DESC",
            $customer->email
        ));
    ?>
    <p><a href="<?php echo admin_url('admin.php?page=produkt-customers'); ?>" class="button">&larr; Zurück</a></p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div>
            <h2>Kunden Details</h2>
            <p><strong>Vorname:</strong> <?php echo esc_html($first); ?></p>
            <p><strong>Nachname:</strong> <?php echo esc_html($last); ?></p>
            <p><strong>E-Mail:</strong> <?php echo esc_html($customer->email); ?></p>
            <p><strong>Telefon:</strong> <?php echo esc_html($phone ?: '–'); ?></p>

            <h2>Bestellungen</h2>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Produkttyp</th>
                        <th>Preis Gesamt</th>
                        <th>Status</th>
                        <th>Datum</th>
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
                <p><strong>Preis:</strong> <?php echo number_format((float)$o->final_price, 2, ',', '.'); ?>€</p>
                <h4>Versandadresse</h4>
                <p><?php echo esc_html(trim($o->customer_street . ', ' . $o->customer_postal . ' ' . $o->customer_city)); ?></p>
                <h4>Rechnungsadresse</h4>
                <p><?php echo esc_html(trim($o->customer_street . ', ' . $o->customer_postal . ' ' . $o->customer_city)); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
