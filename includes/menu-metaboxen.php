<?php
// Plugin-Kategorien und Produkte im Menü-Editor auswählbar machen wie Seiten/Beiträge

if (!defined('ABSPATH')) {
    exit;
}

// Schritt 1: Metabox für Produktkategorien registrieren
add_action('admin_init', function () {
    add_meta_box(
        'produkt_kategorien_navbox',
        'Produktkategorien',
        'produkt_render_kategorien_metabox',
        'nav-menus',
        'side',
        'default'
    );
});

function produkt_render_kategorien_metabox() {
    global $wpdb;
    $table = $wpdb->prefix . 'produkt_product_categories';
    $kategorien = $wpdb->get_results("SELECT * FROM $table ORDER BY name");

    echo '<div id="posttype-plugin_kategorie" class="posttypediv">';
    echo '<div class="tabs-panel tabs-panel-active" id="tabs-panel-plugin_kategorie">';
    echo '<ul id="plugin_kategorie-checklist" class="categorychecklist form-no-clear">';
    foreach ($kategorien as $cat) {
        $url = home_url('/shop/' . sanitize_title($cat->slug));
        echo '<li>';
        echo '<label class="menu-item-title">';
        echo '<input type="checkbox" class="menu-item-checkbox" name="plugin_kategorie_menu[]" value="' . esc_attr($url) . '|' . esc_attr($cat->name) . '"> ' . esc_html($cat->name);
        echo '</label>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
    echo '<p class="button-controls"><span class="add-to-menu">';
    echo '<input type="submit" class="button-secondary submit-add-to-menu right" value="Zum Menü hinzufügen" name="add-plugin-kategorie-menu">';
    echo '<span class="spinner"></span></span></p>';
    echo '</div>';
}

// Schritt 2: Metabox für Einzelprodukte registrieren
add_action('admin_init', function () {
    add_meta_box(
        'produkt_produkte_navbox',
        'Produktseiten',
        'produkt_render_produkte_metabox',
        'nav-menus',
        'side',
        'default'
    );
});

function produkt_render_produkte_metabox() {
    global $wpdb;
    $produkte = $wpdb->get_results("SELECT id, product_title FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order, name");

    echo '<div id="posttype-plugin_produkt" class="posttypediv">';
    echo '<div class="tabs-panel tabs-panel-active" id="tabs-panel-plugin_produkt">';
    echo '<ul id="plugin_produkt-checklist" class="categorychecklist form-no-clear">';
    foreach ($produkte as $p) {
        $url = home_url('/shop/produkt/' . sanitize_title($p->product_title));
        echo '<li>';
        echo '<label class="menu-item-title">';
        echo '<input type="checkbox" class="menu-item-checkbox" name="plugin_produkt_menu[]" value="' . esc_attr($url) . '|' . esc_attr($p->product_title) . '"> ' . esc_html($p->product_title);
        echo '</label>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
    echo '<p class="button-controls"><span class="add-to-menu">';
    echo '<input type="submit" class="button-secondary submit-add-to-menu right" value="Zum Menü hinzufügen" name="add-plugin-produkt-menu">';
    echo '<span class="spinner"></span></span></p>';
    echo '</div>';
}

// Schritt 3: POST-Auswertung & Menü-Eintrag registrieren
add_filter('wp_edit_nav_menu_walker', function ($walker) {
    if (!class_exists('Produkt_Nav_Menu_Custom_Walker')) {
        class Produkt_Nav_Menu_Custom_Walker extends Walker_Nav_Menu_Edit {
            // bleibt leer, da keine Sonderlogik benötigt
        }
    }
    return 'Produkt_Nav_Menu_Custom_Walker';
});

add_action('admin_init', function () {
    if (isset($_POST['plugin_kategorie_menu'])) {
        produkt_menue_custom_items_speichern($_POST['plugin_kategorie_menu']);
    }
    if (isset($_POST['plugin_produkt_menu'])) {
        produkt_menue_custom_items_speichern($_POST['plugin_produkt_menu']);
    }
});

function produkt_menue_custom_items_speichern($eintraege) {
    foreach ($eintraege as $item) {
        list($url, $title) = explode('|', $item);

        wp_update_nav_menu_item(0, 0, [
            'menu-item-type'   => 'custom',
            'menu-item-title'  => $title,
            'menu-item-url'    => $url,
            'menu-item-status' => 'publish',
        ]);
    }
}
