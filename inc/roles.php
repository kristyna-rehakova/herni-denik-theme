<?php
/**
 * Role a schvalování návrhů úprav her.
 * Admin (manage_options) = plná práva. Člen (přihlášený, ne-admin) = zapisuje partie,
 * přidává hráče a NAVRHUJE úpravy her (propíše se až po schválení adminem).
 */
if (!defined('ABSPATH')) exit;

function hd_can_manage() { return current_user_can('manage_options'); }
function hd_is_member()  { return is_user_logged_in() && !hd_can_manage(); }

/** Pole hry, která spravuje formulář „Upravit info" (a jdou přes návrh). */
function hd_game_fields() {
    return ['name','players_min','players_max','time_min','time_max','difficulty','year',
        'publisher','bgg_url','pub_url','notes','desc_priprava','desc_prubeh','desc_konec','image_url','field_src'];
}

/** Zapiš pole hry (používá přímé uložení admina i schválení návrhu). */
function hd_apply_game_fields($gid, $src) {
    $name = sanitize_text_field(wp_unslash($src['name'] ?? ''));
    if ($name !== '') wp_update_post(['ID' => $gid, 'post_title' => $name]);
    foreach (['players_min','players_max','time_min','time_max','year','publisher','bgg_url','pub_url','difficulty'] as $k) {
        if (isset($src[$k])) update_post_meta($gid, $k, sanitize_text_field(wp_unslash($src[$k])));
    }
    foreach (['notes','desc_priprava','desc_prubeh','desc_konec'] as $k) {
        if (isset($src[$k])) update_post_meta($gid, $k, sanitize_textarea_field(wp_unslash($src[$k])));
    }
    if (isset($src['field_src'])) {
        $fs = is_array($src['field_src']) ? $src['field_src'] : json_decode(wp_unslash($src['field_src']), true);
        if (is_array($fs)) {
            $clean = [];
            foreach ($fs as $k => $v) { if (in_array($v, ['manual','mindok','zatrolene'], true)) $clean[sanitize_key($k)] = $v; }
            update_post_meta($gid, 'field_src', $clean);
        }
    }
    $img = trim(wp_unslash($src['image_url'] ?? ''));
    if (!$img && !has_post_thumbnail($gid)) {
        $bgg = trim(wp_unslash($src['bgg_url'] ?? ''));
        if ($bgg && function_exists('hd_resolve_zatrolene_cover')) $img = hd_resolve_zatrolene_cover($bgg);
    }
    if ($img && !has_post_thumbnail($gid)) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $att = media_sideload_image(esc_url_raw($img), $gid, null, 'id');
        if (!is_wp_error($att)) set_post_thumbnail($gid, $att);
    }
}

/** Ulož návrh úpravy (od člena). */
function hd_store_pending($gid, $post) {
    $fields = [];
    foreach (hd_game_fields() as $k) { if (isset($post[$k])) $fields[$k] = wp_unslash($post[$k]); }
    update_post_meta($gid, 'hd_pending', [
        'fields' => $fields,
        'by'     => get_current_user_id(),
        'at'     => current_time('mysql'),
    ]);
}

/* ---------------- schválení / zamítnutí ---------------- */
function hd_handle_approve_edit() {
    $gid = intval($_GET['game'] ?? 0);
    if (!$gid || !hd_can_manage()) wp_die('Nemáš oprávnění.');
    check_admin_referer('hd_approve_' . $gid);
    $p = get_post_meta($gid, 'hd_pending', true);
    if (is_array($p) && !empty($p['fields'])) hd_apply_game_fields($gid, $p['fields']);
    delete_post_meta($gid, 'hd_pending');
    wp_safe_redirect(add_query_arg('hd_edit', 'approved', get_permalink($gid))); exit;
}
add_action('admin_post_hd_approve_edit', 'hd_handle_approve_edit');

function hd_handle_reject_edit() {
    $gid = intval($_GET['game'] ?? 0);
    if (!$gid || !hd_can_manage()) wp_die('Nemáš oprávnění.');
    check_admin_referer('hd_reject_' . $gid);
    delete_post_meta($gid, 'hd_pending');
    wp_safe_redirect(add_query_arg('hd_edit', 'rejected', get_permalink($gid))); exit;
}
add_action('admin_post_hd_reject_edit', 'hd_handle_reject_edit');

/** Popisky polí pro přehled návrhu. */
function hd_field_labels() {
    return ['name'=>'Název','players_min'=>'Min. hráčů','players_max'=>'Max. hráčů','time_min'=>'Délka od',
        'time_max'=>'Délka do','difficulty'=>'Obtížnost','year'=>'Rok','publisher'=>'Vydavatel',
        'bgg_url'=>'Odkaz na Zatrolené','pub_url'=>'Odkaz na Mindok','notes'=>'Poznámka',
        'desc_priprava'=>'Příprava','desc_prubeh'=>'Průběh','desc_konec'=>'Konec','image_url'=>'Obrázek (URL)'];
}

/** Banner s návrhem úpravy (jen admin, na detailu hry). */
function hd_pending_banner($gid) {
    if (!hd_can_manage()) return;
    $p = get_post_meta($gid, 'hd_pending', true);
    if (!is_array($p) || empty($p['fields'])) return;
    $labels = hd_field_labels();
    $who = !empty($p['by']) ? get_userdata($p['by']) : null;
    $rows = '';
    foreach ($p['fields'] as $k => $val) {
        if (!isset($labels[$k])) continue;
        $cur = ($k === 'name') ? get_the_title($gid) : hd_meta($gid, $k);
        $new = is_string($val) ? trim($val) : '';
        if ((string)$cur === (string)$new) continue;
        $shorten = function ($s) { $s = trim(preg_replace('/\s+/', ' ', (string)$s)); return mb_strlen($s) > 70 ? mb_substr($s, 0, 70) . '…' : $s; };
        $rows .= '<tr><th>' . esc_html($labels[$k]) . '</th><td class="old">' . ($cur !== '' ? esc_html($shorten($cur)) : '<em>—</em>') . '</td><td class="new">' . ($new !== '' ? esc_html($shorten($new)) : '<em>smazat</em>') . '</td></tr>';
    }
    if ($rows === '') { // návrh beze změn – uklidit
        return;
    }
    echo '<div class="pending-box">';
    echo '<div class="pending-head">✎ Návrh úpravy' . ($who ? ' od ' . esc_html($who->display_name) : '') . ' čeká na schválení</div>';
    echo '<table class="pending-diff"><thead><tr><th></th><th>Teď</th><th>Návrh</th></tr></thead><tbody>' . $rows . '</tbody></table>';
    echo '<div class="pending-actions">';
    echo '<a class="btn" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_approve_edit&game=' . $gid), 'hd_approve_' . $gid)) . '">✓ Schválit</a> ';
    echo '<a class="btn back" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_reject_edit&game=' . $gid), 'hd_reject_' . $gid)) . '" onclick="return confirm(\'Zamítnout návrh?\')">✕ Zamítnout</a>';
    echo '</div></div>';
}

/** Má hra čekající návrh? (pro odznak). */
function hd_has_pending($gid) {
    $p = get_post_meta($gid, 'hd_pending', true);
    return is_array($p) && !empty($p['fields']);
}
