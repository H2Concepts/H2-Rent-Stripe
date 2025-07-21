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
        
        
        
        if ($variant && $duration) {
            $variant_price = 0;
            $used_price_id  = '';

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

            // Base price for the variant
            $base_price = $variant_price;

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
            $shipping_cost = 0;
            $shipping = $wpdb->get_row("SELECT price FROM {$wpdb->prefix}produkt_shipping_methods WHERE is_default = 1 LIMIT 1");
            if ($shipping) {
                $shipping_cost = floatval($shipping->price);
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
                            $extra_data = [
                                'id'             => (int) $extra->id,
                                'name'           => $extra->name,
                                'price'          => $extra->price,
                                'stripe_price_id'=> $extra->stripe_price_id,
                                'image_url'      => $extra->image_url ?? '',
                                'available'      => intval($option->available),
                            ];
                            if (!empty($extra->stripe_price_id)) {
                                $amount = StripeService::get_price_amount($extra->stripe_price_id);
                                if (!is_wp_error($amount)) {
                                    $extra_data['price'] = $amount;
                                }
                            }
                            $extras[] = $extra_data;
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
                    $extra_data = [
                        'id'             => (int) $e->id,
                        'name'           => $e->name,
                        'price'          => $e->price,
                        'stripe_price_id'=> $e->stripe_price_id,
                        'image_url'      => $e->image_url ?? '',
                        'available'      => 1,
                    ];
                    if (!empty($e->stripe_price_id)) {
                        $amount = StripeService::get_price_amount($e->stripe_price_id);
                        if (!is_wp_error($amount)) {
                            $extra_data['price'] = $amount;
                        }
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

        global $wpdb;
        $shipping_price_id = $wpdb->get_var("SELECT stripe_price_id FROM {$wpdb->prefix}produkt_shipping_methods WHERE is_default = 1 LIMIT 1");
        $extra_ids_raw = sanitize_text_field($body['extra_ids'] ?? '');
        $extra_ids = array_filter(array_map('intval', explode(',', $extra_ids_raw)));

        $items = [[ 'price' => $price_id, 'quantity' => 1 ]];
        $sub_params = [
            'customer' => $customer->id,
            'items' => $items,
            'add_invoice_items' => [],
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
            ],
        ];

        $subscription = StripeService::create_subscription($sub_params);

        if (is_wp_error($subscription)) {
            throw new \Exception($subscription->get_error_message());
        }

        $client_secret = $subscription->latest_invoice->payment_intent->client_secret;

        wp_send_json(['client_secret' => $client_secret]);
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
        ];
        if ($shipping_price_id) {
            $metadata['shipping_price_id'] = $shipping_price_id;
        }

        $line_items = [[
            'price'    => $price_id,
            'quantity' => 1,
        ]];

        $modus = get_option('produkt_betriebsmodus', 'miete');
        if ($modus === 'kauf') {
            $session = StripeService::create_checkout_session_for_sale([
                'price_id'       => $price_id,
                'quantity'       => 1,
                'customer_email' => $customer_email,
                'metadata'       => $metadata,
                'reference'      => $variant_id ? "var-$variant_id" : null,
                'success_url'    => get_option('produkt_success_url', home_url('/danke')),
                'cancel_url'     => get_option('produkt_cancel_url', home_url('/abbrechen')),
            ]);
            if (is_wp_error($session)) {
                throw new \Exception($session->get_error_message());
            }

            wp_send_json(['url' => $session->url]);
        }

        if (!empty($extra_ids)) {
            global $wpdb;
            $placeholders = implode(',', array_fill(0, count($extra_ids), '%d'));
            $extra_prices = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT stripe_price_id FROM {$wpdb->prefix}produkt_extras WHERE id IN ($placeholders)",
                    ...$extra_ids
                )
            );
            foreach ($extra_prices as $price) {
                if (!empty($price)) {
                    $line_items[] = [
                        'price'    => $price,
                        'quantity' => 1,
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
            'mode'                     => 'subscription',
            'payment_method_types'     => ['card', 'paypal'],
            'allow_promotion_codes'    => true,
            'line_items'               => $line_items,
            'subscription_data'        => [ 'metadata' => $metadata ],
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
        if (!empty($customer_email)) {
            $session_args['customer_email'] = $customer_email;
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
                'stripe_session_id'=> $session->id,
                'amount_total'     => 0,
                'produkt_name'     => $metadata['produkt'],
                'zustand_text'     => $metadata['zustand'],
                'produktfarbe_text'=> $metadata['produktfarbe'],
                'gestellfarbe_text'=> $metadata['gestellfarbe'],
                'extra_text'       => $metadata['extra'],
                'dauer_text'       => $metadata['dauer_name'],
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
        error_log('Stripe Checkout Session Error: ' . $e->getMessage());
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
        $price_id = sanitize_text_field($body['price_id'] ?? '');
        if (!$price_id) {
            wp_send_json_error(['message' => 'Keine Preis-ID vorhanden']);
        }

        $extra_price_ids = [];
        if (!empty($body['extra_price_ids'])) {
            if (is_array($body['extra_price_ids'])) {
                $extra_price_ids = array_map('sanitize_text_field', $body['extra_price_ids']);
            } elseif (is_string($body['extra_price_ids'])) {
                $extra_price_ids = array_map('sanitize_text_field', explode(',', $body['extra_price_ids']));
            }
            $extra_price_ids = array_filter($extra_price_ids);
        }

        $extra_ids_raw = sanitize_text_field($body['extra_ids'] ?? '');
        $extra_ids = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        $category_id      = intval($body['category_id'] ?? 0);
        $variant_id       = intval($body['variant_id'] ?? 0);
        $duration_id      = intval($body['duration_id'] ?? 0);
        $condition_id     = intval($body['condition_id'] ?? 0);
        $product_color_id = intval($body['product_color_id'] ?? 0);
        $frame_color_id   = intval($body['frame_color_id'] ?? 0);
        $final_price      = floatval($body['final_price'] ?? 0);
        $customer_email   = sanitize_email($body['email'] ?? '');

        $shipping_price_id = '';
        $shipping_cost = 0;
        if (!empty($body['shipping_price_id'])) {
            $shipping_price_id = sanitize_text_field($body['shipping_price_id']);
            $amt = StripeService::get_price_amount($shipping_price_id);
            if (!is_wp_error($amt)) {
                $shipping_cost = floatval($amt);
            }
        }

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
        ];
        if ($shipping_price_id) {
            $metadata['shipping_price_id'] = $shipping_price_id;
        }

        $line_items = [[
            'price'    => $price_id,
            'quantity' => 1,
        ]];

        foreach ($extra_price_ids as $extra_price_id) {
            $line_items[] = [
                'price'    => $extra_price_id,
                'quantity' => 1,
            ];
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

        $session = \Stripe\Checkout\Session::create([
            'ui_mode'      => 'embedded',
            'line_items'   => $line_items,
            'mode'         => 'subscription',
            'allow_promotion_codes' => true,
            'return_url'   => add_query_arg('session_id', '{CHECKOUT_SESSION_ID}', get_option('produkt_success_url', home_url('/danke'))),
            'automatic_tax'=> ['enabled' => true],
            'metadata'     => $metadata,
            'subscription_data' => [
                'metadata' => $metadata
            ],
            'billing_address_collection' => 'required',
            'shipping_address_collection' => [ 'allowed_countries' => ['DE'] ],
            'phone_number_collection' => [
                'enabled' => true,
            ],
            'consent_collection' => [
                'terms_of_service' => 'required',
            ],
            'custom_text' => $custom_text,
        ]);

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
                'stripe_session_id'=> $session->id,
                'amount_total'     => 0,
                'produkt_name'     => $metadata['produkt'],
                'zustand_text'     => $metadata['zustand'],
                'produktfarbe_text'=> $metadata['produktfarbe'],
                'gestellfarbe_text'=> $metadata['gestellfarbe'],
                'extra_text'       => $metadata['extra'],
                'dauer_text'       => $metadata['dauer_name'],
                'customer_name'    => '',
                'customer_email'   => $customer_email,
                'user_ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent'       => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'discount_amount'  => 0,
                'status'           => 'offen',
                'created_at'       => current_time('mysql', 1)
            ]
        );

        wp_send_json(['client_secret' => $session->client_secret]);
    } catch (\Exception $e) {
        error_log('Stripe Embedded Checkout Error: ' . $e->getMessage());
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
        error_log('Stripe Session Status Error: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
