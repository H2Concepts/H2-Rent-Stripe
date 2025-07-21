<?php
namespace ProduktVerleih;

class OrderService {
    /**
     * Retrieve orders for the given WordPress user.
     *
     * @param int $user_id User ID
     * @return array List of order objects
     */
    public static function get_orders_by_user($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'produkt_orders';
        $email = sanitize_email($user->user_email);

        $sql = "SELECT * FROM $table WHERE customer_email = %s ORDER BY created_at DESC";
        return $wpdb->get_results($wpdb->prepare($sql, $email));
    }
}
