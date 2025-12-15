<?php
namespace ProduktVerleih;

class Database {
    public function update_database() {
        global $wpdb;
        
        // Add category_id column to all tables if it doesn't exist
        $tables_to_update = array(
            'produkt_variants',
            'produkt_extras', 
            'produkt_durations'
        );
        
        foreach ($tables_to_update as $table_suffix) {
            $table_name = $wpdb->prefix . $table_suffix;
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'category_id'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN category_id mediumint(9) DEFAULT 1 AFTER id");
            }
        }

        // Ensure parent_id column exists for product categories
        $table_prod_cats = $wpdb->prefix . 'produkt_product_categories';
        $parent_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_prod_cats LIKE 'parent_id'");
        if (empty($parent_exists)) {
            $wpdb->query("ALTER TABLE $table_prod_cats ADD COLUMN parent_id INT UNSIGNED DEFAULT NULL AFTER id");
            $wpdb->query("ALTER TABLE $table_prod_cats ADD KEY parent_id (parent_id)");
        }
        
        // Add image columns to variants table if they don't exist
        $table_variants = $wpdb->prefix . 'produkt_variants';
        $columns_to_add = array(
            'stripe_price_id'        => "VARCHAR(255) DEFAULT ''",
            'stripe_price_id_sale'   => 'VARCHAR(255) DEFAULT NULL',
            'stripe_price_id_rent'   => 'VARCHAR(255) DEFAULT NULL',
            'stripe_product_id'      => 'VARCHAR(255) DEFAULT NULL',
            'mietpreis_monatlich'    => 'DECIMAL(10,2) DEFAULT 0',
            'verkaufspreis_einmalig' => 'DECIMAL(10,2) DEFAULT 0',
            'weekend_price'         => 'DECIMAL(10,2) DEFAULT 0',
            'stripe_weekend_price_id'=> 'VARCHAR(255) DEFAULT NULL',
            'price_from'             => 'DECIMAL(10,2) DEFAULT 0',
            'mode'                   => "VARCHAR(10) DEFAULT 'miete'",
            'sale_enabled'           => 'TINYINT(1) DEFAULT 0',
            'image_url_1' => 'TEXT',
            'image_url_2' => 'TEXT',
            'image_url_3' => 'TEXT',
            'image_url_4' => 'TEXT',
            'image_url_5' => 'TEXT',
            'available' => 'TINYINT(1) DEFAULT 1',
            'availability_note' => "VARCHAR(255) DEFAULT ''",
            'delivery_time' => "VARCHAR(255) DEFAULT ''",
            'sku' => "VARCHAR(100) DEFAULT ''",
            'stock_available' => 'INT DEFAULT 0',
            'stock_rented' => 'INT DEFAULT 0',
            'weekend_only' => 'TINYINT(1) DEFAULT 0',
            'min_rental_days' => 'INT DEFAULT 0'
        );
        
