<?php
namespace FederwiegenVerleih;

class Ajax {
    
    public function ajax_get_product_price() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        $extra_id = !empty($extra_ids) ? $extra_ids[0] : 0;
        $duration_id = intval($_POST['duration_id']);
        $condition_id = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
        $frame_color_id = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
        
        global $wpdb;
        
        $variant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
            $variant_id
        ));
        
        $extras = [];
        if (!empty($extra_ids)) {
            $placeholders = implode(',', array_fill(0, count($extra_ids), '%d'));
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_extras WHERE id IN ($placeholders)",
                ...$extra_ids
            );
            $extras = $wpdb->get_results($query);
        }
        
        $duration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}federwiegen_durations WHERE id = %d",
            $duration_id
        ));
        
        $condition = null;
        if ($condition_id) {
            $condition = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE id = %d",
                $condition_id
            ));
        }
        
        // Find best matching Stripe link
        $link = $this->find_best_stripe_link($variant_id, $extra_ids_raw, $duration_id, $condition_id, $product_color_id, $frame_color_id);
        
        
        if ($variant && $duration) {
            $variant_price = 0;
            $used_price_id = '';
            $duration_price_id = $wpdb->get_var($wpdb->prepare(
                "SELECT stripe_price_id FROM {$wpdb->prefix}federwiegen_duration_prices WHERE duration_id = %d AND variant_id = %d",
                $duration_id,
                $variant_id
            ));

            $price_id_to_use = $duration_price_id ?: $variant->stripe_price_id;

            if (!empty($price_id_to_use)) {
                $price_res = StripeService::get_price_amount($price_id_to_use);
                if (is_wp_error($price_res)) {
                    wp_send_json_error('Price fetch failed');
                }
                $variant_price = floatval($price_res);
                $used_price_id = $price_id_to_use;
            } else {
                $variant_price = floatval($variant->base_price);
            }
            $extras_price = 0;
            foreach ($extras as $ex) {
                if (!empty($ex->stripe_price_id)) {
                    $pr = StripeService::get_price_amount($ex->stripe_price_id);
                    if (is_wp_error($pr)) {
                        wp_send_json_error('Price fetch failed');
                    }
                    $extras_price += floatval($pr);
                } else {
                    $extras_price += floatval($ex->price);
                }
            }

            // Apply condition price modifier to whole price like before
            if ($condition && $condition->price_modifier != 0) {
                $modifier = 1 + floatval($condition->price_modifier);
                $variant_price *= $modifier;
                $extras_price  *= $modifier;
            }

            $base_price = $variant_price + $extras_price;
            $discount = floatval($duration->discount);
            // Do not apply the discount to the calculated price. It is only used
            // for displaying the badge on the duration option.
            $final_price = $base_price;
            $shipping_cost = defined('FEDERWIEGEN_SHIPPING_COST')
                ? floatval(constant('FEDERWIEGEN_SHIPPING_COST'))
                : 0;
            
            wp_send_json_success(array(
                'base_price' => $base_price,
                'final_price' => $final_price,
                'discount' => $discount,
                'shipping_cost' => $shipping_cost,
                'stripe_link' => $link ?: '#',
                'price_id' => $used_price_id,
                'available' => $variant->available ? true : false,
                'availability_note' => $variant->availability_note ?: '',
                'delivery_time' => $variant->delivery_time ?: ''
            ));
        } else {
            wp_send_json_error('Invalid selection');
        }
    }
    
    private function find_best_stripe_link($variant_id, $extra_ids_raw, $duration_id, $condition_id = null, $product_color_id = null, $frame_color_id = null) {
        $extra_ids_array = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        sort($extra_ids_array);
        $extra_ids_sorted = implode(',', $extra_ids_array);
        $extra_id = !empty($extra_ids_array) ? $extra_ids_array[0] : 0;
        global $wpdb;
        
        // Try to find exact match first
        $exact_link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links
             WHERE variant_id = %d AND extra_id = %d AND extra_ids = %s AND duration_id = %d
             AND condition_id = %d AND product_color_id = %d AND frame_color_id = %d",
            $variant_id, $extra_id, $extra_ids_sorted, $duration_id, $condition_id, $product_color_id, $frame_color_id
        ));
        
        if ($exact_link) {
            return $exact_link;
        }
        
        // Try without frame color
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links
             WHERE variant_id = %d AND extra_id = %d AND extra_ids = %s AND duration_id = %d
             AND condition_id = %d AND product_color_id = %d AND frame_color_id IS NULL",
            $variant_id, $extra_id, $extra_ids_sorted, $duration_id, $condition_id, $product_color_id
        ));
        
        if ($link) {
            return $link;
        }
        
        // Try without product color
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links
             WHERE variant_id = %d AND extra_id = %d AND extra_ids = %s AND duration_id = %d
             AND condition_id = %d AND product_color_id IS NULL AND frame_color_id = %d",
            $variant_id, $extra_id, $extra_ids_sorted, $duration_id, $condition_id, $frame_color_id
        ));
        
        if ($link) {
            return $link;
        }
        
        // Try without both colors
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links
             WHERE variant_id = %d AND extra_id = %d AND extra_ids = %s AND duration_id = %d
             AND condition_id = %d AND product_color_id IS NULL AND frame_color_id IS NULL",
            $variant_id, $extra_id, $extra_ids_sorted, $duration_id, $condition_id
        ));
        
        if ($link) {
            return $link;
        }
        
        // Try without condition
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links
             WHERE variant_id = %d AND extra_id = %d AND extra_ids = %s AND duration_id = %d
             AND condition_id IS NULL AND product_color_id IS NULL AND frame_color_id IS NULL",
            $variant_id, $extra_id, $extra_ids_sorted, $duration_id
        ));
        
        return $link;
    }
    
    public function ajax_get_variant_images() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        
        global $wpdb;
        
        $variant = $wpdb->get_row($wpdb->prepare(
            "SELECT image_url_1, image_url_2, image_url_3, image_url_4, image_url_5 FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
            $variant_id
        ));
        
        if ($variant) {
            $images = array();
            for ($i = 1; $i <= 5; $i++) {
                $image_field = 'image_url_' . $i;
                if (!empty($variant->$image_field)) {
                    $images[] = $variant->$image_field;
                }
            }
            
            wp_send_json_success(array(
                'images' => $images
            ));
        } else {
            wp_send_json_error('Variant not found');
        }
    }
    
    public function ajax_get_extra_image() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids_array = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        sort($extra_ids_array);
        $extra_ids_raw = implode(',', $extra_ids_array);
        $extra_id = !empty($extra_ids_array) ? $extra_ids_array[0] : 0;
        
        global $wpdb;
        
        $extra = $wpdb->get_row($wpdb->prepare(
            "SELECT image_url FROM {$wpdb->prefix}federwiegen_extras WHERE id = %d",
            $extra_id
        ));
        
        if ($extra) {
            wp_send_json_success(array(
                'image_url' => $extra->image_url ?: ''
            ));
        } else {
            wp_send_json_error('Extra not found');
        }
    }
    
    public function ajax_get_variant_options() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        
        global $wpdb;
        
        // Get variant-specific options
        $variant_options = $wpdb->get_results($wpdb->prepare(
            "SELECT option_type, option_id, available FROM {$wpdb->prefix}federwiegen_variant_options WHERE variant_id = %d",
            $variant_id
        ));
        
        $conditions = array();
        $product_colors = array();
        $frame_colors = array();
        $extras = array();
        
        if (!empty($variant_options)) {
            // Get specific options for this variant
            foreach ($variant_options as $option) {
                switch ($option->option_type) {
                    case 'condition':
                        $condition = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE id = %d",
                            $option->option_id
                        ));
                        if ($condition) {
                            $condition->available = intval($option->available);
                            $conditions[] = $condition;
                        }
                        break;
                    case 'product_color':
                        $color = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d",
                            $option->option_id
                        ));
                        if ($color) {
                            $color->available = intval($option->available);
                            $image = $wpdb->get_var($wpdb->prepare(
                                "SELECT image_url FROM {$wpdb->prefix}federwiegen_color_variant_images WHERE color_id = %d AND variant_id = %d",
                                $color->id,
                                $variant_id
                            ));
                            if ($image !== null) {
                                $color->image_url = $image;
                            }
                            $product_colors[] = $color;
                        }
                        break;
                    case 'frame_color':
                        $color = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d",
                            $option->option_id
                        ));
                        if ($color) {
                            $color->available = intval($option->available);
                            $image = $wpdb->get_var($wpdb->prepare(
                                "SELECT image_url FROM {$wpdb->prefix}federwiegen_color_variant_images WHERE color_id = %d AND variant_id = %d",
                                $color->id,
                                $variant_id
                            ));
                            if ($image !== null) {
                                $color->image_url = $image;
                            }
                            $frame_colors[] = $color;
                        }
                        break;
                    case 'extra':
                        $extra = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}federwiegen_extras WHERE id = %d",
                            $option->option_id
                        ));
                        if ($extra) {
                            $extra->available = intval($option->available);
                            if (!empty($extra->stripe_price_id)) {
                                $amount = StripeService::get_price_amount($extra->stripe_price_id);
                                if (!is_wp_error($amount)) {
                                    $extra->price = $amount;
                                }
                            }
                            $extras[] = $extra;
                        }
                        break;
                }
            }
        } else {
            // No specific options defined, get all available options for the category
            $variant = $wpdb->get_row($wpdb->prepare(
                "SELECT category_id FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
                $variant_id
            ));
            
            if ($variant) {
                $conditions = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE category_id = %d ORDER BY sort_order",
                    $variant->category_id
                ));
                foreach ($conditions as $c) { $c->available = 1; }
                
                $product_colors = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'product' ORDER BY sort_order",
                    $variant->category_id
                ));
                foreach ($product_colors as $c) {
                    $c->available = 1;
                    $image = $wpdb->get_var($wpdb->prepare(
                        "SELECT image_url FROM {$wpdb->prefix}federwiegen_color_variant_images WHERE color_id = %d AND variant_id = %d",
                        $c->id,
                        $variant_id
                    ));
                    if ($image !== null) {
                        $c->image_url = $image;
                    }
                }

                $frame_colors = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'frame' ORDER BY sort_order",
                    $variant->category_id
                ));
                foreach ($frame_colors as $c) {
                    $c->available = 1;
                    $image = $wpdb->get_var($wpdb->prepare(
                        "SELECT image_url FROM {$wpdb->prefix}federwiegen_color_variant_images WHERE color_id = %d AND variant_id = %d",
                        $c->id,
                        $variant_id
                    ));
                    if ($image !== null) {
                        $c->image_url = $image;
                    }
                }

                $extras = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}federwiegen_extras WHERE category_id = %d ORDER BY sort_order",
                    $variant->category_id
                ));
                foreach ($extras as $e) {
                    $e->available = 1;
                    if (!empty($e->stripe_price_id)) {
                        $amount = StripeService::get_price_amount($e->stripe_price_id);
                        if (!is_wp_error($amount)) {
                            $e->price = $amount;
                        }
                    }
                }
            }
        }

        wp_send_json_success(array(
            'conditions' => $conditions,
            'product_colors' => $product_colors,
            'frame_colors' => $frame_colors,
            'extras' => $extras
        ));
    }
    
    public function ajax_submit_order() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $category_id = intval($_POST['category_id']);
        $variant_id = intval($_POST['variant_id']);
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids_array = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        sort($extra_ids_array);
        $extra_ids_raw = implode(',', $extra_ids_array);
        $extra_id = !empty($extra_ids_array) ? $extra_ids_array[0] : 0;
        $duration_id = intval($_POST['duration_id']);
        $condition_id = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
        $frame_color_id = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
        $final_price = floatval($_POST['final_price']);
        $stripe_link = esc_url_raw($_POST['stripe_link']);
        
        global $wpdb;
        
        // Insert order
        $result = $wpdb->insert(
            $wpdb->prefix . 'federwiegen_orders',
            array(
                'category_id' => $category_id,
                'variant_id' => $variant_id,
                'extra_id' => $extra_id,
                'extra_ids' => $extra_ids_raw,
                'duration_id' => $duration_id,
                'condition_id' => $condition_id,
                'product_color_id' => $product_color_id,
                'frame_color_id' => $frame_color_id,
                'final_price' => $final_price,
                'stripe_link' => $stripe_link,
                'customer_name' => '',
                'customer_email' => '',
                'user_ip' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $order_id = $wpdb->insert_id;

            $category_name  = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d",
                $category_id
            ));
            $variant_name   = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
                $variant_id
            ));
            $duration_name  = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}federwiegen_durations WHERE id = %d",
                $duration_id
            ));

            $extras_names = '';
            if (!empty($extra_ids_array)) {
                $placeholders = implode(',', array_fill(0, count($extra_ids_array), '%d'));
                $extras = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}federwiegen_extras WHERE id IN ($placeholders)",
                        ...$extra_ids_array
                    )
                );
                $extras_names = implode(', ', $extras);
            }

            $condition_name = '';
            if ($condition_id) {
                $condition_name = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}federwiegen_conditions WHERE id = %d",
                        $condition_id
                    )
                );
            }

            $product_color_name = '';
            if ($product_color_id) {
                $product_color_name = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d",
                        $product_color_id
                    )
                );
            }

            $frame_color_name = '';
            if ($frame_color_id) {
                $frame_color_name = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d",
                        $frame_color_id
                    )
                );
            }

            $admins       = get_users(['role' => 'administrator']);
            $admin_emails = wp_list_pluck($admins, 'user_email');

            $subject = 'Neue Bestellung #' . $order_id;
            $message = "Es wurde eine neue Bestellung aufgegeben.\n";
            if ($category_name) {
                $message .= 'Kategorie: ' . $category_name . "\n";
            }
            if ($variant_name) {
                $message .= 'Ausführung: ' . $variant_name . "\n";
            }
            if ($duration_name) {
                $message .= 'Mietdauer: ' . $duration_name . "\n";
            }
            if ($condition_name) {
                $message .= 'Zustand: ' . $condition_name . "\n";
            }
            if ($product_color_name) {
                $message .= 'Produktfarbe: ' . $product_color_name . "\n";
            }
            if ($frame_color_name) {
                $message .= 'Gestellfarbe: ' . $frame_color_name . "\n";
            }
            if ($extras_names) {
                $message .= 'Extras: ' . $extras_names . "\n";
            }
            $message .= 'Preis: ' . number_format($final_price, 2, ',', '.') . "€/Monat\n";
            $message .= 'Stripe Link: ' . $stripe_link . "\n";

            if (!empty($admin_emails)) {
                wp_mail($admin_emails, $subject, $message);
            }

            wp_send_json_success(array('order_id' => $order_id));
        } else {
            wp_send_json_error('Failed to save order');
        }
    }
    
    public function ajax_track_interaction() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $category_id = intval($_POST['category_id']);
        $event_type = sanitize_text_field($_POST['event_type']);
        $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids_array = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        sort($extra_ids_array);
        $extra_ids_raw = implode(',', $extra_ids_array);
        $extra_id = !empty($extra_ids_array) ? $extra_ids_array[0] : null;
        $duration_id = isset($_POST['duration_id']) ? intval($_POST['duration_id']) : null;
        $condition_id = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
        $frame_color_id = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'federwiegen_analytics',
            array(
                'category_id' => $category_id,
                'event_type' => $event_type,
                'variant_id' => $variant_id,
                'extra_id' => $extra_id,
                'extra_ids' => $extra_ids_raw,
                'duration_id' => $duration_id,
                'condition_id' => $condition_id,
                'product_color_id' => $product_color_id,
                'frame_color_id' => $frame_color_id,
                'user_ip' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to track interaction');
        }
    }
    
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    private function ensure_notifications_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'federwiegen_notifications';
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                category_id mediumint(9) NOT NULL,
                variant_id mediumint(9) DEFAULT NULL,
                extra_ids text DEFAULT NULL,
                duration_id mediumint(9) DEFAULT NULL,
                condition_id mediumint(9) DEFAULT NULL,
                product_color_id mediumint(9) DEFAULT NULL,
                frame_color_id mediumint(9) DEFAULT NULL,
                email varchar(255) NOT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY category_id (category_id),
                KEY variant_id (variant_id),
                KEY created_at (created_at)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    public function ajax_notify_availability() {
        check_ajax_referer('federwiegen_nonce', 'nonce');

        $this->ensure_notifications_table();

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        if (!$email || !is_email($email)) {
            wp_send_json_error('Invalid email');
        }

        $variant_id       = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : 0;
        $category_id      = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $extra_ids_raw    = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids_array  = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        sort($extra_ids_array);
        $extra_ids_raw    = implode(',', $extra_ids_array);
        $duration_id      = isset($_POST['duration_id']) ? intval($_POST['duration_id']) : 0;
        $condition_id     = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : 0;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : 0;
        $frame_color_id   = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : 0;

        global $wpdb;

        $variant_name        = '';
        $category_name       = '';
        $extras_names        = '';
        $duration_name       = '';
        $condition_name      = '';
        $product_color_name  = '';
        $frame_color_name    = '';

        if ($variant_id) {
            $variant = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT v.name, v.category_id, c.name AS category_name FROM {$wpdb->prefix}federwiegen_variants v LEFT JOIN {$wpdb->prefix}federwiegen_categories c ON v.category_id = c.id WHERE v.id = %d",
                    $variant_id
                )
            );
            if ($variant) {
                $variant_name  = $variant->name;
                $category_id   = $variant->category_id;
                $category_name = $variant->category_name;
            }
        }

        if ($condition_id) {
            $condition_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}federwiegen_conditions WHERE id = %d",
                    $condition_id
                )
            );
        }

        if ($product_color_id) {
            $product_color_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d",
                    $product_color_id
                )
            );
        }

        if ($frame_color_id) {
            $frame_color_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d",
                    $frame_color_id
                )
            );
        }

        if (!empty($extra_ids_array)) {
            $placeholders = implode(',', array_fill(0, count($extra_ids_array), '%d'));
            $extras_names = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}federwiegen_extras WHERE id IN ($placeholders)",
                    ...$extra_ids_array
                )
            );
            $extras_names = implode(', ', $extras_names);
        }

        if (!$category_name && $category_id) {
            $category_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d",
                    $category_id
                )
            );
        }

        if ($duration_id) {
            $duration_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}federwiegen_durations WHERE id = %d",
                    $duration_id
                )
            );
        }

        // Save notification request
        $wpdb->insert(
            $wpdb->prefix . 'federwiegen_notifications',
            [
                'category_id'      => $category_id,
                'variant_id'       => $variant_id,
                'extra_ids'        => $extra_ids_raw,
                'duration_id'      => $duration_id,
                'condition_id'     => $condition_id,
                'product_color_id' => $product_color_id,
                'frame_color_id'   => $frame_color_id,
                'email'            => $email
            ],
            ['%d', '%d', '%s', '%d', '%d', '%d', '%d', '%s']
        );

        $admin_email = get_option('admin_email');
        $subject     = 'Verfügbarkeitsanfrage';
        $message     = "Ein Kunde möchte informiert werden, sobald das Produkt wieder verfügbar ist.\n";
        $message    .= 'E-Mail: ' . $email . "\n";
        if ($category_name) {
            $message .= 'Kategorie: ' . $category_name . "\n";
        }
        if ($variant_name) {
            $message .= 'Ausführung: ' . $variant_name . "\n";
        }
        if ($duration_name) {
            $message .= 'Mietdauer: ' . $duration_name . "\n";
        }
        if ($condition_name) {
            $message .= 'Zustand: ' . $condition_name . "\n";
        }
        if ($product_color_name) {
            $message .= 'Produktfarbe: ' . $product_color_name . "\n";
        }
        if ($frame_color_name) {
            $message .= 'Gestellfarbe: ' . $frame_color_name . "\n";
        }
        if ($extras_names) {
            $message .= 'Extras: ' . $extras_names . "\n";
        }

        wp_mail($admin_email, $subject, $message);

        wp_send_json_success();
    }

    public function ajax_exit_intent_feedback() {
        check_ajax_referer('federwiegen_nonce', 'nonce');

        $option          = isset($_POST['option']) ? sanitize_text_field($_POST['option']) : '';
        $variant_id      = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : 0;
        $extra_ids_raw   = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids_array = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        $duration_id     = isset($_POST['duration_id']) ? intval($_POST['duration_id']) : 0;
        $condition_id    = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : 0;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : 0;
        $frame_color_id   = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : 0;

        global $wpdb;

        $variant_name       = '';
        $duration_name      = '';
        $condition_name     = '';
        $product_color_name = '';
        $frame_color_name   = '';
        $extras_names       = '';

        if ($variant_id) {
            $variant_name = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
                $variant_id
            ));
        }

        if ($duration_id) {
            $duration_name = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}federwiegen_durations WHERE id = %d",
                $duration_id
            ));
        }

        if ($condition_id) {
            $condition_name = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}federwiegen_conditions WHERE id = %d",
                $condition_id
            ));
        }

        if ($product_color_id) {
            $product_color_name = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d",
                $product_color_id
            ));
        }

        if ($frame_color_id) {
            $frame_color_name = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d",
                $frame_color_id
            ));
        }

        if (!empty($extra_ids_array)) {
            $placeholders = implode(',', array_fill(0, count($extra_ids_array), '%d'));
            $extras_names = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}federwiegen_extras WHERE id IN ($placeholders)",
                    ...$extra_ids_array
                )
            );
            $extras_names = implode(', ', $extras_names);
        }

        $admins = get_users(['role' => 'administrator']);
        $emails = wp_list_pluck($admins, 'user_email');

        if (!empty($emails)) {
            $subject = 'Exit-Intent Feedback';
            $message = "Kundenrückmeldung: $option\n";
            if ($variant_name) {
                $message .= 'Ausführung: ' . $variant_name . "\n";
            }
            if ($duration_name) {
                $message .= 'Mietdauer: ' . $duration_name . "\n";
            }
            if ($condition_name) {
                $message .= 'Zustand: ' . $condition_name . "\n";
            }
            if ($product_color_name) {
                $message .= 'Produktfarbe: ' . $product_color_name . "\n";
            }
            if ($frame_color_name) {
                $message .= 'Gestellfarbe: ' . $frame_color_name . "\n";
            }
            if ($extras_names) {
                $message .= 'Extras: ' . $extras_names . "\n";
            }
            wp_mail($emails, $subject, $message);
        }

        wp_send_json_success();
    }
}

