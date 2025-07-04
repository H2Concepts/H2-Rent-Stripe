<?php
namespace FederwiegenVerleih;

class Database {
    public function update_database() {
        global $wpdb;
        
        // Add category_id column to all tables if it doesn't exist
        $tables_to_update = array(
            'federwiegen_variants',
            'federwiegen_extras', 
            'federwiegen_durations',
            'federwiegen_links'
        );
        
        foreach ($tables_to_update as $table_suffix) {
            $table_name = $wpdb->prefix . $table_suffix;
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'category_id'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN category_id mediumint(9) DEFAULT 1 AFTER id");
            }
        }
        
        // Add image columns to variants table if they don't exist
        $table_variants = $wpdb->prefix . 'federwiegen_variants';
        $columns_to_add = array(
            'price_from' => 'DECIMAL(10,2) DEFAULT 0',
            'image_url_1' => 'TEXT',
            'image_url_2' => 'TEXT',
            'image_url_3' => 'TEXT',
            'image_url_4' => 'TEXT',
            'image_url_5' => 'TEXT',
            'available' => 'TINYINT(1) DEFAULT 1',
            'availability_note' => 'VARCHAR(255) DEFAULT ""',
            'delivery_time' => 'VARCHAR(255) DEFAULT ""'
        );
        
