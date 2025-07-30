<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';
require_once PRODUKT_PLUGIN_PATH . 'includes/Database.php';
$search      = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$customer_id = isset($_GET['customer']) ? intval($_GET['customer']) : 0;

$branding = [];
$results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}produkt_branding");
foreach ($results as $r) {
    $branding[$r->setting_key] = $r->setting_value;
}

if (!$customer_id) {
    $args = [
        'role'    => 'kunde',
        'orderby' => 'display_name',
        'order'   => 'ASC',
    ];
    if ($search) {
        $args['search']         = '*' . $search . '*';
        $args['search_columns'] = ['user_nicename', 'user_email', 'display_name'];
    }
    $users  = get_users($args);
    $kunden = [];
    foreach ($users as $u) {
        $first = get_user_meta($u->ID, 'first_name', true);
        $last  = get_user_meta($u->ID, 'last_name', true);
        $phone = get_user_meta($u->ID, 'phone', true);
        $name  = trim($first . ' ' . $last);
        if (!$name) {
            $name = $u->display_name;
        }
        $orders = \ProduktVerleih\Database::get_orders_for_user($u->ID);
        foreach ($orders as $o) {
            $o->rental_days = pv_get_order_rental_days($o);
        }
        $last_date = $orders ? date_i18n('d.m.Y', strtotime($orders[0]->created_at)) : '';
        $kunden[] = (object)[
            'id'             => $u->ID,
            'name'           => $name,
            'email'          => $u->user_email,
            'telefon'        => $phone,
            'orders'         => $orders,
            'last_order_date'=> $last_date,
        ];
    }
}
?>
<div class="wrap" id="produkt-admin-customers">
<?php if (!$customer_id): ?>
    <div class="produkt-admin-card">
        <div class="produkt-admin-header-compact">
            <div class="produkt-admin-logo-compact">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="produkt-admin-title-compact">
                <h1>Kundenübersicht</h1>
                <p>Alle registrierten Kunden im Überblick</p>
            </div>
        </div>

        <form method="get" action="" style="margin:20px 0;">
            <input type="hidden" name="page" value="produkt-customers">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Nach Namen suchen">
            <input type="submit" class="button" value="Suchen">
        </form>

        <?php if (empty($kunden)) : ?>
            <div class="produkt-empty-state">
                <span class="dashicons dashicons-info"></span>
                <h4>Keine Kunden gefunden</h4>
                <p>Es wurden bisher keine Kunden im System erfasst.</p>
            </div>
        <?php else : ?>
            <div class="produkt-items-grid">
                <?php foreach ($kunden as $kunde) : ?>
                    <div class="produkt-item-card">
                        <div class="produkt-item-content">
                            <h5><?php echo esc_html($kunde->name); ?></h5>
                            <p><span class="dashicons dashicons-email"></span> <?php echo esc_html($kunde->email); ?></p>
                            <p><span class="dashicons dashicons-phone"></span> <?php echo esc_html($kunde->telefon ?: '–'); ?></p>

                            <div class="produkt-item-meta">
                                <div class="produkt-status available">
                                    Letzte Bestellung: <br>
                                    <strong><?php echo esc_html($kunde->last_order_date ?: '–'); ?></strong>
                                </div>
                                <div class="produkt-status-badge badge badge-success">
                                    <?php echo esc_html(count($kunde->orders)); ?> Bestellungen
                                </div>
                            </div>

                        </div>
                        <div class="produkt-item-actions">
                            <a href="mailto:<?php echo esc_attr($kunde->email); ?>" class="button">E-Mail senden</a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=produkt-customers&customer=' . $kunde->id)); ?>" class="button">Details</a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=produkt-orders')); ?>" class="button button-primary">Alle Bestellungen</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
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
        $latest = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT customer_name, customer_phone FROM {$wpdb->prefix}produkt_orders WHERE customer_email = %s ORDER BY created_at DESC LIMIT 1",
                $user->user_email
            )
        );
        if ($latest) {
            $parts = explode(' ', $latest->customer_name, 2);
            $first = $first ?: ($parts[0] ?? '');
            $last  = $last ?: ($parts[1] ?? '');
            if (!$phone) {
                $phone = $latest->customer_phone;
            }
        }
    }
    $orders = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT o.*, c.name AS category_name,
                    COALESCE(v.name, o.produkt_name) AS variant_name,
                    COALESCE(NULLIF(GROUP_CONCAT(e.name SEPARATOR ', '), ''), o.extra_text) AS extra_names,
                    sm.name AS shipping_name,
                    sm.service_provider AS shipping_provider
             FROM {$wpdb->prefix}produkt_orders o
             LEFT JOIN {$wpdb->prefix}produkt_categories c ON o.category_id = c.id
             LEFT JOIN {$wpdb->prefix}produkt_variants v ON o.variant_id = v.id
             LEFT JOIN {$wpdb->prefix}produkt_extras e ON FIND_IN_SET(e.id, o.extra_ids)
             LEFT JOIN {$wpdb->prefix}produkt_shipping_methods sm
                ON sm.stripe_price_id = COALESCE(o.shipping_price_id, c.shipping_price_id)
             WHERE o.customer_email = %s
             GROUP BY o.id
             ORDER BY o.created_at DESC",
            $user->user_email
        )
    );
    foreach ($orders as $o) {
        $o->rental_days = pv_get_order_rental_days($o);
    }
