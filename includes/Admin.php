<?php
namespace ProduktVerleih;

require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

class Admin {
    public function __construct() {
    }
    public function add_admin_menu() {
        $branding = $this->get_branding_settings();
        $menu_title = $branding['plugin_name'] ?? 'Produkt';
        
        $modus    = get_option('produkt_betriebsmodus', 'miete');
        $is_sale  = ($modus === 'kauf');

        add_menu_page(
            $branding['plugin_name'] ?? 'H2 Rental Pro',
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

        // Submenu: Produkte
        add_submenu_page(
            'produkt-verleih',
            'Produkte',
            'Produkte',
            'manage_options',
            'produkt-categories',
            array($this, 'categories_page')
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
        
        if (!$is_sale) {
            add_submenu_page(
                'produkt-verleih',
                'Mietdauer',
                'Mietdauer',
                'manage_options',
                'produkt-durations',
                array($this, 'durations_page')
            );
        }
        
        // New submenu items
        if (!$is_sale) {
            add_submenu_page(
                'produkt-verleih',
                'Zustand',
                'Zustand',
                'manage_options',
                'produkt-conditions',
                array($this, 'conditions_page')
            );
        }

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

        add_submenu_page(
            'produkt-verleih',
            'Kunden',
            'Kunden',
            'manage_options',
            'produkt-customers',
            array($this, 'customers_page')
        );

        if ($is_sale) {
            add_submenu_page(
                'produkt-verleih',
                'Kalender',
                'Kalender',
                'manage_options',
                'produkt-calendar',
                array($this, 'calendar_page')
            );
        }

        // Global shipping settings
        add_submenu_page(
            'produkt-verleih',
            'Versandkosten',
            'Versandkosten',
            'manage_options',
            'produkt-shipping',
            array($this, 'shipping_page')
        );

        add_submenu_page(
            'produkt-verleih',
            'Filter',
            'Filter',
            'manage_options',
            'produkt-filters',
            array($this, 'filters_page')
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

        $customer_page_id  = get_option(PRODUKT_CUSTOMER_PAGE_OPTION);
        $confirm_page_id   = get_option(PRODUKT_CONFIRM_PAGE_OPTION);
        $is_account_page   = false;
        $is_confirm_page   = false;

        if ($customer_page_id) {
            $is_account_page = is_page($customer_page_id);
        }
        if (!$is_account_page) {
            $is_account_page = has_shortcode($content, 'produkt_account');
        }

        if ($confirm_page_id) {
            $is_confirm_page = is_page($confirm_page_id);
        }
        if (!$is_confirm_page) {
            $is_confirm_page = has_shortcode($content, 'produkt_confirmation');
        }

        // Always load basic assets so the cart icon and sidebar work site-wide

        wp_enqueue_emoji_styles();
        wp_enqueue_style(
            'produkt-style',
            PRODUKT_PLUGIN_URL . 'assets/style.css',
            [],
            PRODUKT_VERSION
        );

        $branding = $this->get_branding_settings();

        if ($is_account_page || $is_confirm_page) {
            wp_enqueue_style(
                'produkt-account-style',
                PRODUKT_PLUGIN_URL . 'assets/account-style.css',
                [],
                PRODUKT_VERSION
            );

            if ($is_account_page) {
                $login_bg     = $branding['login_bg_image'] ?? '';
                $login_layout = $branding['login_layout'] ?? 'classic';
                $primary      = $branding['admin_color_primary'] ?? '#5f7f5f';
                $login_text   = $branding['login_text_color'] ?? '#1f1f1f';
                $card_bg      = $branding['account_card_bg'] ?? '#e8e8e8';
                $card_text    = $branding['account_card_text'] ?? '#000000';
                $inline_css   = '';

                if ($login_layout === 'split') {
                    $inline_css .= ':root{--produkt-login-primary:' . esc_attr($primary) . ';--produkt-login-text:' . esc_attr($login_text) . ';}';
                    if ($login_bg) {
                        $inline_css .= '.produkt-login-visual{background-image:url(' . esc_url($login_bg) . ');}';
                    }
                } elseif ($login_bg) {
                    $inline_css .= 'body.produkt-login-page{background-image:url(' . esc_url($login_bg) . ');background-size:cover;background-position:center;background-repeat:no-repeat;}';
                }

                if ($inline_css === '' && $login_layout === 'split' && $login_text) {
                    $inline_css .= ':root{--produkt-login-text:' . esc_attr($login_text) . ';}';
                }

                if ($card_bg || $card_text) {
                    $inline_css .= ':root{--produkt-account-card-bg:' . esc_attr($card_bg ?: '#e8e8e8') . ';--produkt-account-card-text:' . esc_attr($card_text ?: '#000000') . ';}';
                }

                if ($inline_css) {
                    wp_add_inline_style('produkt-account-style', $inline_css);
                }
            }
        }

        // Load front-end script globally for cart/sidebar behavior
        $checkout_page_id = get_option(PRODUKT_CHECKOUT_PAGE_OPTION);
        $needs_stripe    = false;

        if ($checkout_page_id && is_page($checkout_page_id)) {
            $needs_stripe = true;
        } elseif (is_a($post, 'WP_Post') && has_shortcode($content, 'stripe_elements_form')) {
            $needs_stripe = true;
        }

        if ($needs_stripe) {
            wp_enqueue_script(
                'stripe-js',
                'https://js.stripe.com/basil/stripe.js',
                [],
                null,
                false
            );
        }

        wp_enqueue_script(
            'produkt-script',
            PRODUKT_PLUGIN_URL . 'assets/script.js',
            ['jquery'],
            PRODUKT_VERSION,
            true
        );

        // Always localize the front-end script so cart and configurator logic work site-wide
        $load_script = true;

        if (is_page() && isset($_GET['session_id'])) {
            wp_enqueue_script(
                'produkt-return',
                PRODUKT_PLUGIN_URL . 'assets/return.js',
                [],
                PRODUKT_VERSION,
                true
            );
            wp_localize_script('produkt-return', 'produkt_return', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'checkout_url' => Plugin::get_checkout_page_url(),
            ]);
        }

        $button_color = $branding['front_button_color'] ?? '#5f7f5f';
        $text_color   = $branding['front_text_color'] ?? '#4a674a';
        $border_color = $branding['front_border_color'] ?? '#a4b8a4';
        $button_text_color = $branding['front_button_text_color'] ?? '#ffffff';
        $filter_button_color = $branding['filter_button_color'] ?? '#5f7f5f';
        $cart_badge_bg = $branding['cart_badge_bg'] ?? '#000000';
        $cart_badge_text = $branding['cart_badge_text'] ?? '#ffffff';
        $custom_css = $branding['custom_css'] ?? '';
        $product_padding = $branding['product_padding'] ?? '1';
        $inline_css = ":root{--produkt-button-bg:{$button_color};--produkt-text-color:{$text_color};--produkt-border-color:{$border_color};--produkt-button-text:{$button_text_color};--produkt-filter-button-bg:{$filter_button_color};--produkt-cart-badge-bg:{$cart_badge_bg};--produkt-cart-badge-color:{$cart_badge_text};}";
        if ($product_padding !== '1') {
        $inline_css .= "\n.produkt-product-info,.produkt-right{padding:0;}\n.produkt-content{gap:4rem;}";
        }
        if (!empty($custom_css)) {
            $inline_css .= "\n" . $custom_css;
        }
        wp_add_inline_style('produkt-style', $inline_css);

        if ($is_account_page && !is_user_logged_in()) {
            $hide_header_css = 'body.page-kundenkonto header, body.page-kundenkonto .site-header, body.page-kundenkonto #site-header, body.page-kundenkonto footer, body.page-kundenkonto .site-footer, body.page-kundenkonto #site-footer {display:none !important;}';
            wp_add_inline_style('produkt-style', $hide_header_css);
        }

        $ui = get_option('produkt_ui_settings', []);

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
        $popup_email   = isset($popup_settings['email_enabled']) ? intval($popup_settings['email_enabled']) : 0;
        $trigger_defaults = [
            'desktop_exit'      => 1,
            'mobile_scroll'     => 1,
            'mobile_inactivity' => 1,
        ];
        $popup_triggers = $popup_settings['triggers'] ?? [];
        if (!is_array($popup_triggers)) {
            $popup_triggers = [];
        }
        $popup_triggers = array_merge($trigger_defaults, array_intersect_key($popup_triggers, $trigger_defaults));
        $popup_triggers = array_map('intval', $popup_triggers);

        if ($load_script) {
            $modus = get_option('produkt_betriebsmodus', 'miete');
            $cart_mode = get_option('produkt_miete_cart_mode', 'direct');
            $cart_enabled = $modus === 'kauf' || ($modus === 'miete' && $cart_mode === 'cart');
            $blocked_days = $wpdb->get_col("SELECT day FROM {$wpdb->prefix}produkt_blocked_days");
            $category_button_text = isset($category) && property_exists($category, 'button_text') ? trim((string) $category->button_text) : '';
            $global_button_text = isset($ui['button_text']) ? trim((string) $ui['button_text']) : '';
            $legacy_button_defaults = ['In den Warenkorb', 'Jetzt kaufen', 'Jetzt mieten'];
            $localized_button_text = ($category_button_text !== '' && !in_array($category_button_text, $legacy_button_defaults, true))
                ? $category_button_text
                : $global_button_text;
            wp_localize_script('produkt-script', 'produkt_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('produkt_nonce'),
                'publishable_key' => StripeService::get_publishable_key(),
                'checkout_url' => Plugin::get_checkout_page_url(),
                'account_url' => Plugin::get_customer_page_url(),
                'login_nonce' => wp_create_nonce('request_login_code_action'),
                'is_logged_in' => is_user_logged_in(),
                'price_period' => $category->price_period ?? 'month',
                'price_label' => $category->price_label ?? ($modus === 'kauf' ? 'Einmaliger Kaufpreis' : 'Monatlicher Mietpreis'),
                'vat_included' => isset($category->vat_included) ? intval($category->vat_included) : 0,
                'betriebsmodus' => $modus,
                'cart_enabled' => $cart_enabled ? 1 : 0,
                'button_text' => $localized_button_text,
                'blocked_days' => $blocked_days,
                'variant_blocked_days' => [],
                'popup_settings' => [
                    'enabled'  => $popup_enabled,
                    'days'     => $popup_days,
                    'email'    => $popup_email,
                    'title'    => $popup_settings['title'] ?? '',
                    'content'  => wpautop($popup_settings['content'] ?? ''),
                    'options'  => $options,
                    'triggers' => $popup_triggers,
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

            wp_localize_script('produkt-admin-script', 'produkt_admin', ['ajax_url'=>admin_url('admin-ajax.php'), 'nonce'=>wp_create_nonce('produkt_admin_action')]);
            // Ensure WordPress editor scripts are available for dynamic accordions
            wp_enqueue_editor();
        }

        // previously loaded Select2 for category dropdowns, but reverted to
        // simple selects for reliability
    }
    
    public function get_branding_settings() {
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
        if (empty($_POST[$nonce_field]) || !wp_verify_nonce($_POST[$nonce_field], 'produkt_admin_action')) {
            wp_die(__('Invalid nonce.', 'h2-concepts'));
        }
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
            $product_title = sanitize_text_field($_POST['product_title'] ?? $name);
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
            $global_ui = get_option('produkt_ui_settings', []);
            $button_text = isset($_POST['button_text']) ? sanitize_text_field($_POST['button_text']) : '';
            $legacy_button_defaults = ['In den Warenkorb', 'Jetzt kaufen', 'Jetzt mieten'];
            if ($button_text !== '' && in_array($button_text, $legacy_button_defaults, true)) {
                $button_text = '';
            }
            $button_icon = esc_url_raw($_POST['button_icon'] ?? ($global_ui['button_icon'] ?? ''));
            $payment_icons = isset($_POST['payment_icons']) ? array_map('sanitize_text_field', (array) $_POST['payment_icons']) : (array)($global_ui['payment_icons'] ?? []);
            $payment_icons = implode(',', $payment_icons);
            $shipping_provider = '';
            $shipping_price_id = '';
            $shipping_label = '';
            $price_label = sanitize_text_field($_POST['price_label'] ?? ($global_ui['price_label'] ?? ''));
            $price_period = sanitize_text_field($_POST['price_period'] ?? ($global_ui['price_period'] ?? 'month'));
            $vat_included = isset($_POST['vat_included']) ? 1 : (isset($global_ui['vat_included']) ? intval($global_ui['vat_included']) : 0);
            $layout_style = sanitize_text_field($_POST['layout_style']);
            $price_layout = sanitize_text_field($_POST['price_layout'] ?? 'default');
            $duration_tooltip = sanitize_textarea_field($_POST['duration_tooltip'] ?? ($global_ui['duration_tooltip'] ?? ''));
            $condition_tooltip = sanitize_textarea_field($_POST['condition_tooltip'] ?? ($global_ui['condition_tooltip'] ?? ''));
            $show_features = isset($_POST['show_features']) ? 1 : 0;
            $show_tooltips = isset($_POST['show_tooltips']) ? 1 : (isset($global_ui['show_tooltips']) ? intval($global_ui['show_tooltips']) : 1);
            $show_rating = isset($_POST['show_rating']) ? 1 : 0;
            $rating_value_input = isset($_POST['rating_value']) ? str_replace(',', '.', $_POST['rating_value']) : '';
            $rating_value = $rating_value_input !== '' ? min(5, max(0, floatval($rating_value_input))) : 0;
            $rating_link = esc_url_raw($_POST['rating_link'] ?? '');
            if (!$show_rating) {
                $rating_value = 0;
                $rating_link = '';
            }
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

            $block_titles = isset($_POST['page_block_titles']) ? array_map('sanitize_text_field', (array) $_POST['page_block_titles']) : array();
            $block_texts  = isset($_POST['page_block_texts'])  ? array_map('wp_kses_post', (array) $_POST['page_block_texts'])   : array();
            $block_images = isset($_POST['page_block_images']) ? array_map('esc_url_raw', (array) $_POST['page_block_images']) : array();
            $block_alts   = isset($_POST['page_block_alts'])   ? array_map('sanitize_text_field', (array) $_POST['page_block_alts'])   : array();
            $blocks = array();
            foreach ($block_titles as $k => $t) {
                $text  = $block_texts[$k] ?? '';
                $img   = $block_images[$k] ?? '';
                $alt   = $block_alts[$k] ?? '';
                if ($t !== '' || $text !== '' || $img !== '') {
                    $blocks[] = array('title' => $t, 'text' => $text, 'image' => $img, 'alt' => $alt);
                }
            }
            $page_blocks = json_encode($blocks);

            $detail_titles = isset($_POST['detail_block_titles']) ? array_map('sanitize_text_field', (array) $_POST['detail_block_titles']) : array();
            $detail_texts  = isset($_POST['detail_block_texts'])  ? array_map('wp_kses_post', (array) $_POST['detail_block_texts'])  : array();
            $detail_data   = array();
            foreach ($detail_titles as $k => $t) {
                $txt = $detail_texts[$k] ?? '';
                if ($t !== '' || $txt !== '') {
                    $detail_data[] = array('title' => $t, 'text' => $txt);
                }
            }
            $detail_blocks = json_encode($detail_data);

            $tech_titles = isset($_POST['tech_block_titles']) ? array_map('sanitize_text_field', (array) $_POST['tech_block_titles']) : array();
            $tech_texts  = isset($_POST['tech_block_texts'])  ? array_map('wp_kses_post', (array) $_POST['tech_block_texts'])  : array();
            $tech_data   = array();
            foreach ($tech_titles as $k => $t) {
                $txt = $tech_texts[$k] ?? '';
                if ($t !== '' || $txt !== '') {
                    $tech_data[] = array('title' => $t, 'text' => $txt);
                }
            }
            $tech_blocks = json_encode($tech_data);

            $scope_titles = isset($_POST['scope_block_titles']) ? array_map('sanitize_text_field', (array) $_POST['scope_block_titles']) : array();
            $scope_texts  = isset($_POST['scope_block_texts'])  ? array_map('wp_kses_post', (array) $_POST['scope_block_texts'])  : array();
            $scope_data   = array();
            foreach ($scope_titles as $k => $t) {
                $txt = $scope_texts[$k] ?? '';
                if ($t !== '' || $txt !== '') {
                    $scope_data[] = array('title' => $t, 'text' => $txt);
                }
            }
            $scope_blocks = json_encode($scope_data);

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
                        'page_blocks'   => $page_blocks,
                        'detail_blocks' => $detail_blocks,
                        'tech_blocks'   => $tech_blocks,
                        'scope_blocks'  => $scope_blocks,
                        'price_label' => $price_label,
                        'price_period' => $price_period,
                        'vat_included' => $vat_included,
                        'layout_style' => $layout_style,
                        'price_layout' => $price_layout,
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
                    array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%d','%d','%d','%f','%s','%d'),
                );

                $produkt_id = intval($_POST['id']);

                if ($result !== false) {
                    if (isset($_POST['product_categories']) && is_array($_POST['product_categories'])) {
                        $cat_ids = array_map('intval', $_POST['product_categories']);
                        foreach ($cat_ids as $cid) {
                            $cat_ids = array_merge($cat_ids, Database::get_ancestor_category_ids($cid));
                        }
                        $cat_ids = array_unique($cat_ids);

                        $wpdb->delete($wpdb->prefix . 'produkt_product_to_category', ['produkt_id' => $produkt_id]);
                        foreach ($cat_ids as $cat_id) {
                            $wpdb->insert($wpdb->prefix . 'produkt_product_to_category', [
                                'produkt_id' => $produkt_id,
                                'category_id' => intval($cat_id)
                            ]);
                        }
                    }

                    $wpdb->delete($wpdb->prefix . 'produkt_category_filters', ['category_id' => $produkt_id]);
                    if (!empty($_POST['filters']) && is_array($_POST['filters'])) {
                        $filter_ids = array_map('intval', $_POST['filters']);
                        foreach ($filter_ids as $fid) {
                            $wpdb->insert($wpdb->prefix . 'produkt_category_filters', [
                                'category_id' => $produkt_id,
                                'filter_id'   => $fid
                            ]);
                        }
                    }

                    if (!empty($_POST['stock_available']) && is_array($_POST['stock_available'])) {
                        foreach ($_POST['stock_available'] as $vid => $qty) {
                            $vid = intval($vid);
                            $available = intval($qty);
                            $rented = intval($_POST['stock_rented'][$vid] ?? 0);
                            $sku = sanitize_text_field($_POST['sku'][$vid] ?? '');
                            $wpdb->update(
                                $wpdb->prefix . 'produkt_variants',
                                [
                                    'stock_available' => $available,
                                    'stock_rented'    => $rented,
                                    'sku'             => $sku
                                ],
                                ['id' => $vid],
                                ['%d','%d','%s'],
                                ['%d']
                            );
                        }
                    }
                    if (!empty($_POST['color_stock_available']) && is_array($_POST['color_stock_available'])) {
                        foreach ($_POST['color_stock_available'] as $vid => $colors) {
                            foreach ($colors as $cid => $qty) {
                                $vid = intval($vid);
                                $cid = intval($cid);
                                $available = intval($qty);
                                $rented = intval($_POST['color_stock_rented'][$vid][$cid] ?? 0);
                                $sku = sanitize_text_field($_POST['color_sku'][$vid][$cid] ?? '');
                                $wpdb->update(
                                    $wpdb->prefix . 'produkt_variant_options',
                                    [
                                        'stock_available' => $available,
                                        'stock_rented'    => $rented,
                                        'sku'             => $sku
                                    ],
                                    [
                                        'variant_id'  => $vid,
                                        'option_type' => 'product_color',
                                        'option_id'   => $cid,
                                    ],
                                    ['%d','%d','%s'],
                                    ['%d','%s','%d']
                                );
                            }
                        }
                    }

                    if (!empty($_POST['extra_stock_available']) && is_array($_POST['extra_stock_available'])) {
                        foreach ($_POST['extra_stock_available'] as $eid => $qty) {
                            $eid = intval($eid);
                            $available = intval($qty);
                            $rented = intval($_POST['extra_stock_rented'][$eid] ?? 0);
                            $sku = sanitize_text_field($_POST['extra_sku'][$eid] ?? '');
                            $wpdb->update(
                                $wpdb->prefix . 'produkt_extras',
                                [
                                    'stock_available' => $available,
                                    'stock_rented'    => $rented,
                                    'sku'             => $sku
                                ],
                                ['id' => $eid],
                                ['%d','%d','%s'],
                                ['%d']
                            );
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
                        'page_blocks'   => $page_blocks,
                        'detail_blocks' => $detail_blocks,
                        'tech_blocks'   => $tech_blocks,
                        'scope_blocks'  => $scope_blocks,
                        'price_label' => $price_label,
                        'price_period' => $price_period,
                        'vat_included' => $vat_included,
                        'layout_style' => $layout_style,
                        'price_layout' => $price_layout,
                        'duration_tooltip' => $duration_tooltip,
                        'condition_tooltip' => $condition_tooltip,
                        'show_features' => $show_features,
                        'show_tooltips' => $show_tooltips,
                        'show_rating' => $show_rating,
                        'rating_value' => $rating_value,
                        'rating_link' => $rating_link,
                        'sort_order' => $sort_order,
                    ],
                    array(
                        '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',
                        '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',
                        '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',
                        '%s','%s','%d','%s','%s','%s','%s','%d','%d','%d','%f','%s','%d'
                    )
                );

                $produkt_id = $wpdb->insert_id;

                if ($result !== false) {
                    if (isset($_POST['product_categories']) && is_array($_POST['product_categories'])) {
                        $cat_ids = array_map('intval', $_POST['product_categories']);
                        foreach ($cat_ids as $cid) {
                            $cat_ids = array_merge($cat_ids, Database::get_ancestor_category_ids($cid));
                        }
                        $cat_ids = array_unique($cat_ids);

                        $wpdb->delete($wpdb->prefix . 'produkt_product_to_category', ['produkt_id' => $produkt_id]);
                        foreach ($cat_ids as $cat_id) {
                            $wpdb->insert($wpdb->prefix . 'produkt_product_to_category', [
                                'produkt_id' => $produkt_id,
                                'category_id' => intval($cat_id)
                            ]);
                        }
                    }

                    $wpdb->delete($wpdb->prefix . 'produkt_category_filters', ['category_id' => $produkt_id]);
                    if (!empty($_POST['filters']) && is_array($_POST['filters'])) {
                        $filter_ids = array_map('intval', $_POST['filters']);
                        foreach ($filter_ids as $fid) {
                            $wpdb->insert($wpdb->prefix . 'produkt_category_filters', [
                                'category_id' => $produkt_id,
                                'filter_id'   => $fid
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
            require_once PRODUKT_PLUGIN_PATH . 'includes/stripe-sync.php';

            $result = produkt_hard_delete($category_id);

            if ($result) {
                echo '<div class="notice notice-success"><p>✅ Produkt gelöscht!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
            }
        }

        $edit_item = null;
        if (isset($_GET['edit'])) {
            $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}produkt_categories WHERE id = %d", intval($_GET['edit'])));
        }

        // Filter & Suche
        $product_categories = \ProduktVerleih\Database::get_product_categories_tree();
        $selected_prodcat = isset($_GET['prodcat']) ? intval($_GET['prodcat']) : 0;
        $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $sql = "SELECT c.*, GROUP_CONCAT(pc.name ORDER BY pc.name SEPARATOR ', ') AS categories
                FROM {$wpdb->prefix}produkt_categories c
                LEFT JOIN {$wpdb->prefix}produkt_product_to_category ptc ON c.id = ptc.produkt_id
                LEFT JOIN {$wpdb->prefix}produkt_product_categories pc ON ptc.category_id = pc.id";
        $params = [];
        if ($selected_prodcat > 0) {
            $sql .= " WHERE ptc.category_id = %d";
            $params[] = $selected_prodcat;
        }
        if ($search_term !== '') {
            $like = '%' . $wpdb->esc_like($search_term) . '%';
            $sql .= ($params ? " AND" : " WHERE") . " (c.name LIKE %s OR c.product_title LIKE %s)";
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " GROUP BY c.id ORDER BY c.sort_order, c.name";
        if (!empty($params)) {
            $categories = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        } else {
            $categories = $wpdb->get_results($sql);
        }

        $branding = [];
        $branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}produkt_branding");
        foreach ($branding_results as $result) {
            $branding[$result->setting_key] = $result->setting_value;
        }

        $this->load_template(
            'categories',
            compact('active_tab', 'edit_item', 'categories', 'branding', 'product_categories', 'selected_prodcat', 'search_term')
        );
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
        $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

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
        if ($search_term !== '') {
            $like = '%' . $wpdb->esc_like(ltrim($search_term, '#')) . '%';
            $where_conditions[] = "(o.order_number LIKE %s OR CAST(o.id AS CHAR) LIKE %s OR o.customer_name LIKE %s)";
            $where_values[] = $like;
            $where_values[] = $like;
            $where_values[] = $like;
        }
        $where_clause = implode(' AND ', $where_conditions);

        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, c.name as category_name,
                    COALESCE(v.name, o.produkt_name) as variant_name,
                    COALESCE(NULLIF(GROUP_CONCAT(e.name SEPARATOR ', '), ''), o.extra_text) AS extra_names,
                    COALESCE(d.name, o.dauer_text) as duration_name,
                    COALESCE(cond.name, o.zustand_text) as condition_name,
                    COALESCE(pc.name, o.produktfarbe_text) as product_color_name,
                    COALESCE(fc.name, o.gestellfarbe_text) as frame_color_name,
                    sm.name AS shipping_name,
                    sm.service_provider AS shipping_provider
             FROM {$wpdb->prefix}produkt_orders o
             LEFT JOIN {$wpdb->prefix}produkt_categories c ON o.category_id = c.id
             LEFT JOIN {$wpdb->prefix}produkt_variants v ON o.variant_id = v.id
             LEFT JOIN {$wpdb->prefix}produkt_extras e ON FIND_IN_SET(e.id, o.extra_ids)
             LEFT JOIN {$wpdb->prefix}produkt_durations d ON o.duration_id = d.id
             LEFT JOIN {$wpdb->prefix}produkt_conditions cond ON o.condition_id = cond.id
             LEFT JOIN {$wpdb->prefix}produkt_colors pc ON o.product_color_id = pc.id
             LEFT JOIN {$wpdb->prefix}produkt_colors fc ON o.frame_color_id = fc.id
             LEFT JOIN {$wpdb->prefix}produkt_shipping_methods sm
                ON sm.stripe_price_id = COALESCE(o.shipping_price_id, c.shipping_price_id)
             WHERE $where_clause
             GROUP BY o.id
            ORDER BY o.created_at DESC",
            ...$where_values
        ));
        foreach ($orders as $o) {
            $o->rental_days = pv_get_order_rental_days($o);
        }

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
                    "SELECT id, event, message, created_at FROM {$wpdb->prefix}produkt_order_logs WHERE order_id = %d ORDER BY created_at",
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
            'search_term',
            'orders',
            'order_logs',
            'total_orders',
            'total_revenue',
            'avg_order_value',
            'branding',
            'notice'
        ));
    }

    public function customers_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/customers-page.php';
    }

    public function shipping_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/shipping-page.php';
    }

    public function filters_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/filters-page.php';
    }

    public function calendar_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/calendar-page.php';
    }

    public function settings_page() {
        include PRODUKT_PLUGIN_PATH . 'admin/settings-page.php';
    }

}
