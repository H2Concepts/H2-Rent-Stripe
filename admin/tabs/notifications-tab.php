<?php
// Notifications Tab Content

// Handle newsletter toggle
if (isset($_POST['save_newsletter_settings'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $newsletter_enabled = !empty($_POST['newsletter_enabled']) ? '1' : '0';
    update_option('produkt_newsletter_enabled', $newsletter_enabled);
    echo '<div class="notice notice-success"><p>✅ Newsletter-Einstellungen gespeichert!</p></div>';
}

// Handle delete notification
if (isset($_GET['delete_notification'])) {
    $notification_id = intval($_GET['delete_notification']);
    $result = $wpdb->delete(
        $wpdb->prefix . 'produkt_notifications',
        array('id' => $notification_id),
        array('%d')
    );

    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Eintrag erfolgreich gelöscht!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
    }
}

// Handle bulk delete
if (!empty($_POST['delete_notifications']) && is_array($_POST['delete_notifications'])) {
    $ids = array_map('intval', (array) $_POST['delete_notifications']);
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}produkt_notifications WHERE id IN ($placeholders)",
            ...$ids
        );
        $result = $wpdb->query($query);
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Einträge erfolgreich gelöscht!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }
}

$where_clause = '';
$notifications = $wpdb->get_results(
    "SELECT n.*, v.name AS variant_name,
        d.name AS duration_name,
        c.name AS condition_name,
        pc.name AS product_color_name,
        fc.name AS frame_color_name,
        (SELECT GROUP_CONCAT(e.name SEPARATOR ', ')
            FROM {$wpdb->prefix}produkt_extras e
            WHERE FIND_IN_SET(e.id, n.extra_ids)) AS extras_names
     FROM {$wpdb->prefix}produkt_notifications n
     LEFT JOIN {$wpdb->prefix}produkt_variants v ON n.variant_id = v.id
     LEFT JOIN {$wpdb->prefix}produkt_durations d ON n.duration_id = d.id
     LEFT JOIN {$wpdb->prefix}produkt_conditions c ON n.condition_id = c.id
     LEFT JOIN {$wpdb->prefix}produkt_colors pc ON n.product_color_id = pc.id
     LEFT JOIN {$wpdb->prefix}produkt_colors fc ON n.frame_color_id = fc.id
     $where_clause ORDER BY n.created_at DESC"
);

$newsletter_rows = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}produkt_newsletter_optins ORDER BY requested_at DESC"
);

$pn_notice = isset($_GET['pn_notice']) ? sanitize_text_field(wp_unslash($_GET['pn_notice'])) : '';
$pn_type   = isset($_GET['pn_notice_type']) ? sanitize_text_field(wp_unslash($_GET['pn_notice_type'])) : 'success';

$messages = [
    'newsletter_resend_ok'  => 'Double-Opt-In E-Mail wurde erneut versendet.',
    'newsletter_delete_ok'  => 'Newsletter-Eintrag wurde gelöscht.',
    'newsletter_action_failed' => 'Aktion konnte nicht ausgeführt werden.',
    'newsletter_resend_already_confirmed' => 'Dieser Eintrag ist bereits bestätigt – es wurde keine neue Double-Opt-In E-Mail versendet.',
];

if ($pn_notice && isset($messages[$pn_notice])) {
    if ($pn_type === 'error') {
        $class = 'notice notice-error is-dismissible';
    } elseif ($pn_type === 'warning') {
        $class = 'notice notice-warning is-dismissible';
    } else {
        $class = 'notice notice-success is-dismissible';
    }
    echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($messages[$pn_notice]) . '</p></div>';
}
?>

