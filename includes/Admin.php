<?php
namespace FederwiegenVerleih;

class Admin {
    public function add_admin_menu() {
        $branding = $this->get_branding_settings();
        $menu_title = $branding['plugin_name'] ?? 'Federwiegen';
        
        add_menu_page(
            $branding['plugin_name'] ?? 'Rent Plugin',
            $menu_title,
            'manage_options',
            'federwiegen-verleih',
            array($this, 'admin_page'),
            'dashicons-heart',
            30
        );
        
        // Submenu: Kategorien
        add_submenu_page(
            'federwiegen-verleih',
            'Kategorien',
            'Kategorien',
            'manage_options',
            'federwiegen-categories',
            array($this, 'categories_page')
        );
        
        // Submenu: Produktverwaltung
        add_submenu_page(
            'federwiegen-verleih',
            'Ausführungen',
            'Ausführungen',
            'manage_options',
            'federwiegen-variants',
            array($this, 'variants_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Extras',
            'Extras',
            'manage_options',
            'federwiegen-extras',
            array($this, 'extras_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Mietdauer',
            'Mietdauer',
            'manage_options',
            'federwiegen-durations',
            array($this, 'durations_page')
        );
        
        // New submenu items
        add_submenu_page(
            'federwiegen-verleih',
            'Zustand',
            'Zustand',
            'manage_options',
            'federwiegen-conditions',
            array($this, 'conditions_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Farben',
            'Farben',
            'manage_options',
            'federwiegen-colors',
            array($this, 'colors_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Ausführungs-Optionen',
            'Ausführungs-Optionen',
            'manage_options',
            'federwiegen-variant-options',
            array($this, 'variant_options_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Stripe Links',
            'Stripe Links',
            'manage_options',
            'federwiegen-links',
            array($this, 'links_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Bestellungen',
            'Bestellungen',
            'manage_options',
            'federwiegen-orders',
            array($this, 'orders_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Analytics',
            'Analytics',
            'manage_options',
            'federwiegen-analytics',
            array($this, 'analytics_page')
        );

        // New settings menu with Stripe integration tab
        add_submenu_page(
            'federwiegen-verleih',
            'Einstellungen',
            'Einstellungen',
            'manage_options',
            'federwiegen-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_frontend_assets() {
        global $post;

        if (!is_singular()) {
            return;
        }

        $content = $post->post_content ?? '';
        if (!has_shortcode($content, 'federwiegen_product') && !has_shortcode($content, 'stripe_elements_form')) {
            return;
        }

        wp_enqueue_style('federwiegen-style', FEDERWIEGEN_PLUGIN_URL . 'assets/style.css', array(), FEDERWIEGEN_VERSION);
        wp_enqueue_script('federwiegen-script', FEDERWIEGEN_PLUGIN_URL . 'assets/script.js', array('jquery'), FEDERWIEGEN_VERSION, true);

        global $wpdb;
        $pattern = '/\[federwiegen_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
        preg_match($pattern, $post->post_content, $matches);
        $category_shortcode = isset($matches[1]) ? $matches[1] : '';

        $category = null;
        if (!empty($category_shortcode)) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE shortcode = %s",
                $category_shortcode
            ));
        }
        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order LIMIT 1");
        }

        $popup_settings = get_option('federwiegen_popup_settings', []);
        $options = [];
        if (!empty($popup_settings['options'])) {
            $opts = array_filter(array_map('trim', explode("\n", $popup_settings['options'])));
            $options = array_values($opts);
        }
        $popup_enabled = isset($popup_settings['enabled']) ? intval($popup_settings['enabled']) : 0;
        $popup_days    = isset($popup_settings['days']) ? intval($popup_settings['days']) : 7;

        wp_localize_script('federwiegen-script', 'federwiegen_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('federwiegen_nonce'),
            'price_period' => $category->price_period ?? 'month',
            'price_label' => $category->price_label ?? 'Monatlicher Mietpreis',
            'vat_included' => isset($category->vat_included) ? intval($category->vat_included) : 0,
            'popup_settings' => array(
                'enabled' => $popup_enabled,
                'days'    => $popup_days,
                'title'   => $popup_settings['title'] ?? '',
                'content' => wpautop($popup_settings['content'] ?? ''),
                'options' => $options
            )
        ));
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'federwiegen') !== false) {
            wp_enqueue_style('federwiegen-admin-style', FEDERWIEGEN_PLUGIN_URL . 'assets/admin-style.css', array(), FEDERWIEGEN_VERSION);
            wp_enqueue_script('federwiegen-admin-script', FEDERWIEGEN_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), FEDERWIEGEN_VERSION, true);
            
            // Enqueue WordPress media scripts for image upload
            wp_enqueue_media();
            
            // Enqueue Chart.js for analytics
            if (strpos($hook, 'federwiegen-analytics') !== false) {
                wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
            }
        }
    }
    
