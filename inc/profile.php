<?php
/**
 * Propojení přihlášeného uživatele s hráčem (hrac) – kvůli osobním statistikám a filtru „Moje".
 */
if (!defined('ABSPATH')) exit;

/** ID hráče přiřazeného přihlášenému uživateli (párování hlavně přes e-mail). */
function hd_current_player_id($uid = 0) {
    $uid = $uid ?: get_current_user_id();
    if (!$uid) return 0;
    // 1) ruční volba (starší způsob)
    $pid = (int) get_user_meta($uid, 'hd_player_id', true);
    if ($pid && get_post_type($pid) === 'hrac') return $pid;
    // 2) podle e-mailu hráče == e-mail účtu
    $u = get_userdata($uid);
    if ($u && $u->user_email) {
        $q = get_posts(['post_type' => 'hrac', 'numberposts' => 1, 'meta_key' => 'email', 'meta_value' => $u->user_email, 'fields' => 'ids']);
        if ($q) return (int) $q[0];
    }
    // 3) přes pole wp_user v hráči
    $q = get_posts(['post_type' => 'hrac', 'numberposts' => 1, 'meta_key' => 'wp_user', 'meta_value' => $uid, 'fields' => 'ids']);
    return $q ? (int) $q[0] : 0;
}

/** Uložení volby „já jsem hráč X". */
function hd_handle_set_my_player() {
    if (!is_user_logged_in()) wp_die('Musíš být přihlášen.');
    if (empty($_POST['hd_myplayer_nonce']) || !wp_verify_nonce($_POST['hd_myplayer_nonce'], 'hd_set_my_player')) wp_die('Neplatný požadavek.');
    $pid = intval($_POST['player'] ?? 0);
    if ($pid && get_post_type($pid) === 'hrac') update_user_meta(get_current_user_id(), 'hd_player_id', $pid);
    else delete_user_meta(get_current_user_id(), 'hd_player_id');
    wp_safe_redirect(wp_get_referer() ?: home_url('/'));
    exit;
}
add_action('admin_post_hd_set_my_player', 'hd_handle_set_my_player');

/** Výběr „já jsem hráč". */
function hd_my_player_selector() {
    if (!is_user_logged_in()) return;
    $cur = hd_current_player_id();
    $players = hd_all_players();
    echo '<form class="myplayer-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="hd_set_my_player">';
    wp_nonce_field('hd_set_my_player', 'hd_myplayer_nonce');
    echo '<label class="myplayer-lbl">🙋 Já jsem hráč: <select name="player">';
    echo '<option value="">— vyber —</option>';
    foreach ($players as $id => $name) printf('<option value="%d"%s>%s</option>', $id, selected($cur, $id, false), esc_html($name));
    echo '</select></label> <button type="submit" class="btn small">Uložit</button>';
    echo '</form>';
}
