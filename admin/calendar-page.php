<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$monthNames = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
$dayNames   = ['Mo','Di','Mi','Do','Fr','Sa','So'];

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
$orders = $wpdb->get_results(
    "SELECT o.*, c.name as category_name,
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
     WHERE o.mode = 'kauf'
     GROUP BY o.id"
);

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
    $start = null;
    $end   = null;
    if (!empty($o->start_date) && !empty($o->end_date)) {
        $start = strtotime($o->start_date);
        $end   = strtotime($o->end_date);
    } elseif (preg_match('/(\d{4}-\d{2}-\d{2})\s*-\s*(\d{4}-\d{2}-\d{2})/', $o->dauer_text, $m)) {
        $start = strtotime($m[1]);
        $end   = strtotime($m[2]);
    }
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
    <div class="produkt-admin-header">
        <div class="produkt-admin-logo">📅</div>
        <div class="produkt-admin-title">
            <h1>Kalender</h1>
            <p>Übersicht der Verkaufstage</p>
</div>
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

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <a class="button" href="<?php echo admin_url('admin.php?page=produkt-calendar&month=' . $prev_month . '&year=' . $prev_year); ?>">&laquo; <?php echo $monthNames[$prev_month-1]; ?></a>
        <h2 style="margin:0;"><?php echo $monthNames[$month-1] . ' ' . $year; ?></h2>
        <a class="button" href="<?php echo admin_url('admin.php?page=produkt-calendar&month=' . $next_month . '&year=' . $next_year); ?>"><?php echo $monthNames[$next_month-1]; ?> &raquo;</a>
    </div>

    <div class="calendar-grid">
        <?php foreach ($dayNames as $dn): ?>
            <div class="day-name"><?php echo esc_html($dn); ?></div>
        <?php endforeach; ?>
        <?php for ($i=0; $i<$start_index; $i++): ?>
            <div class="empty"></div>
        <?php endfor; ?>
        <?php for ($d=1; $d<=$last_day; $d++):
            $date  = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $cls   = '';
            $title = '';
            $count = 0;
            if (isset($orders_by_day[$date])) {
                $count = count($orders_by_day[$date]);
                if ($count === 1) {
                    $status = $orders_by_day[$date][0]->status === 'abgeschlossen' ? 'completed' : 'open';
                    $cls    = $status === 'open' ? 'booked-open' : 'booked-completed';
                } elseif ($count > 1) {
                    $cls   = 'booked-multiple';
                    $title = $count . ' Buchungen';
                }
            }
        ?>
            <?php $blocked = in_array($date, $blocked_days, true); ?>
            <div class="calendar-day <?php echo $cls . ($blocked ? ' day-blocked' : ''); ?>" data-date="<?php echo $date; ?>"<?php echo $title ? ' title="' . esc_attr($title) . '"' : ''; ?>>
                <?php echo $d; ?>
                <?php if ($blocked): ?>
                    <span class="blocked-marker">✖</span>
                <?php endif; ?>
                <?php if ($count > 1): ?>
                    <span class="booking-count"><?php echo $count; ?></span>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>

<style>
#produkt-admin-calendar .calendar-grid{
    display:grid;
    grid-template-columns:repeat(7,1fr);
    gap:6px;
    font-size:16px;
}
#produkt-admin-calendar .day-name,
#produkt-admin-calendar .calendar-day{
    text-align:center;
    padding:18px;
    border-radius:4px;
    min-height:60px;
    border:1px solid #ddd;
    position:relative;
    background-color:#fff;
}
#produkt-admin-calendar .booked-open{
    background:#fff3cd;
}
#produkt-admin-calendar .booked-completed{
    background:#d4edda;
}
#produkt-admin-calendar .booked-multiple{
    background:#f8d7da;
}
#produkt-admin-calendar .day-blocked{
    background:#eee;
    color:#999;
}
#produkt-admin-calendar .blocked-marker{
    position:absolute;
    top:2px;
    right:2px;
    font-size:12px;
    color:#dc3545;
}
#produkt-admin-calendar .booking-count{
    position:absolute;
    right:2px;
    bottom:2px;
    font-size:12px;
    background:#dc3545;
    color:#fff;
    border-radius:50%;
    padding:1px 4px;
    min-width:10px;
    line-height:1.2;
}
</style>

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
                    ${order.shipping_cost > 0 ? `<p><strong>Versand:</strong> ${parseFloat(order.shipping_cost).toFixed(2).replace('.', ',')}€ (einmalig)</p>` : ''}
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
                <li><strong>${order.mode === 'kauf' ? 'Miettage' : 'Mietdauer'}:</strong> ${order.duration_name}</li>
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
