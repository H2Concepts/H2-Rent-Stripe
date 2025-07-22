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
$orders = $wpdb->get_results("SELECT id, dauer_text, status, final_price, produkt_name, customer_name, customer_email, extra_text FROM {$wpdb->prefix}produkt_orders WHERE mode = 'kauf'");
foreach ($orders as $o) {
    if (preg_match('/(\d{4}-\d{2}-\d{2})\s*-\s*(\d{4}-\d{2}-\d{2})/', $o->dauer_text, $m)) {
        $start = strtotime($m[1]);
        $end   = strtotime($m[2]);
        while ($start <= $end) {
            $d = date('Y-m-d', $start);
            $status = ($o->status === 'abgeschlossen') ? 'completed' : 'open';
            if (!isset($booked[$d]) || $booked[$d] !== 'open') {
                $booked[$d] = $status;
            }
            if ($status === 'open') {
                $booked[$d] = 'open';
            }
            $orders_by_day[$d][] = [
                'id'       => $o->id,
                'produkt'  => $o->produkt_name,
                'customer' => $o->customer_name,
                'email'    => $o->customer_email,
                'price'    => $o->final_price,
                'status'   => $o->status,
                'extras'   => $o->extra_text,
            ];
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
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $cls = '';
            if (isset($booked[$date])) {
                $cls = $booked[$date] === 'completed' ? 'booked-completed' : 'booked-open';
            }
        ?>
            <div class="calendar-day <?php echo $cls; ?>" data-date="<?php echo $date; ?>"><?php echo $d; ?></div>
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
    padding:8px;
    border-radius:4px;
    min-height:40px;
    border:1px solid #ddd;
}
#produkt-admin-calendar .booked-open{
    background:#fff3cd;
}
#produkt-admin-calendar .booked-completed{
    background:#d4edda;
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
    const ordersByDay = <?php echo json_encode($orders_by_day); ?>;
    const modal = document.getElementById('order-details-modal');
    const modalDate = document.getElementById('modal-date');
    const content = document.getElementById('order-details-content');

    document.querySelectorAll('#produkt-admin-calendar .calendar-day').forEach(function(day){
        day.addEventListener('click', function(){
            const date = this.dataset.date;
            if (!date || !ordersByDay[date]) return;
            modalDate.textContent = date;
            let html = '';
            ordersByDay[date].forEach(function(o){
                html += '<div style="margin-bottom:15px;">';
                html += '<strong>#'+o.id+'</strong> '+o.produkt+'<br>';
                if (o.customer) html += o.customer+'<br>';
                if (o.email) html += o.email+'<br>';
                html += parseFloat(o.price).toFixed(2).replace('.', ',')+'€ - '+o.status;
                if (o.extras) html += '<br>Extras: '+o.extras;
                html += '</div>';
            });
            content.innerHTML = html;
            modal.style.display = 'block';
        });
    });

    modal.addEventListener('click', function(e){
        if (e.target === modal) modal.style.display = 'none';
    });
});

function closeOrderDetails(){
    document.getElementById('order-details-modal').style.display = 'none';
}
</script>
