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

    $nickname = sanitize_text_field(wp_unslash($_POST['nickname'] ?? ''));
    $display  = sanitize_text_field(wp_unslash($_POST['display_name'] ?? ''));
    $email    = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $new1     = (string) ($_POST['new_pass'] ?? '');
    $new2     = (string) ($_POST['new_pass2'] ?? '');
    $current  = (string) ($_POST['current_pass'] ?? '');
    $is_admin = hd_can_manage();

    $upd = ['ID' => $user->ID];

    // 1) přezdívka – smí měnit každý (svoji) a propíše se do jeho hráče
    if ($nickname !== '') {
        $pid = hd_current_player_id($user->ID);
        if (hd_nick_taken($nickname, $pid)) { wp_safe_redirect(add_query_arg('hd_acc', 'nicktaken', $back)); exit; }
        $upd['nickname'] = $nickname;
        if ($pid) update_post_meta($pid, 'nick', $nickname);
    }

    // 2) jméno a příjmení + e-mail – smí měnit jen admin (aby v tom hráči nedělali nepořádek)
    if ($is_admin) {
        if ($display !== '' && $display !== $user->display_name) $upd['display_name'] = $display;
        if ($email !== '' && $email !== $user->user_email) {
            if (!is_email($email)) { wp_safe_redirect(add_query_arg('hd_acc', 'bademail', $back)); exit; }
            $owner = email_exists($email);
            if ($owner && (int) $owner !== (int) $user->ID) { wp_safe_redirect(add_query_arg('hd_acc', 'emailtaken', $back)); exit; }
            $upd['user_email'] = $email;
        }
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
    if ($login === '') { wp_safe_redirect(add_query_arg('hd_acc', 'm_nologin', $back)); exit; }
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
                // přezdívka = uživatelské jméno (ať není prázdná); dotyčný si ji může změnit
                if (!hd_nick_taken($login)) update_post_meta($pid, 'nick', $login);
            }
        }
    }

    // údaje ulož bezpečně do transientu (ne do URL) – na stránce se ukážou jednou a smažou
    // pošli novému členovi e-mail s přihlašovacími údaji
    $mailed = hd_send_member_email($email, $name, $login, $pass);

    set_transient('hd_newmember_' . get_current_user_id(), [
        'login'  => $login,
        'pass'   => $generated ? $pass : '', // heslo jen když ho vygeneroval systém
        'mailed' => $mailed ? '1' : '',
    ], 5 * MINUTE_IN_SECONDS);
    wp_safe_redirect(add_query_arg('hd_acc', 'm_ok', $back)); exit;
}
add_action('admin_post_hd_add_member', 'hd_handle_add_member');

/** E-mail novému členovi: uživatelské jméno, heslo (s výzvou ke změně) a odkaz do deníku. */
function hd_send_member_email($to, $name, $login, $pass) {
    $home = home_url('/');
    $subject = '🎲 Tvůj účet do Herního deníku';
    $b = esc_html($name);
    $l = esc_html($login);
    $p = esc_html($pass);
    $u = esc_url($home);
    $html = '
<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#3f4536">
  <div style="background:linear-gradient(135deg,#FFC125,#F0A400);padding:22px;border-radius:14px 14px 0 0;text-align:center">
    <div style="font-size:24px;font-weight:bold;color:#5a4712">🎲 Můj herní deník</div>
  </div>
  <div style="background:#ffffff;border:1px solid #dde5cc;border-top:none;padding:24px;border-radius:0 0 14px 14px">
    <p>Ahoj <strong>' . $b . '</strong>,</p>
    <p>byl ti vytvořen účet do našeho <strong>Herního deníku</strong> – místa, kam si zapisujeme odehrané deskovky a partie. 🎲</p>
    <p style="margin-bottom:6px"><strong>Tvoje přihlašovací údaje:</strong></p>
    <table style="border-collapse:collapse;margin:0 0 8px">
      <tr><td style="padding:6px 10px;color:#7d8570">Uživatelské jméno:</td><td style="padding:6px 10px"><strong>' . $l . '</strong></td></tr>
      <tr><td style="padding:6px 10px;color:#7d8570">Heslo:</td><td style="padding:6px 10px"><strong>' . $p . '</strong></td></tr>
    </table>
    <p style="background:#fdeede;border:1px solid #f0c9a0;border-radius:10px;padding:10px 12px">⚠️ <strong>Po prvním přihlášení si prosím heslo změň</strong> – vpravo nahoře <em>⚙️ Můj účet → Změna hesla</em>.</p>
    <p style="text-align:center;margin:22px 0">
      <a href="' . $u . '" style="background:#eeb088;color:#3f4536;padding:13px 26px;border-radius:10px;text-decoration:none;font-weight:bold;display:inline-block">Přihlásit se do Deníku</a>
    </p>
    <p style="color:#7d8570;font-size:13px">Kdyby odkaz nefungoval, otevři: <a href="' . $u . '" style="color:#5e8b6e">' . esc_html($home) . '</a></p>
    <p>Hezké hraní! 🎲</p>
  </div>
</div>';
    $host = preg_replace('/^www\./', '', (string) wp_parse_url($home, PHP_URL_HOST));
    $from = $host ? 'Můj herní deník <wordpress@' . $host . '>' : '';
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    if ($from) $headers[] = 'From: ' . $from;
    return wp_mail($to, $subject, $html, $headers);
}