        foreach ($columns_to_add as $column => $type) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_variants LIKE '$column'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_variants ADD COLUMN $column $type AFTER base_price");
            }
        }
        
        // Remove old single image_url column if it exists
        $old_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_variants LIKE 'image_url'");
        if (!empty($old_column_exists)) {
            // Migrate data from old column to new column
            $wpdb->query("UPDATE $table_variants SET image_url_1 = image_url WHERE image_url IS NOT NULL AND image_url != ''");
            $wpdb->query("ALTER TABLE $table_variants DROP COLUMN image_url");
        }
        
        // Add image_url column to extras table if it doesn't exist
        $table_extras = $wpdb->prefix . 'federwiegen_extras';
        $extra_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'image_url'");
        if (empty($extra_column_exists)) {
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN image_url TEXT AFTER price");
        }
        
        // Create categories table if it doesn't exist
        $table_categories = $wpdb->prefix . 'federwiegen_categories';
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
                button_text varchar(255) DEFAULT '',
                button_icon text DEFAULT '',
                shipping_cost decimal(10,2) DEFAULT 0,
                shipping_provider varchar(50) DEFAULT '',
                price_label varchar(255) DEFAULT 'Monatlicher Mietpreis',
                shipping_label varchar(255) DEFAULT 'Einmalige Versandkosten:',
                price_period varchar(20) DEFAULT 'month',
                vat_included tinyint(1) DEFAULT 0,
                layout_style varchar(50) DEFAULT 'default',
                duration_tooltip text DEFAULT '',
                condition_tooltip text DEFAULT '',
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
                'features_title' => 'VARCHAR(255) DEFAULT ""',
                'feature_1_icon' => 'TEXT',
                'feature_1_title' => 'VARCHAR(255)',
                'feature_1_description' => 'TEXT',
                'feature_2_icon' => 'TEXT',
                'feature_2_title' => 'VARCHAR(255)',
                'feature_2_description' => 'TEXT',
                'feature_3_icon' => 'TEXT',
                'feature_3_title' => 'VARCHAR(255)',
                'feature_3_description' => 'TEXT',
                'button_text' => 'VARCHAR(255)',
                'button_icon' => 'TEXT',
                'payment_icons'   => 'TEXT',
                'shipping_cost'   => 'DECIMAL(10,2) DEFAULT 0',
                'shipping_provider' => 'VARCHAR(50) DEFAULT ""',
                'price_label' => 'VARCHAR(255) DEFAULT "Monatlicher Mietpreis"',
                'shipping_label' => 'VARCHAR(255) DEFAULT "Einmalige Versandkosten:"',
                'price_period' => 'VARCHAR(20) DEFAULT "month"',
                'vat_included' => 'TINYINT(1) DEFAULT 0',
                'layout_style' => 'VARCHAR(50) DEFAULT "default"',
                'duration_tooltip' => 'TEXT',
                'condition_tooltip' => 'TEXT',
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
        $table_analytics = $wpdb->prefix . 'federwiegen_analytics';
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
        $table_branding = $wpdb->prefix . 'federwiegen_branding';
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
                'plugin_name' => 'H2 Concepts Rent Plugin',
                'plugin_description' => 'Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration',
                'company_name' => 'H2 Concepts',
                'company_url' => 'https://h2concepts.de',
                'admin_logo' => '',
                'admin_color_primary' => '#5f7f5f',
                'admin_color_secondary' => '#4a674a',
                'footer_text' => 'Powered by H2 Concepts'
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
        
        // Create conditions table if it doesn't exist
        $table_conditions = $wpdb->prefix . 'federwiegen_conditions';
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
        $table_colors = $wpdb->prefix . 'federwiegen_colors';
        $colors_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_colors'");

        if (!$colors_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_colors (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                category_id mediumint(9) DEFAULT 1,
                name varchar(255) NOT NULL,
                color_code varchar(7) NOT NULL,
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
        }

        // Create color variant images table if it doesn't exist
        $table_color_variant_images = $wpdb->prefix . 'federwiegen_color_variant_images';
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
        $table_variant_options = $wpdb->prefix . 'federwiegen_variant_options';
        $variant_options_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_variant_options'");
        
        if (!$variant_options_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_variant_options (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                variant_id mediumint(9) NOT NULL,
                option_type varchar(50) NOT NULL,
                option_id mediumint(9) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY variant_option (variant_id, option_type, option_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Create orders table if it doesn't exist
        $table_orders = $wpdb->prefix . 'federwiegen_orders';
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
                stripe_link text NOT NULL,
                customer_name varchar(255) DEFAULT '',
                customer_email varchar(255) DEFAULT '',
                user_ip varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY category_id (category_id),
                KEY created_at (created_at)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            $new_order_columns = array(
                'extra_ids' => 'varchar(255)'
            );

            foreach ($new_order_columns as $column => $type) {
                $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_orders LIKE '$column'");
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE $table_orders ADD COLUMN $column $type AFTER extra_id");
                }
            }
        }

        // Create notifications table if it doesn't exist
        $table_notifications = $wpdb->prefix . 'federwiegen_notifications';
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
        
        // Update links table with new columns
        $table_links = $wpdb->prefix . 'federwiegen_links';
        $new_link_columns = array(
            'extra_ids'       => 'varchar(255)',
            'condition_id'    => 'mediumint(9)',
            'product_color_id'=> 'mediumint(9)',
            'frame_color_id'  => 'mediumint(9)'
        );
        
        foreach ($new_link_columns as $column => $type) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_links LIKE '$column'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_links ADD COLUMN $column $type AFTER duration_id");
            }
        }

        // Migrate existing link records to populate extra_ids if empty
        $wpdb->query("UPDATE $table_links SET extra_ids = '' WHERE extra_ids IS NULL AND extra_id = 0");
        $wpdb->query("UPDATE $table_links SET extra_ids = CAST(extra_id AS CHAR) WHERE (extra_ids IS NULL OR extra_ids = '') AND extra_id > 0");

        // Ensure unique index includes extra_ids
        $index_info = $wpdb->get_results("SHOW INDEX FROM $table_links WHERE Key_name = 'category_variant_extra_duration'");
        $has_extra_ids = false;
        foreach ($index_info as $row) {
            if ($row->Column_name === 'extra_ids') {
                $has_extra_ids = true;
                break;
            }
        }
        if (!$has_extra_ids && !empty($index_info)) {
            $wpdb->query("ALTER TABLE $table_links DROP INDEX category_variant_extra_duration");
            $wpdb->query("ALTER TABLE $table_links ADD UNIQUE KEY category_variant_extra_duration (category_id, variant_id, extra_id, extra_ids(191), duration_id, condition_id, product_color_id, frame_color_id)");
        }

        // Add availability column to variant options table if it doesn't exist
        $table_variant_options = $wpdb->prefix . 'federwiegen_variant_options';
        $availability_column = $wpdb->get_results("SHOW COLUMNS FROM $table_variant_options LIKE 'available'");
        if (empty($availability_column)) {
            $wpdb->query("ALTER TABLE $table_variant_options ADD COLUMN available TINYINT(1) DEFAULT 1 AFTER option_id");
        }
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Categories table for different product categories (with SEO fields)
        $table_categories = $wpdb->prefix . 'federwiegen_categories';
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
            button_text varchar(255) DEFAULT '',
            button_icon text DEFAULT '',
            payment_icons text DEFAULT '',
            shipping_cost decimal(10,2) DEFAULT 0,
            shipping_provider varchar(50) DEFAULT '',
            price_label varchar(255) DEFAULT 'Monatlicher Mietpreis',
            shipping_label varchar(255) DEFAULT 'Einmalige Versandkosten:',
            price_period varchar(20) DEFAULT 'month',
            vat_included tinyint(1) DEFAULT 0,
            layout_style varchar(50) DEFAULT 'default',
            duration_tooltip text DEFAULT '',
            condition_tooltip text DEFAULT '',
            show_tooltips tinyint(1) DEFAULT 1,
            show_rating tinyint(1) DEFAULT 0,
            rating_value decimal(3,1) DEFAULT 0,
            rating_link text DEFAULT '',
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Variants table (updated with multiple image fields, category_id and availability)
        $table_variants = $wpdb->prefix . 'federwiegen_variants';
        $sql_variants = "CREATE TABLE $table_variants (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            description text,
            base_price decimal(10,2) NOT NULL,
            price_from decimal(10,2) DEFAULT 0,
            image_url_1 text,
            image_url_2 text,
            image_url_3 text,
            image_url_4 text,
            image_url_5 text,
            available tinyint(1) DEFAULT 1,
            availability_note varchar(255) DEFAULT '',
            delivery_time varchar(255) DEFAULT '',
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Extras table (updated with image field and category_id)
        $table_extras = $wpdb->prefix . 'federwiegen_extras';
        $sql_extras = "CREATE TABLE $table_extras (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            price decimal(10,2) NOT NULL,
            image_url text,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Durations table (with category_id)
        $table_durations = $wpdb->prefix . 'federwiegen_durations';
        $sql_durations = "CREATE TABLE $table_durations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            months_minimum int(11) NOT NULL,
            discount decimal(5,4) DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Links table (with category_id and new option columns)
        $table_links = $wpdb->prefix . 'federwiegen_links';
        $sql_links = "CREATE TABLE $table_links (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            variant_id mediumint(9) NOT NULL,
            extra_id mediumint(9) NOT NULL,
            extra_ids varchar(255) DEFAULT NULL,
            duration_id mediumint(9) NOT NULL,
            condition_id mediumint(9) DEFAULT NULL,
            product_color_id mediumint(9) DEFAULT NULL,
            frame_color_id mediumint(9) DEFAULT NULL,
            stripe_link text NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY category_variant_extra_duration (category_id, variant_id, extra_id, extra_ids(191), duration_id, condition_id, product_color_id, frame_color_id)
        ) $charset_collate;";
        
        // Analytics table for tracking (with new option columns)
        $table_analytics = $wpdb->prefix . 'federwiegen_analytics';
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
        $table_branding = $wpdb->prefix . 'federwiegen_branding';
        $sql_branding = "CREATE TABLE $table_branding (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        
        // Conditions table
        $table_conditions = $wpdb->prefix . 'federwiegen_conditions';
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
        $table_colors = $wpdb->prefix . 'federwiegen_colors';
        $sql_colors = "CREATE TABLE $table_colors (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            color_code varchar(7) NOT NULL,
            color_type varchar(20) NOT NULL,
            image_url text,
            available tinyint(1) DEFAULT 1,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Color variant images table
        $table_color_variant_images = $wpdb->prefix . 'federwiegen_color_variant_images';
        $sql_color_variant_images = "CREATE TABLE $table_color_variant_images (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            color_id mediumint(9) NOT NULL,
            variant_id mediumint(9) NOT NULL,
            image_url text DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY color_variant (color_id, variant_id)
        ) $charset_collate;";
        
        // Variant options table
        $table_variant_options = $wpdb->prefix . 'federwiegen_variant_options';
        $sql_variant_options = "CREATE TABLE $table_variant_options (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            variant_id mediumint(9) NOT NULL,
            option_type varchar(50) NOT NULL,
            option_id mediumint(9) NOT NULL,
            available tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY variant_option (variant_id, option_type, option_id)
        ) $charset_collate;";
        
        // Orders table
        $table_orders = $wpdb->prefix . 'federwiegen_orders';
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
            stripe_link text NOT NULL,
            customer_name varchar(255) DEFAULT '',
            customer_email varchar(255) DEFAULT '',
            user_ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_categories);
        dbDelta($sql_variants);
        dbDelta($sql_extras);
        dbDelta($sql_durations);
        dbDelta($sql_links);
        dbDelta($sql_analytics);
        dbDelta($sql_branding);
        dbDelta($sql_conditions);
        dbDelta($sql_colors);
        dbDelta($sql_color_variant_images);
        dbDelta($sql_variant_options);
        dbDelta($sql_orders);

        // Notifications table
        $table_notifications = $wpdb->prefix . 'federwiegen_notifications';
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
    }
    
    public function insert_default_data() {
        global $wpdb;
        
        // Insert default category
        $existing_categories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_categories");
        if ($existing_categories == 0) {
            $wpdb->insert(
                $wpdb->prefix . 'federwiegen_categories',
                array(
                     'name' => 'Standard Federwiegen',
                    'shortcode' => 'federwiegen_product',
                    'page_title' => '',
                    'page_description' => '',
                    'meta_title' => '',
                    'meta_description' => '',
                    'product_title' => '',
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
                    'button_text' => '',
                    'button_icon' => '',
                    'payment_icons' => '',
                    'shipping_cost' => 0,
                    'shipping_provider' => '',
                    'layout_style' => 'default',
                    'duration_tooltip' => '',
                    'condition_tooltip' => '',
                    'show_tooltips' => 1,
                    'show_rating' => 0,
                    'rating_value' => 0,
                    'rating_link' => '',
                    'sort_order' => 0
                )
            );
        }
        
        // Insert default branding settings
        $existing_branding = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_branding");
        if ($existing_branding == 0) {
            $default_branding = array(
                'plugin_name' => '',
                'plugin_description' => '',
                'company_name' => '',
                'company_url' => '',
                'admin_logo' => '',
                'admin_color_primary' => '#5f7f5f',
                'admin_color_secondary' => '#4a674a',
                'footer_text' => ''
            );
            
            foreach ($default_branding as $key => $value) {
                $wpdb->insert(
                    $wpdb->prefix . 'federwiegen_branding',
                    array(
                        'setting_key' => $key,
                        'setting_value' => $value
                    )
                );
            }
        }
        
        // Insert default variants only if table is empty
        $existing_variants = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_variants");
        if ($existing_variants == 0) {
            $variants = array(
                array('Federwiege + Gestell & Motor', 'Komplettset mit stabilem Gestell und leisem Motor', 89.00),
                array('Federwiege + Türklammer & Motor', 'Platzsparende Lösung mit praktischer Türklammer', 79.00),
                array('Wiege + Gestell & Motor mit App-Steuerung', 'Premium-Variante mit smarter App-Steuerung', 119.00)
            );
            
            foreach ($variants as $index => $variant) {
                $wpdb->insert(
                    $wpdb->prefix . 'federwiegen_variants',
                    array(
                        'category_id' => 1,
                        'name' => $variant[0],
                        'description' => $variant[1],
                        'base_price' => $variant[2],
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
        $existing_extras = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_extras");
        if ($existing_extras == 0) {
            $extras = array(
                array('Kein Extra', 0.00),
                array('Himmel', 15.00)
            );
            
            foreach ($extras as $index => $extra) {
                $wpdb->insert(
                    $wpdb->prefix . 'federwiegen_extras',
                    array(
                        'category_id' => 1,
                        'name' => $extra[0],
                        'price' => $extra[1],
                        'image_url' => '',
                        'sort_order' => $index
                    )
                );
            }
        }
        
        // Insert default durations only if table is empty
        $existing_durations = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_durations");
        if ($existing_durations == 0) {
            $durations = array(
                array('Flexible Abo', 1, 0.00),
                array('ab 2+', 2, 0.05),
                array('ab 4+', 4, 0.10),
                array('ab 6+', 6, 0.15)
            );
            
            foreach ($durations as $index => $duration) {
                $wpdb->insert(
                    $wpdb->prefix . 'federwiegen_durations',
                    array(
                        'category_id' => 1,
                        'name' => $duration[0],
                        'months_minimum' => $duration[1],
                        'discount' => $duration[2],
                        'sort_order' => $index
                    )
                );
            }
        }
    }
}
