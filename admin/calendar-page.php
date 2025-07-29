<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

$monthNames = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
$dayNames   = ['Mo','Di','Mi','Do','Fr','Sa','So'];

// Filter parameters
$product_filter = isset($_GET['product']) ? intval($_GET['product']) : 0;
$status_filter  = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$produkte       = \ProduktVerleih\Database::get_all_categories(true);

$year  = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
if ($month < 1) { $month = 1; } elseif ($month > 12) { $month = 12; }

$prev_month = $month - 1;
$prev_year  = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1;
$next_year  = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

$first_day_ts = strtotime(sprintf('%04d-%02d-01', $year, $month));
$last_day      = intval(date('t', $first_day_ts));
$start_index   = (int)date('N', $first_day_ts) - 1; // 0=Mo

// collect booking days and orders per day
$booked        = [];
$orders_by_day = [];
$blocked_days  = $wpdb->get_col("SELECT day FROM {$wpdb->prefix}produkt_blocked_days");

$where = ["o.mode = 'kauf'"];
if ($product_filter) {
    $where[] = $wpdb->prepare('o.category_id = %d', $product_filter);
}

$sql = "SELECT o.*, c.name as category_name,
               COALESCE(v.name, o.produkt_name) as variant_name,
               COALESCE(NULLIF(GROUP_CONCAT(e.name SEPARATOR ', '), ''), o.extra_text) AS extra_names,
               COALESCE(d.name, o.dauer_text) as duration_name,
               COALESCE(cond.name, o.zustand_text) as condition_name,
               COALESCE(pc.name, o.produktfarbe_text) as product_color_name,
               COALESCE(fc.name, o.gestellfarbe_text) as frame_color_name
        FROM {$wpdb->prefix}produkt_orders o
        LEFT JOIN {$wpdb->prefix}produkt_categories c ON o.category_id = c.id
        LEFT JOIN {$wpdb->prefix}produkt_variants v ON o.variant_id = v.id
        LEFT JOIN {$wpdb->prefix}produkt_extras e ON FIND_IN_SET(e.id, o.extra_ids)
        LEFT JOIN {$wpdb->prefix}produkt_durations d ON o.duration_id = d.id
        LEFT JOIN {$wpdb->prefix}produkt_conditions cond ON o.condition_id = cond.id
        LEFT JOIN {$wpdb->prefix}produkt_colors pc ON o.product_color_id = pc.id
        LEFT JOIN {$wpdb->prefix}produkt_colors fc ON o.frame_color_id = fc.id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY o.id";

$orders = $wpdb->get_results($sql);

foreach ($orders as $o) {
    $o->rental_days = pv_get_order_rental_days($o);
}

$order_logs = [];
foreach ($orders as $o) {
    $order_logs[$o->id] = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event, message, created_at FROM {$wpdb->prefix}produkt_order_logs WHERE order_id = %d ORDER BY created_at",
            $o->id
        )
    );
}
foreach ($orders as $o) {
    list($s, $e) = pv_get_order_period($o);
    $start = $s ? strtotime($s) : null;
    $end   = $e ? strtotime($e) : null;
    if ($start && $end) {
        while ($start <= $end) {
            $d = date('Y-m-d', $start);
            $status = ($o->status === 'abgeschlossen') ? 'completed' : 'open';
            if (!isset($booked[$d]) || $booked[$d] !== 'open') {
                $booked[$d] = $status;
            }
            if ($status === 'open') {
                $booked[$d] = 'open';
            }
            $orders_by_day[$d][] = $o;
            $start = strtotime('+1 day', $start);
        }
    }
}
?>

