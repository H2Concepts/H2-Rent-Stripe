<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

$monthNames = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
$dayNames   = ['Mo','Di','Mi','Do','Fr','Sa','So'];

$show_open   = isset($_GET['show_open']);
$show_return = isset($_GET['show_return']);
if (!$show_open && !$show_return) { $show_open = $show_return = true; }

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
$start_index   = (int)date('N', $first_day_ts) - 1;

$orders_by_day = [];
$where = ["o.mode = 'kauf'"];

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
    list($s, $e) = pv_get_order_period($o);
    $start = $s ? strtotime($s) : null;
    $end   = $e ? strtotime($e) : null;
    if ($start && $end) {
        while ($start <= $end) {
            $d = date('Y-m-d', $start);
            $orders_by_day[$d][] = $o;
            $start = strtotime('+1 day', $start);
        }
    }
}
?>
<div id="produkt-admin-calendar" class="calendar-layout">
    <aside class="calendar-sidebar">
        <form method="get" class="calendar-filters">
            <input type="hidden" name="page" value="produkt-calendar">
            <label><input type="checkbox" name="show_open" value="1" <?php checked($show_open); ?>> Ausgeliehen</label>
            <label><input type="checkbox" name="show_return" value="1" <?php checked($show_return); ?>> Rückgabe fällig</label>
            <button class="button" type="submit">Anwenden</button>
        </form>
        <div class="mini-calendar">
            <?php for ($i = 0; $i < $start_index; $i++): ?>
                <div class="mini-day empty"></div>
            <?php endfor; ?>
            <?php for ($d = 1; $d <= $last_day; $d++):
                $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $openCount = 0; $returnCount = 0;
                if (isset($orders_by_day[$date])) {
                    foreach ($orders_by_day[$date] as $o) {
                        if ($show_open && $o->start_date === $date) $openCount++;
                        if ($show_return && $o->end_date === $date) $returnCount++;
                    }
                }
            ?>
            <div class="mini-day<?php echo ($date === current_time('Y-m-d')) ? ' today' : ''; ?>">
                <span class="num"><?php echo $d; ?></span>
                <div class="dots">
                    <?php if ($openCount) : ?><span class="dot open"></span><?php endif; ?>
                    <?php if ($returnCount) : ?><span class="dot return"></span><?php endif; ?>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </aside>
    <div class="calendar-main">
        <div class="calendar-header">
            <h2><?php echo $monthNames[$month-1] . ' ' . $year; ?></h2>
            <div class="month-nav">
                <a class="month-btn" href="<?php echo admin_url('admin.php?page=produkt-calendar&month=' . $prev_month . '&year=' . $prev_year . ($show_open ? '&show_open=1' : '') . ($show_return ? '&show_return=1' : '')); ?>">&larr;</a>
                <a class="month-btn" href="<?php echo admin_url('admin.php?page=produkt-calendar&month=' . $next_month . '&year=' . $next_year . ($show_open ? '&show_open=1' : '') . ($show_return ? '&show_return=1' : '')); ?>">&rarr;</a>
            </div>
        </div>
        <div class="calendar-big-grid">
            <?php for ($i = 0; $i < $start_index; $i++): ?>
                <div class="day-cell empty"></div>
            <?php endfor; ?>
            <?php for ($d = 1; $d <= $last_day; $d++):
                $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $weekday = $dayNames[(int)date('N', strtotime($date)) - 1];
                $openCount = 0; $returnCount = 0;
                if (isset($orders_by_day[$date])) {
                    foreach ($orders_by_day[$date] as $o) {
                        if ($show_open && $o->start_date === $date) $openCount++;
                        if ($show_return && $o->end_date === $date) $returnCount++;
                    }
                }
            ?>
            <div class="day-cell">
                <div class="day-number<?php echo ($date === current_time('Y-m-d')) ? ' today' : ''; ?>"><?php echo $d; ?></div>
                <div class="weekday"><?php echo esc_html($weekday); ?></div>
                <?php if ($returnCount): ?>
                    <div class="event-bar return"><span class="icon">&#10005;</span><span class="count"><?php echo $returnCount; ?></span></div>
                <?php endif; ?>
                <?php if ($openCount): ?>
                    <div class="event-bar open"><span class="icon">&#10003;</span><span class="count"><?php echo $openCount; ?></span></div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>