add_action('wp_ajax_create_payment_intent', __NAMESPACE__ . '\\federwiegen_create_payment_intent');
add_action('wp_ajax_nopriv_create_payment_intent', __NAMESPACE__ . '\\federwiegen_create_payment_intent');
add_action('wp_ajax_create_subscription', __NAMESPACE__ . '\\federwiegen_create_subscription');
add_action('wp_ajax_nopriv_create_subscription', __NAMESPACE__ . '\\federwiegen_create_subscription');

function federwiegen_create_payment_intent() {
    $init = StripeService::init();
    if (is_wp_error($init)) {
        wp_send_json_error(['message' => $init->get_error_message()]);
    }

    $body = json_decode(file_get_contents('php://input'), true);

    try {
        $preis = intval($body['preis']);
        $beschreibung = sprintf(
            '%s | Extra: %s | Abo: %s | Zustand: %s | Produktfarbe: %s | Gestellfarbe: %s',
            sanitize_text_field($body['produkt']),
            sanitize_text_field($body['extra']),
            sanitize_text_field($body['dauer_name'] ?? $body['dauer']),
            sanitize_text_field($body['zustand']),
            sanitize_text_field($body['produktfarbe'] ?? $body['farbe']),
            sanitize_text_field($body['gestellfarbe'] ?? '')
        );

        $intent = StripeService::create_payment_intent([
            'amount'      => $preis,
            'currency'    => 'eur',
            'description' => $beschreibung,
            'payment_method_types' => ['card', 'paypal', 'sepa_debit'],
            'metadata'    => [
                'produkt'     => $body['produkt'],
                'extra'       => $body['extra'],
                'dauer'       => $body['dauer'],
                'dauer_name'  => $body['dauer_name'] ?? '',
                'zustand'     => $body['zustand'],
                'farbe'       => $body['farbe'],
                'produktfarbe' => $body['produktfarbe'] ?? '',
                'gestellfarbe' => $body['gestellfarbe'] ?? '',
            ],
        ]);
        if (is_wp_error($intent)) {
            throw new \Exception($intent->get_error_message());
        }

        wp_send_json(['client_secret' => $intent->client_secret]);
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

function federwiegen_create_subscription() {
    $init = StripeService::init();
    if (is_wp_error($init)) {
        wp_send_json_error(['message' => $init->get_error_message()]);
    }

    $body = json_decode(file_get_contents('php://input'), true);

    try {
        global $wpdb;
        $duration_id = intval($body['duration_id'] ?? $body['dauer']);
        $variant_id = intval($body['variant_id'] ?? 0);
        $price_id = sanitize_text_field($body['price_id'] ?? '');

        if (!$price_id && $variant_id && $duration_id) {
            $price_id = $wpdb->get_var($wpdb->prepare(
                "SELECT stripe_price_id FROM {$wpdb->prefix}federwiegen_duration_prices WHERE duration_id = %d AND variant_id = %d",
                $duration_id,
                $variant_id
            ));
            if (!$price_id) {
                $price_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT stripe_price_id FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
                    $variant_id
                ));
            }
        }

        if (!$price_id) {
            wp_send_json_error(['message' => 'Keine Preis-ID vorhanden']);
        }

        $customer = StripeService::create_customer([
            'name'  => sanitize_text_field($body['fullname'] ?? ''),
            'email' => sanitize_email($body['email'] ?? ''),
            'phone' => sanitize_text_field($body['phone'] ?? ''),
            'address' => [
                'line1'       => sanitize_text_field($body['street'] ?? ''),
                'postal_code' => sanitize_text_field($body['postal'] ?? ''),
                'city'        => sanitize_text_field($body['city'] ?? ''),
                'country'     => strtoupper(sanitize_text_field($body['country'] ?? '')),
            ],
        ]);

        if (is_wp_error($customer)) {
            throw new \Exception($customer->get_error_message());
        }

        $shipping_price_id = sanitize_text_field($body['shipping_price_id'] ?? '');
        $items = [[ 'price' => $price_id ]];
        if ($shipping_price_id) {
            $items[] = ['price' => $shipping_price_id];
        }

        $subscription = StripeService::create_subscription([
            'customer' => $customer->id,
            'items' => $items,
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => [
                'payment_method_types' => ['card', 'paypal'],
                'payment_method_configuration' => FEDERWIEGEN_PMC_ID,
            ],
            'expand' => ['latest_invoice.payment_intent'],
            'metadata' => [
                'produkt'     => $body['produkt'] ?? '',
                'extra'       => $body['extra'] ?? '',
                'dauer'       => $body['dauer'] ?? '',
                'dauer_name'  => $body['dauer_name'] ?? '',
                'zustand'     => $body['zustand'] ?? '',
                'farbe'       => $body['farbe'] ?? '',
                'produktfarbe' => $body['produktfarbe'] ?? '',
                'gestellfarbe' => $body['gestellfarbe'] ?? '',
                'fullname'    => $body['fullname'] ?? '',
                'email'       => $body['email'] ?? '',
                'phone'       => $body['phone'] ?? '',
                'street'      => $body['street'] ?? '',
                'postal'      => $body['postal'] ?? '',
                'city'        => $body['city'] ?? '',
                'country'     => $body['country'] ?? '',
            ],
        ]);

        if (is_wp_error($subscription)) {
            throw new \Exception($subscription->get_error_message());
        }

        $client_secret = $subscription->latest_invoice->payment_intent->client_secret;

        wp_send_json(['client_secret' => $client_secret]);
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}