<div class="wrap" id="produkt-admin-calendar">
    <div class="produkt-admin-card">
        <div class="produkt-admin-header-compact">
            <div class="produkt-admin-logo-compact">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="produkt-admin-title-compact">
                <h1>Kalender-Übersicht</h1>
                <p>Alle Buchungen und Rückgaben im Überblick</p>
            </div>
        </div>

        <div class="produkt-category-selector">
            <form method="get">
                <input type="hidden" name="page" value="produkt-calendar">
                <label for="filter-product">Produkt:</label>
                <select id="filter-product" name="product">
                    <option value="">Alle Produkte</option>
                    <?php foreach ($produkte as $produkt) : ?>
                        <option value="<?php echo esc_attr($produkt->id); ?>" <?php selected($product_filter, $produkt->id); ?>><?php echo esc_html($produkt->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="filter-status">Status:</label>
                <select id="filter-status" name="status">
                    <option value="">Alle</option>
                    <option value="open" <?php selected($status_filter, 'open'); ?>>Ausgeliehen</option>
                    <option value="return" <?php selected($status_filter, 'return'); ?>>Rückgabe</option>
                </select>
                <button class="button button-primary" type="submit">Filtern</button>
            </form>
            <div class="produkt-category-info">
                <code><strong>Legende:</strong> <span class="badge badge-success">Ausgeliehen</span> <span class="badge badge-danger">Rückgabe fällig</span></code>
            </div>
        </div>

        <div class="calendar-nav" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <a class="button button-primary" href="<?php echo admin_url('admin.php?page=produkt-calendar&month=' . $prev_month . '&year=' . $prev_year); ?>">&larr;</a>
            <h2 style="margin:0"><?php echo $monthNames[$month-1] . ' ' . $year; ?></h2>
            <a class="button button-primary" href="<?php echo admin_url('admin.php?page=produkt-calendar&month=' . $next_month . '&year=' . $next_year); ?>">&rarr;</a>
        </div>

<div id="day-action-modal" class="modal-overlay">
    <div class="modal-content" style="text-align:center;">
        <button type="button" class="modal-close" onclick="closeDayAction()">&times;</button>
        <h3 style="margin-top:0;">Aktion f&uuml;r <span id="action-modal-date"></span></h3>
        <div style="margin-top:15px;">
            <button type="button" id="block-day-btn" class="button button-primary"></button>
            <button type="button" id="view-orders-btn" class="button">Buchungen ansehen</button>
        </div>
    </div>
</div>


    <div class="calendar-grid">
        <?php foreach ($dayNames as $dn): ?>
            <div class="calendar-day-name"><?php echo esc_html($dn); ?></div>
        <?php endforeach; ?>
        <?php for ($i = 0; $i < $start_index; $i++): ?>
            <div class="calendar-day empty"></div>
        <?php endfor; ?>
        <?php for ($d = 1; $d <= $last_day; $d++):
            $date    = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $classes = 'calendar-day';
            $badges  = '';
            if ($date === current_time('Y-m-d')) {
                $classes .= ' today';
            }
            if (in_array($date, $blocked_days, true)) {
                $classes .= ' day-blocked';
            }
            if (isset($orders_by_day[$date])) {
                foreach ($orders_by_day[$date] as $o) {
                    if (($status_filter === '' || $status_filter === 'open') && $o->start_date === $date) {
                        $classes .= ' booked';
                        $badges .= '<div class="event-badge badge badge-success">#' . esc_html($o->id) . '</div>';
                    }
                    if (($status_filter === '' || $status_filter === 'return') && $o->end_date === $date) {
                        $classes .= ' return';
                        $badges .= '<div class="event-badge badge badge-danger">#' . esc_html($o->id) . '</div>';
                    }
                }
            }
        ?>
            <div class="<?php echo esc_attr($classes); ?>" data-date="<?php echo esc_attr($date); ?>">
                <div class="day-number"><?php echo $d; ?></div>
                <?php echo $badges; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>


<div id="order-details-modal" class="modal-overlay">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeOrderDetails()">&times;</button>
        <h3 style="margin-top:0;">Bestellungen am <span id="modal-date"></span></h3>
        <div id="order-details-content"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const ordersByDay   = <?php echo json_encode($orders_by_day); ?>;
    const orderLogs     = <?php echo json_encode($order_logs); ?>;
    const blockedDays   = <?php echo json_encode(array_fill_keys($blocked_days, true)); ?>;
    const ajaxUrl       = ajaxurl;
    const nonce         = '<?php echo wp_create_nonce('produkt_nonce'); ?>';

    const modal         = document.getElementById('order-details-modal');
    const modalDate     = document.getElementById('modal-date');
    const content       = document.getElementById('order-details-content');

    const actionModal   = document.getElementById('day-action-modal');
    const actionDate    = document.getElementById('action-modal-date');
    const blockBtn      = document.getElementById('block-day-btn');
    const viewBtn       = document.getElementById('view-orders-btn');

    document.querySelectorAll('#produkt-admin-calendar .calendar-day').forEach(function(day){
        day.addEventListener('click', function(){
            const date = this.dataset.date;
            if (!date) return;
            actionModal.dataset.date = date;
            actionDate.textContent = date;
            const blocked = !!blockedDays[date];
            blockBtn.textContent = blocked ? 'Tag freigeben' : 'Tag sperren';
            blockBtn.dataset.action = blocked ? 'unblock' : 'block';
            viewBtn.style.display = ordersByDay[date] ? 'inline-block' : 'none';
            actionModal.style.display = 'block';
        });
    });

    blockBtn.addEventListener('click', function(){
        const action = this.dataset.action;
        const date = actionModal.dataset.date;
        const data = new URLSearchParams();
        data.append('action', action === 'block' ? 'produkt_block_day' : 'produkt_unblock_day');
        data.append('nonce', nonce);
        data.append('date', date);
        fetch(ajaxUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:data.toString()})
            .then(r => r.json())
            .then(res => { if(res.success){ location.reload(); } else { alert(res.data || 'Fehler'); } });
    });

    viewBtn.addEventListener('click', function(){
        const date = actionModal.dataset.date;
        actionModal.style.display = 'none';
        if (!ordersByDay[date]) return;
        modalDate.textContent = date;
        let html = '';
        ordersByDay[date].forEach(function(o){
            html += buildOrderDetails(o, orderLogs[o.id] || []);
        });
        content.innerHTML = html;
        modal.style.display = 'block';
    });

    [modal, actionModal].forEach(function(m){
        m.addEventListener('click', function(e){ if(e.target === m) m.style.display = 'none'; });
    });
});

