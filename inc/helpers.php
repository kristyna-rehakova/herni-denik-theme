<?php
/**
 * Pomocné funkce pro zobrazení
 */
if (!defined('ABSPATH')) exit;

function hd_meta($id, $key, $default = '') {
    $v = get_post_meta($id, $key, true);
    return ($v === '' || $v === null) ? $default : $v;
}

function hd_players_label($id) {
    $mn = hd_meta($id, 'players_min');
    $mx = hd_meta($id, 'players_max');
    if ($mn && $mx) return ($mn == $mx) ? $mn : "$mn–$mx";
    return $mn ? "$mn+" : '';
}

function hd_time_label($id) {
    $mn = hd_meta($id, 'time_min');
    $mx = hd_meta($id, 'time_max');
    if ($mn && $mx && $mn != $mx) return "$mn–$mx min";
    $t = $mx ?: $mn;
    return $t ? "$t min" : '';
}

/** difficulty meta: 'lehka' | 'stredni' | 'tezka' */
function hd_diff($id) {
    $d = hd_meta($id, 'difficulty');
    $map = [
        'lehka'   => ['label' => 'lehká',   'n' => 1],
        'stredni' => ['label' => 'střední', 'n' => 2],
        'tezka'   => ['label' => 'těžká',   'n' => 3],
    ];
    return isset($map[$d]) ? $map[$d] : null;
}

/** Avatar hráče (barevné kolečko s emoji nebo iniciálou). */
function hd_player_avatar($hrac_id, $size = 30) {
    $color = hd_meta($hrac_id, 'color', '#eeb088');
    $emoji = hd_meta($hrac_id, 'emoji', '');
    $nick  = hd_meta($hrac_id, 'nick', '');
    $label = $nick ?: get_the_title($hrac_id);
    $content = $emoji ?: mb_strtoupper(mb_substr($label, 0, 1));
    $fs = round($size * ($emoji ? 0.55 : 0.46));
    return sprintf(
        '<span class="avatar" style="width:%1$dpx;height:%1$dpx;background:%2$s;font-size:%3$dpx">%4$s</span>',
        $size, esc_attr($color), $fs, esc_html($content)
    );
}

/** Zobrazované jméno hráče = přezdívka || název. */
function hd_player_name($hrac_id) {
    $nick = hd_meta($hrac_id, 'nick', '');
    return $nick ?: get_the_title($hrac_id);
}

/** Vytáhne ID YouTube videa z různých tvarů odkazu. */
function hd_youtube_id($url) {
    if (!$url) return '';
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) return $m[1];
    if (preg_match('~^[A-Za-z0-9_-]{11}$~', $url)) return $url;
    return '';
}

/** Datum ve tvaru YYYY-MM-DD → český formát „21. 7. 2026". */
function hd_format_date($ymd) {
    if (!$ymd) return '';
    $t = strtotime($ymd);
    if (!$t) return $ymd;
    return date_i18n('j. n. Y', $t);
}

/** Inline styl obálky (background-image + pozice + velikost) z náhledového obrázku a meta. */
function hd_cover_style($id) {
    $tid = get_post_thumbnail_id($id);
    if (!$tid) return '';
    $url = wp_get_attachment_image_url($tid, 'large');
    if (!$url) return '';
    $x = hd_meta($id, 'img_x'); $x = ($x === '') ? 50 : $x;
    $y = hd_meta($id, 'img_y'); $y = ($y === '') ? 50 : $y;
    $size = hd_meta($id, 'img_size'); if (!$size) $size = 'cover';
    return "background-image:url('" . esc_url($url) . "');background-size:" . esc_attr($size) . ";background-position:" . esc_attr($x) . "% " . esc_attr($y) . "%";
}

/** Vnitřek obálky: div.cover se stylem, nebo emoji fallback. */
function hd_cover_inner($id, $fallback = '🎲') {
    $cs = hd_cover_style($id);
    return $cs ? '<div class="cover" style="' . $cs . '"></div>' : $fallback;
}

/** Data-atributy pro editor obrázku (na tlačítko tužky). */
function hd_cover_data($id) {
    $tid = get_post_thumbnail_id($id);
    $url = $tid ? wp_get_attachment_image_url($tid, 'large') : '';
    $x = hd_meta($id, 'img_x'); $x = ($x === '') ? 50 : $x;
    $y = hd_meta($id, 'img_y'); $y = ($y === '') ? 50 : $y;
    $zoom = hd_meta($id, 'img_zoom'); $zoom = $zoom ?: 1;
    $size = hd_meta($id, 'img_size');
    return sprintf('data-game="%d" data-img="%s" data-x="%s" data-y="%s" data-zoom="%s" data-size="%s"',
        $id, esc_attr($url), esc_attr($x), esc_attr($y), esc_attr($zoom), esc_attr($size));
}

/** JSON s údaji hry pro předvyplnění front-end formuláře (atribut data-hd). */
function hd_game_edit_json($id) {
    $d = [
        'id'            => $id,
        'name'          => get_the_title($id),
        'players_min'   => hd_meta($id, 'players_min'),
        'players_max'   => hd_meta($id, 'players_max'),
        'time_min'      => hd_meta($id, 'time_min'),
        'time_max'      => hd_meta($id, 'time_max'),
        'difficulty'    => hd_meta($id, 'difficulty'),
        'year'          => hd_meta($id, 'year'),
        'publisher'     => hd_meta($id, 'publisher'),
        'bgg_url'       => hd_meta($id, 'bgg_url'),
        'pub_url'       => hd_meta($id, 'pub_url'),
        'notes'         => hd_meta($id, 'notes'),
        'desc_priprava' => hd_meta($id, 'desc_priprava'),
        'desc_prubeh'   => hd_meta($id, 'desc_prubeh'),
        'desc_konec'    => hd_meta($id, 'desc_konec'),
    ];
    return esc_attr(wp_json_encode($d));
}

/** JSON s údaji partie pro předvyplnění okna „Zapsat partii". */
function hd_play_edit_json($pid) {
    $d = [
        'id'        => $pid,
        'game'      => (int) hd_meta($pid, 'game'),
        'play_date' => hd_meta($pid, 'play_date'),
        'players'   => array_map('intval', (array) hd_meta($pid, 'players', [])),
        'winners'   => array_map('intval', (array) hd_meta($pid, 'winners', [])),
        'note'      => hd_meta($pid, 'note'),
    ];
    return esc_attr(wp_json_encode($d));
}

/** Počet odehraných partií dané hry. */
function hd_play_count($game_id) {
    $q = new WP_Query([
        'post_type' => 'partie', 'post_status' => 'publish', 'fields' => 'ids',
        'posts_per_page' => -1, 'meta_key' => 'game', 'meta_value' => (int) $game_id,
        'no_found_rows' => true,
    ]);
    return $q->post_count;
}

/** Seznam všech her jako [id => název] (abecedně). */
function hd_all_games() {
    $out = [];
    foreach (get_posts(['post_type'=>'hra','numberposts'=>-1,'orderby'=>'title','order'=>'ASC']) as $p) $out[$p->ID] = $p->post_title;
    return $out;
}

/** Seznam všech hráčů jako [id => zobrazované jméno] (abecedně dle jména). */
function hd_all_players() {
    $out = [];
    foreach (get_posts(['post_type'=>'hrac','numberposts'=>-1,'orderby'=>'title','order'=>'ASC']) as $p) $out[$p->ID] = hd_player_name($p->ID);
    return $out;
}