        foreach ($columns_to_add as $column => $type) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_variants LIKE '$column'");
            if (empty($column_exists)) {
                if ($column === 'stripe_price_id') {
                    $after = 'name';
                } elseif ($column === 'stripe_price_id_sale') {
                    $after = 'stripe_price_id';
                } elseif ($column === 'stripe_price_id_rent') {
                    $after = 'stripe_price_id_sale';
                } elseif ($column === 'stripe_product_id') {
                    $after = 'stripe_price_id_rent';
                } elseif ($column === 'mietpreis_monatlich') {
                    $after = 'stripe_product_id';
                } elseif ($column === 'verkaufspreis_einmalig') {
                    $after = 'mietpreis_monatlich';
                } elseif ($column === 'weekend_price') {
                    $after = 'verkaufspreis_einmalig';
                } elseif ($column === 'stripe_weekend_price_id') {
                    $after = 'weekend_price';
                } elseif ($column === 'sale_enabled') {
                    $after = 'mode';
                } elseif ($column === 'mode') {
                    $after = 'price_from';
                } elseif ($column === 'sku') {
                    $after = 'delivery_time';
                } elseif ($column === 'stock_available') {
                    $after = 'sku';
                } elseif ($column === 'stock_rented') {
                    $after = 'stock_available';
                } elseif ($column === 'weekend_only') {
                    $after = 'stock_rented';
                } elseif ($column === 'min_rental_days') {
                    $after = 'weekend_only';
                } else {
                    $after = 'base_price';
                }
                $wpdb->query("ALTER TABLE $table_variants ADD COLUMN $column $type AFTER $after");
            }
        }

        // Ensure stripe_archived column exists
        $archived_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_variants LIKE 'stripe_archived'");
        if (empty($archived_exists)) {
            $after = isset($columns_to_add['stripe_price_id']) ? 'stripe_price_id' : 'name';
            $wpdb->query("ALTER TABLE $table_variants ADD COLUMN stripe_archived TINYINT(1) DEFAULT 0 AFTER $after");
        }
        
        // Remove old single image_url column if it exists
        $old_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_variants LIKE 'image_url'");
        if (!empty($old_column_exists)) {
            // Migrate data from old column to new column
            $wpdb->query("UPDATE $table_variants SET image_url_1 = image_url WHERE image_url IS NOT NULL AND image_url != ''");
            $wpdb->query("ALTER TABLE $table_variants DROP COLUMN image_url");
        }
        
        // Add image_url and Stripe ID columns to extras table if they don't exist
        $table_extras = $wpdb->prefix . 'produkt_extras';
        $extra_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'image_url'");
        if (empty($extra_column_exists)) {
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN image_url TEXT AFTER price");
        }
        $product_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'stripe_product_id'");
        if (empty($product_id_exists)) {
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN stripe_product_id VARCHAR(255) DEFAULT NULL AFTER name");
        }
        $price_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'stripe_price_id'");
        if (empty($price_id_exists)) {
            $after = $product_id_exists ? 'stripe_product_id' : 'name';
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN stripe_price_id VARCHAR(255) DEFAULT NULL AFTER $after");
        }
        $rent_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'stripe_price_id_rent'");
        if (empty($rent_id_exists)) {
            $after = !empty($price_id_exists) ? 'stripe_price_id' : ($product_id_exists ? 'stripe_product_id' : 'name');
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN stripe_price_id_rent VARCHAR(255) DEFAULT NULL AFTER $after");
        }
        $sale_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'stripe_price_id_sale'");
        if (empty($sale_id_exists)) {
            $after = !empty($rent_id_exists) ? 'stripe_price_id_rent' : (!empty($price_id_exists) ? 'stripe_price_id' : ($product_id_exists ? 'stripe_product_id' : 'name'));
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN stripe_price_id_sale VARCHAR(255) DEFAULT NULL AFTER $after");
        }

        // Ensure stripe_archived column exists
        $archived_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'stripe_archived'");
        if (empty($archived_exists)) {
            $after = !empty($price_id_exists) ? 'stripe_price_id' : 'name';
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN stripe_archived TINYINT(1) DEFAULT 0 AFTER $after");
        }

        // Ensure inventory columns exist for extras
        $sku_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'sku'");
        if (empty($sku_exists)) {
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN sku VARCHAR(100) DEFAULT '' AFTER image_url");
        }
        $avail_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'stock_available'");
        if (empty($avail_exists)) {
            $after = !empty($sku_exists) ? 'sku' : 'image_url';
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN stock_available INT DEFAULT 0 AFTER $after");
        }
        $rented_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'stock_rented'");
        if (empty($rented_exists)) {
            $after = !empty($avail_exists) ? 'stock_available' : (!empty($sku_exists) ? 'sku' : 'image_url');
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN stock_rented INT DEFAULT 0 AFTER $after");
        }

        // Per-extra toggle for showing the quantity on frontend
        $extra_show_stock_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'show_stock'");
        if (empty($extra_show_stock_exists)) {
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN show_stock TINYINT(1) DEFAULT 0 AFTER stock_rented");
        }

        // Per-extra low stock threshold
        $extra_threshold_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'stock_threshold'");
        if (empty($extra_threshold_exists)) {
            $after = !empty($extra_show_stock_exists) ? 'show_stock' : 'stock_rented';
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN stock_threshold INT DEFAULT 0 AFTER $after");
        }

        // Ensure separate price columns exist
        $rent_price_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'price_rent'");
        if (empty($rent_price_exists)) {
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN price_rent DECIMAL(10,2) DEFAULT 0 AFTER price");
        }
        $sale_price_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'price_sale'");
        if (empty($sale_price_exists)) {
            $after = !empty($rent_price_exists) ? 'price_rent' : 'price';
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN price_sale DECIMAL(10,2) DEFAULT 0 AFTER $after");
        }

        // Ensure show_badge column exists for durations
        $table_durations = $wpdb->prefix . 'produkt_durations';
        $badge_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_durations LIKE 'show_badge'");
        if (empty($badge_exists)) {
            $wpdb->query("ALTER TABLE $table_durations ADD COLUMN show_badge TINYINT(1) DEFAULT 0 AFTER discount");
        }

        $popular_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_durations LIKE 'show_popular'");
        if (empty($popular_exists)) {
            $wpdb->query("ALTER TABLE $table_durations ADD COLUMN show_popular TINYINT(1) DEFAULT 0 AFTER show_badge");
        }

        $popular_gradient_start_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_durations LIKE 'popular_gradient_start'");
        if (empty($popular_gradient_start_exists)) {
            $wpdb->query("ALTER TABLE $table_durations ADD COLUMN popular_gradient_start VARCHAR(30) DEFAULT '' AFTER show_popular");
        }

        $popular_gradient_end_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_durations LIKE 'popular_gradient_end'");
        if (empty($popular_gradient_end_exists)) {
            $wpdb->query("ALTER TABLE $table_durations ADD COLUMN popular_gradient_end VARCHAR(30) DEFAULT '' AFTER popular_gradient_start");
        }

        $popular_text_color_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_durations LIKE 'popular_text_color'");
        if (empty($popular_text_color_exists)) {
            $wpdb->query("ALTER TABLE $table_durations ADD COLUMN popular_text_color VARCHAR(30) DEFAULT '' AFTER popular_gradient_end");
        }

        // Ensure inventory_enabled column exists for categories
        $table_categories = $wpdb->prefix . 'produkt_categories';
        $inventory_enabled_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_categories LIKE 'inventory_enabled'");
        if (empty($inventory_enabled_exists)) {
            $wpdb->query("ALTER TABLE $table_categories ADD COLUMN inventory_enabled TINYINT(1) DEFAULT 0 AFTER sort_order");
        }

        // Ensure show_stock column exists for variants
        $table_variants = $wpdb->prefix . 'produkt_variants';
        $show_stock_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_variants LIKE 'show_stock'");
        if (empty($show_stock_exists)) {
            $wpdb->query("ALTER TABLE $table_variants ADD COLUMN show_stock TINYINT(1) DEFAULT 0 AFTER stock_rented");
        }

        // Ensure stock_threshold column exists for variants
        $stock_threshold_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_variants LIKE 'stock_threshold'");
        if (empty($stock_threshold_exists)) {
            $wpdb->query("ALTER TABLE $table_variants ADD COLUMN stock_threshold INT DEFAULT 0 AFTER show_stock");
        }
        
        // Create categories table if it doesn't exist
        $table_categories = $wpdb->prefix . 'produkt_categories';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_categories'");
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_categories (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                shortcode varchar(100) NOT NULL UNIQUE,
                page_title varchar(255) DEFAULT '',
                page_description text DEFAULT '',
                meta_title varchar(255) DEFAULT '',
                meta_description text DEFAULT '',
                product_title varchar(255) DEFAULT '',
                short_description text DEFAULT '',
                product_description text DEFAULT '',
                default_image text DEFAULT '',
                features_title varchar(255) DEFAULT '',
                feature_1_icon text DEFAULT '',
                feature_1_title varchar(255) DEFAULT '',
                feature_1_description text DEFAULT '',
                feature_2_icon text DEFAULT '',
                feature_2_title varchar(255) DEFAULT '',
                feature_2_description text DEFAULT '',
                feature_3_icon text DEFAULT '',
                feature_3_title varchar(255) DEFAULT '',
                feature_3_description text DEFAULT '',
                feature_4_icon text DEFAULT '',
                feature_4_title varchar(255) DEFAULT '',
                feature_4_description text DEFAULT '',
                button_text varchar(255) DEFAULT '',
                button_icon text DEFAULT '',
                shipping_cost decimal(10,2) DEFAULT 0,
                shipping_provider varchar(50) DEFAULT '',
                shipping_price_id varchar(255) DEFAULT '',
                price_label varchar(255) DEFAULT 'Monatlicher Mietpreis',
                shipping_label varchar(255) DEFAULT 'Einmalige Versandkosten:',
                price_period varchar(20) DEFAULT 'month',
                vat_included tinyint(1) DEFAULT 0,
                layout_style varchar(50) DEFAULT 'default',
                price_layout varchar(50) DEFAULT 'default',
                description_layout varchar(50) DEFAULT 'left',
                duration_tooltip text DEFAULT '',
                condition_tooltip text DEFAULT '',
                show_features tinyint(1) DEFAULT 0,
                show_tooltips tinyint(1) DEFAULT 1,
                show_rating tinyint(1) DEFAULT 0,
                rating_value decimal(3,1) DEFAULT 0,
                rating_link text DEFAULT '',
                active tinyint(1) DEFAULT 1,
                sort_order int(11) DEFAULT 0,
                PRIMARY KEY (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            // Add new columns to existing categories table
            $new_columns = array(
                'meta_title' => 'VARCHAR(255)',
                'meta_description' => 'TEXT',
                'short_description' => 'TEXT',
                'features_title' => "VARCHAR(255) DEFAULT ''",
                'feature_1_icon' => 'TEXT',
                'feature_1_title' => 'VARCHAR(255)',
                'feature_1_description' => 'TEXT',
                'feature_2_icon' => 'TEXT',
                'feature_2_title' => 'VARCHAR(255)',
                'feature_2_description' => 'TEXT',
                'feature_3_icon' => 'TEXT',
                'feature_3_title' => 'VARCHAR(255)',
                'feature_3_description' => 'TEXT',
                'feature_4_icon' => 'TEXT',
                'feature_4_title' => 'VARCHAR(255)',
                'feature_4_description' => 'TEXT',
                'button_text' => 'VARCHAR(255)',
                'button_icon' => 'TEXT',
                'payment_icons'   => 'TEXT',
                'accordion_data'  => 'LONGTEXT',
                'page_blocks'     => 'LONGTEXT',
                'detail_blocks'   => 'LONGTEXT',
                'tech_blocks'     => 'LONGTEXT',
                'scope_blocks'    => 'LONGTEXT',
                'shipping_cost'   => 'DECIMAL(10,2) DEFAULT 0',
                'shipping_provider' => "VARCHAR(50) DEFAULT ''",
                'shipping_price_id' => "VARCHAR(255) DEFAULT ''",
                'price_label' => "VARCHAR(255) DEFAULT 'Monatlicher Mietpreis'",
                'shipping_label' => "VARCHAR(255) DEFAULT 'Einmalige Versandkosten:'",
                'price_period' => "VARCHAR(20) DEFAULT 'month'",
                'vat_included' => 'TINYINT(1) DEFAULT 0',
                'layout_style' => "VARCHAR(50) DEFAULT 'default'",
                'price_layout' => "VARCHAR(50) DEFAULT 'default'",
                'description_layout' => "VARCHAR(50) DEFAULT 'left'",
                'duration_tooltip' => 'TEXT',
                'condition_tooltip' => 'TEXT',
                'show_features' => 'TINYINT(1) DEFAULT 0',
                'show_tooltips' => 'TINYINT(1) DEFAULT 1',
                'show_rating' => 'TINYINT(1) DEFAULT 0',
                'rating_value' => 'DECIMAL(3,1) DEFAULT 0',
                'rating_link' => 'TEXT'
            );
            
            foreach ($new_columns as $column => $type) {
                $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_categories LIKE '$column'");
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE $table_categories ADD COLUMN $column $type");
                }
            }
        }
        
        // Create analytics table if it doesn't exist
        $table_analytics = $wpdb->prefix . 'produkt_analytics';
        $analytics_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_analytics'");
        
        if (!$analytics_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_analytics (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                category_id mediumint(9) NOT NULL,
                event_type varchar(50) NOT NULL,
                variant_id mediumint(9) DEFAULT NULL,
                extra_id mediumint(9) DEFAULT NULL,
                duration_id mediumint(9) DEFAULT NULL,
                condition_id mediumint(9) DEFAULT NULL,
                product_color_id mediumint(9) DEFAULT NULL,
                frame_color_id mediumint(9) DEFAULT NULL,
                user_ip varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                invoice_url varchar(255) DEFAULT '',
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY category_id (category_id),
                KEY event_type (event_type),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            // Add new columns to analytics table
            $new_analytics_columns = array(
                'condition_id' => 'mediumint(9)',
                'product_color_id' => 'mediumint(9)',
                'frame_color_id' => 'mediumint(9)',
                'extra_ids' => 'varchar(255)'
            );
            
            foreach ($new_analytics_columns as $column => $type) {
                $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_analytics LIKE '$column'");
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE $table_analytics ADD COLUMN $column $type AFTER duration_id");
                }
            }
        }
        
        // Create branding settings table if it doesn't exist
        $table_branding = $wpdb->prefix . 'produkt_branding';
        $branding_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_branding'");
        
        if (!$branding_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_branding (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                setting_key varchar(255) NOT NULL,
                setting_value longtext,
                PRIMARY KEY (id),
                UNIQUE KEY setting_key (setting_key)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Insert default branding settings
            $default_branding = array(
                'plugin_name' => 'H2 Rental Pro',
                'plugin_description' => 'Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration',
                'company_name' => 'H2 Concepts',
                'company_url' => 'https://h2concepts.de',
                'admin_logo' => '',
                'admin_color_primary'   => '#5f7f5f',
                'admin_color_secondary' => '#4a674a',
                'admin_color_text'      => '#ffffff',
                'front_button_color'    => '#5f7f5f',
                'front_text_color'      => '#4a674a',
                'front_border_color'    => '#a4b8a4',
                'front_button_text_color' => '#ffffff',
                'filter_button_color'  => '#5f7f5f',
                'filter_button_hover_color'  => '#5f7f5f',
                'filter_button_icon_color' => '#ffffff',
                'filter_button_position' => 'bottom_left',
                'shop_layout' => 'filters_left',
                'sticky_header_mode' => 'header',
                'product_padding'     => '1',
                'login_bg_image' => '',
                'login_layout' => 'classic',
                'login_logo'   => '',
                'login_text_color' => '#1f1f1f',
                'footer_text' => 'Powered by H2 Concepts',
                'custom_css' => ''
            );
            
            foreach ($default_branding as $key => $value) {
                $wpdb->insert(
                    $table_branding,
                    array(
                        'setting_key' => $key,
                        'setting_value' => $value
                    )
                );
            }
        }

        // Ensure new branding settings exist
        $branding_defaults = array(
            'front_button_color'       => '#5f7f5f',
            'front_text_color'         => '#4a674a',
            'front_border_color'       => '#a4b8a4',
            'front_button_text_color'  => '#ffffff',
            'filter_button_color'      => '#5f7f5f',
            'filter_button_hover_color'  => '#5f7f5f',
            'filter_button_icon_color' => '#ffffff',
            'filter_button_position'   => 'bottom_left',
            'shop_layout'              => 'filters_left',
            'sticky_header_mode'       => 'header',
            'product_padding'          => '1',
            'login_bg_image'           => '',
            'login_layout'            => 'classic',
            'login_logo'              => '',
            'login_text_color'        => '#1f1f1f'
        );
        foreach ($branding_defaults as $key => $value) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_branding WHERE setting_key = %s", $key));
            if (!$exists) {
                $wpdb->insert($table_branding, array('setting_key' => $key, 'setting_value' => $value));
            }
        }
        
        // Create conditions table if it doesn't exist
        $table_conditions = $wpdb->prefix . 'produkt_conditions';
        $conditions_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_conditions'");
        
        if (!$conditions_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_conditions (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                category_id mediumint(9) DEFAULT 1,
                name varchar(255) NOT NULL,
                description text DEFAULT '',
                price_modifier decimal(5,4) DEFAULT 0,
                available tinyint(1) DEFAULT 1,
                active tinyint(1) DEFAULT 1,
                sort_order int(11) DEFAULT 0,
                PRIMARY KEY (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Create colors table if it doesn't exist
        $table_colors = $wpdb->prefix . 'produkt_colors';
        $colors_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_colors'");

        if (!$colors_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_colors (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                category_id mediumint(9) DEFAULT 1,
                name varchar(255) NOT NULL,
                color_code varchar(7) NOT NULL,
                is_multicolor tinyint(1) DEFAULT 0,
                color_type varchar(20) NOT NULL,
                image_url text,
                available tinyint(1) DEFAULT 1,
                active tinyint(1) DEFAULT 1,
                sort_order int(11) DEFAULT 0,
                PRIMARY KEY (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_colors LIKE 'image_url'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_colors ADD COLUMN image_url TEXT AFTER color_type");
            }

            $multicolor_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_colors LIKE 'is_multicolor'");
            if (empty($multicolor_exists)) {
                $wpdb->query("ALTER TABLE $table_colors ADD COLUMN is_multicolor TINYINT(1) DEFAULT 0 AFTER color_code");
            }
        }

        // Create color variant images table if it doesn't exist
        $table_color_variant_images = $wpdb->prefix . 'produkt_color_variant_images';
        $color_variant_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_color_variant_images'");

        if (!$color_variant_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_color_variant_images (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                color_id mediumint(9) NOT NULL,
                variant_id mediumint(9) NOT NULL,
                image_url text DEFAULT '',
                PRIMARY KEY (id),
                UNIQUE KEY color_variant (color_id, variant_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Create variant options table if it doesn't exist
        $table_variant_options = $wpdb->prefix . 'produkt_variant_options';
        $variant_options_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_variant_options'");
        
        if (!$variant_options_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_variant_options (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                variant_id mediumint(9) NOT NULL,
                option_type varchar(50) NOT NULL,
                option_id mediumint(9) NOT NULL,
                condition_id mediumint(9) DEFAULT NULL,
                available tinyint(1) DEFAULT 1,
                sale_available tinyint(1) DEFAULT 0,
                stock_available int DEFAULT 0,
                stock_rented int DEFAULT 0,
                sku varchar(255) DEFAULT NULL,
                stock_threshold int DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY variant_option (variant_id, option_type, option_id, condition_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Ensure condition_id column exists for variant options
        $condition_column = $wpdb->get_results("SHOW COLUMNS FROM $table_variant_options LIKE 'condition_id'");
        if (empty($condition_column)) {
            $wpdb->query("ALTER TABLE $table_variant_options ADD COLUMN condition_id mediumint(9) DEFAULT NULL AFTER option_id");
        }

        // Ensure unique index includes condition_id
        $existing_index = $wpdb->get_results("SHOW INDEX FROM $table_variant_options WHERE Key_name = 'variant_option'");
        $index_needs_update = false;
        if (!empty($existing_index)) {
            $columns = array_map(function($row) { return $row->Column_name; }, $existing_index);
            if (!in_array('condition_id', $columns, true)) {
                $index_needs_update = true;
            }
        }

        if ($index_needs_update) {
            $wpdb->query("ALTER TABLE $table_variant_options DROP INDEX variant_option");
            $wpdb->query("ALTER TABLE $table_variant_options ADD UNIQUE KEY variant_option (variant_id, option_type, option_id, condition_id)");
        }

        // Create variant durations table if it doesn't exist
        $table_variant_durations = $wpdb->prefix . 'produkt_variant_durations';
        $variant_durations_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_variant_durations'");

        if (!$variant_durations_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_variant_durations (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                variant_id mediumint(9) NOT NULL,
                duration_id mediumint(9) NOT NULL,
                available tinyint(1) DEFAULT 1,
                sort_order int(11) DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY variant_duration (variant_id, duration_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Create duration price table if it doesn't exist
        $table_duration_prices = $wpdb->prefix . 'produkt_duration_prices';
        $duration_prices_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_duration_prices'");

        if (!$duration_prices_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_duration_prices (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                duration_id mediumint(9) NOT NULL,
                variant_id mediumint(9) NOT NULL,
                stripe_product_id varchar(255) DEFAULT NULL,
                stripe_price_id varchar(255) DEFAULT NULL,
                stripe_archived tinyint(1) DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY duration_variant (duration_id, variant_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            $exists = $wpdb->get_results("SHOW COLUMNS FROM $table_duration_prices LIKE 'stripe_product_id'");
            if (empty($exists)) {
                $wpdb->query("ALTER TABLE $table_duration_prices ADD COLUMN stripe_product_id VARCHAR(255) DEFAULT NULL AFTER variant_id");
            }
            $exists = $wpdb->get_results("SHOW COLUMNS FROM $table_duration_prices LIKE 'stripe_price_id'");
            if (empty($exists)) {
                $wpdb->query("ALTER TABLE $table_duration_prices ADD COLUMN stripe_price_id VARCHAR(255) DEFAULT NULL AFTER stripe_product_id");
            }
            $exists = $wpdb->get_results("SHOW COLUMNS FROM $table_duration_prices LIKE 'mietpreis_monatlich'");
            if (empty($exists)) {
                $wpdb->query("ALTER TABLE $table_duration_prices ADD COLUMN mietpreis_monatlich DECIMAL(10,2) DEFAULT 0 AFTER stripe_price_id");
            }
            $exists = $wpdb->get_results("SHOW COLUMNS FROM $table_duration_prices LIKE 'verkaufspreis_einmalig'");
            if (empty($exists)) {
                $wpdb->query("ALTER TABLE $table_duration_prices ADD COLUMN verkaufspreis_einmalig DECIMAL(10,2) DEFAULT 0 AFTER mietpreis_monatlich");
            }
            $exists = $wpdb->get_results("SHOW COLUMNS FROM $table_duration_prices LIKE 'custom_price'");
            if (empty($exists)) {
                $wpdb->query("ALTER TABLE $table_duration_prices ADD COLUMN custom_price DECIMAL(10,2) DEFAULT NULL AFTER verkaufspreis_einmalig");
            }

            // Ensure stripe_archived column exists
            $exists = $wpdb->get_results("SHOW COLUMNS FROM $table_duration_prices LIKE 'stripe_archived'");
            if (empty($exists)) {
                $wpdb->query("ALTER TABLE $table_duration_prices ADD COLUMN stripe_archived TINYINT(1) DEFAULT 0 AFTER stripe_price_id");
            }
        }
        
        // Create orders table if it doesn't exist
        $table_orders = $wpdb->prefix . 'produkt_orders';
        $orders_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_orders'");
        
        if (!$orders_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_orders (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                category_id mediumint(9) NOT NULL,
                variant_id mediumint(9) NOT NULL,
                extra_id mediumint(9) NOT NULL,
                extra_ids varchar(255) DEFAULT NULL,
                duration_id mediumint(9) NOT NULL,
                condition_id mediumint(9) DEFAULT NULL,
                product_color_id mediumint(9) DEFAULT NULL,
                frame_color_id mediumint(9) DEFAULT NULL,
                final_price decimal(10,2) NOT NULL,
                shipping_cost decimal(10,2) DEFAULT 0,
                shipping_provider varchar(50) DEFAULT '',
                mode varchar(10) DEFAULT 'miete',
                start_date date DEFAULT NULL,
                end_date date DEFAULT NULL,
                inventory_reverted tinyint(1) DEFAULT 0,
                weekend_tariff tinyint(1) DEFAULT 0,
                stripe_session_id varchar(255) DEFAULT '',
                stripe_subscription_id varchar(255) DEFAULT '',
                amount_total int DEFAULT 0,
                discount_amount decimal(10,2) DEFAULT 0,
                produkt_name varchar(255) DEFAULT '',
                zustand_text varchar(255) DEFAULT '',
                produktfarbe_text varchar(255) DEFAULT '',
                gestellfarbe_text varchar(255) DEFAULT '',
                extra_text text,
                dauer_text varchar(255) DEFAULT '',
                customer_name varchar(255) DEFAULT '',
                customer_email varchar(255) DEFAULT '',
                order_number varchar(50) DEFAULT '',
                invoice_number varchar(50) DEFAULT '',
                user_ip varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                client_info text DEFAULT NULL,
                tracking_number varchar(255) DEFAULT '',
                tracking_sent_at datetime DEFAULT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY category_id (category_id),
                KEY created_at (created_at)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            $new_order_columns = array(
                'extra_ids'         => 'varchar(255)',
                'zustand_text'      => "varchar(255) DEFAULT ''",
                'produktfarbe_text' => "varchar(255) DEFAULT ''",
                'gestellfarbe_text' => "varchar(255) DEFAULT ''",
                'produkt_name'      => "varchar(255) DEFAULT ''",
                'stripe_session_id' => "varchar(255) DEFAULT ''",
                'stripe_subscription_id' => "varchar(255) DEFAULT ''",
                'amount_total'      => 'int DEFAULT 0',
                'discount_amount'   => 'decimal(10,2) DEFAULT 0',
                'extra_text'        => 'text',
                'dauer_text'        => "varchar(255) DEFAULT ''",
                'customer_phone'    => "varchar(50) DEFAULT ''",
                'customer_street'   => "varchar(255) DEFAULT ''",
                'customer_postal'   => "varchar(20) DEFAULT ''",
                'customer_city'     => "varchar(100) DEFAULT ''",
                'customer_country'  => "varchar(2) DEFAULT ''",
                'shipping_cost'     => 'decimal(10,2) DEFAULT 0',
                'shipping_provider' => "varchar(50) DEFAULT ''",
                'shipping_price_id' => "varchar(255) DEFAULT ''",
                'order_number'      => "varchar(50) DEFAULT ''",
                'invoice_number'    => "varchar(50) DEFAULT ''",
                'mode'              => "varchar(10) DEFAULT 'miete'",
                'start_date'        => 'date DEFAULT NULL',
                'end_date'          => 'date DEFAULT NULL',
                'inventory_reverted'=> 'tinyint(1) DEFAULT 0',
                'weekend_tariff'    => 'tinyint(1) DEFAULT 0',
                'status'            => "varchar(20) DEFAULT 'offen'",
                'invoice_url'       => "varchar(255) DEFAULT ''",
                'client_info'       => 'text',
                'tracking_number'   => "varchar(255) DEFAULT ''",
                'tracking_sent_at'  => 'datetime DEFAULT NULL'
            );

            foreach ($new_order_columns as $column => $type) {
                $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_orders LIKE '$column'");
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE $table_orders ADD COLUMN $column $type AFTER extra_id");
                }
            }

            $order_items_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_orders LIKE 'order_items'");
            if (empty($order_items_exists)) {
                $wpdb->query("ALTER TABLE $table_orders ADD COLUMN order_items LONGTEXT AFTER client_info");
            }

            // Fill newly added date columns from dauer_text if possible
            $missing_dates = $wpdb->get_results("SELECT id, dauer_text FROM $table_orders WHERE start_date IS NULL AND dauer_text LIKE '%-%'");
            foreach ($missing_dates as $row) {
                if (preg_match('/(\d{4}-\d{2}-\d{2})\s*-\s*(\d{4}-\d{2}-\d{2})/', $row->dauer_text, $m)) {
                    $wpdb->update(
                        $table_orders,
                        ['start_date' => $m[1], 'end_date' => $m[2]],
                        ['id' => $row->id],
                        ['%s','%s'],
                        ['%d']
                    );
                }
            }
        }

        // Create customers table if it doesn't exist
        $table_customers = $wpdb->prefix . 'produkt_customers';
        $customers_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_customers'");

        if (!$customers_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_customers (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                email varchar(255) NOT NULL,
                stripe_customer_id varchar(255) NOT NULL,
                first_name varchar(255) DEFAULT '',
                last_name varchar(255) DEFAULT '',
                phone varchar(50) DEFAULT '',
                street varchar(255) DEFAULT '',
                postal_code varchar(20) DEFAULT '',
                city varchar(255) DEFAULT '',
                country varchar(50) DEFAULT '',
                PRIMARY KEY (id),
                UNIQUE KEY email (email)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Create notifications table if it doesn't exist
        $table_notifications = $wpdb->prefix . 'produkt_notifications';
        $notifications_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_notifications'");

        if (!$notifications_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_notifications (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                category_id mediumint(9) NOT NULL,
                variant_id mediumint(9) DEFAULT NULL,
                extra_ids varchar(255) DEFAULT NULL,
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
        } else {
            $new_columns = array(
                'extra_ids'        => 'varchar(255)',
                'duration_id'      => 'mediumint(9)',
                'condition_id'     => 'mediumint(9)',
                'product_color_id' => 'mediumint(9)',
                'frame_color_id'   => 'mediumint(9)'
            );

            foreach ($new_columns as $column => $type) {
                $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_notifications LIKE '$column'");
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE $table_notifications ADD COLUMN $column $type");
                }
            }
        }

        // Newsletter Opt-ins table (Double Opt-In)
        $table_newsletter = $wpdb->prefix . 'produkt_newsletter_optins';
        $newsletter_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_newsletter'");
        if (!$newsletter_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql_newsletter = "CREATE TABLE $table_newsletter (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                email VARCHAR(255) NOT NULL,
                first_name VARCHAR(255) DEFAULT '',
                last_name VARCHAR(255) DEFAULT '',
                phone VARCHAR(50) DEFAULT '',
                street VARCHAR(255) DEFAULT '',
                postal_code VARCHAR(20) DEFAULT '',
                city VARCHAR(255) DEFAULT '',
                country VARCHAR(50) DEFAULT '',
                status TINYINT(1) DEFAULT 0,
                token_hash CHAR(64) DEFAULT NULL,
                requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                confirmed_at DATETIME DEFAULT NULL,
                confirm_ip VARCHAR(45) DEFAULT NULL,
                confirm_user_agent TEXT DEFAULT NULL,
                stripe_session_id VARCHAR(255) DEFAULT '',
                PRIMARY KEY (id),
                UNIQUE KEY email (email),
                KEY status (status),
                KEY requested_at (requested_at)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_newsletter);
        }

        // Add availability column to variant options table if it doesn't exist
        $table_variant_options = $wpdb->prefix . 'produkt_variant_options';
        $availability_column = $wpdb->get_results("SHOW COLUMNS FROM $table_variant_options LIKE 'available'");
        if (empty($availability_column)) {
            $wpdb->query("ALTER TABLE $table_variant_options ADD COLUMN available TINYINT(1) DEFAULT 1 AFTER option_id");
        }

        // Per-row toggle for showing the quantity on frontend
        $vo_show_stock_column = $wpdb->get_results("SHOW COLUMNS FROM $table_variant_options LIKE 'show_stock'");
        if (empty($vo_show_stock_column)) {
            $wpdb->query("ALTER TABLE $table_variant_options ADD COLUMN show_stock TINYINT(1) DEFAULT 0 AFTER available");
        }

        $stock_avail_column = $wpdb->get_results("SHOW COLUMNS FROM $table_variant_options LIKE 'stock_available'");
        if (empty($stock_avail_column)) {
            $after = !empty($vo_show_stock_column) ? 'show_stock' : 'available';
            $wpdb->query("ALTER TABLE $table_variant_options ADD COLUMN stock_available INT DEFAULT 0 AFTER $after");
        }

        $stock_rented_column = $wpdb->get_results("SHOW COLUMNS FROM $table_variant_options LIKE 'stock_rented'");
        if (empty($stock_rented_column)) {
            $wpdb->query("ALTER TABLE $table_variant_options ADD COLUMN stock_rented INT DEFAULT 0 AFTER stock_available");
        }

        $sku_column = $wpdb->get_results("SHOW COLUMNS FROM $table_variant_options LIKE 'sku'");
        if (empty($sku_column)) {
            $wpdb->query("ALTER TABLE $table_variant_options ADD COLUMN sku VARCHAR(255) DEFAULT NULL AFTER stock_rented");
        }

        // Per-row low stock threshold for variant options (e.g. per condition + color)
        $vo_threshold_column = $wpdb->get_results("SHOW COLUMNS FROM $table_variant_options LIKE 'stock_threshold'");
        if (empty($vo_threshold_column)) {
            $wpdb->query("ALTER TABLE $table_variant_options ADD COLUMN stock_threshold INT DEFAULT 0 AFTER sku");
        }

        $sale_available_column = $wpdb->get_results("SHOW COLUMNS FROM $table_variant_options LIKE 'sale_available'");
        if (empty($sale_available_column)) {
            $wpdb->query("ALTER TABLE $table_variant_options ADD COLUMN sale_available TINYINT(1) DEFAULT 0 AFTER available");
        }

        // Create order logs table if it doesn't exist
        $table_logs = $wpdb->prefix . 'produkt_order_logs';
        $logs_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_logs'");
        if (!$logs_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_logs (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                order_id mediumint(9) NOT NULL,
                event varchar(50) NOT NULL,
                message text DEFAULT '',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY order_id (order_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Create customer notes table if it doesn't exist
        $table_cnotes = $wpdb->prefix . 'produkt_customer_notes';
        $cnotes_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_cnotes'");
        if (!$cnotes_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_cnotes (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                customer_id mediumint(9) NOT NULL,
                message text NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY customer_id (customer_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Create content blocks table if it doesn't exist
        $table_blocks = $wpdb->prefix . 'produkt_content_blocks';
        $blocks_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_blocks'");
        if (!$blocks_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_blocks (
                id INT NOT NULL AUTO_INCREMENT,
                category_id INT NOT NULL,
                position INT NOT NULL,
                position_mobile INT NOT NULL DEFAULT 6,
                style VARCHAR(20) DEFAULT 'wide',
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                image_url TEXT,
                button_text TEXT,
                button_url TEXT,
                PRIMARY KEY (id),
                KEY category_id (category_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            // Add missing columns for desktop/mobile positions
            $mobile_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_blocks LIKE 'position_mobile'");
            if (empty($mobile_exists)) {
                $wpdb->query("ALTER TABLE $table_blocks ADD COLUMN position_mobile INT NOT NULL DEFAULT 6 AFTER position");
            }

            $color_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_blocks LIKE 'background_color'");
            if (empty($color_exists)) {
                $wpdb->query("ALTER TABLE $table_blocks ADD COLUMN background_color VARCHAR(20) DEFAULT '' AFTER button_url");
            }
            $badge_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_blocks LIKE 'badge_text'");
            if (empty($badge_exists)) {
                $wpdb->query("ALTER TABLE $table_blocks ADD COLUMN badge_text TEXT AFTER background_color");
            }
            $style_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_blocks LIKE 'style'");
            if (empty($style_exists)) {
                $wpdb->query("ALTER TABLE $table_blocks ADD COLUMN style VARCHAR(20) DEFAULT 'wide' AFTER position_mobile");
            }
        }

        // Create shipping methods table if it doesn't exist
        $table_shipping = $wpdb->prefix . 'produkt_shipping_methods';
        $shipping_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_shipping'");
        if (!$shipping_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_shipping (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                service_provider VARCHAR(50),
                stripe_product_id VARCHAR(255),
                stripe_price_id VARCHAR(255),
                is_default TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            $exists = $wpdb->get_results("SHOW COLUMNS FROM $table_shipping LIKE 'is_default'");
            if (empty($exists)) {
                $wpdb->query("ALTER TABLE $table_shipping ADD COLUMN is_default TINYINT(1) DEFAULT 0 AFTER stripe_price_id");
            }
        }

        // Create blocked days table if it doesn't exist
        $table_blocked = $wpdb->prefix . 'produkt_blocked_days';
        $blocked_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_blocked'");
        if (!$blocked_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_blocked (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                day date NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY day (day)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Create filter groups table
        $table_filter_groups = $wpdb->prefix . 'produkt_filter_groups';
        $groups_exists      = $wpdb->get_var("SHOW TABLES LIKE '$table_filter_groups'");
        if (!$groups_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql             = "CREATE TABLE $table_filter_groups (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }

        // Create filters table if it doesn't exist or ensure group_id column
        $table_filters  = $wpdb->prefix . 'produkt_filters';
        $filters_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_filters'");
        if (!$filters_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql             = "CREATE TABLE $table_filters (
                id INT NOT NULL AUTO_INCREMENT,
                group_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                PRIMARY KEY (id),
                KEY group_id (group_id)
            ) $charset_collate;";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        } else {
            $col = $wpdb->get_results("SHOW COLUMNS FROM $table_filters LIKE 'group_id'");
            if (empty($col)) {
                $wpdb->query("ALTER TABLE $table_filters ADD COLUMN group_id INT NOT NULL DEFAULT 0 AFTER id");
                $wpdb->query("ALTER TABLE $table_filters ADD KEY group_id (group_id)");
            }
        }

        // Mapping table between products and filters
        $table_cat_filters = $wpdb->prefix . 'produkt_category_filters';
        $map_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_cat_filters'");
        if (!$map_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_cat_filters (
                category_id INT NOT NULL,
                filter_id INT NOT NULL,
                PRIMARY KEY (category_id, filter_id),
                KEY filter_id (filter_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Ensure review reminders table exists
        $table_review_reminders = $wpdb->prefix . 'produkt_review_reminders';
        $reminder_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_review_reminders'");
        if (!$reminder_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $reminder_sql = "CREATE TABLE $table_review_reminders (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                customer_id BIGINT UNSIGNED NOT NULL,
                subscription_key VARCHAR(255) NOT NULL,
                sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_customer_subscription (customer_id, subscription_key),
                KEY sent_at (sent_at)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($reminder_sql);
        }
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Simple category table for grouping products
        $table_prod_categories = $wpdb->prefix . 'produkt_product_categories';
        $sql_prod_categories = "CREATE TABLE IF NOT EXISTS $table_prod_categories (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            parent_id INT UNSIGNED DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parent_id (parent_id)
        ) $charset_collate;";

        // Mapping table between products and categories
        $table_prod_to_cat = $wpdb->prefix . 'produkt_product_to_category';
        $sql_prod_to_cat = "CREATE TABLE IF NOT EXISTS $table_prod_to_cat (
            produkt_id INT UNSIGNED NOT NULL,
            category_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (produkt_id, category_id),
            KEY category_id (category_id)
        ) $charset_collate;";
        
        // Categories table for different product categories (with SEO fields)
        $table_categories = $wpdb->prefix . 'produkt_categories';
        $sql_categories = "CREATE TABLE $table_categories (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            shortcode varchar(100) NOT NULL UNIQUE,
            page_title varchar(255) DEFAULT '',
            page_description text DEFAULT '',
            meta_title varchar(255) DEFAULT '',
            meta_description text DEFAULT '',
            product_title varchar(255) DEFAULT '',
            product_description text DEFAULT '',
            default_image text DEFAULT '',
            features_title varchar(255) DEFAULT '',
            feature_1_icon text DEFAULT '',
            feature_1_title varchar(255) DEFAULT '',
            feature_1_description text DEFAULT '',
            feature_2_icon text DEFAULT '',
            feature_2_title varchar(255) DEFAULT '',
            feature_2_description text DEFAULT '',
            feature_3_icon text DEFAULT '',
            feature_3_title varchar(255) DEFAULT '',
            feature_3_description text DEFAULT '',
            feature_4_icon text DEFAULT '',
            feature_4_title varchar(255) DEFAULT '',
            feature_4_description text DEFAULT '',
            button_text varchar(255) DEFAULT '',
            button_icon text DEFAULT '',
            payment_icons text DEFAULT '',
            accordion_data longtext DEFAULT NULL,
            page_blocks longtext DEFAULT NULL,
            detail_blocks longtext DEFAULT NULL,
            tech_blocks longtext DEFAULT NULL,
            scope_blocks longtext DEFAULT NULL,
            shipping_cost decimal(10,2) DEFAULT 0,
            shipping_provider varchar(50) DEFAULT '',
            shipping_price_id varchar(255) DEFAULT '',
            price_label varchar(255) DEFAULT 'Monatlicher Mietpreis',
            shipping_label varchar(255) DEFAULT 'Einmalige Versandkosten:',
            price_period varchar(20) DEFAULT 'month',
            vat_included tinyint(1) DEFAULT 0,
            layout_style varchar(50) DEFAULT 'default',
            duration_tooltip text DEFAULT '',
            condition_tooltip text DEFAULT '',
            show_features tinyint(1) DEFAULT 0,
            show_tooltips tinyint(1) DEFAULT 1,
            show_rating tinyint(1) DEFAULT 0,
            rating_value decimal(3,1) DEFAULT 0,
            rating_link text DEFAULT '',
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Variants table (updated with multiple image fields, category_id and availability)
        $table_variants = $wpdb->prefix . 'produkt_variants';
        $sql_variants = "CREATE TABLE $table_variants (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            description text,
            stripe_product_id varchar(255) DEFAULT NULL,
            stripe_price_id varchar(255) DEFAULT NULL,
            stripe_price_id_sale varchar(255) DEFAULT NULL,
            stripe_price_id_rent varchar(255) DEFAULT NULL,
            stripe_archived tinyint(1) DEFAULT 0,
            mietpreis_monatlich decimal(10,2) DEFAULT 0,
            verkaufspreis_einmalig decimal(10,2) DEFAULT 0,
            weekend_price decimal(10,2) DEFAULT 0,
            stripe_weekend_price_id varchar(255) DEFAULT NULL,
            base_price decimal(10,2) NOT NULL,
            price_from decimal(10,2) DEFAULT 0,
            mode varchar(10) DEFAULT 'miete',
            sale_enabled tinyint(1) DEFAULT 0,
            image_url_1 text,
            image_url_2 text,
            image_url_3 text,
            image_url_4 text,
            image_url_5 text,
            available tinyint(1) DEFAULT 1,
            availability_note varchar(255) DEFAULT '',
            delivery_time varchar(255) DEFAULT '',
            sku varchar(100) DEFAULT '',
            stock_available int DEFAULT 0,
            stock_rented int DEFAULT 0,
            weekend_only tinyint(1) DEFAULT 0,
            min_rental_days int DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Extras table (updated with image field, category_id and Stripe price)
        $table_extras = $wpdb->prefix . 'produkt_extras';
        $sql_extras = "CREATE TABLE $table_extras (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            stripe_product_id varchar(255) DEFAULT NULL,
            stripe_price_id varchar(255) DEFAULT NULL,
            stripe_price_id_rent varchar(255) DEFAULT NULL,
            stripe_price_id_sale varchar(255) DEFAULT NULL,
            stripe_archived tinyint(1) DEFAULT 0,
            price_rent decimal(10,2) DEFAULT 0,
            price_sale decimal(10,2) DEFAULT 0,
            price decimal(10,2) NOT NULL,
            image_url text,
            sku varchar(100) DEFAULT '',
            stock_available int DEFAULT 0,
            stock_rented int DEFAULT 0,
            show_stock tinyint(1) DEFAULT 0,
            stock_threshold int DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Durations table (with category_id)
        $table_durations = $wpdb->prefix . 'produkt_durations';
        $sql_durations = "CREATE TABLE $table_durations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            months_minimum int(11) NOT NULL,
            discount decimal(5,4) DEFAULT 0,
            show_badge tinyint(1) DEFAULT 0,
            show_popular tinyint(1) DEFAULT 0,
            popular_gradient_start varchar(30) DEFAULT '',
            popular_gradient_end varchar(30) DEFAULT '',
            popular_text_color varchar(30) DEFAULT '',
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Links table (with category_id and new option columns)
        // Analytics table for tracking (with new option columns)
        $table_analytics = $wpdb->prefix . 'produkt_analytics';
        $sql_analytics = "CREATE TABLE $table_analytics (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) NOT NULL,
            event_type varchar(50) NOT NULL,
            variant_id mediumint(9) DEFAULT NULL,
            extra_id mediumint(9) DEFAULT NULL,
            extra_ids varchar(255) DEFAULT NULL,
            duration_id mediumint(9) DEFAULT NULL,
            condition_id mediumint(9) DEFAULT NULL,
            product_color_id mediumint(9) DEFAULT NULL,
            frame_color_id mediumint(9) DEFAULT NULL,
            user_ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Branding settings table
        $table_branding = $wpdb->prefix . 'produkt_branding';
        $sql_branding = "CREATE TABLE $table_branding (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        
        // Conditions table
        $table_conditions = $wpdb->prefix . 'produkt_conditions';
        $sql_conditions = "CREATE TABLE $table_conditions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            description text DEFAULT '',
            price_modifier decimal(5,4) DEFAULT 0,
            available tinyint(1) DEFAULT 1,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Colors table
        $table_colors = $wpdb->prefix . 'produkt_colors';
        $sql_colors = "CREATE TABLE $table_colors (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            color_code varchar(7) NOT NULL,
            is_multicolor tinyint(1) DEFAULT 0,
            color_type varchar(20) NOT NULL,
            image_url text,
            available tinyint(1) DEFAULT 1,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Color variant images table
        $table_color_variant_images = $wpdb->prefix . 'produkt_color_variant_images';
        $sql_color_variant_images = "CREATE TABLE $table_color_variant_images (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            color_id mediumint(9) NOT NULL,
            variant_id mediumint(9) NOT NULL,
            image_url text DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY color_variant (color_id, variant_id)
        ) $charset_collate;";
        
        // Variant options table
        // IMPORTANT: Keep this in sync with the actual table used in runtime code.
        // Older versions used UNIQUE KEY variant_option (variant_id, option_type, option_id) without condition_id.
        // That causes "Duplicate key name 'variant_option'" on activation when a newer unique index already exists.
        $table_variant_options = $wpdb->prefix . 'produkt_variant_options';
        $sql_variant_options = "CREATE TABLE $table_variant_options (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            variant_id mediumint(9) NOT NULL,
            option_type varchar(50) NOT NULL,
            option_id mediumint(9) NOT NULL,
            condition_id mediumint(9) DEFAULT NULL,
            available tinyint(1) DEFAULT 1,
            sale_available tinyint(1) DEFAULT 0,
            show_stock tinyint(1) DEFAULT 0,
            stock_available int DEFAULT 0,
            stock_rented int DEFAULT 0,
            sku varchar(255) DEFAULT NULL,
            stock_threshold int DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY variant_option (variant_id, option_type, option_id, condition_id)
        ) $charset_collate;";

        // Variant durations table
        $table_variant_durations = $wpdb->prefix . 'produkt_variant_durations';
        $sql_variant_durations = "CREATE TABLE $table_variant_durations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            variant_id mediumint(9) NOT NULL,
            duration_id mediumint(9) NOT NULL,
            available tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY variant_duration (variant_id, duration_id)
        ) $charset_collate;";

        // Variant price IDs per duration
        $table_duration_prices = $wpdb->prefix . 'produkt_duration_prices';
        $sql_duration_prices = "CREATE TABLE $table_duration_prices (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            duration_id mediumint(9) NOT NULL,
            variant_id mediumint(9) NOT NULL,
            stripe_product_id varchar(255) DEFAULT NULL,
            stripe_price_id varchar(255) DEFAULT NULL,
            stripe_archived tinyint(1) DEFAULT 0,
            mietpreis_monatlich decimal(10,2) DEFAULT 0,
            verkaufspreis_einmalig decimal(10,2) DEFAULT 0,
            custom_price decimal(10,2) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY duration_variant (duration_id, variant_id)
        ) $charset_collate;";
        
        // Orders table
        $table_orders = $wpdb->prefix . 'produkt_orders';
        $sql_orders = "CREATE TABLE $table_orders (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) NOT NULL,
            variant_id mediumint(9) NOT NULL,
            extra_id mediumint(9) NOT NULL,
            extra_ids varchar(255) DEFAULT NULL,
            duration_id mediumint(9) NOT NULL,
            condition_id mediumint(9) DEFAULT NULL,
            product_color_id mediumint(9) DEFAULT NULL,
            frame_color_id mediumint(9) DEFAULT NULL,
            final_price decimal(10,2) NOT NULL,
            shipping_cost decimal(10,2) DEFAULT 0,
            shipping_price_id varchar(255) DEFAULT '',
            mode varchar(10) DEFAULT 'miete',
            stripe_session_id varchar(255) DEFAULT '',
            stripe_subscription_id varchar(255) DEFAULT '',
            amount_total int DEFAULT 0,
            customer_name varchar(255) DEFAULT '',
            customer_email varchar(255) DEFAULT '',
            customer_phone varchar(50) DEFAULT '',
            customer_street varchar(255) DEFAULT '',
            customer_postal varchar(20) DEFAULT '',
            customer_city varchar(100) DEFAULT '',
            customer_country varchar(2) DEFAULT '',
            produkt_name varchar(255) DEFAULT '',
            zustand_text varchar(255) DEFAULT '',
            produktfarbe_text varchar(255) DEFAULT '',
            gestellfarbe_text varchar(255) DEFAULT '',
            extra_text text,
            dauer_text varchar(255) DEFAULT '',
            user_ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            invoice_url varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'offen',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Category layouts table
        $table_cat_layouts = $wpdb->prefix . 'produkt_category_layouts';
        $sql_cat_layouts = "CREATE TABLE $table_cat_layouts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            layout_type TINYINT NOT NULL DEFAULT 1,
            categories TEXT NOT NULL,
            border_radius TINYINT(1) NOT NULL DEFAULT 0,
            heading_tag VARCHAR(3) NOT NULL DEFAULT 'h3',
            shortcode VARCHAR(100) NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_prod_categories);
        dbDelta($sql_prod_to_cat);
        dbDelta($sql_categories);
        dbDelta($sql_variants);
        dbDelta($sql_extras);
        dbDelta($sql_durations);
        dbDelta($sql_analytics);
        dbDelta($sql_branding);
        dbDelta($sql_conditions);
        dbDelta($sql_colors);
        dbDelta($sql_color_variant_images);
        dbDelta($sql_variant_options);
        dbDelta($sql_variant_durations);
        dbDelta($sql_duration_prices);
        dbDelta($sql_cat_layouts);
        // Content blocks table
        $table_content_blocks = $wpdb->prefix . 'produkt_content_blocks';
        $sql_content_blocks = "CREATE TABLE $table_content_blocks (
            id INT NOT NULL AUTO_INCREMENT,
            category_id INT NOT NULL,
            position INT NOT NULL,
            position_mobile INT NOT NULL DEFAULT 6,
            style VARCHAR(20) DEFAULT 'wide',
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            image_url TEXT,
            button_text TEXT,
            button_url TEXT,
            background_color VARCHAR(20) DEFAULT '',
            badge_text TEXT,
            PRIMARY KEY (id),
            KEY category_id (category_id)
        ) $charset_collate;";
        dbDelta($sql_content_blocks);
        dbDelta($sql_orders);

        // Customers table
        $table_customers = $wpdb->prefix . 'produkt_customers';
        $sql_customers = "CREATE TABLE $table_customers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            stripe_customer_id varchar(255) NOT NULL,
            first_name varchar(255) DEFAULT '',
            last_name varchar(255) DEFAULT '',
            phone varchar(50) DEFAULT '',
            street varchar(255) DEFAULT '',
            postal_code varchar(20) DEFAULT '',
            city varchar(255) DEFAULT '',
            country varchar(50) DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        dbDelta($sql_customers);

        // Review reminders table
        $table_review_reminders = $wpdb->prefix . 'produkt_review_reminders';
        $reminder_sql = "CREATE TABLE $table_review_reminders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            subscription_key VARCHAR(255) NOT NULL,
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_customer_subscription (customer_id, subscription_key),
            KEY sent_at (sent_at)
        ) $charset_collate;";
        dbDelta($reminder_sql);

        // Create reviews table if it doesn't exist
        $table_reviews = $wpdb->prefix . 'produkt_reviews';
        $reviews_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_reviews'");

        if (!$reviews_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_reviews (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                product_id BIGINT UNSIGNED NOT NULL,
                customer_id BIGINT UNSIGNED NOT NULL,
                subscription_key VARCHAR(255) NOT NULL,
                order_id BIGINT UNSIGNED DEFAULT NULL,
                product_index INT UNSIGNED DEFAULT 0,
                rating TINYINT UNSIGNED NOT NULL,
                review_text TEXT DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'approved',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_review (customer_id, subscription_key),
                KEY product_id (product_id),
                KEY status (status),
                KEY created_at (created_at)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Shipping methods table
        $table_shipping = $wpdb->prefix . 'produkt_shipping_methods';
        $sql_shipping = "CREATE TABLE $table_shipping (
            id INT NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            service_provider VARCHAR(50),
            stripe_product_id VARCHAR(255),
            stripe_price_id VARCHAR(255),
            is_default TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_shipping);

        // Blocked days table
        $table_blocked = $wpdb->prefix . 'produkt_blocked_days';
        $sql_blocked    = "CREATE TABLE $table_blocked (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            day date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY day (day)
        ) $charset_collate;";
        dbDelta($sql_blocked);

        // Filter groups table
        $table_filter_groups = $wpdb->prefix . 'produkt_filter_groups';
        $sql_filter_groups   = "CREATE TABLE IF NOT EXISTS $table_filter_groups (
            id INT NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_filter_groups);

        // Filters table
        $table_filters = $wpdb->prefix . 'produkt_filters';
        $sql_filters   = "CREATE TABLE IF NOT EXISTS $table_filters (
            id INT NOT NULL AUTO_INCREMENT,
            group_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            KEY group_id (group_id)
        ) $charset_collate;";
        dbDelta($sql_filters);

        // Category to filter mapping
        $table_cat_filters = $wpdb->prefix . 'produkt_category_filters';
        $sql_cat_filters = "CREATE TABLE IF NOT EXISTS $table_cat_filters (
            category_id INT NOT NULL,
            filter_id INT NOT NULL,
            PRIMARY KEY (category_id, filter_id),
            KEY filter_id (filter_id)
        ) $charset_collate;";
        dbDelta($sql_cat_filters);

        // Notifications table
        $table_notifications = $wpdb->prefix . 'produkt_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) NOT NULL,
            variant_id mediumint(9) DEFAULT NULL,
            extra_ids varchar(255) DEFAULT NULL,
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

        dbDelta($sql_notifications);

        // Newsletter Opt-ins table (Double Opt-In)
        $table_newsletter = $wpdb->prefix . 'produkt_newsletter_optins';
        $newsletter_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_newsletter'");
        if (!$newsletter_exists) {
            $sql_newsletter = "CREATE TABLE $table_newsletter (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                email VARCHAR(255) NOT NULL,
                first_name VARCHAR(255) DEFAULT '',
                last_name VARCHAR(255) DEFAULT '',
                phone VARCHAR(50) DEFAULT '',
                street VARCHAR(255) DEFAULT '',
                postal_code VARCHAR(20) DEFAULT '',
                city VARCHAR(255) DEFAULT '',
                country VARCHAR(50) DEFAULT '',
                status TINYINT(1) DEFAULT 0,
                token_hash CHAR(64) DEFAULT NULL,
                requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                confirmed_at DATETIME DEFAULT NULL,
                confirm_ip VARCHAR(45) DEFAULT NULL,
                confirm_user_agent TEXT DEFAULT NULL,
                stripe_session_id VARCHAR(255) DEFAULT '',
                PRIMARY KEY (id),
                UNIQUE KEY email (email),
                KEY status (status),
                KEY requested_at (requested_at)
            ) $charset_collate;";
            dbDelta($sql_newsletter);
        }

        // Order logs table
        $table_logs = $wpdb->prefix . 'produkt_order_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id mediumint(9) NOT NULL,
            event varchar(50) NOT NULL,
            message text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id)
        ) $charset_collate;";

        dbDelta($sql_logs);

        // Newsletter Opt-ins: confirm_ip und confirm_user_agent nachrüsten
        $table = $wpdb->prefix . 'produkt_newsletter_optins';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if ($table_exists) {
            // confirm_ip nachrüsten, wenn fehlt
            $col = $wpdb->get_var($wpdb->prepare(
                "SHOW COLUMNS FROM $table LIKE %s",
                'confirm_ip'
            ));
            if (!$col) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN confirm_ip VARCHAR(45) DEFAULT NULL AFTER confirmed_at");
            }

            // confirm_user_agent nachrüsten, wenn fehlt
            $col = $wpdb->get_var($wpdb->prepare(
                "SHOW COLUMNS FROM $table LIKE %s",
                'confirm_user_agent'
            ));
            if (!$col) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN confirm_user_agent TEXT DEFAULT NULL AFTER confirm_ip");
            }
        }

    }
    
    public function insert_default_data() {
        global $wpdb;
        
        // Insert default category
        $existing_categories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_categories");
        if ($existing_categories == 0) {
            $wpdb->insert(
                $wpdb->prefix . 'produkt_categories',
                array(
                     'name' => 'Standard Produkt',
                    'shortcode' => 'produkt_product',
                    'page_title' => '',
                    'page_description' => '',
                    'meta_title' => '',
                    'meta_description' => '',
                    'product_title' => '',
                    'short_description' => '',
                    'product_description' => '',
                    'default_image' => '',
                    'features_title' => '',
                    'feature_1_icon' => '',
                    'feature_1_title' => '',
                    'feature_1_description' => '',
                    'feature_2_icon' => '',
                    'feature_2_title' => '',
                    'feature_2_description' => '',
                    'feature_3_icon' => '',
                    'feature_3_title' => '',
                    'feature_3_description' => '',
                    'feature_4_icon' => '',
                    'feature_4_title' => '',
                    'feature_4_description' => '',
                    'button_text' => '',
                    'button_icon' => '',
                    'payment_icons' => '',
                    'accordion_data' => '',
                    'page_blocks' => '',
                    'detail_blocks' => '',
                    'tech_blocks' => '',
                    'scope_blocks' => '',
                    'shipping_cost' => 0,
                    'shipping_provider' => '',
                    'shipping_price_id' => '',
                    'layout_style' => 'default',
                    'duration_tooltip' => '',
                    'condition_tooltip' => '',
                    'show_features' => 0,
                    'show_tooltips' => 1,
                    'show_rating' => 0,
                    'rating_value' => 0,
                    'rating_link' => '',
                    'sort_order' => 0
                )
            );
        }
        
        // Insert default branding settings
        $existing_branding = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_branding");
        if ($existing_branding == 0) {
            $default_branding = array(
                'plugin_name' => '',
                'plugin_description' => '',
                'company_name' => '',
                'company_url' => '',
                'admin_logo' => '',
                'admin_color_primary' => '#5f7f5f',
                'admin_color_secondary' => '#4a674a',
                'admin_color_text' => '#ffffff',
                'front_button_color'    => '#5f7f5f',
                'front_text_color'      => '#4a674a',
                'front_border_color'    => '#a4b8a4',
                'front_button_text_color' => '#ffffff',
                'shop_layout'           => 'filters_left',
                'sticky_header_mode'   => 'header',
                'product_padding'       => '1',
                'login_bg_image'         => '',
                'login_layout'          => 'classic',
                'login_logo'            => '',
                'footer_text' => '',
                'custom_css' => ''
            );
            
            foreach ($default_branding as $key => $value) {
                $wpdb->insert(
                    $wpdb->prefix . 'produkt_branding',
                    array(
                        'setting_key' => $key,
                        'setting_value' => $value
                    )
                );
            }
        }
        
        // Insert default variants only if table is empty
        $existing_variants = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_variants");
        if ($existing_variants == 0) {
            $variants = array(
                array('Produkt + Gestell & Motor', 'Komplettset mit stabilem Gestell und leisem Motor', 'price_1QutK3RxDui5dUOqWEiBal7P'),
                array('Produkt + Türklammer & Motor', 'Platzsparende Lösung mit praktischer Türklammer', ''),
                array('Wiege + Gestell & Motor mit App-Steuerung', 'Premium-Variante mit smarter App-Steuerung', '')
            );
            
            foreach ($variants as $index => $variant) {
                $wpdb->insert(
                    $wpdb->prefix . 'produkt_variants',
                    array(
                        'category_id' => 1,
                        'name' => $variant[0],
                        'description' => $variant[1],
                        'stripe_price_id' => $variant[2],
                        'price_from' => 0,
                        'image_url_1' => '',
                        'image_url_2' => '',
                        'image_url_3' => '',
                        'image_url_4' => '',
                        'image_url_5' => '',
                        'available' => 1,
                        'availability_note' => '',
                        'delivery_time' => '3-5 Werktagen',
                        'sort_order' => $index
                    )
                );
            }
        }
        
        // Insert default extras only if table is empty
        $existing_extras = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_extras");
        if ($existing_extras == 0) {
            $extras = array(
                array('Kein Extra', '' , 0.00),
                array('Himmel', '', 15.00)
            );
            
            foreach ($extras as $index => $extra) {
                $wpdb->insert(
                    $wpdb->prefix . 'produkt_extras',
                    array(
                        'category_id'       => 1,
                        'name'              => $extra[0],
                        'stripe_product_id' => '',
                        'stripe_price_id'   => $extra[1],
                        'price'             => $extra[2],
                        'image_url'         => '',
                        'sort_order'        => $index
                    )
                );
            }
        }

        // Insert default durations only if table is empty
        $existing_durations = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_durations");
        if ($existing_durations == 0) {
            $durations = array(
                array('Flexible Abo', 1, 0.00),
                array('ab 2+', 2, 0.05),
                array('ab 4+', 4, 0.10),
                array('ab 6+', 6, 0.15)
            );
            
            foreach ($durations as $index => $duration) {
                $wpdb->insert(
                    $wpdb->prefix . 'produkt_durations',
                    array(
                        'category_id' => 1,
                        'name' => $duration[0],
                        'months_minimum' => $duration[1],
                        'discount' => $duration[2],
                        'show_badge' => 0,
                        'show_popular' => 0,
                        'popular_gradient_start' => '',
                        'popular_gradient_end' => '',
                        'popular_text_color' => '',
                        'sort_order' => $index
                    )
                );
            }
        }
    }

    /**
     * Drop all plugin tables from the database.
     */
    public function drop_tables() {
        global $wpdb;

        $tables = array(
            'produkt_product_categories',
            'produkt_product_to_category',
            'produkt_categories',
            'produkt_variants',
            'produkt_extras',
            'produkt_durations',
            'produkt_conditions',
            'produkt_colors',
            'produkt_color_variant_images',
            'produkt_variant_options',
            'produkt_variant_durations',
            'produkt_duration_prices',
            'produkt_orders',
            'produkt_order_logs',
            'produkt_analytics',
            'produkt_branding',
            'produkt_notifications',
            'produkt_content_blocks',
            'produkt_shipping_methods',
            'produkt_filter_groups',
            'produkt_filters',
            'produkt_category_filters',
            'produkt_customers',
            'produkt_customer_notes',
            'produkt_category_layouts',
            'produkt_reviews',
            'produkt_review_reminders'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table");
        }
    }

    /**
     * Get all Stripe price IDs for every variant and duration in a category.
     *
     * @param int $category_id
     * @return array
     */
    public static function getAllStripePriceIdsByCategory($category_id) {
        global $wpdb;

        $variant_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d",
                $category_id
            )
        );

        if (empty($variant_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($variant_ids), '%d'));

        $sql   = "SELECT stripe_price_id FROM {$wpdb->prefix}produkt_duration_prices WHERE variant_id IN ($placeholders)";
        $query = $wpdb->prepare($sql, ...$variant_ids);
        $price_ids = $wpdb->get_col($query);

        // Fallback: if no duration prices were found, check the variants table
        if (empty($price_ids)) {
            $sql   = "SELECT stripe_price_id FROM {$wpdb->prefix}produkt_variants WHERE id IN ($placeholders)";
            $query = $wpdb->prepare($sql, ...$variant_ids);
            $fallbacks = $wpdb->get_col($query);
            $price_ids = array_merge((array) $price_ids, (array) $fallbacks);
        }

        return array_filter((array) $price_ids);
    }

    /**
     * Retrieve all product categories.
     *
     * @param bool $only_active If true, return only active categories
     * @return array List of category objects
     */
    public static function get_all_categories($only_active = true) {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}produkt_categories";
        if ($only_active) {
            $sql .= " WHERE active = 1";
        }
        $sql .= " ORDER BY sort_order";

        return $wpdb->get_results($sql);
    }

    /**
     * Retrieve content blocks for a product category.
     *
     * @param int $category_id
     * @return array
     */
    public static function get_content_blocks_for_category($category_id) {
        $cache_key = 'produkt_content_blocks_' . intval($category_id);
        $blocks = get_transient($cache_key);
        if ($blocks !== false) {
            return $blocks;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'produkt_content_blocks';
        $blocks = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE category_id = %d ORDER BY position",
                $category_id
            )
        );

        set_transient($cache_key, $blocks, HOUR_IN_SECONDS);
        return $blocks;
    }

    /**
     * Clear cached content blocks for a category.
     *
     * @param int $category_id
     */
    public static function clear_content_blocks_cache($category_id) {
        delete_transient('produkt_content_blocks_' . intval($category_id));
    }

    /**
     * Retrieve orders placed by a specific WordPress user.
     *
     * @param int $user_id User ID
     * @return array List of order objects
     */
    public static function get_orders_for_user($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'produkt_orders';
        $email = sanitize_email($user->user_email);

        $sql = $wpdb->prepare(
            "SELECT o.*, c.name as category_name,
                    COALESCE(v.name, o.produkt_name) as variant_name,
                    COALESCE(NULLIF(GROUP_CONCAT(e.name SEPARATOR ', '), ''), o.extra_text) AS extra_names,
                    COALESCE(d.name, o.dauer_text) as duration_name,
                    COALESCE(cond.name, o.zustand_text) as condition_name,
                    COALESCE(pc.name, o.produktfarbe_text) as product_color_name,
                    COALESCE(fc.name, o.gestellfarbe_text) as frame_color_name,
                    sm.name AS shipping_name,
                    sm.service_provider AS shipping_provider,
                    stripe_subscription_id AS subscription_id
             FROM {$table} o
             LEFT JOIN {$wpdb->prefix}produkt_categories c ON o.category_id = c.id
             LEFT JOIN {$wpdb->prefix}produkt_variants v ON o.variant_id = v.id
             LEFT JOIN {$wpdb->prefix}produkt_extras e ON FIND_IN_SET(e.id, o.extra_ids)
             LEFT JOIN {$wpdb->prefix}produkt_durations d ON o.duration_id = d.id
             LEFT JOIN {$wpdb->prefix}produkt_conditions cond ON o.condition_id = cond.id
             LEFT JOIN {$wpdb->prefix}produkt_colors pc ON o.product_color_id = pc.id
             LEFT JOIN {$wpdb->prefix}produkt_colors fc ON o.frame_color_id = fc.id
            LEFT JOIN {$wpdb->prefix}produkt_shipping_methods sm
                ON sm.stripe_price_id = COALESCE(o.shipping_price_id, c.shipping_price_id)
             WHERE o.customer_email = %s
             GROUP BY o.id
             ORDER BY o.created_at DESC",
            $email
        );
        return $wpdb->get_results($sql);
    }
    /**
     * Get the Stripe customer ID for a WordPress user.
     *
     * @param int $user_id User ID
     * @return string Customer ID or empty string if none found
     */
    public static function get_stripe_customer_id_for_user($user_id) {
        // 1. Prefer the usermeta value if present
        $customer_id = get_user_meta($user_id, 'stripe_customer_id', true);
        if (!empty($customer_id)) {
            return $customer_id;
        }

        // 2. Fallback: look up by email in the custom customers table
        $user = get_userdata($user_id);
        if ($user && !empty($user->user_email)) {
            $by_email = self::get_stripe_customer_id_by_email($user->user_email);
            if (!empty($by_email)) {
                update_user_meta($user_id, 'stripe_customer_id', $by_email);
                return $by_email;
            }
        }

        return '';
    }

    /**
     * Get the Stripe customer ID for a user by email address.
     *
     * @param string $email User email
     * @return string Customer ID or empty string when none found
     */
    public static function get_stripe_customer_id_from_usermeta($email) {
        global $wpdb;

        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->users} WHERE user_email = %s",
                $email
            )
        );

        if (!$user_id) {
            return null;
        }

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'stripe_customer_id'",
                $user_id
            )
        );
    }

    public static function get_stripe_customer_id_by_email($email) {
        global $wpdb;

        $email = sanitize_email($email);

        // Check the custom customers table first to reuse existing Stripe customers for guests
        $table = $wpdb->prefix . 'produkt_customers';
        $customer_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT stripe_customer_id FROM $table WHERE email = %s LIMIT 1",
                $email
            )
        );

        // Fallback to WordPress user meta for existing accounts
        if (!$customer_id) {
            $customer_id = self::get_stripe_customer_id_from_usermeta($email);
        }

        return $customer_id ? $customer_id : '';
    }

    public static function get_customer_row_id_by_email($email) {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_customers';
        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table WHERE email = %s LIMIT 1", sanitize_email($email))
        );
    }

    public static function has_sent_review_reminder($customer_id, $subscription_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_review_reminders';
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE customer_id = %d AND subscription_key = %s LIMIT 1",
                (int) $customer_id,
                (string) $subscription_key
            )
        ) > 0;
    }

    public static function log_review_reminder($customer_id, $subscription_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_review_reminders';
        return (bool) $wpdb->insert(
            $table,
            [
                'customer_id'      => (int) $customer_id,
                'subscription_key' => (string) $subscription_key,
                'sent_at'          => current_time('mysql'),
            ],
            ['%d', '%s', '%s']
        );
    }

    public static function has_review_for_subscription_key($customer_id, $subscription_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_reviews';
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE customer_id = %d AND subscription_key = %s LIMIT 1",
                (int) $customer_id,
                (string) $subscription_key
            )
        ) > 0;
    }

    public static function get_reviewed_subscription_keys($customer_id, array $subscription_keys) {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_reviews';
        $subscription_keys = array_values(array_filter(array_map('strval', $subscription_keys)));
        if (!$customer_id || empty($subscription_keys)) return [];

        $placeholders = implode(',', array_fill(0, count($subscription_keys), '%s'));
        $sql = $wpdb->prepare(
            "SELECT subscription_key FROM $table
             WHERE customer_id = %d AND subscription_key IN ($placeholders)",
            array_merge([(int)$customer_id], $subscription_keys)
        );

        $rows = $wpdb->get_col($sql);
        return array_fill_keys($rows ?: [], true);
    }

    public static function get_product_reviews_summary($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_reviews';
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS cnt, AVG(rating) AS avg_rating
                 FROM $table
                 WHERE product_id = %d AND status = 'approved'",
                (int) $product_id
            )
        );
        return [
            'count' => (int) ($row->cnt ?? 0),
            'avg'   => (float) ($row->avg_rating ?? 0),
        ];
    }

    public static function get_product_reviews_breakdown($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_reviews';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT rating, COUNT(*) AS cnt FROM $table WHERE product_id = %d AND status = 'approved' GROUP BY rating",
                (int) $product_id
            )
        );

        $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $rating = (int) ($row->rating ?? 0);
                if ($rating >= 1 && $rating <= 5) {
                    $counts[$rating] = (int) ($row->cnt ?? 0);
                }
            }
        }

        return $counts;
    }

    public static function get_latest_product_reviews($product_id, $limit = 6) {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_reviews';
        $customers_table = $wpdb->prefix . 'produkt_customers';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.rating, r.review_text, r.created_at, c.first_name, c.last_name
                 FROM $table r
                 LEFT JOIN $customers_table c ON r.customer_id = c.id
                 WHERE r.product_id = %d AND r.status = 'approved'
                 ORDER BY r.created_at DESC
                 LIMIT %d",
                (int) $product_id,
                (int) $limit
            )
        );
    }

    public static function get_review_admin_metrics() {
        global $wpdb;
        $reviews_table = $wpdb->prefix . 'produkt_reviews';
        $orders_table  = $wpdb->prefix . 'produkt_orders';

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $reviews_table");
        $one   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $reviews_table WHERE rating = 1");
        $five  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $reviews_table WHERE rating = 5");

        $orders = $wpdb->get_results("SELECT id, order_items, status, stripe_subscription_id, mode FROM $orders_table WHERE mode = 'miete'");
        $active_keys = [];

        if (!empty($orders)) {
            foreach ($orders as $order) {
                $items = [];
                if (!empty($order->order_items)) {
                    $decoded = json_decode($order->order_items, true);
                    if (is_array($decoded)) {
                        $items = $decoded;
                    }
                }

                if (empty($items)) {
                    $items = [['rental_status' => $order->status ?? 'aktiv']];
                }

                foreach ($items as $idx => $item) {
                    $status = function_exists('pv_normalize_rental_status')
                        ? pv_normalize_rental_status($item['rental_status'] ?? ($order->status ?? 'aktiv'))
                        : ($item['rental_status'] ?? ($order->status ?? 'aktiv'));

                    if ($status !== 'aktiv') {
                        continue;
                    }

                    $base_sub_id = trim((string) ($order->stripe_subscription_id ?? ''));
                    $key = $base_sub_id !== ''
                        ? ($base_sub_id . '-' . $idx)
                        : ('order-' . $order->id . '-' . $idx);

                    $active_keys[$key] = true;
                }
            }
        }

        $unreviewed = 0;
        if (!empty($active_keys)) {
            $keys = array_keys($active_keys);
            $placeholders = implode(',', array_fill(0, count($keys), '%s'));
            $reviewed = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT subscription_key FROM $reviews_table WHERE subscription_key IN ($placeholders)",
                    $keys
                )
            );
            $unreviewed = max(0, count($active_keys) - count($reviewed));
        }

        return [
            'total'       => $total,
            'one_star'    => $one,
            'five_star'   => $five,
            'unreviewed'  => $unreviewed,
        ];
    }

    public static function get_reviews_with_meta($category_id = 0, $search_term = '', $limit = 200) {
        global $wpdb;
        $reviews_table   = $wpdb->prefix . 'produkt_reviews';
        $customers_table = $wpdb->prefix . 'produkt_customers';
        $categories_table = $wpdb->prefix . 'produkt_categories';

        $clauses = [];
        $params  = [];

        if ($category_id) {
            $clauses[] = 'r.product_id = %d';
            $params[]  = (int) $category_id;
        }

        $search_term = trim((string) $search_term);
        if ($search_term !== '') {
            $clauses[] = '(cat.name LIKE %s OR c.first_name LIKE %s OR c.last_name LIKE %s OR r.review_text LIKE %s)';
            $like = '%' . $wpdb->esc_like($search_term) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT r.*, c.first_name, c.last_name, cat.name AS product_name
                FROM $reviews_table r
                LEFT JOIN $customers_table c ON r.customer_id = c.id
                LEFT JOIN $categories_table cat ON r.product_id = cat.id";

        if (!empty($clauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $sql .= ' ORDER BY r.created_at DESC';
        $sql .= ' LIMIT ' . intval($limit);

        return !empty($params) ? $wpdb->get_results($wpdb->prepare($sql, ...$params)) : $wpdb->get_results($sql);
    }

    /**
     * Update the Stripe customer ID for a user identified by email.
     *
     * @param string $email       User email
     * @param string $customer_id Stripe customer ID
     * @return void
     */
    public static function update_stripe_customer_id_by_email($email, $customer_id) {
        $user = get_user_by('email', sanitize_email($email));
        if ($user) {
            update_user_meta($user->ID, 'stripe_customer_id', $customer_id);
        }
    }

    /**
     * Insert or update a customer record in the custom customers table.
     */
    public static function upsert_customer($email, $stripe_customer_id, $first_name = '', $last_name = '', $phone = '', $street = '', $postal = '', $city = '', $country = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_customers';

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE email = %s", $email));

        if ($existing) {
            $wpdb->update(
                $table,
                ['stripe_customer_id' => $stripe_customer_id],
                ['id' => $existing],
                ['%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $table,
                [
                    'email'              => $email,
                    'stripe_customer_id' => $stripe_customer_id,
                    'first_name'         => $first_name,
                    'last_name'          => $last_name,
                    'phone'              => $phone,
                    'street'             => $street,
                    'postal_code'        => $postal,
                    'city'               => $city,
                    'country'            => $country,
                ],
                ['%s','%s','%s','%s','%s','%s','%s','%s','%s']
            );
        }
    }

    /**
     * Insert or update a record in the produkt_customers table using the email
     * as unique identifier.
     *
     * @param string $email
     * @param string $stripe_customer_id
     * @param string $fullname
     * @param string $phone
     * @param array  $address
     * @return void
     */
    public static function upsert_customer_record_by_email($email, $stripe_customer_id, $fullname = '', $phone = '', $address = []) {
        global $wpdb;

        // Check if email already exists
        $table = $wpdb->prefix . 'produkt_customers';
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE email = %s", $email));

        $data = [
            'stripe_customer_id' => $stripe_customer_id,
            'first_name'        => $fullname,
            'phone'             => $phone,
            'email'             => $email,
        ];

        if (!empty($address)) {
            $data['street']       = $address['street'] ?? '';
            $data['postal_code']  = $address['postal_code'] ?? '';
            $data['city']         = $address['city'] ?? '';
            $data['country']      = $address['country'] ?? '';
        }

        if ($existing) {
            $wpdb->update($table, $data, ['email' => $email]);
        } else {
            $wpdb->insert($table, $data);
        }
    }


    /**
     * Retrieve all product categories sorted hierarchically.
     * Each returned object has a 'depth' property for indentation.
     *
     * @return array
     */
    public static function get_product_categories_tree() {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_product_categories';
        $has_parent = $wpdb->get_var("SHOW COLUMNS FROM $table LIKE 'parent_id'");
        if (!$has_parent) {
            $cats = $wpdb->get_results("SELECT id, name, slug FROM $table ORDER BY name");
            foreach ($cats as $c) {
                $c->parent_id = 0;
                $c->depth = 0;
            }
            return $cats;
        }
        $cats = $wpdb->get_results("SELECT id, parent_id, name, slug FROM $table ORDER BY name");
        $map = [];
        foreach ($cats as $c) {
            $c->children = [];
            $map[$c->id] = $c;
        }
        foreach ($cats as $c) {
            if ($c->parent_id && isset($map[$c->parent_id])) {
                $map[$c->parent_id]->children[] = $c;
            }
        }
        $ordered = [];
        $add = function($cat, $level) use (&$ordered, &$add) {
            $cat->depth = $level;
            $ordered[] = $cat;
            foreach ($cat->children as $child) {
                $add($child, $level + 1);
            }
        };
        foreach ($cats as $c) {
            if (empty($c->parent_id)) {
                $add($c, 0);
            }
        }
        return $ordered;
    }

    /**
     * Get IDs of all descendant categories of a given parent.
     *
     * @param int $parent_id
     * @return array
     */
    public static function get_descendant_category_ids($parent_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_product_categories';
        $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM $table WHERE parent_id = %d", $parent_id));
        $all = $ids;
        foreach ($ids as $id) {
            $all = array_merge($all, self::get_descendant_category_ids($id));
        }
        return $all;
    }

    /**
     * Get IDs of all ancestor categories for a given category ID.
     * Returns an empty array if the hierarchy column does not exist.
     *
     * @param int $category_id
     * @return array
     */
    public static function get_ancestor_category_ids($category_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_product_categories';
        $has_parent = $wpdb->get_var("SHOW COLUMNS FROM $table LIKE 'parent_id'");
        if (!$has_parent) {
            return [];
        }

        $ids = [];
        $parent = $wpdb->get_var(
            $wpdb->prepare("SELECT parent_id FROM $table WHERE id = %d", $category_id)
        );
        while (!empty($parent)) {
            $ids[] = (int) $parent;
            $parent = $wpdb->get_var(
                $wpdb->prepare("SELECT parent_id FROM $table WHERE id = %d", $parent)
            );
        }
        return $ids;
    }

    /**
     * Check if the product categories table has the parent_id column.
     *
     * @return bool
     */
    public function categories_table_has_parent_column() {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_product_categories';
        return (bool) $wpdb->get_var("SHOW COLUMNS FROM $table LIKE 'parent_id'");
    }

    public function customer_notes_table_exists() {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_customer_notes';
        return (bool) $wpdb->get_var("SHOW TABLES LIKE '$table'");
    }

    /**
     * Determine if any variants are configured for direct sale with a price.
     *
     * @return bool
     */
    public function has_sale_enabled_variants() {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_variants';

        $count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE sale_enabled = 1 AND verkaufspreis_einmalig > 0"
        );

        return $count > 0;
    }

    /**
     * Check if the category layouts table exists with required columns.
     *
     * @return bool
     */
    public function category_layouts_table_exists() {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_category_layouts';
        $exists = (bool) $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$exists) {
            return false;
        }
        return (bool) $wpdb->get_var("SHOW COLUMNS FROM $table LIKE 'heading_tag'");
    }

    /**
     * Search active products (categories) by term for frontend search results.
     *
     * @param string $term  Search term from WordPress search query.
     * @param int    $limit Maximum number of results to return.
     * @return array<int, object>
     */
    public function search_products_by_term($term, $limit = 50) {
        global $wpdb;

        $like = '%' . $wpdb->esc_like($term) . '%';
        $table = $wpdb->prefix . 'produkt_categories';

        $sql = $wpdb->prepare(
            "SELECT id, product_title, product_description, short_description, default_image, rating_value, show_rating
             FROM $table
             WHERE active = 1
               AND (product_title LIKE %s OR product_description LIKE %s OR short_description LIKE %s)
             ORDER BY sort_order ASC
             LIMIT %d",
            $like,
            $like,
            $like,
            intval($limit)
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Get orders whose rental period has ended and inventory has not yet been returned.
     *
     * @return array List of order objects
     */
    public static function get_due_returns() {
        global $wpdb;

        require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

        $mode = get_option('produkt_betriebsmodus', 'miete');

        if ($mode === 'kauf') {
            $today = current_time('Y-m-d');
            $orders = $wpdb->get_results($wpdb->prepare(
                "SELECT
                        o.id,
                        o.order_number,
                        o.customer_name,
                        o.variant_id,
                        o.extra_ids,
                        o.start_date,
                        o.end_date,
                        o.order_items,
                        COALESCE(c.name, o.produkt_name) AS category_name,
                        COALESCE(v.name, o.produkt_name) AS variant_name,
                        COALESCE(
                            NULLIF(
                                (SELECT GROUP_CONCAT(ex.name SEPARATOR ', ')
                                 FROM {$wpdb->prefix}produkt_extras ex
                                 WHERE FIND_IN_SET(ex.id, o.extra_ids)),
                                ''
                            ),
                            o.extra_text
                        ) AS extra_names
                 FROM {$wpdb->prefix}produkt_orders o
                 LEFT JOIN {$wpdb->prefix}produkt_categories c ON o.category_id = c.id
                 LEFT JOIN {$wpdb->prefix}produkt_variants v ON o.variant_id = v.id
                 WHERE o.mode = 'kauf'
                   AND o.end_date IS NOT NULL
                   AND o.end_date <= %s
                   AND COALESCE(o.inventory_reverted, 0) = 0
                 ORDER BY o.end_date",
                $today
            ));
        } else {
            // Rental mode: end_date may be stored per item inside order_items (cart orders),
            // so we must fetch candidate orders and filter due returns after expanding items.
            $orders = $wpdb->get_results(
                "SELECT
                        o.id,
                        o.order_number,
                        o.customer_name,
                        o.variant_id,
                        o.extra_ids,
                        o.start_date,
                        o.end_date,
                        o.created_at,
                        o.order_items,
                        COALESCE(c.name, o.produkt_name) AS category_name,
                        COALESCE(v.name, o.produkt_name) AS variant_name,
                        COALESCE(
                            NULLIF(
                                (SELECT GROUP_CONCAT(ex.name SEPARATOR ', ')
                                 FROM {$wpdb->prefix}produkt_extras ex
                                 WHERE FIND_IN_SET(ex.id, o.extra_ids)),
                                ''
                            ),
                            o.extra_text
                        ) AS extra_names
                 FROM {$wpdb->prefix}produkt_orders o
                 LEFT JOIN {$wpdb->prefix}produkt_categories c ON o.category_id = c.id
                 LEFT JOIN {$wpdb->prefix}produkt_variants v ON o.variant_id = v.id
                 WHERE COALESCE(o.inventory_reverted, 0) = 0
                   AND (o.mode IS NULL OR o.mode NOT IN ('kauf','sale'))
                   AND o.status IN ('abgeschlossen','gekündigt','beendet')
                 ORDER BY COALESCE(o.end_date, o.created_at)"
            );
        }

        $orders = $orders ?: [];

        $expanded_orders = [];
        $today = current_time('Y-m-d');
        foreach ($orders as $o) {
            self::ensure_return_pending_log((int) $o->id);

            $products = pv_expand_order_products($o);
            if (empty($products)) {
                // Fallback: only include as due when order-level end_date is set and reached
                if (!empty($o->end_date) && substr((string) $o->end_date, 0, 10) <= $today) {
                    $expanded_orders[] = $o;
                }
                continue;
            }

            foreach ($products as $p) {
                $clone = clone $o;
                $clone->category_name = $p->produkt_name ?? $o->category_name;
                $clone->variant_name  = $p->variant_name ?? $o->variant_name;
                $clone->extra_names   = $p->extra_names ?? $o->extra_names;
                $clone->start_date    = $p->start_date ?? $o->start_date;
                $clone->end_date      = $p->end_date ?? $o->end_date;
                
                // Include items that are:
                // 1. Cancelled (gekündigt) - appear immediately in "Offene Rückgaben"
                // 2. Ended (beendet) - appear immediately
                // 3. Rental period has ended (end_date <= today)
                $rental_status = pv_normalize_rental_status($p->rental_status ?? null);
                $end = !empty($clone->end_date) ? substr((string) $clone->end_date, 0, 10) : '';
                $is_cancelled_or_ended = in_array($rental_status, ['gekündigt', 'beendet'], true);
                $period_ended = ($end && $end <= $today);
                
                if ($is_cancelled_or_ended || $period_ended) {
                    $expanded_orders[] = $clone;
                }
            }
        }

        return $expanded_orders;
    }

    /**
     * Ensure that a pending return log exists for an order.
     *
     * @param int $order_id Order ID
     */
    public static function ensure_return_pending_log(int $order_id): void {
        global $wpdb;
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_order_logs WHERE order_id = %d AND event = 'inventory_returned_not_accepted'",
                $order_id
            )
        );
        if (!$exists) {
            produkt_add_order_log($order_id, 'inventory_returned_not_accepted');
        }
    }
    /**
     * Mark a single order as returned and update inventory.
     *
     * @param int $order_id Order ID
     * @return bool Success state
     */
    public static function process_inventory_return($order_id) {
        global $wpdb;
        $order = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT variant_id, extra_ids, product_color_id, inventory_reverted, mode, category_id FROM {$wpdb->prefix}produkt_orders WHERE id = %d",
                $order_id
            )
        );
        if (!$order || $order->inventory_reverted) {
            return false;
        }
        
        // Nur im Vermietungsmodus die Lagerverwaltung aktualisieren
        if ($order->mode !== 'miete') {
            return false;
        }
        
        // Prüfe ob Lagerverwaltung für die Kategorie aktiviert ist
        if ($order->category_id) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT inventory_enabled FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                $order->category_id
            ));
            if (!$category || !$category->inventory_enabled) {
                return false;
            }
        }
        if ($order->variant_id) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}produkt_variants SET stock_available = stock_available + 1, stock_rented = GREATEST(stock_rented - 1,0) WHERE id = %d",
                    $order->variant_id
                )
            );
            if ($order->product_color_id) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}produkt_variant_options SET stock_available = stock_available + 1, stock_rented = GREATEST(stock_rented - 1,0) WHERE variant_id = %d AND option_type = 'product_color' AND option_id = %d",
                        $order->variant_id,
                        $order->product_color_id
                    )
                );
            }
        }
        if (!empty($order->extra_ids)) {
            $ids = array_filter(array_map('intval', explode(',', $order->extra_ids)));
            foreach ($ids as $eid) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}produkt_extras SET stock_available = stock_available + 1, stock_rented = GREATEST(stock_rented - 1,0) WHERE id = %d",
                        $eid
                    )
                );
            }
        }
        $wpdb->update(
            $wpdb->prefix . 'produkt_orders',
            ['inventory_reverted' => 1],
            ['id' => $order_id],
            ['%d'],
            ['%d']
        );
        produkt_add_order_log((int)$order_id, 'inventory_returned_accepted');
        return true;
    }

    /**
     * Daily cron hook to flag orders whose rental period has ended.
     *
     * Inventory is not adjusted automatically; administrators must confirm
     * returns manually, at which point stock levels are updated.
     */
    public static function process_inventory_returns() {
        // Ensure pending return logs exist for overdue orders so they appear
        // in the admin dashboard for manual processing.
        self::get_due_returns();
    }

    /**
     * Mark a single rental item inside an order as cancelled/ended (stored in order_items JSON).
     *
     * @param int    $order_id
     * @param int    $product_index
     * @param string $status One of: aktiv|gekündigt|beendet
     * @return true|\WP_Error
     */
    public static function set_rental_item_status(int $order_id, int $product_index, string $status, ?string $end_date = null) {
        global $wpdb;
        require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

        $status = pv_normalize_rental_status($status);

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status, mode, order_items FROM {$wpdb->prefix}produkt_orders WHERE id = %d",
            $order_id
        ));
        if (!$order) {
            return new \WP_Error('missing_order', 'Bestellung nicht gefunden.');
        }

        $raw = $order->order_items ?? '';
        $items = [];
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }
        if (empty($items)) {
            $items = [[]];
        }

        if ($product_index < 0 || $product_index >= count($items)) {
            // fallback to first item
            $product_index = 0;
        }

        $items[$product_index]['rental_status'] = $status;
        $items[$product_index]['rental_status_updated_at'] = current_time('mysql', true);
        if ($end_date) {
            $items[$product_index]['end_date'] = substr($end_date, 0, 10);
        }

        $new_json = wp_json_encode($items);
        $wpdb->update(
            $wpdb->prefix . 'produkt_orders',
            ['order_items' => $new_json],
            ['id' => $order_id],
            ['%s'],
            ['%d']
        );

        // Update order.status only when all items share the same terminal state.
        $counts = pv_get_rental_item_status_counts((object) ['order_items' => $new_json, 'status' => $order->status]);
        $total = max(1, (int) $counts['total']);
        $ended = (int) $counts['beendet'];
        $cancelled = (int) $counts['gekuendigt'];
        $active = (int) $counts['aktiv'];

        $new_order_status = $order->status;
        if ($ended === $total) {
            $new_order_status = 'beendet';
        } elseif ($cancelled === $total) {
            $new_order_status = 'gekündigt';
        } elseif ($active === $total) {
            // keep existing (usually 'abgeschlossen')
            $new_order_status = $order->status;
        } else {
            // mixed -> keep 'abgeschlossen' to represent still-running rentals
            $new_order_status = 'abgeschlossen';
        }

        if ($new_order_status && $new_order_status !== $order->status) {
            $wpdb->update(
                $wpdb->prefix . 'produkt_orders',
                ['status' => $new_order_status],
                ['id' => $order_id],
                ['%s'],
                ['%d']
            );
            if (function_exists('\\produkt_add_order_log')) {
                \produkt_add_order_log($order_id, 'status_updated', ($order->status ?: '') . ' -> ' . $new_order_status);
            }
        }

        return true;
    }

    /**
     * Daily cron hook: mark rental items as "beendet" when end_date has passed.
     */
    public static function process_rental_statuses(): void {
        global $wpdb;
        require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

        $today = current_time('Y-m-d');
        $orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, status, mode, order_items
                 FROM {$wpdb->prefix}produkt_orders
                 WHERE (mode IS NULL OR mode NOT IN ('kauf','sale'))
                   AND status IN ('abgeschlossen','gekündigt')
                   AND (end_date IS NULL OR end_date <= %s OR end_date > %s)", // no-op filter; per-item end_date is in order_items
                $today,
                $today
            )
        );

        foreach ($orders as $o) {
            $raw = $o->order_items ?? '';
            if (empty($raw)) {
                continue;
            }
            $items = json_decode($raw, true);
            if (!is_array($items) || empty($items)) {
                continue;
            }
            $changed = false;
            foreach ($items as $idx => $it) {
                $end = $it['end_date'] ?? '';
                if (!$end) {
                    continue;
                }
                $end = substr((string) $end, 0, 10);
                if ($end <= $today) {
                    $cur = pv_normalize_rental_status($it['rental_status'] ?? ($o->status ?? 'aktiv'));
                    if ($cur !== 'beendet') {
                        $items[$idx]['rental_status'] = 'beendet';
                        $items[$idx]['rental_status_updated_at'] = current_time('mysql', true);
                        $changed = true;
                    }
                }
            }
            if ($changed) {
                $new_json = wp_json_encode($items);
                $wpdb->update(
                    $wpdb->prefix . 'produkt_orders',
                    ['order_items' => $new_json],
                    ['id' => (int) $o->id],
                    ['%s'],
                    ['%d']
                );
                // also recalc order-level status
                $counts = pv_get_rental_item_status_counts((object) ['order_items' => $new_json, 'status' => $o->status]);
                $total = max(1, (int) $counts['total']);
                if ((int) $counts['beendet'] === $total) {
                    if ($o->status !== 'beendet') {
                        $wpdb->update(
                            $wpdb->prefix . 'produkt_orders',
                            ['status' => 'beendet'],
                            ['id' => (int) $o->id],
                            ['%s'],
                            ['%d']
                        );
                        if (function_exists('\\produkt_add_order_log')) {
                            \produkt_add_order_log((int) $o->id, 'status_updated', ($o->status ?: '') . ' -> beendet');
                        }
                    }
                }
            }
        }
    }
}
