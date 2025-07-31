<?php
namespace ProduktVerleih;

class Ajax {
    
    public function ajax_get_product_price() {
        check_ajax_referer('produkt_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        $extra_id = !empty($extra_ids) ? $extra_ids[0] : 0;
        $duration_id = intval($_POST['duration_id']);
        $condition_id = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
        $frame_color_id = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
        $days = isset($_POST['days']) ? max(1, intval($_POST['days'])) : 1;
        
        global $wpdb;
        
        $variant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
            $variant_id
        ));
        
        $extras = [];
        if (!empty($extra_ids)) {
            $placeholders = implode(',', array_fill(0, count($extra_ids), '%d'));
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}produkt_extras WHERE id IN ($placeholders)",
                ...$extra_ids
            );
            $extras = $wpdb->get_results($query);
        }
        
        $modus = get_option('produkt_betriebsmodus', 'miete');

        $duration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}produkt_durations WHERE id = %d",
            $duration_id
        ));
        
        $condition = null;
        if ($condition_id) {
            $condition = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}produkt_conditions WHERE id = %d",
                $condition_id
            ));
        }
        
        
        
        if ($variant && ($duration || $modus === 'kauf')) {
            $variant_price = 0;
            $used_price_id  = '';

            if ($modus === 'kauf') {
                $variant_price = floatval($variant->verkaufspreis_einmalig);
                $used_price_id = $variant->stripe_price_id;
            } else {
                // Determine the Stripe price ID to send to checkout
                $price_id_to_use = $wpdb->get_var($wpdb->prepare(
                    "SELECT stripe_price_id FROM {$wpdb->prefix}produkt_duration_prices WHERE duration_id = %d AND variant_id = %d",
                    $duration_id,
                    $variant_id
                ));

                if (empty($price_id_to_use)) {
                    $price_id_to_use = $variant->stripe_price_id;
                }

                $used_price_id = $price_id_to_use;

                // For display always use the variant's own Stripe price ID
                if (!empty($variant->stripe_price_id)) {
                    $price_res = StripeService::get_price_amount($variant->stripe_price_id);
                    if (is_wp_error($price_res)) {
                        wp_send_json_error('Price fetch failed');
                    }
                    $variant_price = floatval($price_res);
                } else {
                    $variant_price = floatval($variant->base_price);
                    if ($variant_price <= 0) {
                        $variant_price = floatval($variant->mietpreis_monatlich);
                    }
                }
            }

            $extras_price = 0;
            foreach ($extras as $ex) {
                $pid = ($modus === 'kauf')
                    ? ($ex->stripe_price_id_sale ?: $ex->stripe_price_id)
                    : ($ex->stripe_price_id_rent ?: $ex->stripe_price_id);
                if (!empty($pid)) {
                    $pr = StripeService::get_price_amount($pid);
                    if (is_wp_error($pr)) {
                        wp_send_json_error('Price fetch failed');
                    }
                    $extras_price += floatval($pr);
                } else {
                    $fallback = ($modus === 'kauf') ? ($ex->price_sale ?? 0) : ($ex->price_rent ?? $ex->price);
                    $extras_price += floatval($fallback);
                }
            }

            // Apply condition price modifier to whole price like before
            if ($condition && $condition->price_modifier != 0) {
                $modifier = 1 + floatval($condition->price_modifier);
                $variant_price *= $modifier;
                $extras_price  *= $modifier;
            }

            // Base price for the variant
            $base_price = $variant_price;

            if ($modus === 'kauf') {
                $final_price = ($base_price + $extras_price) * $days;
                $duration_price = $base_price;
                $original_price = null;
                $discount = 0;
            } else {
                $duration_custom_price = $wpdb->get_var($wpdb->prepare(
                    "SELECT custom_price FROM {$wpdb->prefix}produkt_duration_prices WHERE duration_id = %d AND variant_id = %d",
                    $duration_id,
                    $variant_id
                ));
                $duration_price = ($duration_custom_price !== null) ? floatval($duration_custom_price) : $base_price;

                $original_price = null;
                $discount = 0;
                if ($duration->show_badge && $duration_price < $base_price) {
                    $original_price = $base_price;
                    $discount = 1 - ($duration_price / $base_price);
                }

                $final_price = $duration_price + $extras_price;
            }
            $shipping_cost = 0;
            if (!empty($_POST['shipping_price_id'])) {
                $spid = sanitize_text_field($_POST['shipping_price_id']);
                $amt = StripeService::get_price_amount($spid);
                if (!is_wp_error($amt)) {
                    $shipping_cost = floatval($amt);
                }
            } else {
                $shipping = $wpdb->get_row("SELECT price FROM {$wpdb->prefix}produkt_shipping_methods WHERE is_default = 1 LIMIT 1");
                if ($shipping) {
                    $shipping_cost = floatval($shipping->price);
                }
            }
            
            wp_send_json_success(array(
                'base_price'    => $base_price,
                'final_price'   => $final_price,
                'original_price'=> $original_price,
                'discount'      => $discount,
                'shipping_cost' => $shipping_cost,
                'price_id'      => $used_price_id,
                'available'     => $variant->available ? true : false,
                'availability_note' => $variant->availability_note ?: '',
                'delivery_time' => $variant->delivery_time ?: ''
            ));
        } else {
            wp_send_json_error('Invalid selection');
        }
    }
    
    
    public function ajax_get_variant_images() {
        check_ajax_referer('produkt_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        
        global $wpdb;
        
        $variant = $wpdb->get_row($wpdb->prepare(
            "SELECT image_url_1, image_url_2, image_url_3, image_url_4, image_url_5 FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
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
        check_ajax_referer('produkt_nonce', 'nonce');
        
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids_array = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        sort($extra_ids_array);
        $extra_ids_raw = implode(',', $extra_ids_array);
        $extra_id = !empty($extra_ids_array) ? $extra_ids_array[0] : 0;
        
        global $wpdb;
        
        $extra = $wpdb->get_row($wpdb->prepare(
            "SELECT image_url FROM {$wpdb->prefix}produkt_extras WHERE id = %d",
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
        check_ajax_referer('produkt_nonce', 'nonce');

        $variant_id = intval($_POST['variant_id']);

        $modus = get_option('produkt_betriebsmodus', 'miete');
        
        global $wpdb;
        
        // Get variant-specific options
        $variant_options = $wpdb->get_results($wpdb->prepare(
            "SELECT option_type, option_id, available FROM {$wpdb->prefix}produkt_variant_options WHERE variant_id = %d",
            $variant_id
        ));
        
        $conditions = array();
        $product_colors = array();
        $frame_colors = array();
        $extras = array();
        $duration_discounts = array();
        
        if (!empty($variant_options)) {
            // Get specific options for this variant
            foreach ($variant_options as $option) {
                switch ($option->option_type) {
                    case 'condition':
                        $condition = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}produkt_conditions WHERE id = %d",
                            $option->option_id
                        ));
                        if ($condition) {
                            $condition->available = intval($option->available);
                            $conditions[] = $condition;
                        }
                        break;
                    case 'product_color':
                        $color = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}produkt_colors WHERE id = %d",
                            $option->option_id
                        ));
                        if ($color) {
                            $color->available = intval($option->available);
                            $image = $wpdb->get_var($wpdb->prepare(
                                "SELECT image_url FROM {$wpdb->prefix}produkt_color_variant_images WHERE color_id = %d AND variant_id = %d",
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
                            "SELECT * FROM {$wpdb->prefix}produkt_colors WHERE id = %d",
                            $option->option_id
                        ));
                        if ($color) {
                            $color->available = intval($option->available);
                            $image = $wpdb->get_var($wpdb->prepare(
                                "SELECT image_url FROM {$wpdb->prefix}produkt_color_variant_images WHERE color_id = %d AND variant_id = %d",
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
                            "SELECT * FROM {$wpdb->prefix}produkt_extras WHERE id = %d",
                            $option->option_id
                        ));
                        if ($extra) {
                            $pid = $modus === 'kauf'
                                ? ($extra->stripe_price_id_sale ?: $extra->stripe_price_id)
                                : ($extra->stripe_price_id_rent ?: $extra->stripe_price_id);
                            if (!empty($pid)) {
                                $extra_data = [
                                    'id'             => (int) $extra->id,
                                    'name'           => $extra->name,
                                    'price'          => ($modus === 'kauf') ? ($extra->price_sale ?? $extra->price) : ($extra->price_rent ?? $extra->price),
                                    'stripe_price_id'=> $pid,
                                    'image_url'      => $extra->image_url ?? '',
                                    'available'      => intval($option->available),
                                    'stock_available' => isset($extra->stock_available) ? (int) $extra->stock_available : 0,
                                ];
                                $amount = StripeService::get_price_amount($pid);
                                if (!is_wp_error($amount)) {
                                    $extra_data['price'] = $amount;
                                }
                                $extras[] = $extra_data;
                            }
                        }
                        break;
                }
            }
        } else {
            // No specific options defined, get all available options for the category
            $variant = $wpdb->get_row($wpdb->prepare(
                "SELECT category_id FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
                $variant_id
            ));
            
            if ($variant) {
                $conditions = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}produkt_conditions WHERE category_id = %d ORDER BY sort_order",
                    $variant->category_id
                ));
                foreach ($conditions as $c) { $c->available = 1; }
                
                $product_colors = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}produkt_colors WHERE category_id = %d AND color_type = 'product' ORDER BY sort_order",
                    $variant->category_id
                ));
                foreach ($product_colors as $c) {
                    $c->available = 1;
                    $image = $wpdb->get_var($wpdb->prepare(
                        "SELECT image_url FROM {$wpdb->prefix}produkt_color_variant_images WHERE color_id = %d AND variant_id = %d",
                        $c->id,
                        $variant_id
                    ));
                    if ($image !== null) {
                        $c->image_url = $image;
                    }
                }

                $frame_colors = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}produkt_colors WHERE category_id = %d AND color_type = 'frame' ORDER BY sort_order",
                    $variant->category_id
                ));
                foreach ($frame_colors as $c) {
                    $c->available = 1;
                    $image = $wpdb->get_var($wpdb->prepare(
                        "SELECT image_url FROM {$wpdb->prefix}produkt_color_variant_images WHERE color_id = %d AND variant_id = %d",
                        $c->id,
                        $variant_id
                    ));
                    if ($image !== null) {
                        $c->image_url = $image;
                    }
                }

                $extras_rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}produkt_extras WHERE category_id = %d ORDER BY sort_order",
                    $variant->category_id
                ));
                $extras = [];
                foreach ($extras_rows as $e) {
                    $pid = $modus === 'kauf'
                        ? ($e->stripe_price_id_sale ?: $e->stripe_price_id)
                        : ($e->stripe_price_id_rent ?: $e->stripe_price_id);
                    if (empty($pid)) {
                        continue;
                    }
                    $extra_data = [
                        'id'             => (int) $e->id,
                        'name'           => $e->name,
                        'price'          => $e->price,
                        'stripe_price_id'=> $pid,
                        'image_url'      => $e->image_url ?? '',
                        'available'      => intval($e->available) ? 1 : 0,
                        'stock_available'=> intval($e->stock_available),
                    ];
                    $amount = StripeService::get_price_amount($pid);
                    if (!is_wp_error($amount)) {
                        $extra_data['price'] = $amount;
                    }
                    $extras[] = $extra_data;
                }
            }
        }

        // Calculate discounts for each duration based on this variant
        $variant_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
                $variant_id
            )
        );
        if ($variant_data) {
            $base_price = 0;
            if (!empty($variant_data->stripe_price_id)) {
                $amount = StripeService::get_price_amount($variant_data->stripe_price_id);
                if (!is_wp_error($amount)) {
                    $base_price = floatval($amount);
                }
            }
            if ($base_price <= 0) {
                $base_price = floatval($variant_data->base_price);
                if ($base_price <= 0) {
                    $base_price = floatval($variant_data->mietpreis_monatlich);
                }
            }

            $duration_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, show_badge FROM {$wpdb->prefix}produkt_durations WHERE category_id = %d",
                    $variant_data->category_id
                )
            );
            $price_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT duration_id, custom_price FROM {$wpdb->prefix}produkt_duration_prices WHERE variant_id = %d",
                    $variant_id
                )
            );
            $price_map = [];
            foreach ($price_rows as $row) {
                $price_map[(int) $row->duration_id] = $row->custom_price !== null ? floatval($row->custom_price) : null;
            }

            foreach ($duration_rows as $d) {
                $price = $base_price;
                if (isset($price_map[$d->id]) && $price_map[$d->id] !== null) {
                    $price = $price_map[$d->id];
                }
                $discount = 0;
                if ($d->show_badge && $price < $base_price && $base_price > 0) {
                    $discount = 1 - ($price / $base_price);
                }
                $duration_discounts[$d->id] = $discount;
            }
        }

        wp_send_json_success(array(
            'conditions' => $conditions,
            'product_colors' => $product_colors,
            'frame_colors' => $frame_colors,
            'extras' => $extras,
            'duration_discounts' => $duration_discounts
        ));
    }

    public function ajax_get_variant_booked_days() {
        check_ajax_referer('produkt_nonce', 'nonce');
        $variant_id = intval($_POST['variant_id']);
        if (!$variant_id) {
            wp_send_json_success(['days' => []]);
        }

        global $wpdb;
        $available = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT stock_available FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
            $variant_id
        ));
        if ($available > 0) {
            wp_send_json_success(['days' => []]);
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT start_date, end_date FROM {$wpdb->prefix}produkt_orders WHERE variant_id = %d AND mode = 'kauf' AND status IN ('offen','abgeschlossen')",
            $variant_id
        ));
        $days = [];
        foreach ($rows as $r) {
            if ($r->start_date && $r->end_date) {
                $s = strtotime($r->start_date);
                $e = strtotime($r->end_date);
                while ($s <= $e) {
                    $days[] = date('Y-m-d', $s);
                    $s = strtotime('+1 day', $s);
                }
            }
        }
        $days = array_values(array_unique($days));
        wp_send_json_success(['days' => $days]);
    }

    public function ajax_get_extra_booked_days() {
        check_ajax_referer('produkt_nonce', 'nonce');
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        if (empty($extra_ids)) {
            wp_send_json_success(['days' => []]);
        }

        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($extra_ids), '%d'));
        $min_available = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT MIN(stock_available) FROM {$wpdb->prefix}produkt_extras WHERE id IN ($placeholders)",
            ...$extra_ids
        ));
        if ($min_available > 0) {
            wp_send_json_success(['days' => []]);
        }

        $conds = [];
        foreach ($extra_ids as $eid) {
            $conds[] = $wpdb->prepare('(FIND_IN_SET(%d, extra_ids) OR extra_id = %d)', $eid, $eid);
        }
        $where = implode(' OR ', $conds);
        $rows = $wpdb->get_results(
            "SELECT start_date, end_date FROM {$wpdb->prefix}produkt_orders WHERE ($where) AND mode = 'kauf' AND status IN ('offen','abgeschlossen')"
        );
        $days = [];
        foreach ($rows as $r) {
            if ($r->start_date && $r->end_date) {
                $s = strtotime($r->start_date);
                $e = strtotime($r->end_date);
                while ($s <= $e) {
                    $days[] = date('Y-m-d', $s);
                    $s = strtotime('+1 day', $s);
                }
            }
        }
        $days = array_values(array_unique($days));
        wp_send_json_success(['days' => $days]);
    }

    public function ajax_check_extra_availability() {
        check_ajax_referer('produkt_nonce', 'nonce');

        $category_id = intval($_POST['category_id'] ?? 0);
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date   = sanitize_text_field($_POST['end_date'] ?? '');

        if (!$category_id || empty($extra_ids) || !$start_date || !$end_date) {
            wp_send_json_success(['unavailable' => []]);
        }

        global $wpdb;

        $placeholders = implode(',', array_fill(0, count($extra_ids), '%d'));

        $params = array_merge([$category_id], $extra_ids, [$end_date, $start_date]);

        $query = "SELECT e.id FROM {$wpdb->prefix}produkt_extras e
            WHERE e.category_id = %d
              AND e.id IN ($placeholders)
              AND e.stock_available = 0
              AND EXISTS (
                SELECT 1 FROM {$wpdb->prefix}produkt_orders o
                WHERE (FIND_IN_SET(e.id, o.extra_ids) OR o.extra_id = e.id)
                  AND o.mode = 'kauf'
                  AND o.status IN ('offen','abgeschlossen')
                  AND o.start_date <= %s AND o.end_date >= %s
              )";
        $prepared = $wpdb->prepare($query, ...$params);
        $unavailable = $wpdb->get_col($prepared);

        $unavailable = array_map('intval', $unavailable);

        wp_send_json_success(['unavailable' => $unavailable]);
    }
    
    
    public function ajax_track_interaction() {
        check_ajax_referer('produkt_nonce', 'nonce');
        
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
            $wpdb->prefix . 'produkt_analytics',
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
        $table = $wpdb->prefix . 'produkt_notifications';
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
        check_ajax_referer('produkt_nonce', 'nonce');

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
                    "SELECT v.name, v.category_id, c.name AS category_name FROM {$wpdb->prefix}produkt_variants v LEFT JOIN {$wpdb->prefix}produkt_categories c ON v.category_id = c.id WHERE v.id = %d",
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
                    "SELECT name FROM {$wpdb->prefix}produkt_conditions WHERE id = %d",
                    $condition_id
                )
            );
        }

        if ($product_color_id) {
            $product_color_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}produkt_colors WHERE id = %d",
                    $product_color_id
                )
            );
        }

        if ($frame_color_id) {
            $frame_color_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}produkt_colors WHERE id = %d",
                    $frame_color_id
                )
            );
        }

        if (!empty($extra_ids_array)) {
            $placeholders = implode(',', array_fill(0, count($extra_ids_array), '%d'));
            $extras_names = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}produkt_extras WHERE id IN ($placeholders)",
                    ...$extra_ids_array
                )
            );
            $extras_names = implode(', ', $extras_names);
        }

        if (!$category_name && $category_id) {
            $category_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                    $category_id
                )
            );
        }

        if ($duration_id) {
            $duration_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}produkt_durations WHERE id = %d",
                    $duration_id
                )
            );
        }

        // Save notification request
        $wpdb->insert(
            $wpdb->prefix . 'produkt_notifications',
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
            $message .= 'Produkt: ' . $category_name . "\n";
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
        check_ajax_referer('produkt_nonce', 'nonce');

        $option          = isset($_POST['option']) ? sanitize_text_field($_POST['option']) : '';
        $user_email      = isset($_POST['user_email']) ? sanitize_text_field($_POST['user_email']) : '';
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
                "SELECT name FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
                $variant_id
            ));
        }

        if ($duration_id) {
            $duration_name = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}produkt_durations WHERE id = %d",
                $duration_id
            ));
        }

        if ($condition_id) {
            $condition_name = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}produkt_conditions WHERE id = %d",
                $condition_id
            ));
        }

        if ($product_color_id) {
            $product_color_name = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}produkt_colors WHERE id = %d",
                $product_color_id
            ));
        }

        if ($frame_color_id) {
            $frame_color_name = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}produkt_colors WHERE id = %d",
                $frame_color_id
            ));
        }

        if (!empty($extra_ids_array)) {
            $placeholders = implode(',', array_fill(0, count($extra_ids_array), '%d'));
            $extras_names = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}produkt_extras WHERE id IN ($placeholders)",
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
            if ($user_email) {
                $message .= 'E-Mail: ' . $user_email . "\n";
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
            wp_mail($emails, $subject, $message);
        }

        wp_send_json_success();
    }

}