function buildOrderDetails(order, logs) {
    let detailsHtml = `
        <div style="margin-bottom:20px;border-bottom:1px solid #ddd;padding-bottom:15px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h4>📋 Bestellinformationen</h4>
                    <p><strong>Bestellnummer:</strong> #${order.id}</p>
                    <p><strong>Datum:</strong> ${new Date(order.created_at).toLocaleString('de-DE')}</p>
                    <p><strong>Preis:</strong> ${parseFloat(order.final_price).toFixed(2).replace('.', ',')}€${order.mode === 'kauf' ? '' : '/Monat'}</p>
                    ${(order.shipping_name || order.shipping_cost > 0) ? `<p><strong>Versand:</strong> ${order.shipping_name ? order.shipping_name : 'Versand'}${order.shipping_cost > 0 ? ' - ' + parseFloat(order.shipping_cost).toFixed(2).replace('.', ',') + '€' : ''}</p>` : ''}
                    <p><strong>Rabatt:</strong> ${order.discount_amount > 0 ? '-' + parseFloat(order.discount_amount).toFixed(2).replace('.', ',') + '€' : '–'}</p>
                </div>
                <div>
                    <h4>👤 Kundendaten</h4>
                    <p><strong>Name:</strong> ${order.customer_name || 'Nicht angegeben'}</p>
                    <p><strong>E-Mail:</strong> ${order.customer_email || 'Nicht angegeben'}</p>
                    <p><strong>Telefon:</strong> ${order.customer_phone || 'Nicht angegeben'}</p>
                    <p><strong>Adresse:</strong> ${order.customer_street ? order.customer_street + ', ' + order.customer_postal + ' ' + order.customer_city + ', ' + order.customer_country : 'Nicht angegeben'}</p>
                    <p><strong>IP-Adresse:</strong> ${order.user_ip}</p>
                </div>
            </div>

            <h4>🛍️ Produktauswahl</h4>
            <ul>
                <li><strong>Ausführung:</strong> ${order.variant_name}</li>
                <li><strong>Extra:</strong> ${order.extra_names}</li>
                <li><strong>${order.mode === 'kauf' ? 'Miettage' : 'Mietdauer'}:</strong> ${order.rental_days ? order.rental_days : order.duration_name}</li>
                ${order.start_date && order.end_date ? `<li><strong>Zeitraum:</strong> ${new Date(order.start_date).toLocaleDateString('de-DE')} - ${new Date(order.end_date).toLocaleDateString('de-DE')}</li>` : ''}
                ${order.condition_name ? `<li><strong>Zustand:</strong> ${order.condition_name}</li>` : ''}
                ${order.product_color_name ? `<li><strong>Produktfarbe:</strong> ${order.product_color_name}</li>` : ''}
                ${order.frame_color_name ? `<li><strong>Gestellfarbe:</strong> ${order.frame_color_name}</li>` : ''}
            </ul>

            <h4>🖥️ Technische Daten</h4>
            <p><strong>User Agent:</strong> ${order.user_agent}</p>
    `;

    if (logs.length) {
        detailsHtml += '<h4>📑 Verlauf</h4><ul>';
        logs.forEach(function(l){
            const date = new Date(l.created_at).toLocaleString('de-DE');
            detailsHtml += `<li>[${date}] ${l.event}${l.message ? ' - ' + l.message : ''}</li>`;
        });
        detailsHtml += '</ul>';
    }

    detailsHtml += '</div>';
    return detailsHtml;
}

function closeOrderDetails(){
    document.getElementById('order-details-modal').style.display = 'none';
}

function closeDayAction(){
    document.getElementById('day-action-modal').style.display = 'none';
}
</script>
