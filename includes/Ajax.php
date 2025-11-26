<?php
namespace ProduktVerleih;

class Ajax {
    
    public function ajax_get_product_price() {
        check_ajax_referer('produkt_nonce', 'nonce');
        
        date_default_timezone_set('Europe/Berlin');
        $variant_id = intval($_POST['variant_id']);
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        $extra_id = !empty($extra_ids) ? $extra_ids[0] : 0;
        $duration_id = intval($_POST['duration_id']);
        $condition_id = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
        $frame_color_id = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
        $days = isset($_POST['days']) ? max(1, intval($_POST['days'])) : 1;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date   = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        
        global $wpdb;
        
        $modus = get_option('produkt_betriebsmodus', 'miete');

        $variant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
            $variant_id
        ));

        $base_duration_id = null;
        $base_duration_price = null;
        if ($variant && $modus !== 'kauf') {
            $base_duration = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}produkt_durations WHERE category_id = %d ORDER BY months_minimum ASC, sort_order ASC, id ASC LIMIT 1",
                $variant->category_id
            ));
            if ($base_duration) {
                $base_duration_id = (int) $base_duration->id;
                $base_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT custom_price, stripe_price_id FROM {$wpdb->prefix}produkt_duration_prices WHERE duration_id = %d AND variant_id = %d",
                    $base_duration_id,
                    $variant_id
                ));
                if ($base_row) {
                    if ($base_row->custom_price !== null && floatval($base_row->custom_price) > 0) {
                        $base_duration_price = floatval($base_row->custom_price);
                    } elseif (!empty($base_row->stripe_price_id)) {
                        $amount = StripeService::get_price_amount($base_row->stripe_price_id);
                        if (!is_wp_error($amount)) {
                            $base_duration_price = floatval($amount);
                        }
                    }
                }
            }
        }
        
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
        
        
        
        if ($variant && ($duration || $modus === 'kauf')) {
            $variant_price = 0;
            $used_price_id  = '';
            $weekend_applied = false;

            if ($modus === 'kauf') {
                $variant_price = floatval($variant->verkaufspreis_einmalig);
                $used_price_id = $variant->stripe_price_id;
                $weekend_price = floatval($variant->weekend_price);
                if ($weekend_price > 0 && $start_date && $end_date) {
                    try {
                        $tz = new \DateTimeZone('Europe/Berlin');
                        $s = new \DateTime($start_date, $tz);
                        $e = new \DateTime($end_date, $tz);
                        $period = new \DatePeriod($s, new \DateInterval('P1D'), (clone $e)->modify('+1 day'));
                        $weekend_only = true;
                        foreach ($period as $dt) {
                            $dw = (int)$dt->format('w');
                            if (!in_array($dw, [5,6,0], true)) { $weekend_only = false; break; }
                        }
                        if ($weekend_only) {
                            $variant_price = $weekend_price;
                            $used_price_id = $variant->stripe_weekend_price_id ?: $variant->stripe_price_id;
                            $weekend_applied = true;
                        }
                    } catch (\Exception $ex) {
                        // ignore date errors
                    }
                }
            } else {
                // Determine the Stripe price ID to send to checkout
                $price_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT stripe_price_id, custom_price FROM {$wpdb->prefix}produkt_duration_prices WHERE duration_id = %d AND variant_id = %d",
                    $duration_id,
                    $variant_id
                ));

                $duration_price = 0.0;
                $price_id_to_use = '';
                if ($price_row) {
                    $price_id_to_use = $price_row->stripe_price_id ?: '';
                    if ($price_row->custom_price !== null && floatval($price_row->custom_price) > 0) {
                        $duration_price = floatval($price_row->custom_price);
                    } elseif (!empty($price_row->stripe_price_id)) {
                        $amount = StripeService::get_price_amount($price_row->stripe_price_id);
                        if (is_wp_error($amount)) {
                            wp_send_json_error('Price fetch failed');
                        }
                        $duration_price = floatval($amount);
                    }
                }

                if (empty($price_id_to_use)) {
                    $price_id_to_use = $variant->stripe_price_id;
                }

                $used_price_id = $price_id_to_use;

                // For display use the selected duration price or fallback to the variant Stripe price
                if ($duration_price > 0) {
                    $variant_price = $duration_price;
                } elseif (!empty($variant->stripe_price_id)) {
                    $price_res = StripeService::get_price_amount($variant->stripe_price_id);
                    if (is_wp_error($price_res)) {
                        wp_send_json_error('Price fetch failed');
                    }
                    $variant_price = floatval($price_res);
                } else {
                    $variant_price = floatval($variant->base_price);
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
            if ($modus !== 'kauf') {
                $candidate_base = ($base_duration_price !== null) ? floatval($base_duration_price) : 0.0;
                if ($candidate_base > 0) {
                    $base_price = $candidate_base;
                }
            }

            if ($modus === 'kauf') {
                $final_price = ($base_price + $extras_price) * $days;
                $duration_price = $base_price;
                $original_price = null;
                $discount = 0;
            } else {
                $duration_custom_price = ($price_row && $price_row->custom_price !== null) ? floatval($price_row->custom_price) : null;
                $duration_price = ($duration_custom_price !== null && $duration_custom_price > 0) ? $duration_custom_price : $duration_price;

                if ($duration_price <= 0) {
                    $duration_price = $base_price;
                }

                $original_price = null;
                $discount = 0;
                if ($duration->show_badge && $base_price > 0 && $duration_price > 0 && $duration_price < $base_price) {
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
                'delivery_time' => $variant->delivery_time ?: '',
                'weekend_applied' => $weekend_applied,
                'weekend_price_set' => $variant->weekend_price > 0
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
            "SELECT option_type, option_id, available, stock_available, stock_rented, sku FROM {$wpdb->prefix}produkt_variant_options WHERE variant_id = %d",
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
                            $color->stock_available = intval($option->stock_available);
                            $color->stock_rented = intval($option->stock_rented);
                            $color->sku = $option->sku;
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
                            $color->stock_available = intval($option->stock_available);
                            $color->stock_rented = intval($option->stock_rented);
                            $color->sku = $option->sku;
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
            $base_price = 0.0;
            $base_duration_id = null;
            $base_duration_months = PHP_INT_MAX;
            $base_duration_sort = PHP_INT_MAX;

            $duration_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, show_badge, show_popular, popular_gradient_start, popular_gradient_end, popular_text_color, months_minimum, sort_order FROM {$wpdb->prefix}produkt_durations WHERE category_id = %d",
                    $variant_data->category_id
                )
            );
            $price_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT duration_id, custom_price, stripe_price_id FROM {$wpdb->prefix}produkt_duration_prices WHERE variant_id = %d",
                    $variant_id
                )
            );
            $price_map = [];
            foreach ($price_rows as $row) {
                $duration_id = (int) $row->duration_id;
                $price = null;
                if ($row->custom_price !== null && floatval($row->custom_price) > 0) {
                    $price = floatval($row->custom_price);
                } elseif (!empty($row->stripe_price_id)) {
                    $amount = StripeService::get_price_amount($row->stripe_price_id);
                    if (!is_wp_error($amount)) {
                        $price = floatval($amount);
                    }
                }
                $price_map[$duration_id] = $price;
            }

            foreach ($duration_rows as $d) {
                $months = isset($d->months_minimum) ? (int) $d->months_minimum : PHP_INT_MAX;
                $sort = isset($d->sort_order) ? (int) $d->sort_order : PHP_INT_MAX;
                if (
                    $base_duration_id === null ||
                    $months < $base_duration_months ||
                    ($months === $base_duration_months && (
                        $sort < $base_duration_sort ||
                        ($sort === $base_duration_sort && (int) $d->id < $base_duration_id)
                    ))
                ) {
                    $base_duration_id = (int) $d->id;
                    $base_duration_months = $months;
                    $base_duration_sort = $sort;
                }
            }

            if ($base_duration_id !== null && isset($price_map[$base_duration_id]) && $price_map[$base_duration_id] !== null) {
                $base_price = $price_map[$base_duration_id];
            }

            if ($base_price <= 0 && !empty($variant_data->stripe_price_id)) {
                $amount = StripeService::get_price_amount($variant_data->stripe_price_id);
                if (!is_wp_error($amount)) {
                    $base_price = floatval($amount);
                }
            }
            if ($base_price <= 0) {
                $base_price = floatval($variant_data->base_price);
            }

            foreach ($duration_rows as $d) {
                $price = $base_price;
                if (isset($price_map[$d->id]) && $price_map[$d->id] !== null) {
                    $price = $price_map[$d->id];
                } elseif ($d->id === $base_duration_id && $base_price > 0) {
                    $price = $base_price;
                }
                $discount = 0;
                if ($d->show_badge && $base_price > 0 && $price > 0 && $price < $base_price) {
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
    require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';
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
        $client_info = !empty($body['client_info']) ? wp_json_encode($body['client_info']) : '';

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
        $weekend_tarif     = !empty($body['weekend_tarif']) ? 1 : 0;
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
            'weekend_tarif' => $weekend_tarif,
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
        // Assign custom order number if numbering is enabled
        $order_number = \pv_generate_order_number();
        $insert_data = [
            'category_id'       => $category_id,
            'variant_id'        => $variant_id,
            'extra_id'          => $extra_id,
            'extra_ids'         => $extra_ids_raw,
            'duration_id'       => $duration_id,
            'condition_id'      => $condition_id ?: null,
            'product_color_id'  => $product_color_id ?: null,
            'frame_color_id'    => $frame_color_id ?: null,
            'final_price'       => $final_price,
            'shipping_cost'     => $shipping_cost,
            'shipping_price_id' => $shipping_price_id,
            'mode'              => $modus,
            'start_date'        => $start_date ?: null,
            'end_date'          => $end_date ?: null,
            'inventory_reverted'=> 0,
            'stripe_session_id' => $session->id,
            'amount_total'      => 0,
            'produkt_name'      => $metadata['produkt'],
            'zustand_text'      => $metadata['zustand'],
            'produktfarbe_text' => $metadata['produktfarbe'],
            'gestellfarbe_text' => $metadata['gestellfarbe'],
            'extra_text'        => $metadata['extra'],
            'dauer_text'        => $modus === 'kauf' && empty($metadata['dauer_name'])
                ? ($days . ' Tag' . ($days > 1 ? 'e' : '')
                    . ($start_date && $end_date ? ' (' . $start_date . ' - ' . $end_date . ')' : ''))
                : $metadata['dauer_name'],
            'customer_name'     => '',
            'customer_email'    => $customer_email,
            'user_ip'           => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'        => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'client_info'       => $client_info,
            'discount_amount'   => 0,
            'weekend_tariff'    => $weekend_tarif,
            'status'            => 'offen',
            'created_at'        => current_time('mysql', 1),
        ];
        if ($order_number !== '') {
            $insert_data['order_number'] = $order_number;
        }
        $wpdb->insert(
            $wpdb->prefix . 'produkt_orders',
            $insert_data
        );

        wp_send_json(['url' => $session->url]);
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

function produkt_create_embedded_checkout_session() {
    require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';
    try {
        $init = StripeService::init();
        if (is_wp_error($init)) {
            wp_send_json_error(['message' => $init->get_error_message()]);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $purchase_mode = !empty($body['purchase_mode']) ? sanitize_text_field($body['purchase_mode']) : '';
        $client_info = !empty($body['client_info']) ? wp_json_encode($body['client_info']) : '';
        $modus      = get_option('produkt_betriebsmodus', 'miete');
        $cart_items = [];
        if (!empty($body['cart_items']) && is_array($body['cart_items'])) {
            $cart_items = $body['cart_items'];
        } else {
            $cart_items[] = $body;
        }

        $has_direct_buy = ($purchase_mode === 'direct_buy');

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
            $item_purchase_mode = !empty($it['purchase_mode']) ? sanitize_text_field($it['purchase_mode']) : '';
            if ($item_purchase_mode === 'direct_buy') {
                $has_direct_buy = true;
            }
            $line_items[] = [ 'price' => $pid, 'quantity' => ($item_purchase_mode === 'direct_buy' ? 1 : $it_days) ];

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
                    'quantity' => (($item_purchase_mode === 'direct_buy' || $modus === 'kauf') ? $it_days : 1),
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
                'weekend_tariff'   => !empty($it['weekend_tarif']) ? 1 : 0,
                'metadata' => [
                    'produkt'       => sanitize_text_field($it['produkt'] ?? ''),
                    'extra'         => sanitize_text_field($it['extra'] ?? ''),
                    'dauer_name'    => sanitize_text_field($it['dauer_name'] ?? ''),
                    'zustand'       => sanitize_text_field($it['zustand'] ?? ''),
                    'produktfarbe'  => sanitize_text_field($it['produktfarbe'] ?? ''),
                    'gestellfarbe'  => sanitize_text_field($it['gestellfarbe'] ?? ''),
                    'weekend_tarif' => !empty($it['weekend_tarif']) ? 1 : 0
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

        $checkout_mode = ($has_direct_buy || $modus === 'kauf') ? 'payment' : 'subscription';

        $session_params = [
            'ui_mode'      => 'embedded',
            'line_items'   => $line_items,
            'mode'         => $checkout_mode,
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

        if ($checkout_mode !== 'payment') {
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

        $order_mode = ($checkout_mode === 'payment') ? 'kauf' : $modus;

        if (!empty($session_params['automatic_tax']['enabled'])) {
            if (!empty($session_params['customer'])) {
                $session_params['customer_update'] = ['shipping' => 'auto'];
            }
        }

        $session = \Stripe\Checkout\Session::create($session_params);

        global $wpdb;
        foreach ($orders as $o) {
            $order_number = pv_generate_order_number();
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
                    'mode'             => $order_mode,
                    'start_date'       => $o['start_date'],
                    'end_date'         => $o['end_date'],
                    'inventory_reverted' => 0,
                    'weekend_tariff'   => $o['weekend_tariff'],
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
                    'client_info'      => $client_info,
                    'discount_amount'  => 0,
                    'status'           => 'offen',
                    'order_number'     => $order_number,
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

    require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

    global $wpdb;
    global $order, $order_logs, $order_notes, $sd, $ed, $days, $rental_payments; // make variables available to the template

    $order_array = pv_get_order_by_id($order_id);
    $order = $order_array ? (object) $order_array : null;

    if (!$order) {
        wp_send_json_error('Bestellung nicht gefunden');
    }

    $image_url = pv_get_image_url_by_variant_or_category($order->variant_id ?? 0, $order->category_id ?? 0);
    list($sd, $ed) = pv_get_order_period($order);
    $days = pv_get_order_rental_days($order);

    // Logs abrufen
    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}produkt_order_logs
         WHERE order_id = %d ORDER BY created_at DESC",
        $order_id
    ));

    $payment_info    = pv_calculate_rental_payments($order, $logs);
    $rental_payments = $payment_info['payments'];

    if (!empty($payment_info['log_entries'])) {
        $logs = array_merge($logs, $payment_info['log_entries']);
        usort($logs, function ($a, $b) {
            $ta = isset($a->created_at) ? strtotime($a->created_at) : 0;
            $tb = isset($b->created_at) ? strtotime($b->created_at) : 0;
            return $tb <=> $ta;
        });
    }

    $order_logs = $logs;

    $order_notes = $wpdb->get_results($wpdb->prepare(
        "SELECT id, message, created_at FROM {$wpdb->prefix}produkt_order_logs
         WHERE order_id = %d AND event = 'Notiz' ORDER BY created_at DESC",
        $order_id
    ));

    // HTML generieren (Template einbinden)
    global $order, $order_logs, $order_notes, $sd, $ed, $days; // macht $order im Template sichtbar
    ob_start();
    $order_data = $order;
    include PRODUKT_PLUGIN_PATH . 'admin/dashboard/sidebar-order-details.php';
    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
    ]);
}

add_action('wp_ajax_pv_save_order_note', __NAMESPACE__ . '\\pv_save_order_note');
function pv_save_order_note() {
    check_ajax_referer('produkt_admin_action', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('forbidden', 403);
    }

    $order_id = intval($_POST['order_id'] ?? 0);
    $note = sanitize_textarea_field($_POST['note'] ?? '');
    if (!$order_id || $note === '') {
        wp_send_json_error('missing');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'produkt_order_logs';
    $wpdb->insert($table, [
        'order_id'   => $order_id,
        'event'      => 'Notiz',
        'message'    => $note,
        'created_at' => current_time('mysql'),
    ], [
        '%d', '%s', '%s', '%s'
    ]);

    $note_id = $wpdb->insert_id;
    $date = date_i18n('d.m.Y H:i', current_time('timestamp'));
    wp_send_json_success(['date' => $date, 'id' => $note_id]);
}

add_action('wp_ajax_pv_delete_order_note', __NAMESPACE__ . '\\pv_delete_order_note');
function pv_delete_order_note() {
    check_ajax_referer('produkt_admin_action', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('forbidden', 403);
    }

    $note_id = intval($_POST['note_id'] ?? 0);
    if (!$note_id) {
        wp_send_json_error('missing');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'produkt_order_logs';
    $deleted = $wpdb->delete($table, ['id' => $note_id], ['%d']);

    if ($deleted !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error('db');
    }
}

add_action('wp_ajax_pv_save_customer_note', __NAMESPACE__ . '\\pv_save_customer_note');
function pv_save_customer_note() {
    check_ajax_referer('produkt_admin_action', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('forbidden', 403);
    }

    $customer_id = intval($_POST['customer_id'] ?? 0);
    $note = sanitize_textarea_field($_POST['note'] ?? '');
    if (!$customer_id || $note === '') {
        wp_send_json_error('missing');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'produkt_customer_notes';
    $inserted = $wpdb->insert($table, [
        'customer_id' => $customer_id,
        'message'     => $note,
        'created_at'  => current_time('mysql'),
    ], ['%d','%s','%s']);

    if (!$inserted) {
        wp_send_json_error('db');
    }

    $note_id = $wpdb->insert_id;
    $date = date_i18n('d.m.Y H:i', current_time('timestamp'));
    wp_send_json_success(['date' => $date, 'id' => $note_id]);
}

add_action('wp_ajax_pv_delete_customer_note', __NAMESPACE__ . '\\pv_delete_customer_note');
function pv_delete_customer_note() {
    check_ajax_referer('produkt_admin_action', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('forbidden', 403);
    }

    $note_id = intval($_POST['note_id'] ?? 0);
    if (!$note_id) {
        wp_send_json_error('missing');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'produkt_customer_notes';
    $deleted = $wpdb->delete($table, ['id' => $note_id], ['%d']);

    if ($deleted !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error('db');
    }
}

add_action('wp_ajax_pv_load_customer_logs', __NAMESPACE__ . '\\pv_load_customer_logs');
function pv_load_customer_logs() {
    check_ajax_referer('produkt_admin_action', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('forbidden', 403);
    }

    $order_ids = isset($_POST['order_ids']) ? array_map('intval', (array)$_POST['order_ids']) : [];
    $offset = intval($_POST['offset'] ?? 0);
    $initials = sanitize_text_field($_POST['initials'] ?? '');
    if (!$order_ids) {
        wp_send_json_error('missing');
    }

    global $wpdb;
    $placeholders = implode(',', array_fill(0, count($order_ids), '%d'));
    $order_sql = $wpdb->prepare(
        "SELECT id, order_number, status, mode, final_price, shipping_cost, start_date, created_at, inventory_reverted, dauer_text
         FROM {$wpdb->prefix}produkt_orders WHERE id IN ($placeholders)",
        $order_ids
    );
    $orders = $wpdb->get_results($order_sql, OBJECT_K);

    $logs_sql = $wpdb->prepare(
        "SELECT l.id, l.order_id, o.order_number, l.event, l.message, l.created_at FROM {$wpdb->prefix}produkt_order_logs l JOIN {$wpdb->prefix}produkt_orders o ON l.order_id = o.id WHERE l.order_id IN ($placeholders) ORDER BY l.created_at DESC",
        $order_ids
    );
    $db_logs = $wpdb->get_results($logs_sql);

    $payment_logs = [];
    if ($orders) {
        foreach ($orders as $oid => $order_row) {
            if (empty($order_row->mode) || $order_row->mode === 'kauf') {
                continue;
            }
            $info = pv_calculate_rental_payments($order_row);
            if (empty($info['log_entries'])) {
                continue;
            }
            foreach ($info['log_entries'] as $entry) {
                if (empty($entry->order_number) && !empty($order_row->order_number)) {
                    $entry->order_number = $order_row->order_number;
                }
                $payment_logs[] = $entry;
            }
        }
    }

    $all_logs = $db_logs;
    if ($payment_logs) {
        $all_logs = array_merge($all_logs, $payment_logs);
        usort($all_logs, function ($a, $b) {
            $ta = isset($a->created_at) ? strtotime($a->created_at) : 0;
            $tb = isset($b->created_at) ? strtotime($b->created_at) : 0;
            return $tb <=> $ta;
        });
    }

    $logs = array_slice($all_logs, $offset, 5);

    ob_start();
    $system_events = ['inventory_returned_not_accepted','inventory_returned_accepted','welcome_email_sent','status_updated','checkout_completed','auto_rental_payment'];
    foreach ($logs as $log) {
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
            case 'auto_rental_payment':
                $text = $log->message ?: 'Monatszahlung verbucht.';
                break;
            default:
                $text = $log->message ?: $log->event;
        }
        $order_no = !empty($log->order_number) ? $log->order_number : $log->order_id;
        $date_id = date_i18n('d.m.Y H:i', strtotime($log->created_at)) . ' / #' . $order_no;
        echo '<div class="order-log-entry"><div class="log-avatar">' . esc_html($avatar) . '</div><div class="log-body"><div class="log-date">' . esc_html($date_id) . '</div><div class="log-message">' . esc_html($text) . '</div></div></div>';
    }
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html, 'count' => count($logs)]);
}

add_action('wp_ajax_pv_set_default_shipping', __NAMESPACE__ . '\\pv_set_default_shipping');
function pv_set_default_shipping() {
    check_ajax_referer('produkt_admin_action', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('forbidden', 403);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'produkt_shipping_methods';
    $wpdb->query("UPDATE $table SET is_default = 0");
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $updated = $wpdb->update($table, ['is_default' => 1], ['id' => $id], ['%d'], ['%d']);
        if ($updated === false) {
            wp_send_json_error('db_error');
        }
    }

    wp_send_json_success();
}
