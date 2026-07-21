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
