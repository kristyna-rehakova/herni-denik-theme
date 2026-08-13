<?php
/**
 * Statistiky – automaticky vytvořená stránka + pomocné výpočty.
 */
if (!defined('ABSPATH')) exit;

/** Jednou vytvoř stránku „Statistiky" s vlastní šablonou. */
function hd_ensure_stats_page() {
    $existing = (int) get_option('hd_stats_page_id');
    if ($existing && get_post_status($existing) === 'publish') return;
    $id = wp_insert_post([
        'post_type'   => 'page',
        'post_title'  => 'Statistiky',
        'post_name'   => 'statistiky',
        'post_status' => 'publish',
        'post_content'=> '',
    ]);
    if ($id && !is_wp_error($id)) {
        update_post_meta($id, '_wp_page_template', 'page-statistiky.php');
        update_option('hd_stats_page_id', $id);
    }
}
add_action('init', 'hd_ensure_stats_page');

/** Odkaz na stránku statistik. */
function hd_stats_url() {
    $id = (int) get_option('hd_stats_page_id');
    return $id ? get_permalink($id) : home_url('/');
}

/**
 * Spočítá statistiky ze všech partií.
 * Vrací: totals + top hry + žebříček hráčů.
 */
function hd_compute_stats() {
    $games   = get_posts(['post_type'=>'hra','numberposts'=>-1,'fields'=>'ids']);
    $players = get_posts(['post_type'=>'hrac','numberposts'=>-1,'fields'=>'ids']);
    $plays   = get_posts(['post_type'=>'partie','numberposts'=>-1,'fields'=>'ids']);

    $game_plays = [];     // game_id => počet
    $p_played = [];       // hrac_id => počet partií
    $p_won = [];          // hrac_id => počet výher
    foreach ($players as $pid) { $p_played[$pid] = 0; $p_won[$pid] = 0; }

    foreach ($plays as $play) {
        $gid = (int) get_post_meta($play, 'game', true);
        if ($gid) $game_plays[$gid] = ($game_plays[$gid] ?? 0) + 1;
        $ps = (array) get_post_meta($play, 'players', true);
        $ws = (array) get_post_meta($play, 'winners', true);
        foreach ($ps as $x) { $x = (int)$x; if (isset($p_played[$x])) $p_played[$x]++; }
        foreach ($ws as $x) { $x = (int)$x; if (isset($p_won[$x])) $p_won[$x]++; }
    }

    arsort($game_plays);
    $top_games = [];
    foreach ($game_plays as $gid => $cnt) { $top_games[] = ['id'=>$gid, 'count'=>$cnt]; }

    // žebříček hráčů podle výher, pak podle odehraných
    $rank = [];
    foreach ($players as $pid) { $rank[] = ['id'=>$pid, 'played'=>$p_played[$pid], 'won'=>$p_won[$pid]]; }
    usort($rank, function($a, $b){ return $b['won'] <=> $a['won'] ?: $b['played'] <=> $a['played']; });

    return [
        'games'   => count($games),
        'players' => count($players),
        'plays'   => count($plays),
        'top_games' => array_slice($top_games, 0, 8),
        'rank'    => $rank,
    ];
}

/** Statistiky jedné hry: počet partií + žebříček hráčů (vč. hostů). */
function hd_game_stats($gid) {
    $pids = get_posts(['post_type' => 'partie', 'numberposts' => -1, 'meta_key' => 'game', 'meta_value' => (int) $gid, 'fields' => 'ids']);
    $tally = [];
    foreach ($pids as $pid) {
        $players = array_map('intval', (array) get_post_meta($pid, 'players', true));
        $winners = array_map('intval', (array) get_post_meta($pid, 'winners', true));
        $ext = (array) get_post_meta($pid, 'ext_players', true);
        $extw = (array) get_post_meta($pid, 'ext_winners', true);
        foreach ($players as $hp) {
            $k = 'h' . $hp;
            if (!isset($tally[$k])) $tally[$k] = ['name' => hd_player_name($hp), 'id' => $hp, 'ext' => false, 'played' => 0, 'won' => 0];
            $tally[$k]['played']++;
            if (in_array($hp, $winners, true)) $tally[$k]['won']++;
        }
        foreach ($ext as $en) {
            $k = 'e' . $en;
            if (!isset($tally[$k])) $tally[$k] = ['name' => $en, 'id' => 0, 'ext' => true, 'played' => 0, 'won' => 0];
            $tally[$k]['played']++;
            if (in_array($en, $extw, true)) $tally[$k]['won']++;
        }
    }
    $rank = array_values($tally);
    usort($rank, function ($a, $b) { return $b['won'] <=> $a['won'] ?: $b['played'] <=> $a['played']; });
    return ['total' => count($pids), 'rank' => $rank];
}

/** Osobní statistiky jednoho hráče. */
function hd_compute_player_stats($hrac_id) {
    $hrac_id = (int) $hrac_id;
    $plays = get_posts(['post_type' => 'partie', 'numberposts' => -1, 'fields' => 'ids']);
    $played = 0; $won = 0; $game_counts = [];
    foreach ($plays as $pl) {
        $ps = array_map('intval', (array) get_post_meta($pl, 'players', true));
        if (!in_array($hrac_id, $ps, true)) continue;
        $played++;
        $ws = array_map('intval', (array) get_post_meta($pl, 'winners', true));
        if (in_array($hrac_id, $ws, true)) $won++;
        $gid = (int) get_post_meta($pl, 'game', true);
        if ($gid) $game_counts[$gid] = ($game_counts[$gid] ?? 0) + 1;
    }
    arsort($game_counts);
    $top = [];
    foreach ($game_counts as $gid => $c) $top[] = ['id' => $gid, 'count' => $c];
    return [
        'played'   => $played,
        'won'      => $won,
        'winrate'  => $played ? round($won / $played * 100) : 0,
        'top_games' => array_slice($top, 0, 5),
    ];
}
