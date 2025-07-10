<?php
namespace ProduktVerleih;

class Admin {
    public function add_admin_menu() {
        $branding = $this->get_branding_settings();
        $menu_title = $branding['plugin_name'] ?? 'Produkt';
        
        add_menu_page(
            $branding['plugin_name'] ?? 'H2 Concepts Rental Pro',
            $menu_title,
            'manage_options',
            'produkt-verleih',
            array($this, 'admin_page'),
            'dashicons-heart',
            30
        );

        // Explicitly register a Dashboard submenu so the first item shows
        // "Dashboard" instead of repeating the plugin name
        add_submenu_page(
            'produkt-verleih',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'produkt-verleih',
            array($this, 'admin_page')
        );
        
        // Submenu: Produkte
        add_submenu_page(
            'produkt-verleih',
            'Produkte',
            'Produkte',
            'manage_options',
            'produkt-categories',
            array($this, 'categories_page')
        );

        // Manage simple product categories
        add_submenu_page(
            'produkt-verleih',
            'Kategorien',
            'Kategorien',
            'manage_options',
            'produkt-kategorien',
            function () {
                include PRODUKT_PLUGIN_PATH . 'admin/product-categories-page.php';
            }
        );
        
        // Submenu: Produktverwaltung
        add_submenu_page(
            'produkt-verleih',
            'Ausführungen',
            'Ausführungen',
            'manage_options',
            'produkt-variants',
            array($this, 'variants_page')
        );
        
        add_submenu_page(
            'produkt-verleih',
            'Extras',
            'Extras',
            'manage_options',
            'produkt-extras',
            array($this, 'extras_page')
        );
        
        add_submenu_page(
            'produkt-verleih',
            'Mietdauer',
            'Mietdauer',
            'manage_options',
            'produkt-durations',
            array($this, 'durations_page')
        );
        
        // New submenu items
        add_submenu_page(
            'produkt-verleih',
            'Zustand',
            'Zustand',
            'manage_options',
            'produkt-conditions',
            array($this, 'conditions_page')
        );
        
        add_submenu_page(
            'produkt-verleih',
            'Farben',
            'Farben',
            'manage_options',
            'produkt-colors',
            array($this, 'colors_page')
        );
        
        
        
        add_submenu_page(
            'produkt-verleih',
            'Bestellungen',
            'Bestellungen',
            'manage_options',
            'produkt-orders',
            array($this, 'orders_page')
        );
        

        // New settings menu with Stripe integration tab
        add_submenu_page(
            'produkt-verleih',
            'Einstellungen',
            'Einstellungen',
            'manage_options',
            'produkt-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_frontend_assets() {
        if (
            is_page('shop') ||
            get_query_var('produkt_category_slug') ||
            get_query_var('produkt_slug')
        ) {
            wp_enqueue_style(
                'produkt-style',
                PRODUKT_PLUGIN_URL . 'assets/style.css',
                [],
                '1.0'
            );
        }
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'produkt') !== false) {
            wp_enqueue_admin_bar_header_styles();
            wp_enqueue_style('produkt-admin-style', PRODUKT_PLUGIN_URL . 'assets/admin-style.css', array(), PRODUKT_VERSION);
            wp_enqueue_script('produkt-admin-script', PRODUKT_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), PRODUKT_VERSION, true);

            // Enqueue WordPress media scripts for image upload
            wp_enqueue_media();