    private function get_branding_settings() {
        global $wpdb;
        
        $settings = array();
        $results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}federwiegen_branding");
        foreach ($results as $result) {
            $settings[$result->setting_key] = $result->setting_value;
        }
        
        return $settings;
    }

    private function load_template(string $slug, array $vars = []) {
        if (!empty($vars)) {
            extract($vars);
        }
        include FEDERWIEGEN_PLUGIN_PATH . "admin/{$slug}-page.php";
    }
    
    public function custom_admin_footer($text) {
        $branding = $this->get_branding_settings();
        
        if (isset($_GET['page']) && strpos($_GET['page'], 'federwiegen') !== false) {
            $footer_text = $branding['footer_text'] ?? 'Powered by H2 Concepts';
            $company_url = $branding['company_url'] ?? '#';
            $company_name = $branding['company_name'] ?? 'H2 Concepts';
            
            return '<span id="footer-thankyou">' . $footer_text . ' | <a href="' . esc_url($company_url) . '" target="_blank">' . esc_html($company_name) . '</a></span>';
        }
        
        return $text;
    }
    
    public function custom_admin_styles() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'federwiegen') === false) {
            return;
        }
        $branding = $this->get_branding_settings();
        $primary_color = $branding['admin_color_primary'] ?? '#5f7f5f';
        $secondary_color = $branding['admin_color_secondary'] ?? '#4a674a';
        
        echo '<style>
            .button-primary {
                background: ' . esc_attr($primary_color) . ' !important;
                border-color: ' . esc_attr($secondary_color) . ' !important;
            }
            
            .button-primary:hover {
                background: ' . esc_attr($secondary_color) . ' !important;
            }
            
            .nav-tab-active {
                background: ' . esc_attr($primary_color) . ';
                color: #fff;
                border-color: ' . esc_attr($secondary_color) . ';
            }
        </style>';
       }

    /**
     * Verify nonce and user capabilities for admin form submissions.
     */
    public static function verify_admin_action($nonce_field = 'federwiegen_admin_nonce') {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'h2-concepts'));
        }
        check_admin_referer('federwiegen_admin_action', $nonce_field);
    }
    
    public function admin_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/main-page.php';
    }
    
    public function categories_page() {
        global $wpdb;

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

        if (isset($_POST['submit_category'])) {
            self::verify_admin_action();
            $name = sanitize_text_field($_POST['name']);
            $shortcode = sanitize_text_field($_POST['shortcode']);
            $meta_title = sanitize_text_field($_POST['meta_title']);
            $meta_description = sanitize_textarea_field($_POST['meta_description']);
            $product_title = sanitize_text_field($_POST['product_title']);
            $product_description = wp_kses_post($_POST['product_description']);
            $default_image = esc_url_raw($_POST['default_image']);
            $features_title = sanitize_text_field($_POST['features_title']);
            $feature_1_icon = esc_url_raw($_POST['feature_1_icon']);
            $feature_1_title = sanitize_text_field($_POST['feature_1_title']);
            $feature_1_description = sanitize_textarea_field($_POST['feature_1_description']);
            $feature_2_icon = esc_url_raw($_POST['feature_2_icon']);
            $feature_2_title = sanitize_text_field($_POST['feature_2_title']);
            $feature_2_description = sanitize_textarea_field($_POST['feature_2_description']);
            $feature_3_icon = esc_url_raw($_POST['feature_3_icon']);
            $feature_3_title = sanitize_text_field($_POST['feature_3_title']);
            $feature_3_description = sanitize_textarea_field($_POST['feature_3_description']);
            $button_text = sanitize_text_field($_POST['button_text']);
            $button_icon = esc_url_raw($_POST['button_icon']);
            $payment_icons = isset($_POST['payment_icons']) ? array_map('sanitize_text_field', (array) $_POST['payment_icons']) : array();
            $payment_icons = implode(',', $payment_icons);
            $shipping_provider = sanitize_text_field($_POST['shipping_provider'] ?? '');
            $shipping_label = sanitize_text_field($_POST['shipping_label']);
            $price_label = sanitize_text_field($_POST['price_label']);
            $price_period = sanitize_text_field($_POST['price_period']);
            $vat_included = isset($_POST['vat_included']) ? 1 : 0;
            $layout_style = sanitize_text_field($_POST['layout_style']);
            $duration_tooltip = sanitize_textarea_field($_POST['duration_tooltip']);
            $condition_tooltip = sanitize_textarea_field($_POST['condition_tooltip']);
            $show_tooltips = isset($_POST['show_tooltips']) ? 1 : 0;
            $show_rating = isset($_POST['show_rating']) ? 1 : 0;
            $rating_value_input = isset($_POST['rating_value']) ? str_replace(',', '.', $_POST['rating_value']) : '';
            $rating_value = $rating_value_input !== '' ? min(5, max(0, floatval($rating_value_input))) : 0;
            $rating_link = esc_url_raw($_POST['rating_link']);
            $sort_order = intval($_POST['sort_order']);

            $table_name = $wpdb->prefix . 'federwiegen_categories';

            if (isset($_POST['id']) && $_POST['id']) {
                $result = $wpdb->update(
                    $table_name,
                    [
                        'name' => $name,
                        'shortcode' => $shortcode,
                        'meta_title' => $meta_title,
                        'meta_description' => $meta_description,
                        'product_title' => $product_title,
                        'product_description' => $product_description,
                        'default_image' => $default_image,
                        'features_title' => $features_title,
                        'feature_1_icon' => $feature_1_icon,
                        'feature_1_title' => $feature_1_title,
                        'feature_1_description' => $feature_1_description,
                        'feature_2_icon' => $feature_2_icon,
                        'feature_2_title' => $feature_2_title,
                        'feature_2_description' => $feature_2_description,
                        'feature_3_icon' => $feature_3_icon,
                        'feature_3_title' => $feature_3_title,
                        'feature_3_description' => $feature_3_description,
                        'button_text' => $button_text,
                        'button_icon' => $button_icon,
                        'payment_icons' => $payment_icons,
                        'shipping_provider' => $shipping_provider,
                        'price_label' => $price_label,
                        'shipping_label' => $shipping_label,
                        'price_period' => $price_period,
                        'vat_included' => $vat_included,
                        'layout_style' => $layout_style,
                        'duration_tooltip' => $duration_tooltip,
                        'condition_tooltip' => $condition_tooltip,
                        'show_tooltips' => $show_tooltips,
                        'show_rating' => $show_rating,
                        'rating_value' => $rating_value,
                        'rating_link' => $rating_link,
                        'sort_order' => $sort_order,
                    ],
                    ['id' => intval($_POST['id'])],
                    array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%d','%d','%f','%s','%d'),
                );

                if ($result !== false) {
                    echo '<div class="notice notice-success"><p>✅ Kategorie erfolgreich aktualisiert!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>❌ Fehler beim Aktualisieren: ' . esc_html($wpdb->last_error) . '</p></div>';
                }
            } else {
                $result = $wpdb->insert(
                    $table_name,
                    [
                        'name' => $name,
                        'shortcode' => $shortcode,
                        'meta_title' => $meta_title,
                        'meta_description' => $meta_description,
                        'product_title' => $product_title,
                        'product_description' => $product_description,
                        'default_image' => $default_image,
                        'features_title' => $features_title,
                        'feature_1_icon' => $feature_1_icon,
                        'feature_1_title' => $feature_1_title,
                        'feature_1_description' => $feature_1_description,
                        'feature_2_icon' => $feature_2_icon,
                        'feature_2_title' => $feature_2_title,
                        'feature_2_description' => $feature_2_description,
                        'feature_3_icon' => $feature_3_icon,
                        'feature_3_title' => $feature_3_title,
                        'feature_3_description' => $feature_3_description,
                        'button_text' => $button_text,
                        'button_icon' => $button_icon,
                        'payment_icons' => $payment_icons,
                        'shipping_provider' => $shipping_provider,
                        'price_label' => $price_label,
                        'shipping_label' => $shipping_label,
                        'price_period' => $price_period,
                        'vat_included' => $vat_included,
                        'layout_style' => $layout_style,
                        'duration_tooltip' => $duration_tooltip,
                        'condition_tooltip' => $condition_tooltip,
                        'show_tooltips' => $show_tooltips,
                        'show_rating' => $show_rating,
                        'rating_value' => $rating_value,
                        'rating_link' => $rating_link,
                        'sort_order' => $sort_order,
                    ],
                    array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%d','%d','%f','%s','%d')
                );

                if ($result !== false) {
                    echo '<div class="notice notice-success"><p>✅ Kategorie erfolgreich hinzugefügt!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
                }
            }
        }

        if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'federwiegen_admin_action')) {
            $category_id = intval($_GET['delete']);
            $table_name = $wpdb->prefix . 'federwiegen_categories';
            $result = $wpdb->delete($table_name, ['id' => $category_id], ['%d']);
            if ($result !== false) {
                echo '<div class="notice notice-success"><p>✅ Kategorie gelöscht!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
            }
        }

        $edit_item = null;
        if (isset($_GET['edit'])) {
            $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d", intval($_GET['edit'])));
        }

        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order, name");

        $branding = [];
        $branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}federwiegen_branding");
        foreach ($branding_results as $result) {
            $branding[$result->setting_key] = $result->setting_value;
        }

        $this->load_template('categories', compact('active_tab', 'edit_item', 'categories', 'branding'));
    }
    
    public function variants_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/variants-page.php';
    }
    
    public function extras_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/extras-page.php';
    }
    
    public function durations_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/durations-page.php';
    }
    
    public function conditions_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/conditions-page.php';
    }
    
    public function colors_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/colors-page.php';
    }
    
    public function variant_options_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/variant-options-page.php';
    }
    
    public function links_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/links-page.php';
    }

    public function orders_page() {
        global $wpdb;
        $notice = '';

        // Handle single deletion via GET
        if (isset($_GET['delete_order'])) {
            $order_id = intval($_GET['delete_order']);
            $deleted = $wpdb->delete(
                $wpdb->prefix . 'federwiegen_orders',
                array('id' => $order_id),
                array('%d')
            );

            $notice = ($deleted !== false) ? 'deleted' : 'error';
        }

        // Handle bulk deletion via POST
        if (!empty($_POST['delete_orders']) && is_array($_POST['delete_orders'])) {
            $ids = array_map('intval', (array) $_POST['delete_orders']);
            if ($ids) {
                $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                $query = $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}federwiegen_orders WHERE id IN ($placeholders)",
                    ...$ids
                );
                $deleted = $wpdb->query($query);
                $notice = ($deleted !== false) ? 'bulk_deleted' : 'error';
            }
        }

        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order, name");
        $selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;

        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');

        $current_category = null;
        if ($selected_category > 0) {
            $current_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d", $selected_category));
        }

        $where_conditions = ["o.created_at BETWEEN %s AND %s"];
        $where_values = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
        if ($selected_category > 0) {
            $where_conditions[] = "o.category_id = %d";
            $where_values[] = $selected_category;
        }
        $where_clause = implode(' AND ', $where_conditions);

        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, c.name as category_name, v.name as variant_name,
                    GROUP_CONCAT(e.name SEPARATOR ', ') AS extra_names,
                    d.name as duration_name, cond.name as condition_name,
                    pc.name as product_color_name, fc.name as frame_color_name
             FROM {$wpdb->prefix}federwiegen_orders o
             LEFT JOIN {$wpdb->prefix}federwiegen_categories c ON o.category_id = c.id
             LEFT JOIN {$wpdb->prefix}federwiegen_variants v ON o.variant_id = v.id
             LEFT JOIN {$wpdb->prefix}federwiegen_extras e ON FIND_IN_SET(e.id, o.extra_ids)
             LEFT JOIN {$wpdb->prefix}federwiegen_durations d ON o.duration_id = d.id
             LEFT JOIN {$wpdb->prefix}federwiegen_conditions cond ON o.condition_id = cond.id
             LEFT JOIN {$wpdb->prefix}federwiegen_colors pc ON o.product_color_id = pc.id
             LEFT JOIN {$wpdb->prefix}federwiegen_colors fc ON o.frame_color_id = fc.id
             WHERE $where_clause
             GROUP BY o.id
             ORDER BY o.created_at DESC",
            ...$where_values
        ));

        $total_orders = count($orders);
        $total_revenue = array_sum(array_column($orders, 'final_price'));
        $avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

        $branding = [];
        $branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}federwiegen_branding");
        foreach ($branding_results as $result) {
            $branding[$result->setting_key] = $result->setting_value;
        }

        $this->load_template('orders', compact(
            'categories',
            'selected_category',
            'date_from',
            'date_to',
            'current_category',
            'orders',
            'total_orders',
            'total_revenue',
            'avg_order_value',
            'branding',
            'notice'
        ));
    }
    
    public function analytics_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/analytics-page.php';
    }
    
    public function settings_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/settings-page.php';
    }

}
