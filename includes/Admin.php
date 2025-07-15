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

        // Global shipping settings
        add_menu_page(
            'Versandkosten',
            '\xf0\x9f\x9a\x9a Versandkosten',
            'manage_options',
            'produkt-shipping',
            array($this, 'shipping_page'),
            'dashicons-admin-site-alt3',
            27
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
            'Content-Blöcke',
            'Content-Blöcke',
            'manage_options',
            'produkt-content-blocks',
            array($this, 'content_blocks_page')
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
        global $post, $wpdb;

        $slug          = sanitize_title(get_query_var('produkt_slug'));
        $category_slug = sanitize_title(get_query_var('produkt_category_slug'));
        $content       = $post->post_content ?? '';

        $customer_page_id = get_option(PRODUKT_CUSTOMER_PAGE_OPTION);
        $is_account_page  = false;
        if ($customer_page_id) {
            $is_account_page = is_page($customer_page_id);
        }
        if (!$is_account_page) {
            $is_account_page = has_shortcode($content, 'produkt_account');
        }

        if (!is_page('shop') && !$is_account_page && empty($slug) && empty($category_slug)) {
            if (!has_shortcode($content, 'produkt_product') &&
                !has_shortcode($content, 'stripe_elements_form') &&
                !has_shortcode($content, 'produkt_shop_grid') &&
                !has_shortcode($content, 'produkt_account')) {
                return;
            }
        }

        wp_enqueue_emoji_styles();
        wp_enqueue_style(
            'produkt-style',
            PRODUKT_PLUGIN_URL . 'assets/style.css',
            [],
            PRODUKT_VERSION
        );

        $branding = $this->get_branding_settings();

        if ($is_account_page) {
            wp_enqueue_style(
                'produkt-account-style',
                PRODUKT_PLUGIN_URL . 'assets/account-style.css',
                [],
                PRODUKT_VERSION
            );

            $login_bg = $branding['login_bg_image'] ?? '';
            if ($login_bg) {
                $login_css = 'body.produkt-login-page{background-image:url(' . esc_url($login_bg) . ');background-size:cover;background-position:center;background-repeat:no-repeat;}';
                wp_add_inline_style('produkt-account-style', $login_css);
            }
        }

        $load_script = !empty($slug) || !empty($category_slug) || is_page('shop') ||
            has_shortcode($content, 'produkt_product') ||
            has_shortcode($content, 'produkt_shop_grid');
        if ($load_script) {
            wp_enqueue_script(
                'produkt-script',
                PRODUKT_PLUGIN_URL . 'assets/script.js',
                ['jquery'],
                PRODUKT_VERSION,
                true
            );
        }

        $button_color = $branding['front_button_color'] ?? '#5f7f5f';
        $text_color   = $branding['front_text_color'] ?? '#4a674a';
        $border_color = $branding['front_border_color'] ?? '#a4b8a4';
        $button_text_color = $branding['front_button_text_color'] ?? '#ffffff';
        $filter_button_color = $branding['filter_button_color'] ?? '#5f7f5f';
        $custom_css = $branding['custom_css'] ?? '';
        $inline_css = ":root{--produkt-button-bg:{$button_color};--produkt-text-color:{$text_color};--produkt-border-color:{$border_color};--produkt-button-text:{$button_text_color};--produkt-filter-button-bg:{$filter_button_color};}";
        if (!empty($custom_css)) {
            $inline_css .= "\n" . $custom_css;
        }
        wp_add_inline_style('produkt-style', $inline_css);

        $category = null;
        if ($slug) {
            $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories");
            foreach ($categories as $cat) {
                if (sanitize_title($cat->product_title) === $slug) {
                    $category = $cat;
                    break;
                }
            }
        } else {
            $pattern = '/\[produkt_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
            preg_match($pattern, $post->post_content ?? '', $matches);
            $category_shortcode = $matches[1] ?? '';

            if (!empty($category_shortcode)) {
                $category = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}produkt_categories WHERE shortcode = %s",
                    $category_shortcode
                ));
            }
        }
        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order LIMIT 1");
        }

        $popup_settings = get_option('produkt_popup_settings');
        if ($popup_settings === false) {
            $legacy_key = base64_decode('ZmVkZXJ3aWVnZV9wb3B1cF9zZXR0aW5ncw==');
            $popup_settings = get_option($legacy_key, []);
        }
        $options = [];
        if (!empty($popup_settings['options'])) {
            $opts = array_filter(array_map('trim', explode("\n", $popup_settings['options'])));
            $options = array_values($opts);
        }
        $popup_enabled = isset($popup_settings['enabled']) ? intval($popup_settings['enabled']) : 0;
        $popup_days    = isset($popup_settings['days']) ? intval($popup_settings['days']) : 7;

        if ($load_script) {
            wp_localize_script('produkt-script', 'produkt_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('produkt_nonce'),
                'price_period' => $category->price_period ?? 'month',
                'price_label' => $category->price_label ?? 'Monatlicher Mietpreis',
                'vat_included' => isset($category->vat_included) ? intval($category->vat_included) : 0,
                'popup_settings' => [
                    'enabled' => $popup_enabled,
                    'days'    => $popup_days,
                    'title'   => $popup_settings['title'] ?? '',
                    'content' => wpautop($popup_settings['content'] ?? ''),
                    'options' => $options,
                ],
            ]);
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
            $short_description = sanitize_textarea_field($_POST['short_description']);
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
            $shipping_provider = '';
            $shipping_price_id = '';
            $shipping_label = '';
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

            // Ensure new short_description column exists to prevent SQL errors
            $col_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'short_description'");
            if (!$col_exists) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN short_description TEXT DEFAULT ''");
            }

            if (isset($_POST['id']) && $_POST['id']) {
                $result = $wpdb->update(
                    $table_name,
                    [
                        'name' => $name,
                        'shortcode' => $slug,
                        'meta_title' => $meta_title,
                        'meta_description' => $meta_description,
                        'product_title' => $product_title,
                        'short_description' => $short_description,
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
                        'price_label' => $price_label,
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
                    array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%d','%d','%d','%f','%s','%d'),
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
                        'short_description' => $short_description,
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
                        'price_label' => $price_label,
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
                    array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%d','%d','%d','%f','%s','%d')
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

    public function content_blocks_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/content-blocks-page.php';
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

    public function shipping_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/shipping-page.php';
    }


    public function settings_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/settings-page.php';
    }

}