?>
    <div class="produkt-admin-card">
      <div class="produkt-admin-header-compact">
        <div class="produkt-admin-logo-compact">
          <span class="dashicons dashicons-id"></span>
        </div>
        <div class="produkt-admin-title-compact">
          <h1>Kundendetails: <?php echo esc_html($first . ' ' . $last); ?></h1>
          <p><?php echo esc_html($user->user_email); ?></p>
        </div>
      </div>

      <p><a href="<?php echo admin_url('admin.php?page=produkt-customers'); ?>" class="button">&larr; Zur Kundenübersicht</a></p>

      <div class="produkt-customer-grid">
        <div class="produkt-customer-details">
          <h2>Kundendaten</h2>
          <p><strong>Vorname:</strong> <?php echo esc_html($first); ?></p>
          <p><strong>Nachname:</strong> <?php echo esc_html($last); ?></p>
          <p><strong>Telefon:</strong> <?php echo esc_html($phone ?: '–'); ?></p>
          <p><strong>E-Mail:</strong> <?php echo esc_html($user->user_email); ?></p>

          <?php
            $addr_row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT street, postal_code, city, country FROM {$wpdb->prefix}produkt_customers WHERE email = %s",
                    $user->user_email
                )
            );
            $addr = '';
            if ($addr_row) {
                $addr = trim($addr_row->street . ', ' . $addr_row->postal_code . ' ' . $addr_row->city);
                if ($addr_row->country) {
                    $addr .= ', ' . $addr_row->country;
                }
            }
          ?>
          <h3>Versandadresse</h3>
          <p><?php echo esc_html($addr ?: '–'); ?></p>

          <h3>Rechnungsadresse</h3>
          <p><?php echo esc_html($addr ?: '–'); ?></p>

          <h3>Statistik</h3>
          <p><strong>Bestellungen:</strong> <?php echo count($orders); ?></p>
        </div>

        <div class="produkt-customer-orders orders-accordion">
          <h2>Bestellübersicht</h2>
          <?php if (empty($orders)) : ?>
            <p>Keine Bestellungen gefunden.</p>
          <?php else : ?>
            <?php foreach ($orders as $idx => $o): ?>
              <?php
                $variant_id = $o->variant_id ?? 0;
                $image_url  = pv_get_image_url_by_variant_or_category($variant_id, $o->category_id ?? 0);
                $order      = $o;
              ?>
              <div class="produkt-accordion-item <?php echo $idx === 0 ? 'active' : ''; ?>">
                <button type="button" class="produkt-accordion-header">
                  Bestellung #<?php echo esc_html($o->order_number ?: $o->id); ?> – <?php echo esc_html(date_i18n('d.m.Y', strtotime($o->created_at))); ?>
                </button>
                <div class="produkt-accordion-content">
                  <?php include PRODUKT_PLUGIN_PATH . 'includes/render-order-details.php'; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php endif; ?>
</div>

