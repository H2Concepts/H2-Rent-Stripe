<?php
if (!defined('ABSPATH')) exit;

$order = $order_data ?? null;
$modus = get_option('produkt_betriebsmodus', 'miete');

if (empty($order) || !is_object($order)) {
    echo '<p>Fehler: Keine gültigen Auftragsdaten übergeben.</p>';
    return;
}

// Initialen erzeugen
$initials = '';
if (!empty($order->customer_name)) {
    $names = explode(' ', $order->customer_name);
    $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
}

// Prozent Mietdauer berechnen
$percent = 0;
if (!empty($sd) && !empty($ed)) {
    $start = strtotime($sd);
    $end = strtotime($ed);
    $today = time();
    $total = max(1, $end - $start);
    $elapsed = min(max(0, $today - $start), $total);
    $percent = floor(($elapsed / $total) * 100);
}

// Status-Text für den Badge ermitteln
$badge_status = 'In Vermietung';
if ($modus !== 'miete') {
    if ($percent >= 100) {
        $badge_status = 'Abgeschlossen';
    } elseif ($percent <= 0) {
        $badge_status = 'Ausstehend';
    }
}

// Laufende Miettage für Vermietungsmodus ermitteln
$rental_elapsed_days = null;
if ($modus === 'miete' && !empty($sd)) {
    $start_timestamp = strtotime($sd);
    if ($start_timestamp) {
        $now = function_exists('current_time') ? current_time('timestamp') : time();
        $day_in_seconds = defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 86400;
        $diff_days = floor(($now - $start_timestamp) / $day_in_seconds);
        $rental_elapsed_days = max(0, $diff_days) + 1;
    }
}

$start_label = $sd ? date_i18n('d. M', strtotime($sd)) : '–';
$end_label = $ed ? date_i18n('d. M', strtotime($ed)) : '–';
$day_counter_label = $rental_elapsed_days !== null
    ? $rental_elapsed_days . ' Tag' . ($rental_elapsed_days === 1 ? '' : 'e')
    : '–';

// Produkte ermitteln
$produkte = $order->produkte ?? [$order]; // fallback
?>

