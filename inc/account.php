<?php
/**
 * „Můj účet" na front-endu: změna hesla, e-mailu a jména bez nutnosti chodit do wp-adminu.
 * Pro adminy navíc přívětivé přidání nového člena (účtu) přímo z webu.
 */
if (!defined('ABSPATH')) exit;

/** Jednou vytvoř stránku „Můj účet". */
function hd_ensure_ucet_page() {
    $existing = (int) get_option('hd_ucet_page_id');
    if ($existing && get_post_status($existing) === 'publish') return;
    $id = wp_insert_post([
        'post_type'   => 'page',
        'post_title'  => 'Můj účet',
        'post_name'   => 'ucet',
        'post_status' => 'publish',
        'post_content'=> '',
    ]);
    if ($id && !is_wp_error($id)) {
        update_post_meta($id, '_wp_page_template', 'page-ucet.php');
        update_option('hd_ucet_page_id', $id);
    }
}
add_action('init', 'hd_ensure_ucet_page');

/** Odkaz na stránku účtu. */
function hd_ucet_url() {
    $id = (int) get_option('hd_ucet_page_id');
    return $id ? get_permalink($id) : home_url('/');
}

/** Uložení vlastního účtu (jméno, e-mail, případně nové heslo). */
function hd_handle_update_account() {
    if (!is_user_logged_in()) wp_die('Musíš být přihlášen.');
    if (empty($_POST['hd_acc_nonce']) || !wp_verify_nonce($_POST['hd_acc_nonce'], 'hd_update_account')) wp_die('Neplatný požadavek.');
    $back = hd_ucet_url();
    $user = wp_get_current_user();

    $display = sanitize_text_field(wp_unslash($_POST['display_name'] ?? ''));
    $email   = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $new1    = (string) ($_POST['new_pass'] ?? '');
    $new2    = (string) ($_POST['new_pass2'] ?? '');
    $current = (string) ($_POST['current_pass'] ?? '');

    // 1) jméno + e-mail
    $upd = ['ID' => $user->ID];
    if ($display !== '' && $display !== $user->display_name) $upd['display_name'] = $display;
    if ($email !== '' && $email !== $user->user_email) {
        if (!is_email($email)) { wp_safe_redirect(add_query_arg('hd_acc', 'bademail', $back)); exit; }
        $owner = email_exists($email);
        if ($owner && (int) $owner !== (int) $user->ID) { wp_safe_redirect(add_query_arg('hd_acc', 'emailtaken', $back)); exit; }
        $upd['user_email'] = $email;
    }
    if (count($upd) > 1) wp_update_user($upd);

    // 2) změna hesla (jen když je aspoň jedno pole vyplněné)
    if ($new1 !== '' || $new2 !== '') {
        if (!wp_check_password($current, $user->user_pass, $user->ID)) { wp_safe_redirect(add_query_arg('hd_acc', 'badcurrent', $back)); exit; }
        if ($new1 !== $new2)       { wp_safe_redirect(add_query_arg('hd_acc', 'mismatch', $back)); exit; }
        if (strlen($new1) < 6)     { wp_safe_redirect(add_query_arg('hd_acc', 'short', $back)); exit; }
        wp_set_password($new1, $user->ID);
        // heslo mění relaci → přihlas uživatele znovu, ať nevypadne
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        wp_safe_redirect(add_query_arg('hd_acc', 'passok', $back)); exit;
    }

    wp_safe_redirect(add_query_arg('hd_acc', 'ok', $back)); exit;
}
add_action('admin_post_hd_update_account', 'hd_handle_update_account');

/** ADMIN: přidání nového člena (účtu) z webu. */
function hd_handle_add_member() {
    if (!hd_can_manage()) wp_die('Přidávat členy může jen admin.');
    if (empty($_POST['hd_member_nonce']) || !wp_verify_nonce($_POST['hd_member_nonce'], 'hd_add_member')) wp_die('Neplatný požadavek.');
    $back = hd_ucet_url();

    $name  = sanitize_text_field(wp_unslash($_POST['m_name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['m_email'] ?? ''));
    $login = sanitize_user(wp_unslash($_POST['m_login'] ?? ''), true);
    $pass  = (string) ($_POST['m_pass'] ?? '');
    $make_player = !empty($_POST['m_make_player']);

    if ($name === '' || !is_email($email)) { wp_safe_redirect(add_query_arg('hd_acc', 'm_bad', $back)); exit; }
    if ($login === '') { // odvoď login z e-mailu / jména
        $login = sanitize_user(current(explode('@', $email)), true) ?: sanitize_user($name, true);
    }
    if (username_exists($login)) { wp_safe_redirect(add_query_arg(['hd_acc' => 'm_login', 'v' => rawurlencode($login)], $back)); exit; }
    if (email_exists($email))    { wp_safe_redirect(add_query_arg(['hd_acc' => 'm_email', 'v' => rawurlencode($email)], $back)); exit; }

    $generated = false;
    if ($pass === '') { $pass = wp_generate_password(10, false); $generated = true; }

    $uid = wp_insert_user([
        'user_login'   => $login,
        'user_email'   => $email,
        'user_pass'    => $pass,
        'display_name' => $name,
        'nickname'     => $name,
        'role'         => 'subscriber', // člen = přihlášený ne-admin
    ]);
    if (is_wp_error($uid)) { wp_safe_redirect(add_query_arg('hd_acc', 'm_err', $back)); exit; }

    // volitelně rovnou hráč + spárování přes e-mail
    if ($make_player) {
        $existing = get_posts(['post_type' => 'hrac', 'numberposts' => 1, 'meta_key' => 'email', 'meta_value' => $email, 'fields' => 'ids']);
        if (!$existing) {
            $pid = wp_insert_post(['post_type' => 'hrac', 'post_status' => 'publish', 'post_title' => $name]);
            if ($pid && !is_wp_error($pid)) {
                update_post_meta($pid, 'email', $email);
                update_post_meta($pid, 'color', '#eeb088');
            }
        }
    }

    // údaje ulož bezpečně do transientu (ne do URL) – na stránce se ukážou jednou a smažou
    set_transient('hd_newmember_' . get_current_user_id(), [
        'login' => $login,
        'pass'  => $generated ? $pass : '', // heslo jen když ho vygeneroval systém
    ], 5 * MINUTE_IN_SECONDS);
    wp_safe_redirect(add_query_arg('hd_acc', 'm_ok', $back)); exit;
}
add_action('admin_post_hd_add_member', 'hd_handle_add_member');
