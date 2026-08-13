<?php
/**
 * Do wp-adminu smí jen „vlastník" webu. Ostatní (i další admini) používají jen front-end.
 * Vlastník = účet s loginem 'admin3649' (lze změnit konstantou HD_OWNER_LOGIN ve wp-config.php).
 */
if (!defined('ABSPATH')) exit;

/** Je uživatel vlastník webu (jediný s přístupem do wp-adminu)? */
function hd_is_owner($u = null) {
    $u = $u ?: wp_get_current_user();
    if (!$u || !$u->exists()) return false;
    $owner       = defined('HD_OWNER_LOGIN') ? HD_OWNER_LOGIN : 'admin3649';
    $owner_email = defined('HD_OWNER_EMAIL') ? HD_OWNER_EMAIL : 'me@pavelrehak.com';
    if ($u->user_login === $owner) return true;
    // záložní přístup přes e-mail (i když účet není admin) – pro případ ztráty účtu admin3649
    if ($owner_email && strtolower($u->user_email) === strtolower($owner_email)) return true;
    // pojistka proti zamčení: když účet vlastníka neexistuje (přejmenování), ber každého admina
    if (!get_user_by('login', $owner) && user_can($u, 'manage_options')) return true;
    return false;
}

/** Nevlastníka z wp-adminu vrať na web (kromě AJAXu a admin-post handlerů front-endu). */
add_action('admin_init', function () {
    global $pagenow;
    if (in_array($pagenow, ['admin-post.php', 'admin-ajax.php'], true)) return;
    if (wp_doing_ajax()) return;
    if (hd_is_owner()) return;
    wp_safe_redirect(home_url('/'));
    exit;
});

/** Horní admin lištu vidí jen vlastník. */
add_filter('show_admin_bar', function ($show) {
    return hd_is_owner() ? $show : false;
});
