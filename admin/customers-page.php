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

    $total_spent = 0.0;
    $completed_orders = 0;
    $canceled_orders = 0;
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

    $order_search   = isset($_GET['order_s']) ? sanitize_text_field($_GET['order_s']) : '';
    $invoice_search = isset($_GET['invoice_s']) ? sanitize_text_field($_GET['invoice_s']) : '';

    $all_orders = $wpdb->get_results(
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

    // Prüfe vorhandene Rechnungsdateien und setze URL falls nötig
    $upload_dir = wp_upload_dir();
    foreach ($all_orders as &$o) {
        if (empty($o->invoice_url)) {
            $filename    = 'rechnung-' . $o->id . '.pdf';
            $new_path    = trailingslashit($upload_dir['basedir']) . 'rechnungen-h2-rental-pro/' . $filename;
            $legacy_path = trailingslashit($upload_dir['basedir']) . $filename;
            if (file_exists($new_path)) {
                $o->invoice_url = trailingslashit($upload_dir['baseurl']) . 'rechnungen-h2-rental-pro/' . $filename;
                $wpdb->update(
                    "{$wpdb->prefix}produkt_orders",
                    ['invoice_url' => $o->invoice_url],
                    ['id' => $o->id],
                    ['%s'],
                    ['%d']
                );
            } elseif (file_exists($legacy_path)) {
                $o->invoice_url = trailingslashit($upload_dir['baseurl']) . $filename;
                $wpdb->update(
                    "{$wpdb->prefix}produkt_orders",
                    ['invoice_url' => $o->invoice_url],
                    ['id' => $o->id],
                    ['%s'],
                    ['%d']
                );
            }
        }
    }
    unset($o);

    $order_payment_map = [];
    $order_lookup      = [];
    foreach ($all_orders as $order_row) {
        $order_lookup[$order_row->id] = $order_row;
        $status = strtolower($order_row->status ?? '');
        if ($status === 'abgeschlossen') {
            $completed_orders++;
        } elseif ($status === 'gekündigt') {
            $canceled_orders++;
        }

        if ($order_row->mode !== 'kauf') {
            $order_payment_map[$order_row->id] = pv_calculate_rental_payments($order_row);
        }

        if (in_array($status, ['abgeschlossen', 'gekündigt'], true)) {
            if ($order_row->mode === 'kauf') {
                $total_spent += (float) $order_row->final_price + (float) $order_row->shipping_cost;
            } elseif (!empty($order_payment_map[$order_row->id]['total'])) {
                $total_spent += (float) $order_payment_map[$order_row->id]['total'];
            }
        }
    }

    $orders = $all_orders;
    if ($order_search) {
        $os = ltrim($order_search, '#');
        $orders = array_values(array_filter($all_orders, function ($o) use ($os) {
            return ($o->order_number === $os || (string) $o->id === $os);
        }));
    }

    $invoices = array_values(array_filter($all_orders, function ($o) {
        return !empty($o->invoice_url);
    }));
    if ($invoice_search) {
        $is = ltrim($invoice_search, '#');
        $invoices = array_values(array_filter($invoices, function ($o) use ($is) {
            return ($o->order_number === $is || (string) $o->id === $is);
        }));
    }

    $last_order = $all_orders[0] ?? null;
    $order_ids = wp_list_pluck($all_orders, 'id');
    $customer_logs      = [];
    $total_logs         = 0;
    $initial_log_count  = 0;
    if ($order_ids) {
        $placeholders = implode(',', array_fill(0, count($order_ids), '%d'));
        $logs_sql = "SELECT l.id, l.order_id, o.order_number, l.event, l.message, l.created_at FROM {$wpdb->prefix}produkt_order_logs l JOIN {$wpdb->prefix}produkt_orders o ON l.order_id = o.id WHERE l.order_id IN ($placeholders) ORDER BY l.created_at DESC";
        $customer_logs = $wpdb->get_results($wpdb->prepare($logs_sql, $order_ids));
        $total_logs = count($customer_logs);
    }

    $payment_logs = [];
    if (!empty($order_payment_map)) {
        foreach ($order_payment_map as $oid => $info) {
            if (empty($info['log_entries'])) {
                continue;
            }
            foreach ($info['log_entries'] as $entry) {
                if (empty($entry->order_number) && !empty($order_lookup[$oid]->order_number)) {
                    $entry->order_number = $order_lookup[$oid]->order_number;
                }
                $payment_logs[] = $entry;
            }
        }
    }

    if ($payment_logs) {
        $customer_logs = array_merge($customer_logs, $payment_logs);
    }

    if ($customer_logs) {
        usort($customer_logs, function ($a, $b) {
            $ta = isset($a->created_at) ? strtotime($a->created_at) : 0;
            $tb = isset($b->created_at) ? strtotime($b->created_at) : 0;
            return $tb <=> $ta;
        });
    }

    $total_logs += count($payment_logs);
    $initial_log_count = count($customer_logs);

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

    $registered_date     = date_i18n('d.m.Y', strtotime($user->user_registered));
    $last_activity_date  = $customer_logs ? date_i18n('d.m.Y', strtotime($customer_logs[0]->created_at)) : '–';
    $last_order_date_card = $last_order ? date_i18n('d.m.Y', strtotime($last_order->created_at)) : '–';
    $client_info = [];
    if ($last_order && !empty($last_order->client_info)) {
        $decoded = json_decode($last_order->client_info, true);
        if (is_array($decoded)) {
            $client_info = $decoded;
        }
    }
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
                <p class="customer-registered">Registriert: <?php echo esc_html($registered_date); ?></p>
                <p class="customer-last-activity">Letzte Aktivität: <?php echo esc_html($last_activity_date); ?></p>
                <p class="customer-last-order">Letzte Bestellung: <?php echo esc_html($last_order_date_card); ?></p>
            </div>

            <div class="customer-row">
                <div class="dashboard-card customer-tech-card">
                    <h2>Technische Daten</h2>
                    <p class="card-subline">Technische Informationen zum Nutzer</p>
                    <p><strong>User Agent:</strong> <?php echo esc_html($last_order->user_agent ?? '–'); ?></p>
                    <p><strong>IP-Adresse:</strong> <?php echo esc_html($last_order->user_ip ?? '–'); ?></p>
                    <?php if ($client_info) : ?>
                        <?php if (!empty($client_info['language'])) : ?><p><strong>Sprache:</strong> <?php echo esc_html($client_info['language']); ?></p><?php endif; ?>
                        <?php if (!empty($client_info['languages'])) : ?><p><strong>Weitere Sprachen:</strong> <?php echo esc_html($client_info['languages']); ?></p><?php endif; ?>
                        <?php if (!empty($client_info['timezone'])) : ?><p><strong>Zeitzone:</strong> <?php echo esc_html($client_info['timezone']); ?></p><?php endif; ?>
                        <?php if (!empty($client_info['screen'])) : ?><p><strong>Bildschirm:</strong> <?php echo esc_html($client_info['screen']); ?></p><?php endif; ?>
                        <?php if (!empty($client_info['viewport'])) : ?><p><strong>Viewport:</strong> <?php echo esc_html($client_info['viewport']); ?></p><?php endif; ?>
                        <?php if (!empty($client_info['colorDepth'])) : ?><p><strong>Farbtiefe:</strong> <?php echo esc_html($client_info['colorDepth']); ?></p><?php endif; ?>
                        <?php if (!empty($client_info['browser'])) : ?><p><strong>Browser:</strong> <?php echo esc_html($client_info['browser']); ?></p><?php endif; ?>
                        <?php if (!empty($client_info['os'])) : ?><p><strong>Betriebssystem:</strong> <?php echo esc_html($client_info['os']); ?></p><?php endif; ?>
                        <?php if (!empty($client_info['hardwareConcurrency'])) : ?><p><strong>CPU-Kerne:</strong> <?php echo esc_html($client_info['hardwareConcurrency']); ?></p><?php endif; ?>
                        <?php if (!empty($client_info['deviceMemory'])) : ?><p><strong>RAM:</strong> <?php echo esc_html($client_info['deviceMemory']); ?> GB</p><?php endif; ?>
                        <?php if (isset($client_info['touch'])) : ?><p><strong>Touchscreen:</strong> <?php echo $client_info['touch'] ? 'ja' : 'nein'; ?></p><?php endif; ?>
                        <?php if (!empty($client_info['connection'])) : ?><p><strong>Netzwerk:</strong> <?php echo esc_html($client_info['connection']); ?></p><?php endif; ?>
                        <?php if (!empty($client_info['battery'])) : ?><p><strong>Batterie:</strong> <?php echo esc_html($client_info['battery']); ?></p><?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="dashboard-card">
                    <h2>Verlauf</h2>
                    <p class="card-subline">Benutzerverlauf im Detail</p>
                    <?php if ($customer_logs) : ?>
                        <div class="order-log-list">
                            <?php
                            $system_events = ['inventory_returned_not_accepted','inventory_returned_accepted','welcome_email_sent','status_updated','checkout_completed','auto_rental_payment','tracking_updated','tracking_email_sent'];
                            foreach ($customer_logs as $log) :
                                $is_customer = !in_array($log->event, $system_events, true);
                                $avatar = $is_customer ? $initials : 'H2';
                                switch ($log->event) {
                                    case 'inventory_returned_not_accepted':
                                        $text = 'Miete zuende aber noch nicht akzeptiert.';
                                        break;
                                    case 'inventory_returned_accepted':
                                        $text = 'Rückgabe wurde akzeptiert.';
                                        break;
                                    case 'welcome_email_sent':
                                        $text = 'Bestellbestätigung an Kunden gesendet.';
                                        break;
                                    case 'status_updated':
                                        $text = ($log->message ? $log->message . ': ' : '') . 'Kauf abgeschlossen.';
                                        break;
                                    case 'checkout_completed':
                                        $text = 'Checkout abgeschlossen.';
                                        break;
                                    case 'auto_rental_payment':
                                        $text = $log->message ?: 'Monatszahlung verbucht.';
                                        break;
                                    case 'tracking_updated':
                                        $text = $log->message ?: 'Tracking aktualisiert.';
                                        break;
                                    case 'tracking_email_sent':
                                        $text = $log->message ?: 'Tracking an Kunden gesendet.';
                                        break;
                                    default:
                                        $text = $log->message ?: $log->event;
                                }
                                ?>
                                <div class="order-log-entry">
                                    <div class="log-avatar"><?php echo esc_html($avatar); ?></div>
                                    <div class="log-body">
                                        <?php $order_no = !empty($log->order_number) ? $log->order_number : $log->order_id; ?>
                                        <div class="log-date"><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($log->created_at)) . ' / #' . $order_no); ?></div>
                                        <div class="log-message"><?php echo esc_html($text); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($total_logs > $initial_log_count) : ?>
                            <button type="button" class="icon-btn icon-btn-no-stroke customer-log-load-more" title="Mehr anzeigen" data-offset="<?php echo intval($initial_log_count); ?>" data-total="<?php echo intval($total_logs); ?>" data-order-ids="<?php echo esc_attr(implode(',', $order_ids)); ?>" data-initials="<?php echo esc_attr($initials); ?>">
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
                        <h2>Rechnungen</h2>
                        <p class="card-subline">Alle Rechnungen im Überblick</p>
                    </div>
                    <form method="get" class="produkt-filter-form product-search-bar">
                        <input type="hidden" name="page" value="produkt-customers">
                        <input type="hidden" name="customer" value="<?php echo $user->ID; ?>">
                        <div class="search-input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="search-icon"><path d="M10 2a8 8 0 105.3 14.1l4.3 4.3a1 1 0 101.4-1.4l-4.3-4.3A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/></svg>
                            <input type="text" name="invoice_s" placeholder="Nach ID suchen" value="<?php echo esc_attr($invoice_search); ?>">
                        </div>
                        <button type="submit" class="icon-btn filter-submit-btn" aria-label="Filtern">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 22.1"><path d="M16,0C7.2,0,0,4.9,0,11s7.2,11,16,11,16-4.9,16-11S24.8,0,16,0ZM16,20c-7.7,0-14-4-14-9S8.3,2,16,2s14,4,14,9-6.3,9-14,9ZM16,5c-3.3,0-6,2.7-6,6s2.7,6,6,6,6-2.7,6-6-2.7-6-6-6ZM16,15c-2.2,0-4-1.8-4-4s1.8-4,4-4,4,1.8,4,4-1.8,4-4,4Z"/></svg>
                        </button>
                    </form>
                </div>
                <?php if ($invoices) : ?>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv) : ?>
                            <?php
                                $download_url = pv_get_invoice_download_url((int) ($inv->id ?? 0));
                                if (!$download_url && !empty($inv->invoice_url)) {
                                    $download_url = $inv->invoice_url;
                                }
                            ?>
                            <tr>
                                <td>#<?php echo esc_html($inv->order_number ?: $inv->id); ?></td>
                                <td class="details-cell">
                                    <a href="<?php echo esc_url($download_url); ?>" class="icon-btn icon-btn-no-stroke" target="_blank" download aria-label="Download">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 20h14v-2H5v2zm7-18v10l4-4 1.41 1.41L12 16.83 6.59 11.41 8 10l4 4V2h-2z"/></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                    <p>Keine Rechnungen gefunden.</p>
                <?php endif; ?>
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
                    <form method="get" class="produkt-filter-form product-search-bar">
                        <input type="hidden" name="page" value="produkt-customers">
                        <input type="hidden" name="customer" value="<?php echo $user->ID; ?>">
                        <div class="search-input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="search-icon"><path d="M10 2a8 8 0 105.3 14.1l4.3 4.3a1 1 0 101.4-1.4l-4.3-4.3A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/></svg>
                            <input type="text" name="order_s" placeholder="Nach ID suchen" value="<?php echo esc_attr($order_search); ?>">
                        </div>
                        <button type="submit" class="icon-btn filter-submit-btn" aria-label="Filtern">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 22.1"><path d="M16,0C7.2,0,0,4.9,0,11s7.2,11,16,11,16-4.9,16-11S24.8,0,16,0ZM16,20c-7.7,0-14-4-14-9S8.3,2,16,2s14,4,14,9-6.3,9-14,9ZM16,5c-3.3,0-6,2.7-6,6s2.7,6,6,6,6-2.7,6-6-2.7-6-6-6ZM16,15c-2.2,0-4-1.8-4-4s1.8-4,4-4,4,1.8,4,4-1.8,4-4,4Z"/></svg>
                        </button>
                    </form>
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
                                <?php
                                $start = $o->start_date ? date_i18n('d.m.Y', strtotime($o->start_date)) : '–';
                                $end   = $o->end_date ? date_i18n('d.m.Y', strtotime($o->end_date)) : '–';
                                ?>
                                <td><?php echo esc_html($start . ' - ' . $end); ?></td>
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
