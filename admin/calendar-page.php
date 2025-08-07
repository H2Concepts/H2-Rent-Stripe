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

$open_by_day = [];
$return_by_day = [];
$orders_detail = [];
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

$blocked_days = $wpdb->get_col("SELECT day FROM {$wpdb->prefix}produkt_blocked_days");

foreach ($orders as $o) {
    if (!empty($o->start_date)) {
        $date = $o->start_date;
        $open_by_day[$date][] = $o;
        $orders_detail[$date][] = [
            'id'      => (int)$o->id,
            'num'     => !empty($o->order_number) ? $o->order_number : $o->id,
            'name'    => $o->customer_name,
            'product' => $o->category_name ?: $o->produkt_name,
            'variant' => $o->variant_name,
            'extras'  => $o->extra_names,
            'action'  => 'Ausgeliehen'
        ];
    }
    if (!empty($o->end_date)) {
        $date = $o->end_date;
        $return_by_day[$date][] = $o;
        $orders_detail[$date][] = [
            'id'      => (int)$o->id,
            'num'     => !empty($o->order_number) ? $o->order_number : $o->id,
            'name'    => $o->customer_name,
            'product' => $o->category_name ?: $o->produkt_name,
            'variant' => $o->variant_name,
            'extras'  => $o->extra_names,
            'action'  => 'Rückgabe'
        ];
    }
}
?>
<div class="produkt-admin dashboard-wrapper">
    <div id="produkt-admin-calendar" class="calendar-layout">
        <aside class="calendar-sidebar">
            <div class="filters-block">
                <h2>Filter</h2>
                <p class="subline">Einstellungen auswählen</p>
                <form method="get" class="calendar-filters">
                    <input type="hidden" name="page" value="produkt-calendar">
                    <label class="filter-option"><input class="filter-checkbox filter-open" type="checkbox" name="show_open" value="1" <?php checked($show_open); ?>> Ausgeliehen</label>
                    <label class="filter-option"><input class="filter-checkbox filter-return" type="checkbox" name="show_return" value="1" <?php checked($show_return); ?>> Rückgabe fällig</label>
                    <button type="submit" class="icon-btn" aria-label="Anwenden">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3"><path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Zm8.2,26.2c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6Zm0-70.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/></svg>
                    </button>
                </form>
            </div>
            <div class="mini-calendar-block">
                <h2><?php echo $monthNames[$month-1] . ' ' . $year; ?></h2>
                <div class="mini-calendar">
                    <?php for ($i = 0; $i < $start_index; $i++): ?>
                        <div class="mini-day empty"></div>
                    <?php endfor; ?>
                    <?php for ($d = 1; $d <= $last_day; $d++):
                        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                        $openCount   = ($show_open   && isset($open_by_day[$date]))   ? count($open_by_day[$date])   : 0;
                        $returnCount = ($show_return && isset($return_by_day[$date])) ? count($return_by_day[$date]) : 0;
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
                    $openCount   = ($show_open   && isset($open_by_day[$date]))   ? count($open_by_day[$date])   : 0;
                    $returnCount = ($show_return && isset($return_by_day[$date])) ? count($return_by_day[$date]) : 0;
                    $orders_info = $orders_detail[$date] ?? [];
                    if (!$show_open || !$show_return) {
                        $orders_info = array_values(array_filter($orders_info, function($o) use ($show_open, $show_return) {
                            return ($o['action'] === 'Ausgeliehen' && $show_open) || ($o['action'] === 'Rückgabe' && $show_return);
                        }));
                    }
                    $is_blocked = in_array($date, $blocked_days, true);
                ?>
                <div class="day-cell<?php echo $is_blocked ? ' blocked' : ''; ?>" data-date="<?php echo esc_attr($date); ?>" data-blocked="<?php echo $is_blocked ? '1' : '0'; ?>" data-orders='<?php echo esc_attr(json_encode($orders_info)); ?>'>
                    <div class="day-number<?php echo ($date === current_time('Y-m-d')) ? ' today' : ''; ?>"><?php echo $d; ?></div>
                    <div class="weekday"><?php echo esc_html($weekday); ?></div>
                    <?php if ($returnCount): ?>
                        <div class="event-bar return">
                            <span class="label">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ff6617" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                Rückgabe
                            </span>
                            <span class="count"><?php echo $returnCount; ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($openCount): ?>
                        <div class="event-bar open">
                            <span class="label">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#1cdd4e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Ausgeliehen
                            </span>
                            <span class="count"><?php echo $openCount; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
            <div id="day-orders-card" class="dashboard-card">
                <div class="card-header">
                    <h2>Bestellungen am <span id="day-orders-date"></span></h2>
                    <div class="day-actions">
                        <button id="block-day" class="block-day-btn" type="button">
                            <span>Tag sperren</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                        </button>
                        <button id="unblock-day" class="block-day-btn" type="button">
                            <span>Tag freigeben</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3"><path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/><path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/></svg>
                        </button>
                    </div>
                </div>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Produkte</th>
                            <th>Ausführungen</th>
                            <th>Extras</th>
                            <th>Aktion</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody id="day-orders-body"></tbody>
                </table>
            </div>
        </div>

        <div id="order-details-sidebar" class="order-details-sidebar">
            <div class="order-details-header">
                <h3>Bestelldetails</h3>
                <button class="close-sidebar">&times;</button>
            </div>
            <div class="order-details-content">
                <p>Lade Details…</p>
            </div>
        </div>
        <div id="order-details-overlay" class="order-details-overlay"></div>
    </div>
</div>
<script>
document.getElementById('wpwrap').classList.add('calendar-page');
var produkt_calendar_nonce = '<?php echo wp_create_nonce('produkt_nonce'); ?>';
</script>
