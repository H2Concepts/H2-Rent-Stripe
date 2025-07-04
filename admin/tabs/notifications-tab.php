<?php
// Notifications Tab Content

// Handle delete notification
if (isset($_GET['delete_notification'])) {
    $notification_id = intval($_GET['delete_notification']);
    $result = $wpdb->delete(
        $wpdb->prefix . 'federwiegen_notifications',
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
            "DELETE FROM {$wpdb->prefix}federwiegen_notifications WHERE id IN ($placeholders)",
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

$where_clause = $selected_category > 0 ? $wpdb->prepare('WHERE n.category_id = %d', $selected_category) : '';
$notifications = $wpdb->get_results(
    "SELECT n.*, v.name AS variant_name,
        d.name AS duration_name,
        c.name AS condition_name,
        pc.name AS product_color_name,
        fc.name AS frame_color_name,
        (SELECT GROUP_CONCAT(e.name SEPARATOR ', ')
            FROM {$wpdb->prefix}federwiegen_extras e
            WHERE FIND_IN_SET(e.id, n.extra_ids)) AS extras_names
     FROM {$wpdb->prefix}federwiegen_notifications n
     LEFT JOIN {$wpdb->prefix}federwiegen_variants v ON n.variant_id = v.id
     LEFT JOIN {$wpdb->prefix}federwiegen_durations d ON n.duration_id = d.id
     LEFT JOIN {$wpdb->prefix}federwiegen_conditions c ON n.condition_id = c.id
     LEFT JOIN {$wpdb->prefix}federwiegen_colors pc ON n.product_color_id = pc.id
     LEFT JOIN {$wpdb->prefix}federwiegen_colors fc ON n.frame_color_id = fc.id
     $where_clause ORDER BY n.created_at DESC"
);
?>

<div class="federwiegen-notifications-tab">
    <div class="federwiegen-orders-card">
        <div class="federwiegen-orders-header">
            <h4>📧 Benachrichtigungsanfragen</h4>
            <?php if (!empty($notifications)): ?>
            <div class="federwiegen-bulk-actions">
                <button type="button" class="button" onclick="toggleSelectAllNotifications()">Alle auswählen</button>
                <button type="button" class="button" onclick="deleteSelectedNotifications()" style="color: #dc3232;">Ausgewählte löschen</button>
            </div>
            <?php endif; ?>
        </div>
        <?php if (empty($notifications)): ?>
            <div class="federwiegen-empty-state">
                <p>Keine Einträge vorhanden.</p>
            </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="wp-list-table widefat fixed striped">
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
                            <a href="<?php echo admin_url('admin.php?page=federwiegen-analytics&tab=notifications&category=' . $selected_category . '&delete_notification=' . $note->id); ?>" class="button button-small" style="color:#dc3232;" onclick="return confirm('Eintrag wirklich löschen?');">🗑️ Löschen</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
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
    if (!confirm('Ausgewählte Einträge löschen?')) return;
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
