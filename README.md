# H2 Concepts Rental Pro

This WordPress plugin enables the rental of configurable products with built-in Stripe integration. It was initially developed for renting baby swings ("Produkt") but can be adapted to other products.

## Features

- Admin pages to manage product categories, variants, extras, rental durations and more
- Shortcode `[produkt_product]` to embed a product page on the front‑end
- Calculates prices dynamically and links to your Stripe checkout URLs
- Supports Stripe promotion codes so customers can redeem coupons
- Collects customer phone numbers and addresses during checkout
- Custom checkout texts can be configured in the Stripe settings
- Tracks user interactions for analytics
- Generates SEO meta tags, Open Graph tags and schema markup
- Shows cancelled subscriptions as "gekündigt" in the orders overview
- Revenue statistics only include orders marked as "abgeschlossen"

## Installation

1. Upload the plugin files to the `/wp-content/plugins` directory or install through the WordPress admin panel.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Upon first activation database tables will be created automatically.

### Loading Demo Data

By default no sample data is inserted. To load the example records define the following constant **before** activating the plugin (for example in `wp-config.php`):

```php
define('PRODUKT_LOAD_DEFAULT_DATA', true);
```

You may also toggle this behaviour with the `produkt_load_default_data` filter.

## Usage

1. Configure your categories, variants, extras and durations in the new **Produkt** admin menu.
2. Add the shortcode to a page or post:

```php
[produkt_product category="STANDARD"]
```

Use the `category` attribute to select a specific product category by shortcode.

Under **Einstellungen → Stripe Integration** you can specify the link to your terms of service (AGB) and set the success and cancel URLs that Stripe should use. You may also define optional custom texts shown next to the shipping address fields, on the submit button and after the form is submitted. You can store the PayPal Payment Method Configuration ID used for subscriptions and the Webhook Signing Secret to validate Stripe callbacks. The success URL you enter is automatically appended with `?session_id=CHECKOUT_SESSION_ID` so you only need to provide the base path. The text on the checkout page uses your AGB link directly as UTF‑8 and is not encoded again. In the Branding tab you can now choose a primary, secondary and text color for the admin pages. The same page also lets you configure the button, text, border and button text colors used on the front-end. In the category settings you can also choose whether the features section should appear on the product page.

## Development

The plugin code is organised in the `includes`, `admin`, `templates` and `assets` directories. Activation and deactivation hooks are registered in `produkt-verleih.php`. Core functionality lives in `includes/` where an autoloader loads the classes.