<div class="sidebar-wrapper" data-order-id="<?php echo esc_attr($order->id); ?>">

    <!-- Header -->
    <div class="sidebar-header">
        <h2>Bestellübersicht</h2>
        <span class="order-id">#<?php echo esc_html(!empty($order->order_number) ? $order->order_number : $order->id); ?></span>
    </div>

    <!-- Kundeninfo -->
    <div class="customer-info">
        <div class="customer-avatar"><?php echo esc_html($initials); ?></div>
        <div class="customer-details">
            <strong><?php echo esc_html($order->customer_name ?? '–'); ?></strong>
            <div class="email"><?php echo esc_html($order->customer_email ?? '–'); ?></div>
        </div>
        <?php $user = get_user_by('email', $order->customer_email); ?>
        <div class="customer-icons">
            <a class="icon-btn icon-btn-no-stroke customer-profile-link" href="<?php echo $user ? admin_url('admin.php?page=produkt-customers&customer=' . $user->ID) : '#'; ?>" title="Kundenprofil">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 85 85.1"><path d="M42.5.4C19.4.4.6,19.1.6,42.2s18.8,41.8,41.8,41.8,41.8-18.8,41.8-41.8S65.5.4,42.5.4ZM18.1,70.2l2.3-4.8,13.9-2.2c1.1-.2,1.9-1,2-2.1l.4-4.3c1.8.9,3.8,1.4,5.8,1.4s4-.5,5.8-1.4l.4,4.3c.1,1.1.9,1.9,2,2.1l13.9,2.2,2.3,4.8c-6.5,5.7-15.1,9.2-24.4,9.2s-17.9-3.5-24.4-9.2h0ZM51.7,20.1c2.7,2.3,4.4,5.7,4.8,9.9,0,.7.5,1.4,1.1,1.8.2.2.9,1.3.9,3.2s-.5,2.7-.8,3.1h0c-1,0-1.9.7-2.2,1.7-2.4,8.1-7.7,13.8-12.9,13.8s-10.5-5.7-12.9-13.8c-.3-1-1.2-1.7-2.2-1.7h0c-.3-.4-.8-1.4-.8-3.1s.7-3.1.9-3.2c.6-.4,1-1,.1-1.8.7-8,6.1-12.9,14-12.9s1.7,0,2.6.2c.3,0,.6,0,.9,0l5.8-1.4c-.3.5-.5,1.1-.8,1.6-.4,1-.1,2.1.7,2.7h0ZM70.4,66.7l-2.1-4.4c-.3-.7-1-1.2-1.7-1.3l-13.3-2.1-.5-5.2c3-2.9,5.3-7,6.7-11.1,2.2-.9,3.7-3.9,3.7-7.5s-.8-4.9-2.1-6.3c-.6-4.4-2.3-8.1-5-10.9,1.1-2.1,2.6-4.2,2.7-4.2.6-.8.6-1.9,0-2.7-.5-.8-1.5-1.2-2.5-1l-10.9,2.7c-.9-.1-1.9-.2-2.8-.2-10.1,0-17.3,6.4-18.6,16.3-1.3,1.4-2.1,3.7-2.1,6.3s1.5,6.6,3.7,7.5c1.4,4.1,3.7,8.1,6.7,11.1l-.5,5.3-13.3,2.1c-.8.1-1.4.6-1.7,1.3l-2.1,4.4c-5.7-6.6-9.2-15.1-9.2-24.5C5.3,21.7,22,5,42.5,5s37.2,16.7,37.2,37.2-3.5,17.9-9.2,24.5h0Z"/></svg>
            </a>
            <a class="icon-btn icon-btn-no-stroke" href="mailto:<?php echo esc_attr($order->customer_email); ?>" title="E-Mail senden">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 84.7 66.7"><path d="M70.6.7H14.3C6.9.7.9,6.8.9,14.2v38.1c0,7.4,6,13.4,13.4,13.4h56.3c7.4,0,13.4-6,13.4-13.4V14.2c0-7.4-6-13.4-13.4-13.4ZM78.2,56.5l-17.2-20.4,18.3-16.6v32.7c0,1.5-.4,3-1.1,4.2h0ZM14.3,5.4h56.3c4.5,0,8.2,3.4,8.7,7.8l-36.8,33.4L5.6,13.2c.5-4.4,4.2-7.8,8.7-7.8h0ZM6.7,56.5c-.7-1.3-1.1-2.7-1.1-4.2V19.5l18.3,16.6L6.7,56.5ZM14.3,61c-.9,0-1.7-.1-2.5-.4l12.6-2.9c1.3-.3,2-1.6,1.8-2.8-.3-1.3-1.5-2-2.8-1.8l-9.6,2.2,13.6-16.1,13.5,12.3c.4.4,1,.6,1.6.6s1.1-.2,1.6-.6l13.6-12.3,13.6,16.1-9.6-2.2c-1.3-.3-2.5.5-2.8,1.8-.3,1.3.5,2.5,1.8,2.8l12.6,2.9c-.8.2-1.6.4-2.5.4H14.3h0Z"/></svg>
            </a>
            <button type="button" class="icon-btn icon-btn-no-stroke note-icon" title="Notiz hinzufügen">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 81.2 80.7"><path d="M80.5,19.1c0-2.2-.9-4.2-2.4-5.8l-10.3-10.3c-1.5-1.5-3.6-2.4-5.8-2.4s-4.2.8-5.8,2.4l-3.9,3.9c-1.2-.6-2.6-.9-4-.9-2.5,0-4.9,1-6.7,2.8-2.9,2.9-3.5,7.1-1.9,10.6L7.4,51.9s0,0-.1.1c0,0-.1.1-.1.2,0,0,0,.1-.1.2,0,0,0,.1-.1.2,0,0,0,.2,0,.2,0,0,0,.1,0,.2L1,77.4c-.2.8,0,1.6.6,2.2.4.4,1,.7,1.7.7s.4,0,.5,0l24.3-5.8c0,0,.1,0,.2,0,0,0,.2,0,.2,0,0,0,.1,0,.2-.1,0,0,.1,0,.2-.1,0,0,.1-.1.2-.2,0,0,0,0,.1-.1l32.5-32.5c1.2.6,2.6.9,4,.9,2.5,0,4.9-1,6.7-2.8,2.9-2.9,3.5-7.1,1.9-10.6l3.9-3.9c1.5-1.5,2.4-3.6,2.4-5.8h0ZM25.5,66.7l-11.2-11.2c.1,0,.2,0,.4-.1l17.4-6.4-6.7,17.7ZM34.8,43l-18.8,6.9,26.8-26.8,5.9,5.9-13.9,13.9ZM10.4,58.2l12.6,12.6-16.6,4,4-16.6ZM31,65.4l7.2-19.1,13.9-13.9,5.9,5.9-27.1,27.1ZM69.1,36.1s0,0,0,0c-.9.9-2.1,1.4-3.3,1.4s-2.5-.5-3.3-1.4l-17.3-17.3c-1.8-1.8-1.8-4.8,0-6.7.9-.9,2.1-1.4,3.3-1.4s2.5.5,3.3,1.4l17.3,17.3c1.9,1.8,1.9,4.8,0,6.7h0ZM74.9,21.5l-3.5,3.5-15.2-15.2,3.5-3.5c.7-.7,1.5-1,2.5-1s1.8.4,2.5,1l10.3,10.3c.6.6,1,1.5,1,2.5s-.4,1.8-1,2.5h0Z"/></svg>
            </button>
        </div>
    </div>

    <!-- Kundendaten -->
    <div class="customer-contact">
        <h3>Kundendaten</h3>
        <?php if (!empty($order->customer_phone)) : ?>
            <p><strong>Telefon:</strong> <?php echo esc_html($order->customer_phone); ?></p>
        <?php endif; ?>

        <?php
            $addr_parts = [];
            if (!empty($order->customer_street)) {
                $addr_parts[] = $order->customer_street;
            }
            if (!empty($order->customer_postal) || !empty($order->customer_city)) {
                $addr_parts[] = trim(($order->customer_postal ?: '') . ' ' . ($order->customer_city ?: ''));
            }
            if (!empty($order->customer_country)) {
                $addr_parts[] = $order->customer_country;
            }
            $full_address = implode(', ', array_filter($addr_parts));
        ?>
        <?php if ($full_address) : ?>
            <p><strong>Rechnung:</strong> <?php echo esc_html($full_address); ?></p>
            <p><strong>Versand:</strong> <?php echo esc_html($full_address); ?></p>
        <?php endif; ?>
    </div>

    <!-- Mietzeitraum -->
    <div class="rental-period-box">
        <div class="badge-status"><?php echo esc_html($badge_status); ?></div>
        <h3>Mietzeitraum</h3>
        <?php if ($modus === 'miete') : ?>
            <div class="rental-progress-number"><?php echo esc_html($day_counter_label); ?></div>
            <div class="rental-progress rental-progress-days">
                <div class="day-counter-text">Tage seit Buchung</div>
            </div>
            <div class="rental-dates">
                <span>Start: <?php echo esc_html($start_label); ?></span>
            </div>
        <?php else : ?>
            <div class="rental-progress-number"><?php echo intval($percent); ?>%</div>
            <div class="rental-progress">
                <div class="bar">
                    <div class="fill" style="width: <?php echo intval($percent); ?>%;"></div>
                </div>
            </div>
            <div class="rental-dates">
                <span>Abgeholt: <?php echo esc_html($start_label); ?></span>
                <span>Rückgabe: <?php echo esc_html($end_label); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Produktliste -->
    <div class="product-list">
        <h3>Produkte</h3>
        <?php foreach ($produkte as $p) : ?>
            <div class="product-item">
                <?php
                    $thumb_url = $p->image_url ?? $image_url ?? '';
                    if (!empty($thumb_url)) :
                ?>
                    <img class="product-thumb" src="<?php echo esc_url($thumb_url); ?>" alt="Produktbild">
                <?php endif; ?>

                <div class="product-details">
                    <strong><?php echo esc_html($p->produkt_name ?? '–'); ?></strong>
                    <?php if (!empty($p->variant_name)) : ?>
                        <div>Ausführung: <?php echo esc_html($p->variant_name); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($p->extra_names)) : ?>
                        <div>Extras: <?php echo esc_html($p->extra_names); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($p->product_color_name)) : ?>
                        <div>Farbe: <?php echo esc_html($p->product_color_name); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($p->frame_color_name)) : ?>
                        <div>Gestellfarbe: <?php echo esc_html($p->frame_color_name); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($p->weekend_tariff)) : ?>
                        <div>Hinweis: Wochenendtarif</div>
                    <?php endif; ?>
                    <div>Miettage: <?php echo esc_html($days !== null ? $days : ($p->dauer_text ?? '–')); ?></div>
                </div>

                <div class="product-price">
                    <?php echo number_format((float)$p->final_price, 2, ',', '.'); ?> €
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Gesamtpreis -->
    <div class="total-section">
        <p><strong>Gesamtpreis:</strong> <?php echo number_format((float)$order->final_price, 2, ',', '.'); ?> €</p>

        <?php if ($order->shipping_cost > 0 || !empty($order->shipping_name)) : ?>
            <p><strong>Versand:</strong>
                <?php echo esc_html($order->shipping_name ?: 'Versand'); ?>
                <?php if ($order->shipping_cost > 0) : ?>
                    – <?php echo number_format((float)$order->shipping_cost, 2, ',', '.'); ?> €
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="orders-accordion">
        <div class="produkt-accordion-item">
            <button type="button" class="produkt-accordion-header">Technische Daten</button>
            <div class="produkt-accordion-content">
                <p><strong>User Agent:</strong> <?php echo esc_html($order->user_agent ?: '–'); ?></p>
                <p><strong>IP-Adresse:</strong> <?php echo esc_html($order->user_ip ?: '–'); ?></p>
            </div>
        </div>
        <div class="produkt-accordion-item">
            <button type="button" class="produkt-accordion-header">Verlauf</button>
            <div class="produkt-accordion-content">
                <?php if (!empty($order_logs)) : ?>
                    <div class="order-log-list">
                        <?php
                        $system_events = ['inventory_returned_not_accepted','inventory_returned_accepted','welcome_email_sent','status_updated','checkout_completed'];
                        foreach ($order_logs as $log) :
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
                                default:
                                    $text = $log->message ?: $log->event;
                            }
                            $order_no = !empty($order->order_number) ? $order->order_number : $order->id;
                            $date_id = date_i18n('d.m.Y H:i', strtotime($log->created_at)) . ' / #' . $order_no;
                            ?>
                            <div class="order-log-entry">
                                <div class="log-avatar"><?php echo esc_html($avatar); ?></div>
                                <div class="log-body">
                                    <div class="log-date"><?php echo esc_html($date_id); ?></div>
                                    <div class="log-message"><?php echo esc_html($text); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p>Keine Einträge</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="order-notes-section">
        <h3>Notizen</h3>
        <?php if (!empty($order_notes)) : ?>
            <?php foreach ($order_notes as $note) : ?>
                <div class="order-note" data-note-id="<?php echo intval($note->id); ?>">
                    <div class="note-text"><?php echo esc_html($note->message); ?></div>
                    <div class="note-date"><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($note->created_at))); ?></div>
                    <button type="button" class="icon-btn note-delete-btn" title="Notiz löschen">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div id="order-note-form" class="order-note-form">
        <textarea placeholder="Notiz"></textarea>
        <div class="note-actions">
            <button type="button" class="button button-primary note-save">Speichern</button>
            <button type="button" class="button note-cancel">Abbrechen</button>
        </div>
    </div>
</div>