<div class="settings-tab">
    <div class="produkt-form-sections">
        <div class="dashboard-card card-activity">
            <h2>Benachrichtigungsanfragen</h2>
            <p class="card-subline">Eingegangene Wünsche</p>
            <?php if (!empty($notifications)): ?>
            <div class="produkt-bulk-actions">
                <button type="button" class="button" onclick="toggleSelectAllNotifications()">Alle auswählen</button>
                <button type="button" class="button" onclick="deleteSelectedNotifications()" style="color: #dc3232;">Ausgewählte löschen</button>
            </div>
            <?php endif; ?>
            <?php if (empty($notifications)): ?>
                <div class="produkt-empty-state">
                    <p>Keine Einträge vorhanden.</p>
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="select-all-notifications"></th>
                            <th style="width:80px;">ID</th>
                            <th style="width:140px;">Datum</th>
                            <th>E-Mail</th>
                            <th>Details</th>
                            <th style="width:120px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $note): ?>
                        <tr>
                            <td><input type="checkbox" class="notification-checkbox" value="<?php echo $note->id; ?>"></td>
                            <td><strong>#<?php echo $note->id; ?></strong></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($note->created_at)); ?></td>
                            <td><?php echo esc_html($note->email); ?></td>
                            <td>
                                <?php
                                    $parts = array();
                                    if ($note->variant_name) {
                                        $parts[] = $note->variant_name;
                                    }
                                    if ($note->duration_name) {
                                        $parts[] = 'Mietdauer: ' . $note->duration_name;
                                    }
                                    if ($note->condition_name) {
                                        $parts[] = 'Zustand: ' . $note->condition_name;
                                    }
                                    if ($note->product_color_name) {
                                        $parts[] = 'Produktfarbe: ' . $note->product_color_name;
                                    }
                                    if ($note->frame_color_name) {
                                        $parts[] = 'Gestellfarbe: ' . $note->frame_color_name;
                                    }
                                    if ($note->extras_names) {
                                        $parts[] = 'Extras: ' . $note->extras_names;
                                    }
                                    echo esc_html(implode(', ', $parts));
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=notifications&delete_notification=' . $note->id); ?>" class="button button-small" style="color:#dc3232;" onclick="return confirm('Bist du sicher das du Löschen möchtest?');">🗑️ Löschen</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card card-activity" style="margin-top:18px;">
            <div class="card-header-flex">
                <div>
                    <h2>Newsletter Opt-ins</h2>
                    <p class="card-subline">Bestellungen mit Newsletter-Checkbox (Double-Opt-In Status)</p>
                </div>
                <form method="post" action="" style="display:inline;">
                    <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
                    <?php 
                    $newsletter_enabled = get_option('produkt_newsletter_enabled', '1');
                    ?>
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="newsletter_enabled" value="1" <?php checked($newsletter_enabled, '1'); ?> onchange="this.form.submit();">
                        <span class="produkt-toggle-slider"></span>
                        <span>Newsletter aktivieren</span>
                    </label>
                    <input type="hidden" name="save_newsletter_settings" value="1">
                </form>
            </div>

            <?php if (empty($newsletter_rows)): ?>
                <div class="produkt-empty-state">
                    <p>Keine Einträge vorhanden.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th style="width:140px;">Datum</th>
                                <th>Name</th>
                                <th>Adresse</th>
                                <th>Telefon</th>
                                <th>E-Mail</th>
                                <th style="width:120px;">Double Opt-In</th>
                                <th style="width:120px;">Confirm IP</th>
                                <th style="width:200px;">User Agent</th>
                                <th style="width:180px;">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($newsletter_rows as $r): ?>
                                <tr>
                                    <td><?php echo esc_html(date('d.m.Y H:i', strtotime($r->requested_at))); ?></td>
                                    <td><?php echo esc_html(trim($r->first_name . ' ' . $r->last_name)); ?></td>
                                    <td><?php echo esc_html(trim($r->street . ', ' . $r->postal_code . ' ' . $r->city . ' ' . $r->country)); ?></td>
                                    <td><?php echo esc_html($r->phone); ?></td>
                                    <td><?php echo esc_html($r->email); ?></td>
                                    <td>
                                        <?php if (intval($r->status) === 1): ?>
                                            <strong>Ja</strong>
                                        <?php else: ?>
                                            <span>Nein</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($r->confirm_ip ?? '–'); ?></td>
                                    <td><?php 
                                        $ua = $r->confirm_user_agent ?? '';
                                        if ($ua) {
                                            $ua_short = mb_strlen($ua) > 60 ? mb_substr($ua, 0, 60) . '...' : $ua;
                                            echo esc_html($ua_short);
                                        } else {
                                            echo '–';
                                        }
                                    ?></td>
                                    <td>
                                        <?php if (intval($r->status) === 0): ?>
                                            <a class="button button-small"
                                               href="<?php echo esc_url(wp_nonce_url(
                                                   admin_url('admin-post.php?action=produkt_newsletter_resend&email=' . rawurlencode($r->email)),
                                                   'produkt_newsletter_resend'
                                               )); ?>">
                                                DOI erneut senden
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="button button-small" disabled
                                                style="opacity:.55; cursor:not-allowed;">
                                                DOI erneut senden
                                            </button>
                                        <?php endif; ?>

                                        <a class="icon-btn" 
                                           href="<?php echo esc_url(wp_nonce_url(
                                               admin_url('admin-post.php?action=produkt_newsletter_delete&email=' . rawurlencode($r->email)),
                                               'produkt_newsletter_delete'
                                           )); ?>"
                                           onclick="return confirm('Eintrag wirklich löschen?');"
                                           aria-label="Löschen">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                                <path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/>
                                                <path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleSelectAllNotifications() {
    const selectAll = document.getElementById('select-all-notifications');
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
    selectAll.checked = !allChecked;
}

function deleteSelectedNotifications() {
    const selected = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('Bitte wählen Sie mindestens einen Eintrag aus.');
        return;
    }
    if (!confirm('Bist du sicher das du Löschen möchtest?')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.href;
    selected.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_notifications[]';
        input.value = id;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
}
</script>
