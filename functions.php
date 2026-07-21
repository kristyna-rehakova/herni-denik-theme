<?php
/**
 * Herní deník – theme setup
 */
if (!defined('ABSPATH')) exit;

define('HD_VERSION', '0.2.1');

function hd_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails'); // obálky her = "featured image"
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    register_nav_menus(['primary' => __('Hlavní menu', 'herni-denik')]);
}
add_action('after_setup_theme', 'hd_setup');

function hd_assets() {
    wp_enqueue_style('herni-denik', get_stylesheet_uri(), [], HD_VERSION);
}
add_action('wp_enqueue_scripts', 'hd_assets');

require get_template_directory() . '/inc/helpers.php';
require get_template_directory() . '/inc/cpt.php';
require get_template_directory() . '/inc/meta.php';
if (is_admin()) require get_template_directory() . '/inc/import.php';

/**
 * Přesměruj nepřihlášené na login (skupinový deník = jen pro členy).
 * Vypni konstantou define('HD_PUBLIC', true) ve wp-config.php, pokud chceš web veřejný.
 */
function hd_require_login() {
    if (is_admin() || (defined('HD_PUBLIC') && HD_PUBLIC)) return;
    if (is_user_logged_in()) return;
    // povol přihlašovací a registrační stránky
    global $pagenow;
    if (in_array($pagenow, ['wp-login.php','wp-register.php'], true)) return;
    auth_redirect();
}
add_action('template_redirect', 'hd_require_login');
