<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';
require_once PRODUKT_PLUGIN_PATH . 'includes/Database.php';

$search      = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$customer_id = isset($_GET['customer']) ? intval($_GET['customer']) : 0;

if (!$customer_id) {
    // Stats
    $total_customers = count(get_users(['role' => 'kunde']));
    $total_orders    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_orders");
    $total_revenue   = (float) $wpdb->get_var("SELECT SUM(final_price) FROM {$wpdb->prefix}produkt_orders");
    $avg_per_customer = $total_customers ? $total_revenue / $total_customers : 0;

    // Customer list
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
        $kunden[] = (object) [
            'id'      => $u->ID,
            'first'   => $first,
            'last'    => $last,
            'name'    => $name,
            'email'   => $u->user_email,
            'telefon' => $phone,
            'orders'  => $orders,
        ];
    }
}
?>
<div class="produkt-admin dashboard-wrapper" id="produkt-admin-customers">
<?php if (!$customer_id): ?>
    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Kunden verwalten</p>

    <div class="product-info-grid cols-4">
        <div class="product-info-box bg-pastell-gelb">
            <div class="label">Kunden</div>
            <div class="value"><?php echo esc_html($total_customers); ?></div>
        </div>
        <div class="product-info-box bg-pastell-gruen">
            <div class="label">Bestellungen</div>
            <div class="value"><?php echo esc_html($total_orders); ?></div>
        </div>
        <div class="product-info-box bg-pastell-mint">
            <div class="label">Umsatz</div>
            <div class="value"><?php echo number_format($total_revenue, 2, ',', '.'); ?>€</div>
        </div>
        <div class="product-info-box bg-pastell-orange">
            <div class="label">Ø pro Kunde</div>
            <div class="value"><?php echo number_format($avg_per_customer, 2, ',', '.'); ?>€</div>
        </div>
    </div>

    <div class="h2-rental-card">
        <div class="card-header-flex">
            <div>
                <h2>Kundenübersicht</h2>
                <p class="card-subline">Kunden durchsuchen</p>
            </div>
            <form method="get" class="produkt-filter-form product-search-bar">
                <input type="hidden" name="page" value="produkt-customers">
                <div class="search-input-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="search-icon">
                        <path d="M10 2a8 8 0 105.3 14.1l4.3 4.3a1 1 0 101.4-1.4l-4.3-4.3A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/>
                    </svg>
                    <input type="text" name="s" placeholder="Nach Namen suchen" value="<?php echo esc_attr($search); ?>">
                </div>
                <button type="submit" class="icon-btn filter-submit-btn" aria-label="Filtern">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 22.1">
                        <path d="M16,0C7.2,0,0,4.9,0,11s7.2,11,16,11,16-4.9,16-11S24.8,0,16,0ZM16,20c-7.7,0-14-4-14-9S8.3,2,16,2s14,4,14,9-6.3,9-14,9ZM16,5c-3.3,0-6,2.7-6,6s2.7,6,6,6,6-2.7,6-6-2.7-6-6-6ZM16,15c-2.2,0-4-1.8-4-4s1.8-4,4-4,4,1.8,4,4-1.8,4-4,4Z"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <?php if (empty($kunden)) : ?>
        <p>Keine Kunden gefunden.</p>
    <?php else : ?>
        <div class="customers-grid">
            <?php foreach ($kunden as $kunde) :
                $initials = strtoupper(mb_substr($kunde->first, 0, 1) . mb_substr($kunde->last, 0, 1));
                $last_order_date = !empty($kunde->orders) ? date_i18n('d.m.Y', strtotime($kunde->orders[0]->created_at)) : '–';
            ?>
                <div class="customer-card">
                    <div class="customer-header">
                        <div class="customer-avatar"><?php echo esc_html($initials); ?></div>
                        <div class="customer-ident">
                            <h3 class="customer-name"><?php echo esc_html($kunde->name); ?></h3>
                            <p class="customer-email"><?php echo esc_html($kunde->email); ?></p>
                        </div>
                    </div>
                    <p class="customer-phone">Telefon: <?php echo esc_html($kunde->telefon ?: '–'); ?></p>
                    <div class="customer-orders-row">
                        <span class="customer-orders">Bestellungen: <?php echo esc_html(count($kunde->orders)); ?></span>
                        <span class="customer-last-order">Letzte Bestellung: <?php echo esc_html($last_order_date); ?></span>
                    </div>
                    <div class="customer-actions">
                        <a href="<?php echo admin_url('admin.php?page=produkt-customers&customer=' . $kunde->id); ?>" class="icon-btn icon-btn-no-stroke" aria-label="Details">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 22.1">
                                <path d="M16,0C7.2,0,0,4.9,0,11s7.2,11,16,11,16-4.9,16-11S24.8,0,16,0ZM16,20c-7.7,0-14-4-14-9S8.3,2,16,2s14,4,14,9-6.3,9-14,9ZM16,5c-3.3,0-6,2.7-6,6s2.7,6,6,6,6-2.7,6-6-2.7-6-6-6ZM16,15c-2.2,0-4-1.8-4-4s1.8-4,4-4,4,1.8,4,4-1.8,4-4,4Z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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

    $total_spent = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(final_price) FROM {$wpdb->prefix}produkt_orders WHERE customer_email = %s AND status = 'abgeschlossen'",
        $user->user_email
    ));
    $completed_orders = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_orders WHERE customer_email = %s AND status = 'abgeschlossen'",
        $user->user_email
    ));
    $canceled_orders = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_orders WHERE customer_email = %s AND status = 'gekündigt'",
        $user->user_email
    ));
    $year_start = date('Y-01-01 00:00:00');
    $year_end   = date('Y-12-31 23:59:59');
    $year_orders = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_orders WHERE customer_email = %s AND status = 'abgeschlossen' AND created_at BETWEEN %s AND %s",
        $user->user_email, $year_start, $year_end
    ));
    if ($year_orders <= 5) {
        $activity_score = 'Gering';
    } elseif ($year_orders <= 12) {
        $activity_score = 'Mittel';
    } else {
        $activity_score = 'Hoch';
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

    $last_order = $orders[0] ?? null;
    $order_ids = wp_list_pluck($orders, 'id');
    if ($order_ids) {
        $placeholders = implode(',', array_fill(0, count($order_ids), '%d'));
        $sql = "SELECT id, event, message, created_at FROM {$wpdb->prefix}produkt_order_logs WHERE order_id IN ($placeholders) ORDER BY created_at DESC LIMIT 5";
        $customer_logs = $wpdb->get_results($wpdb->prepare($sql, $order_ids));
        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_order_logs WHERE order_id IN ($placeholders)";
        $total_logs = (int) $wpdb->get_var($wpdb->prepare($count_sql, $order_ids));
    } else {
        $customer_logs = [];
        $total_logs = 0;
    }

    $customer_notes = $wpdb->get_results($wpdb->prepare(
        "SELECT id, message, created_at FROM {$wpdb->prefix}produkt_customer_notes WHERE customer_id = %d ORDER BY created_at DESC",
        $user->ID
    ));

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
    $initials = strtoupper(mb_substr($first,0,1) . mb_substr($last,0,1));
?>
    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Kundendetails</p>

    <div class="product-info-grid cols-4">
        <div class="product-info-box bg-pastell-gelb">
            <div class="label">Ausgaben</div>
            <div class="value"><?php echo number_format($total_spent, 2, ',', '.'); ?>€</div>
        </div>
        <div class="product-info-box bg-pastell-gruen">
            <div class="label">Abgeschlossen</div>
            <div class="value"><?php echo esc_html($completed_orders); ?></div>
        </div>
        <div class="product-info-box bg-pastell-mint">
            <div class="label">Abgebrochen</div>
            <div class="value"><?php echo esc_html($canceled_orders); ?></div>
        </div>
        <div class="product-info-box bg-pastell-orange">
            <div class="label">Activity Score</div>
            <div class="value"><?php echo esc_html($activity_score); ?></div>
        </div>
    </div>

    <div class="customer-detail-grid">
        <div class="customer-left">
            <div class="dashboard-card customer-card">
                <div class="customer-header">
                    <div class="customer-avatar"><?php echo esc_html($initials); ?></div>
                    <div class="customer-ident">
                        <h3 class="customer-name"><?php echo esc_html(trim($first . ' ' . $last)); ?></h3>
                        <p class="customer-email"><?php echo esc_html($user->user_email); ?></p>
                    </div>
                </div>
                <p class="customer-phone">Telefon: <?php echo esc_html($phone ?: '–'); ?></p>
                <p class="customer-address">Versandadresse: <?php echo esc_html($addr ?: '–'); ?></p>
                <p class="customer-address">Rechnungsadresse: <?php echo esc_html($addr ?: '–'); ?></p>
            </div>

            <div class="customer-row">
                <div class="dashboard-card customer-tech-card">
                    <h2>Technische Daten</h2>
                    <p class="card-subline">Technische Informationen zum Nutzer</p>
                    <p><strong>User Agent:</strong> <?php echo esc_html($last_order->user_agent ?? '–'); ?></p>
                    <p><strong>IP-Adresse:</strong> <?php echo esc_html($last_order->user_ip ?? '–'); ?></p>
                </div>
                <div class="dashboard-card">
                    <h2>Verlauf</h2>
                    <p class="card-subline">Benutzerverlauf im Detail</p>
                    <?php if ($customer_logs) : ?>
                        <ul class="order-log-list">
                            <?php foreach ($customer_logs as $log) : ?>
                                <li><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($log->created_at)) . ': ' . $log->id . ' / ' . $log->event . ($log->message ? ': ' . $log->message : '')); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($total_logs > 5) : ?>
                            <button type="button" class="icon-btn icon-btn-no-stroke customer-log-load-more" title="Mehr anzeigen" data-offset="5" data-total="<?php echo intval($total_logs); ?>" data-order-ids="<?php echo esc_attr(implode(',', $order_ids)); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 5c-.6 0-1 .4-1 1v5H6c-.6 0-1 .4-1 1s.4 1 1 1h5v5c0 .6.4 1 1 1s1-.4 1-1v-5h5c.6 0 1-.4 1-1s-.4-1-1-1h-5V6c0-.6-.4-1-1-1z"/></svg>
                            </button>
                        <?php endif; ?>
                    <?php else : ?>
                        <p>Keine Einträge</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Notizen</h2>
                        <p class="card-subline">Anmerkungen zum Kunden</p>
                    </div>
                    <button type="button" class="icon-btn icon-btn-no-stroke customer-note-icon" title="Notiz hinzufügen">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 5c-.6 0-1 .4-1 1v5H6c-.6 0-1 .4-1 1s.4 1 1 1h5v5c0 .6.4 1 1 1s1-.4 1-1v-5h5c.6 0 1-.4 1-1s-.4-1-1-1h-5V6c0-.6-.4-1-1-1z"/></svg>
                    </button>
                </div>
                <div class="customer-notes-section">
                    <?php foreach ($customer_notes as $note) : ?>
                        <div class="order-note" data-note-id="<?php echo intval($note->id); ?>">
                            <div class="note-text"><?php echo esc_html($note->message); ?></div>
                            <div class="note-date"><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($note->created_at))); ?></div>
                            <button type="button" class="icon-btn customer-note-delete-btn" title="Notiz löschen"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg></button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="customer-note-form" class="order-note-form" data-customer-id="<?php echo $user->ID; ?>">
                    <textarea placeholder="Notiz"></textarea>
                    <div class="note-actions">
                        <button type="button" class="button button-primary customer-note-save">Speichern</button>
                        <button type="button" class="button customer-note-cancel">Abbrechen</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="customer-right">
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Bestellübersicht</h2>
                        <p class="card-subline">Letzte Bestellungen</p>
                    </div>
                </div>
                <?php if ($orders) : ?>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produkte</th>
                            <th>Ausführungen</th>
                            <th>Extras</th>
                            <th>Mietzeitraum</th>
                            <th>Gesamtpreis</th>
                            <th>Versand</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o) : ?>
                            <tr>
                                <td>#<?php echo esc_html($o->order_number ?: $o->id); ?></td>
                                <td><?php echo esc_html($o->category_name); ?></td>
                                <td><?php echo esc_html($o->variant_name ?: '–'); ?></td>
                                <td><?php echo esc_html($o->extra_names ?: '–'); ?></td>
                                <td><?php echo esc_html(date_i18n('d.m.Y', strtotime($o->start_date)) . ' - ' . date_i18n('d.m.Y', strtotime($o->end_date))); ?></td>
                                <td><?php echo number_format($o->final_price, 2, ',', '.'); ?>€</td>
                                <td><?php echo esc_html($o->shipping_name ?: '–'); ?><?php if ($o->shipping_cost > 0) : ?> (<?php echo number_format($o->shipping_cost, 2, ',', '.'); ?>€)<?php endif; ?></td>
                                <td class="details-cell">
                                    <button type="button" class="icon-btn icon-btn-no-stroke view-details-link" data-order-id="<?php echo esc_attr($o->id); ?>" aria-label="Details">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 22.1">
                                            <path d="M16,0C7.2,0,0,4.9,0,11s7.2,11,16,11,16-4.9,16-11S24.8,0,16,0ZM16,20c-7.7,0-14-4-14-9S8.3,2,16,2s14,4,14,9-6.3,9-14,9ZM16,5c-3.3,0-6,2.7-6,6s2.7,6,6,6,6-2.7,6-6-2.7-6-6-6ZM16,15c-2.2,0-4-1.8-4-4s1.8-4,4-4,4,1.8,4,4-1.8,4-4,4Z"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                    <p>Keine Bestellungen gefunden.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Sidebar-Overlay für Bestelldetails -->
<div id="order-details-sidebar" class="order-details-sidebar">
    <div class="order-details-header">
        <h3>Bestelldetails</h3>
        <button class="close-sidebar" aria-label="Schließen">&times;</button>
    </div>
    <div class="order-details-content">
        <p>Lade Details…</p>
    </div>
</div>
<div id="order-details-overlay" class="order-details-overlay"></div>
</div>
