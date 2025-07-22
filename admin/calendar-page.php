<?php
if (!defined('ABSPATH')) {
    exit;
}

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

// collect booking days per status
$booked = [];
$orders = $wpdb->get_results("SELECT dauer_text, status FROM {$wpdb->prefix}produkt_orders WHERE mode = 'kauf'");
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
            <div class="calendar-day <?php echo $cls; ?>"><?php echo $d; ?></div>
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