            // Ensure WordPress editor scripts are available for dynamic accordions
            wp_enqueue_editor();
        }
    }
    
    private function get_branding_settings() {
        global $wpdb;
        
        $settings = array();
        $results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}produkt_branding");
        foreach ($results as $result) {
            $settings[$result->setting_key] = $result->setting_value;
        }
        
        return $settings;
    }

    private function load_template(string $slug, array $vars = []) {
        if (!empty($vars)) {
            extract($vars);
        }
        include PRODUKT_PLUGIN_PATH . "admin/{$slug}-page.php";
    }
    
    public function custom_admin_footer($text) {
        $branding = $this->get_branding_settings();
        
        if (isset($_GET['page']) && strpos($_GET['page'], 'produkt') !== false) {
            $footer_text = $branding['footer_text'] ?? 'Powered by H2 Concepts';
            $company_url = $branding['company_url'] ?? '#';
            $company_name = $branding['company_name'] ?? 'H2 Concepts';
            
            return '<span id="footer-thankyou">' . $footer_text . ' | <a href="' . esc_url($company_url) . '" target="_blank">' . esc_html($company_name) . '</a></span>';
        }
        
        return $text;
    }
    
    public function custom_admin_styles() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'produkt') === false) {
            return;
        }
        $branding = $this->get_branding_settings();
        $primary_color = $branding['admin_color_primary'] ?? '#5f7f5f';
        $secondary_color = $branding['admin_color_secondary'] ?? '#4a674a';
        $text_color = $branding['admin_color_text'] ?? '#ffffff';
        
        echo '<style>
            :root {
                --produkt-primary: ' . esc_attr($primary_color) . ';
                --produkt-secondary: ' . esc_attr($secondary_color) . ';
                --produkt-text: ' . esc_attr($text_color) . ';
            }

            .button-primary {
                background: var(--produkt-primary) !important;
                border-color: var(--produkt-secondary) !important;
                color: var(--produkt-text) !important;
            }

            .button-primary:hover {
                background: var(--produkt-secondary) !important;
                color: var(--produkt-text) !important;
            }

            .nav-tab-active {
                background: var(--produkt-primary);
                color: var(--produkt-text);
                border-color: var(--produkt-secondary);
            }
        </style>';
       }

    /**
     * Verify nonce and user capabilities for admin form submissions.
     */
    public static function verify_admin_action($nonce_field = 'produkt_admin_nonce') {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'h2-concepts'));
        }
        check_admin_referer('produkt_admin_action', $nonce_field);
    }
    
    public function admin_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/main-page.php';
    }
    
    public function categories_page() {
        global $wpdb;

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

        if (isset($_POST['submit_category'])) {
            self::verify_admin_action();
            $name = sanitize_text_field($_POST['name']);
            $raw_slug = $_POST['shortcode'] ?? $_POST['name'] ?? '';
            $slug = sanitize_title($raw_slug);

            // Fallback, falls slug leer bleibt
            if (empty($slug)) {
                $slug = 'kategorie-' . uniqid();
            }
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
            $feature_4_icon = esc_url_raw($_POST['feature_4_icon']);
            $feature_4_title = sanitize_text_field($_POST['feature_4_title']);
            $feature_4_description = sanitize_textarea_field($_POST['feature_4_description']);
            $button_text = sanitize_text_field($_POST['button_text']);
            $button_icon = esc_url_raw($_POST['button_icon']);
            $payment_icons = isset($_POST['payment_icons']) ? array_map('sanitize_text_field', (array) $_POST['payment_icons']) : array();
            $payment_icons = implode(',', $payment_icons);
            $shipping_provider = sanitize_text_field($_POST['shipping_provider'] ?? '');
            $shipping_price_id = sanitize_text_field($_POST['shipping_price_id'] ?? '');
            $shipping_label = sanitize_text_field($_POST['shipping_label']);
            $price_label = sanitize_text_field($_POST['price_label']);
            $price_period = sanitize_text_field($_POST['price_period']);
            $vat_included = isset($_POST['vat_included']) ? 1 : 0;
            $layout_style = sanitize_text_field($_POST['layout_style']);
            $duration_tooltip = sanitize_textarea_field($_POST['duration_tooltip']);
            $condition_tooltip = sanitize_textarea_field($_POST['condition_tooltip']);
            $show_features = isset($_POST['show_features']) ? 1 : 0;
            $show_tooltips = isset($_POST['show_tooltips']) ? 1 : 0;
            $show_rating = isset($_POST['show_rating']) ? 1 : 0;
            $rating_value_input = isset($_POST['rating_value']) ? str_replace(',', '.', $_POST['rating_value']) : '';
            $rating_value = $rating_value_input !== '' ? min(5, max(0, floatval($rating_value_input))) : 0;
            $rating_link = esc_url_raw($_POST['rating_link']);
            $sort_order = intval($_POST['sort_order']);

            $accordion_titles = isset($_POST['accordion_titles']) ? array_map('sanitize_text_field', (array) $_POST['accordion_titles']) : array();
            $accordion_contents = isset($_POST['accordion_contents']) ? array_map('wp_kses_post', (array) $_POST['accordion_contents']) : array();
            $acc_data = array();
            foreach ($accordion_titles as $k => $t) {
                $content = $accordion_contents[$k] ?? '';
                if ($t !== '' || $content !== '') {
                    $acc_data[] = array('title' => $t, 'content' => $content);
                }
            }
            $accordion_data = json_encode($acc_data);

            $table_name = $wpdb->prefix . 'produkt_categories';

            if (isset($_POST['id']) && $_POST['id']) {
                $result = $wpdb->update(
                    $table_name,
                    [
                        'name' => $name,
                        'shortcode' => $slug,
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
                        'feature_4_icon' => $feature_4_icon,
                        'feature_4_title' => $feature_4_title,
                        'feature_4_description' => $feature_4_description,
                        'button_text' => $button_text,
                        'button_icon' => $button_icon,
                        'payment_icons' => $payment_icons,
                        'accordion_data' => $accordion_data,
                        'shipping_provider' => $shipping_provider,
                        'shipping_price_id' => $shipping_price_id,
                        'price_label' => $price_label,
                        'shipping_label' => $shipping_label,
                        'price_period' => $price_period,
                        'vat_included' => $vat_included,
                        'layout_style' => $layout_style,
                        'duration_tooltip' => $duration_tooltip,
                        'condition_tooltip' => $condition_tooltip,
                        'show_features' => $show_features,
                        'show_tooltips' => $show_tooltips,
                        'show_rating' => $show_rating,
                        'rating_value' => $rating_value,
                        'rating_link' => $rating_link,
                        'sort_order' => $sort_order,
                    ],
                    ['id' => intval($_POST['id'])],
                    array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%d','%d','%d','%f','%s','%d'),
                );

                $produkt_id = intval($_POST['id']);

                if ($result !== false) {
                    if (isset($_POST['product_categories']) && is_array($_POST['product_categories'])) {
                        $wpdb->delete($wpdb->prefix . 'produkt_product_to_category', ['produkt_id' => $produkt_id]);
                        foreach ($_POST['product_categories'] as $cat_id) {
                            $wpdb->insert($wpdb->prefix . 'produkt_product_to_category', [
                                'produkt_id' => $produkt_id,
                                'category_id' => intval($cat_id)
                            ]);
                        }
                    }
                    echo '<div class="notice notice-success"><p>✅ Produkt erfolgreich aktualisiert!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>❌ Fehler beim Aktualisieren: ' . esc_html($wpdb->last_error) . '</p></div>';
                }
            } else {
                $result = $wpdb->insert(
                    $table_name,
                    [
                        'name' => $name,
                        'shortcode' => $slug,
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
                        'feature_4_icon' => $feature_4_icon,
                        'feature_4_title' => $feature_4_title,
                        'feature_4_description' => $feature_4_description,
                        'button_text' => $button_text,
                        'button_icon' => $button_icon,
                        'payment_icons' => $payment_icons,
                        'accordion_data' => $accordion_data,
                        'shipping_provider' => $shipping_provider,
                        'shipping_price_id' => $shipping_price_id,
                        'price_label' => $price_label,
                        'shipping_label' => $shipping_label,
                        'price_period' => $price_period,
                        'vat_included' => $vat_included,
                        'layout_style' => $layout_style,
                        'duration_tooltip' => $duration_tooltip,
                        'condition_tooltip' => $condition_tooltip,
                        'show_features' => $show_features,
                        'show_tooltips' => $show_tooltips,
                        'show_rating' => $show_rating,
                        'rating_value' => $rating_value,
                        'rating_link' => $rating_link,
                        'sort_order' => $sort_order,
                    ],
                    array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%d','%d','%d','%f','%s','%d')
                );

                $produkt_id = $wpdb->insert_id;

                if ($result !== false) {
                    if (isset($_POST['product_categories']) && is_array($_POST['product_categories'])) {
                        $wpdb->delete($wpdb->prefix . 'produkt_product_to_category', ['produkt_id' => $produkt_id]);
                        foreach ($_POST['product_categories'] as $cat_id) {
                            $wpdb->insert($wpdb->prefix . 'produkt_product_to_category', [
                                'produkt_id' => $produkt_id,
                                'category_id' => intval($cat_id)
                            ]);
                        }
                    }
                    echo '<div class="notice notice-success"><p>✅ Produkt erfolgreich hinzugefügt!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
                }
            }
        }

        if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
            $category_id = intval($_GET['delete']);
            $table_name = $wpdb->prefix . 'produkt_categories';
            $result = $wpdb->delete($table_name, ['id' => $category_id], ['%d']);
            if ($result !== false) {
                echo '<div class="notice notice-success"><p>✅ Produkt gelöscht!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
            }
        }

        $edit_item = null;
        if (isset($_GET['edit'])) {
            $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}produkt_categories WHERE id = %d", intval($_GET['edit'])));
        }

        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order, name");

        $branding = [];
        $branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}produkt_branding");
        foreach ($branding_results as $result) {
            $branding[$result->setting_key] = $result->setting_value;
        }

        $this->load_template('categories', compact('active_tab', 'edit_item', 'categories', 'branding'));
    }
    
    public function variants_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/variants-page.php';
    }
    
    public function extras_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/extras-page.php';
    }
    
    public function durations_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/durations-page.php';
    }
    
    public function conditions_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/conditions-page.php';
    }
    
    public function colors_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/colors-page.php';
    }
    
    public function orders_page() {
        global $wpdb;
        $notice = '';

        // Handle single deletion via GET
        if (isset($_GET['delete_order'])) {
            $order_id = intval($_GET['delete_order']);
            $deleted = $wpdb->delete(
                $wpdb->prefix . 'produkt_orders',
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
                    "DELETE FROM {$wpdb->prefix}produkt_orders WHERE id IN ($placeholders)",
                    ...$ids
                );
                $deleted = $wpdb->query($query);
                $notice = ($deleted !== false) ? 'bulk_deleted' : 'error';
            }
        }

        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order, name");
        $selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;

        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');

        $current_category = null;
        if ($selected_category > 0) {
            $current_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}produkt_categories WHERE id = %d", $selected_category));
        }

        $where_conditions = ["o.created_at BETWEEN %s AND %s"];
        $where_values = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
        if ($selected_category > 0) {
            $where_conditions[] = "o.category_id = %d";
            $where_values[] = $selected_category;
        }
        $where_clause = implode(' AND ', $where_conditions);

        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, c.name as category_name,
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
             WHERE $where_clause
             GROUP BY o.id
             ORDER BY o.created_at DESC",
            ...$where_values
        ));

        $total_orders = count($orders);
        $completed_orders = array_filter($orders, function ($o) {
            return $o->status === 'abgeschlossen';
        });
        $total_revenue = array_sum(array_column($completed_orders, 'final_price'));
        $completed_count = count($completed_orders);
        $avg_order_value = $completed_count > 0 ? $total_revenue / $completed_count : 0;

        $branding = [];
        $branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}produkt_branding");
        foreach ($branding_results as $result) {
            $branding[$result->setting_key] = $result->setting_value;
        }

        $order_logs = [];
        foreach ($orders as $o) {
            $order_logs[$o->id] = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT event, message, created_at FROM {$wpdb->prefix}produkt_order_logs WHERE order_id = %d ORDER BY created_at",
                    $o->id
                )
            );
        }

        $this->load_template('orders', compact(
            'categories',
            'selected_category',
            'date_from',
            'date_to',
            'current_category',
            'orders',
            'order_logs',
            'total_orders',
            'total_revenue',
            'avg_order_value',
            'branding',
            'notice'
        ));
    }
    
    
    public function settings_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/settings-page.php';
    }

}