add_action('wp_ajax_create_payment_intent', __NAMESPACE__ . '\\produkt_create_payment_intent');
add_action('wp_ajax_nopriv_create_payment_intent', __NAMESPACE__ . '\\produkt_create_payment_intent');
add_action('wp_ajax_create_subscription', __NAMESPACE__ . '\\produkt_create_subscription');
add_action('wp_ajax_nopriv_create_subscription', __NAMESPACE__ . '\\produkt_create_subscription');
add_action('wp_ajax_create_checkout_session', __NAMESPACE__ . '\\produkt_create_checkout_session');
add_action('wp_ajax_nopriv_create_checkout_session', __NAMESPACE__ . '\\produkt_create_checkout_session');
add_action('wp_ajax_create_embedded_checkout_session', __NAMESPACE__ . '\\produkt_create_embedded_checkout_session');
add_action('wp_ajax_nopriv_create_embedded_checkout_session', __NAMESPACE__ . '\\produkt_create_embedded_checkout_session');

function produkt_create_payment_intent() {
    $init = StripeService::init();
    if (is_wp_error($init)) {
        wp_send_json_error(['message' => $init->get_error_message()]);
    }

    $body = json_decode(file_get_contents('php://input'), true);

    $days       = isset($body['days']) ? max(1, intval($body['days'])) : 1;
    $start_date = sanitize_text_field($body['start_date'] ?? '');
    $end_date   = sanitize_text_field($body['end_date'] ?? '');

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

function produkt_create_subscription() {
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
                "SELECT stripe_price_id FROM {$wpdb->prefix}produkt_duration_prices WHERE duration_id = %d AND variant_id = %d",
                $duration_id,
                $variant_id
            ));
            if (!$price_id) {
                $price_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT stripe_price_id FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
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

        Database::upsert_customer_record_by_email(
            sanitize_email($body['email'] ?? ''),
            $customer->id,
            sanitize_text_field($body['fullname'] ?? ''),
            sanitize_text_field($body['phone'] ?? ''),
            [
                'street'      => $body['street'] ?? '',
                'postal_code' => $body['postal'] ?? '',
                'city'        => $body['city'] ?? '',
                'country'     => $body['country'] ?? '',
            ]
        );
        $user = get_user_by('email', sanitize_email($body['email'] ?? ''));
        if ($user) {
            update_user_meta($user->ID, 'stripe_customer_id', $customer->id);
        }

        global $wpdb;
        $shipping_price_id = $wpdb->get_var("SELECT stripe_price_id FROM {$wpdb->prefix}produkt_shipping_methods WHERE is_default = 1 LIMIT 1");
        $extra_ids_raw = sanitize_text_field($body['extra_ids'] ?? '');
        $extra_ids = array_filter(array_map('intval', explode(',', $extra_ids_raw)));

        $items = [[ 'price' => $price_id, 'quantity' => 1 ]];
        $sub_params = [
            'customer' => $customer->id,
            'items' => $items,
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => [
                'payment_method_types' => ['card', 'paypal'],
                'payment_method_options' => [
                    'paypal' => [
                        'payment_method_configuration' => StripeService::get_payment_method_configuration_id(),
                    ],
                ],
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
                'start_date'  => $start_date,
                'end_date'    => $end_date,
                'days'        => $days,
            ],
        ];

        $mode = get_option('produkt_betriebsmodus', 'miete');
        if ($mode === 'kauf') {
            $cust_email = sanitize_email($body['email'] ?? '');
            $current_user = wp_get_current_user();
            if ($current_user && $current_user->exists()) {
                $cust_email = $current_user->user_email;
            }
            $fullname   = sanitize_text_field($body['fullname'] ?? '');
            $stripe_customer_id = Database::get_stripe_customer_id_by_email($cust_email);
            if (!$stripe_customer_id) {
                $customer = \Stripe\Customer::create([
                    'email' => $cust_email,
                    'name'  => $fullname,
                ]);
                $stripe_customer_id = $customer->id;
                Database::update_stripe_customer_id_by_email($cust_email, $stripe_customer_id);
                Database::upsert_customer_record_by_email(
                    $cust_email,
                    $stripe_customer_id,
                    $fullname,
                    $phone,
                    [
                        'street'      => $body['street'] ?? '',
                        'postal_code' => $body['postal'] ?? '',
                        'city'        => $body['city'] ?? '',
                        'country'     => $body['country'] ?? '',
                    ]
                );
                $user = get_user_by('email', $cust_email);
                if ($user) {
                    update_user_meta($user->ID, 'stripe_customer_id', $stripe_customer_id);
                }
            }

            $session = StripeService::create_checkout_session_for_sale([
                'price_id'    => $price_id,
                'quantity'    => $days,
                'customer'    => $stripe_customer_id,
                'metadata'    => $sub_params['metadata'],
                'reference'   => $variant_id ? "var-$variant_id" : null,
                'success_url' => get_option('produkt_success_url', home_url('/danke')),
                'cancel_url'  => get_option('produkt_cancel_url', home_url('/abbrechen')),
            ]);

            if (is_wp_error($session)) {
                throw new \Exception($session->get_error_message());
            }

            wp_send_json(['url' => $session->url]);
        } else {
            $subscription = StripeService::create_subscription($sub_params);

            if (is_wp_error($subscription)) {
                throw new \Exception($subscription->get_error_message());
            }

            $client_secret = $subscription->latest_invoice->payment_intent->client_secret;

            wp_send_json(['client_secret' => $client_secret]);
        }
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

function produkt_create_checkout_session() {
    try {
        $init = StripeService::init();
        if (is_wp_error($init)) {
            wp_send_json_error(['message' => $init->get_error_message()]);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $days       = isset($body['days']) ? max(1, intval($body['days'])) : 1;
        $start_date = sanitize_text_field($body['start_date'] ?? '');
        $end_date   = sanitize_text_field($body['end_date'] ?? '');
        $modus      = get_option('produkt_betriebsmodus', 'miete');
        $price_id = sanitize_text_field($body['price_id'] ?? '');
        if (!$price_id) {
            wp_send_json_error(['message' => 'Keine Preis-ID vorhanden']);
        }

        global $wpdb;
        $shipping_price_id = $wpdb->get_var("SELECT stripe_price_id FROM {$wpdb->prefix}produkt_shipping_methods WHERE is_default = 1 LIMIT 1");
        $shipping_cost = 0;
        if ($shipping_price_id) {
            $sc = StripeService::get_price_amount($shipping_price_id);
            if (!is_wp_error($sc)) {
                $shipping_cost = floatval($sc);
            }
        }
        $extra_ids_raw     = sanitize_text_field($body['extra_ids'] ?? '');
        $extra_ids         = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        $category_id       = intval($body['category_id'] ?? 0);
        $variant_id        = intval($body['variant_id'] ?? 0);
        $duration_id       = intval($body['duration_id'] ?? 0);
        $condition_id      = intval($body['condition_id'] ?? 0);
        $product_color_id  = intval($body['product_color_id'] ?? 0);
        $frame_color_id    = intval($body['frame_color_id'] ?? 0);
        $final_price       = floatval($body['final_price'] ?? 0);
        $customer_email    = sanitize_email($body['email'] ?? '');
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->exists()) {
            $customer_email = $current_user->user_email;
        }
        $fullname          = sanitize_text_field($body['fullname'] ?? '');
        $phone             = sanitize_text_field($body['phone'] ?? '');

        $metadata = [
            'produkt'       => sanitize_text_field($body['produkt'] ?? ''),
            'extra'         => sanitize_text_field($body['extra'] ?? ''),
            'dauer'         => sanitize_text_field($body['dauer'] ?? ''),
            'dauer_name'    => sanitize_text_field($body['dauer_name'] ?? ''),
            'zustand'       => sanitize_text_field($body['zustand'] ?? ''),
            'produktfarbe'  => sanitize_text_field($body['produktfarbe'] ?? ''),
            'gestellfarbe'  => sanitize_text_field($body['gestellfarbe'] ?? ''),
            'email'         => $customer_email,
            'user_ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'    => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'start_date'    => $start_date,
            'end_date'      => $end_date,
            'days'          => $days,
        ];
        if ($shipping_price_id) {
            $metadata['shipping_price_id'] = $shipping_price_id;
        }

        $line_items = [[
            'price'    => $price_id,
            'quantity' => $days,
        ]];

        $modus = get_option('produkt_betriebsmodus', 'miete');
        if ($modus === 'kauf') {
            $stripe_customer_id = null;
            if ($customer_email) {
                $stripe_customer_id = Database::get_stripe_customer_id_by_email($customer_email);
                $fullname = sanitize_text_field($body['fullname'] ?? '');
                if (!$stripe_customer_id) {
                $customer = \Stripe\Customer::create([
                    'email' => $customer_email,
                    'name'  => $fullname,
                    'phone' => $phone,
                ]);
                $stripe_customer_id = $customer->id;
                Database::update_stripe_customer_id_by_email($customer_email, $stripe_customer_id);
                Database::upsert_customer_record_by_email(
                    $customer_email,
                    $stripe_customer_id,
                    $fullname,
                    $phone,
                    [
                        'street'      => $body['street'] ?? '',
                        'postal_code' => $body['postal'] ?? '',
                        'city'        => $body['city'] ?? '',
                        'country'     => $body['country'] ?? '',
                    ]
                );
                $user = get_user_by('email', $customer_email);
                if ($user) {
                    update_user_meta($user->ID, 'stripe_customer_id', $stripe_customer_id);
                }
            }
            }
            $session = StripeService::create_checkout_session_for_sale([
                'price_id'    => $price_id,
                'quantity'    => $days,
                'customer'    => $stripe_customer_id,
                'metadata'    => $metadata,
                'reference'   => $variant_id ? "var-$variant_id" : null,
                'success_url' => get_option('produkt_success_url', home_url('/danke')),
                'cancel_url'  => get_option('produkt_cancel_url', home_url('/abbrechen')),
            ]);
            if (is_wp_error($session)) {
                throw new \Exception($session->get_error_message());
            }

            wp_send_json(['url' => $session->url]);
        }

        if (!empty($extra_ids)) {
            global $wpdb;
            $placeholders = implode(',', array_fill(0, count($extra_ids), '%d'));
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT stripe_price_id, stripe_price_id_sale, stripe_price_id_rent FROM {$wpdb->prefix}produkt_extras WHERE id IN ($placeholders)",
                    ...$extra_ids
                )
            );
            foreach ($rows as $row) {
                $price = $modus === 'kauf'
                    ? ($row->stripe_price_id_sale ?: $row->stripe_price_id)
                    : ($row->stripe_price_id_rent ?: $row->stripe_price_id);
                if (!empty($price)) {
                    $line_items[] = [
                        'price'    => $price,
                        'quantity' => ($modus === 'kauf') ? $days : 1,
                    ];
                }
            }
        }

        if ($shipping_price_id) {
            $line_items[] = [
                'price'    => $shipping_price_id,
                'quantity' => 1,
            ];
        }

        $tos_url = get_option('produkt_tos_url', home_url('/agb'));
        $custom_text = [];
        $agb_msg = get_option('produkt_ct_agb', '');
        if ($agb_msg !== '') {
            $custom_text['terms_of_service_acceptance'] = ['message' => $agb_msg];
        } else {
            $custom_text['terms_of_service_acceptance'] = [
                'message' => 'Ich akzeptiere die [Allgemeinen Geschäftsbedingungen (AGB)](' . esc_url($tos_url) . ')',
            ];
        }
        $ct_shipping = get_option('produkt_ct_shipping', '');
        if ($ct_shipping !== '') {
            $custom_text['shipping_address'] = [ 'message' => $ct_shipping ];
        }
        $ct_submit = get_option('produkt_ct_submit', '');
        if ($ct_submit !== '') {
            $custom_text['submit'] = [ 'message' => $ct_submit ];
        }
        $ct_after = get_option('produkt_ct_after_submit', '');
        if ($ct_after !== '') {
            $custom_text['after_submit'] = [ 'message' => $ct_after ];
        }

        $session_args = [
            'mode'                     => ($modus === 'kauf' ? 'payment' : 'subscription'),
            'payment_method_types'     => ['card', 'paypal'],
            'allow_promotion_codes'    => true,
            'line_items'               => $line_items,
            'metadata'                 => $metadata,
            'billing_address_collection' => 'required',
            'shipping_address_collection' => ['allowed_countries' => ['DE']],
            'phone_number_collection'     => [
                'enabled' => true,
            ],
            'success_url'              => add_query_arg('session_id', '{CHECKOUT_SESSION_ID}', get_option('produkt_success_url', home_url('/danke'))),
            'cancel_url'               => get_option('produkt_cancel_url', home_url('/abbrechen')),
            'consent_collection'       => [
                'terms_of_service' => 'required',
            ],
            'custom_text'              => $custom_text,
        ];
        if ($modus !== 'kauf') {
            $session_args['subscription_data'] = [ 'metadata' => $metadata ];
        }
        if (!empty($customer_email)) {
            // Prüfe, ob Stripe-Kunde mit dieser E-Mail bereits existiert
            $stripe_customer_id = Database::get_stripe_customer_id_by_email($customer_email);

            if (!$stripe_customer_id) {
                // Erstelle neuen Stripe-Kunden
                $customer = \Stripe\Customer::create([
                    'email' => $customer_email,
                    'name'  => $fullname,
                ]);

                $stripe_customer_id = $customer->id;

                // Speichere die ID in deiner Kundentabelle
                Database::update_stripe_customer_id_by_email($customer_email, $stripe_customer_id);
                Database::upsert_customer_record_by_email(
                    $customer_email,
                    $stripe_customer_id,
                    $fullname,
                    $phone,
                    [
                        'street'      => $body['street'] ?? '',
                        'postal_code' => $body['postal'] ?? '',
                        'city'        => $body['city'] ?? '',
                        'country'     => $body['country'] ?? '',
                    ]
                );
                $user = get_user_by('email', $customer_email);
                if ($user) {
                    update_user_meta($user->ID, 'stripe_customer_id', $stripe_customer_id);
                }
            }

            if ($stripe_customer_id) {
                $session_args['customer'] = $stripe_customer_id;
            } else {
                $session_args['customer_creation'] = 'always';
            }
        } else {
            $session_args['customer_creation'] = 'always';
        }

        $session = \Stripe\Checkout\Session::create($session_args);

        // store preliminary order with status "offen"
        global $wpdb;
        $extra_id = !empty($extra_ids) ? $extra_ids[0] : 0;
        $wpdb->insert(
            $wpdb->prefix . 'produkt_orders',
            [
                'category_id'      => $category_id,
                'variant_id'       => $variant_id,
                'extra_id'         => $extra_id,
                'extra_ids'        => $extra_ids_raw,
                'duration_id'      => $duration_id,
                'condition_id'     => $condition_id ?: null,
                'product_color_id' => $product_color_id ?: null,
                'frame_color_id'   => $frame_color_id ?: null,
                'final_price'      => $final_price,
                'shipping_cost'    => $shipping_cost,
                'shipping_price_id'=> $shipping_price_id,
                'mode'             => $modus,
                'start_date'       => $start_date ?: null,
                'end_date'         => $end_date ?: null,
                'inventory_reverted' => 0,
                'stripe_session_id'=> $session->id,
                'amount_total'     => 0,
                'produkt_name'     => $metadata['produkt'],
                'zustand_text'     => $metadata['zustand'],
                'produktfarbe_text'=> $metadata['produktfarbe'],
                'gestellfarbe_text'=> $metadata['gestellfarbe'],
                'extra_text'       => $metadata['extra'],
                'dauer_text'       => $modus === 'kauf' && empty($metadata['dauer_name'])
                    ? ($days . ' Tag' . ($days > 1 ? 'e' : '')
                        . ($start_date && $end_date ? ' (' . $start_date . ' - ' . $end_date . ')' : ''))
                    : $metadata['dauer_name'],
                'customer_name'    => '',
                'customer_email'   => $customer_email,
                'user_ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent'       => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'discount_amount'  => 0,
                'status'           => 'offen',
                'created_at'       => current_time('mysql', 1)
            ]
        );

        wp_send_json(['url' => $session->url]);
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

function produkt_create_embedded_checkout_session() {
    try {
        $init = StripeService::init();
        if (is_wp_error($init)) {
            wp_send_json_error(['message' => $init->get_error_message()]);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $modus      = get_option('produkt_betriebsmodus', 'miete');
        $cart_items = [];
        if (!empty($body['cart_items']) && is_array($body['cart_items'])) {
            $cart_items = $body['cart_items'];
        } else {
            $cart_items[] = $body;
        }

        $customer_email   = sanitize_email($body['email'] ?? '');
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->exists()) {
            $customer_email = $current_user->user_email;
        }
        $fullname         = sanitize_text_field($body['fullname'] ?? '');
        $phone            = sanitize_text_field($body['phone'] ?? '');

        $shipping_price_id = '';
        $shipping_cost = 0;
        if (!empty($body['shipping_price_id'])) {
            $shipping_price_id = sanitize_text_field($body['shipping_price_id']);
            $amt = StripeService::get_price_amount($shipping_price_id);
            if (!is_wp_error($amt)) {
                $shipping_cost = floatval($amt);
            }
        }

        $line_items = [];
        $orders = [];
        foreach ($cart_items as $it) {
            $it_days = isset($it['days']) ? max(1, intval($it['days'])) : 1;
            $it_start = sanitize_text_field($it['start_date'] ?? '');
            $it_end   = sanitize_text_field($it['end_date'] ?? '');
            $pid      = sanitize_text_field($it['price_id'] ?? '');
            if (!$pid) { continue; }
            $line_items[] = [ 'price' => $pid, 'quantity' => $it_days ];

            $extra_price_ids = [];
            if (!empty($it['extra_price_ids'])) {
                if (is_array($it['extra_price_ids'])) {
                    $extra_price_ids = array_map('sanitize_text_field', $it['extra_price_ids']);
                } elseif (is_string($it['extra_price_ids'])) {
                    $extra_price_ids = array_map('sanitize_text_field', explode(',', $it['extra_price_ids']));
                }
                $extra_price_ids = array_filter($extra_price_ids);
            }

            $extra_ids_raw = sanitize_text_field($it['extra_ids'] ?? '');
            $extra_ids = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
            if (empty($extra_price_ids) && !empty($extra_ids)) {
                global $wpdb;
                $placeholders = implode(',', array_fill(0, count($extra_ids), '%d'));
                $rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT stripe_price_id, stripe_price_id_sale, stripe_price_id_rent FROM {$wpdb->prefix}produkt_extras WHERE id IN ($placeholders)",
                        ...$extra_ids
                    )
                );
                foreach ($rows as $row) {
                    $eid = $modus === 'kauf'
                        ? ($row->stripe_price_id_sale ?: $row->stripe_price_id)
                        : ($row->stripe_price_id_rent ?: $row->stripe_price_id);
                    if (!empty($eid)) {
                        $extra_price_ids[] = $eid;
                    }
                }
            }

            foreach ($extra_price_ids as $extra_price_id) {
                $line_items[] = [
                    'price'    => $extra_price_id,
                    'quantity' => ($modus === 'kauf') ? $it_days : 1,
                ];
            }

            $orders[] = [
                'category_id'      => intval($it['category_id'] ?? 0),
                'variant_id'       => intval($it['variant_id'] ?? 0),
                'extra_id'         => !empty($extra_ids) ? $extra_ids[0] : 0,
                'extra_ids'        => $extra_ids_raw,
                'duration_id'      => intval($it['duration_id'] ?? 0),
                'condition_id'     => intval($it['condition_id'] ?? 0) ?: null,
                'product_color_id' => intval($it['product_color_id'] ?? 0) ?: null,
                'frame_color_id'   => intval($it['frame_color_id'] ?? 0) ?: null,
                'final_price'      => floatval($it['final_price'] ?? 0),
                'start_date'       => $it_start ?: null,
                'end_date'         => $it_end ?: null,
                'days'             => $it_days,
                'metadata' => [
                    'produkt'       => sanitize_text_field($it['produkt'] ?? ''),
                    'extra'         => sanitize_text_field($it['extra'] ?? ''),
                    'dauer_name'    => sanitize_text_field($it['dauer_name'] ?? ''),
                    'zustand'       => sanitize_text_field($it['zustand'] ?? ''),
                    'produktfarbe'  => sanitize_text_field($it['produktfarbe'] ?? ''),
                    'gestellfarbe'  => sanitize_text_field($it['gestellfarbe'] ?? '')
                ]
            ];
        }
        if ($shipping_price_id) {
            $line_items[] = [
                'price'    => $shipping_price_id,
                'quantity' => 1,
            ];
        }
        $metadata = [
            'cart_count' => count($orders),
            'email'      => $customer_email,
            'user_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ];
        if ($shipping_price_id) {
            $metadata['shipping_price_id'] = $shipping_price_id;
        }

        $tos_url = get_option('produkt_tos_url', home_url('/agb'));
        $custom_text = [];
        $agb_msg = get_option('produkt_ct_agb', '');
        if ($agb_msg !== '') {
            $custom_text['terms_of_service_acceptance'] = ['message' => $agb_msg];
        } else {
            $custom_text['terms_of_service_acceptance'] = [
                'message' => 'Ich akzeptiere die [Allgemeinen Geschäftsbedingungen (AGB)](' . esc_url($tos_url) . ')',
            ];
        }
        $ct_shipping = get_option('produkt_ct_shipping', '');
        if ($ct_shipping !== '') {
            $custom_text['shipping_address'] = [ 'message' => $ct_shipping ];
        }
        $ct_submit = get_option('produkt_ct_submit', '');
        if ($ct_submit !== '') {
            $custom_text['submit'] = [ 'message' => $ct_submit ];
        }
        $ct_after = get_option('produkt_ct_after_submit', '');
        if ($ct_after !== '') {
            $custom_text['after_submit'] = [ 'message' => $ct_after ];
        }

        $session_params = [
            'ui_mode'      => 'embedded',
            'line_items'   => $line_items,
            'mode'         => ($modus === 'kauf' ? 'payment' : 'subscription'),
            'allow_promotion_codes' => true,
            'return_url'   => add_query_arg('session_id', '{CHECKOUT_SESSION_ID}', get_option('produkt_success_url', home_url('/danke'))),
            'automatic_tax'=> ['enabled' => true],
            'metadata'     => $metadata,
            'billing_address_collection' => 'required',
            'shipping_address_collection' => [ 'allowed_countries' => ['DE'] ],
            'phone_number_collection' => [
                'enabled' => true,
            ],
            'consent_collection' => [
                'terms_of_service' => 'required',
            ],
            'custom_text' => $custom_text,
        ];

        if ($modus !== 'kauf') {
            $session_params['subscription_data'] = [ 'metadata' => $metadata ];
        } else {
            if (!empty($customer_email)) {
                $stripe_customer_id = Database::get_stripe_customer_id_by_email($customer_email);
                if (!$stripe_customer_id) {
                    $customer = \Stripe\Customer::create([
                        'email' => $customer_email,
                        'name'  => $fullname,
                        'phone' => $phone,
                    ]);
                    $stripe_customer_id = $customer->id;
                    Database::update_stripe_customer_id_by_email($customer_email, $stripe_customer_id);
                    Database::upsert_customer_record_by_email(
                        $customer_email,
                        $stripe_customer_id,
                        $fullname,
                        $phone,
                        [
                            'street'      => $body['street'] ?? '',
                            'postal_code' => $body['postal'] ?? '',
                            'city'        => $body['city'] ?? '',
                            'country'     => $body['country'] ?? '',
                        ]
                    );
                    $user = get_user_by('email', $customer_email);
                    if ($user) {
                        update_user_meta($user->ID, 'stripe_customer_id', $stripe_customer_id);
                    }
                }
            }

            if (!empty($stripe_customer_id)) {
                $session_params['customer'] = $stripe_customer_id;
            } else {
                $session_params['customer_creation'] = 'always';
            }
        }

        if (!empty($session_params['automatic_tax']['enabled'])) {
            if (!empty($session_params['customer'])) {
                $session_params['customer_update'] = ['shipping' => 'auto'];
            }
        }

        $session = \Stripe\Checkout\Session::create($session_params);

        global $wpdb;
        foreach ($orders as $o) {
            $wpdb->insert(
                $wpdb->prefix . 'produkt_orders',
                [
                    'category_id'      => $o['category_id'],
                    'variant_id'       => $o['variant_id'],
                    'extra_id'         => $o['extra_id'],
                    'extra_ids'        => $o['extra_ids'],
                    'duration_id'      => $o['duration_id'],
                    'condition_id'     => $o['condition_id'],
                    'product_color_id' => $o['product_color_id'],
                    'frame_color_id'   => $o['frame_color_id'],
                    'final_price'      => $o['final_price'],
                    'shipping_cost'    => $shipping_cost,
                    'shipping_price_id'=> $shipping_price_id,
                    'mode'             => $modus,
                    'start_date'       => $o['start_date'],
                    'end_date'         => $o['end_date'],
                    'inventory_reverted' => 0,
                    'stripe_session_id'=> $session->id,
                    'amount_total'     => 0,
                    'produkt_name'     => $o['metadata']['produkt'],
                    'zustand_text'     => $o['metadata']['zustand'],
                    'produktfarbe_text'=> $o['metadata']['produktfarbe'],
                    'gestellfarbe_text'=> $o['metadata']['gestellfarbe'],
                    'extra_text'       => $o['metadata']['extra'],
                    'dauer_text'       => $modus === 'kauf' && empty($o['metadata']['dauer_name'])
                        ? ($o['days'] . ' Tag' . ($o['days'] > 1 ? 'e' : '')
                            . ($o['start_date'] && $o['end_date'] ? ' (' . $o['start_date'] . ' - ' . $o['end_date'] . ')' : ''))
                        : $o['metadata']['dauer_name'],
                    'customer_name'    => '',
                    'customer_email'   => $customer_email,
                    'user_ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent'       => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                    'discount_amount'  => 0,
                    'status'           => 'offen',
                    'created_at'       => current_time('mysql', 1)
                ]
            );
        }

        wp_send_json(['client_secret' => $session->client_secret]);
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

add_action('wp_ajax_get_checkout_session_status', __NAMESPACE__ . '\\produkt_get_checkout_session_status');
add_action('wp_ajax_nopriv_get_checkout_session_status', __NAMESPACE__ . '\\produkt_get_checkout_session_status');

function produkt_get_checkout_session_status() {
    try {
        $init = StripeService::init();
        if (is_wp_error($init)) {
            wp_send_json_error(['message' => $init->get_error_message()]);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $session_id = sanitize_text_field($body['session_id'] ?? '');
        if (!$session_id) {
            wp_send_json_error(['message' => 'Keine Session-ID vorhanden']);
        }

        $session = \Stripe\Checkout\Session::retrieve($session_id);
        $email = $session->customer_details->email ?? '';
        wp_send_json(['status' => $session->status, 'customer_email' => $email]);
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

add_action('wp_ajax_produkt_block_day', __NAMESPACE__ . '\\produkt_block_day');
add_action('wp_ajax_produkt_unblock_day', __NAMESPACE__ . '\\produkt_unblock_day');

function produkt_block_day() {
    check_ajax_referer('produkt_nonce', 'nonce');
    $date = sanitize_text_field($_POST['date'] ?? '');
    if (!$date) {
        wp_send_json_error('no date');
    }
    global $wpdb;
    $table = $wpdb->prefix . 'produkt_blocked_days';
    $wpdb->replace($table, ['day' => $date], ['%s']);
    wp_send_json_success();
}

function produkt_unblock_day() {
    check_ajax_referer('produkt_nonce', 'nonce');
    $date = sanitize_text_field($_POST['date'] ?? '');
    if (!$date) {
        wp_send_json_error('no date');
    }
    global $wpdb;
    $table = $wpdb->prefix . 'produkt_blocked_days';
    $wpdb->delete($table, ['day' => $date], ['%s']);
    wp_send_json_success();
}

function produkt_confirm_return() {
    check_ajax_referer('produkt_admin_action', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('forbidden', 403);
    }
    $order_id = intval($_POST['order_id'] ?? 0);
    if (!$order_id) {
        wp_send_json_error('missing');
    }
    $res = \ProduktVerleih\Database::process_inventory_return($order_id);
    if ($res) {
        wp_send_json_success();
    } else {
        wp_send_json_error('failed');
    }
}
add_action('wp_ajax_confirm_return', __NAMESPACE__ . '\\produkt_confirm_return');
add_action('wp_ajax_get_order_details', __NAMESPACE__ . '\\produkt_get_order_details');

function produkt_get_order_details() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Nicht autorisiert']);
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

    if (!$order_id) {
        wp_send_json_error(['message' => 'Keine Order-ID übermittelt']);
    }

    require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';
    $order_array = pv_get_order_by_id($order_id);
    $order = $order_array ? (object) $order_array : null;

    if (!$order) {
        wp_send_json_error(['message' => 'Bestellung nicht gefunden']);
    }

    // Beispiel-Ausgabe (kann später erweitert werden)
    ob_start();
    ?>
    <div class="order-details-inner">
        <h3>Details zur Bestellung #<?php echo esc_html($order->id); ?></h3>
        <p><strong>Kunde:</strong> <?php echo esc_html($order->customer_name ?? '–'); ?></p>
<p><strong>E-Mail:</strong> <?php echo esc_html($order->customer_email ?? '–'); ?></p>
        <p><strong>Produkt:</strong> <?php echo esc_html($order->category_name ?: $order->produkt_name ?: '–'); ?></p>
        <?php if (!empty($order->variant_name)) : ?>
        <p><strong>Ausführung:</strong> <?php echo esc_html($order->variant_name); ?></p>
        <?php endif; ?>
        <p><strong>Extras:</strong> <?php echo esc_html($order->extra_names ?: '–'); ?></p>
        <p><strong>Mietzeitraum:</strong> <?php echo esc_html($order->start_date); ?> – <?php echo esc_html($order->end_date); ?></p>
        <p><strong>Bestellt am:</strong> <?php echo date_i18n('d.m.Y H:i', strtotime($order->created_at)); ?></p>
    </div>
    <?php

    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}

add_action('wp_ajax_pv_load_order_sidebar_details', __NAMESPACE__ . '\\pv_load_order_sidebar_details');
function pv_load_order_sidebar_details() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Nicht autorisiert');
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error('Keine Bestell-ID übergeben');
    }

    global $wpdb;
    global $order; // make \$order available globally for the template

    // Bestellung abrufen (inkl. Produktname, Varianten, Extras, Farben etc.)
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT
            o.*,
            c.name AS category_name,
            COALESCE(v.name, o.produkt_name) AS variant_name,
            COALESCE(NULLIF(GROUP_CONCAT(e.name SEPARATOR ', '), ''), o.extra_text) AS extra_names,
            COALESCE(dur.name, o.dauer_text) AS dauer_text,
            con.name AS condition_name,
            pc.name AS produktfarbe_text,
            fc.name AS gestellfarbe_text,
            s.name AS shipping_name
         FROM {$wpdb->prefix}produkt_orders o
         LEFT JOIN {$wpdb->prefix}produkt_categories c ON c.id = o.category_id
         LEFT JOIN {$wpdb->prefix}produkt_variants v ON v.id = o.variant_id
         LEFT JOIN {$wpdb->prefix}produkt_extras e ON FIND_IN_SET(e.id, o.extra_ids)
         LEFT JOIN {$wpdb->prefix}produkt_durations dur ON dur.id = o.duration_id
         LEFT JOIN {$wpdb->prefix}produkt_conditions con ON con.id = o.condition_id
         LEFT JOIN {$wpdb->prefix}produkt_colors pc ON pc.id = o.product_color_id
         LEFT JOIN {$wpdb->prefix}produkt_colors fc ON fc.id = o.frame_color_id
        LEFT JOIN {$wpdb->prefix}produkt_shipping_methods s
            ON s.stripe_price_id = COALESCE(o.shipping_price_id, c.shipping_price_id)
         WHERE o.id = %d
         GROUP BY o.id",
        $order_id
    ));

    if (!$order) {
        wp_send_json_error('Bestellung nicht gefunden');
    }

    // 🔍 Debug-Ausgabe (in debug.log sichtbar!)
    error_log('DEBUG $order in Sidebar: ' . print_r($order, true));

    // Logs abrufen
    $logs = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}produkt_order_logs
        WHERE order_id = %d ORDER BY created_at DESC
    ", $order_id));

    // HTML generieren (Template einbinden)
    global $order; // macht $order im Template sichtbar
    ob_start();
$order_data = $order;
include PRODUKT_PLUGIN_PATH . 'admin/dashboard/sidebar-order-details.php';
$html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
    ]);
}
