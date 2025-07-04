# H2 Concepts Rent Plugin

This WordPress plugin enables the rental of configurable products with built-in Stripe integration. It was initially developed for renting baby swings ("Federwiegen") but can be adapted to other products.

## Features

- Admin pages to manage product categories, variants, extras, rental durations and more
- Shortcode `[federwiegen_product]` to embed a product page on the front‑end
- Calculates prices dynamically and links to your Stripe checkout URLs
- Tracks user interactions for analytics
- Generates SEO meta tags, Open Graph tags and schema markup

## Installation

1. Upload the plugin files to the `/wp-content/plugins` directory or install through the WordPress admin panel.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Upon first activation database tables will be created automatically.

### Loading Demo Data

By default no sample data is inserted. To load the example records define the following constant **before** activating the plugin (for example in `wp-config.php`):

```php
define('FEDERWIEGEN_LOAD_DEFAULT_DATA', true);
```

You may also toggle this behaviour with the `federwiegen_load_default_data` filter.

## Usage

1. Configure your categories, variants, extras and durations in the new **Federwiegen** admin menu.
2. For each combination create the corresponding Stripe link under **Stripe Links**.
3. Add the shortcode to a page or post:

```php
[federwiegen_product category="STANDARD"]
```

Use the `category` attribute to select a specific product category by shortcode.

## Development

The plugin code is organised in the `includes`, `admin`, `templates` and `assets` directories. Activation and deactivation hooks are registered in `federwiegen-verleih.php`. Core functionality lives in `includes/` where an autoloader loads the classes.


