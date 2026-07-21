<?php
/**
 * Jednorázový import ze zálohy původní HTML appky (JSON).
 * Nástroje → Import Herního deníku. Bezpečné spustit víckrát – podle
 * původního ID (meta hd_src_id) se existující záznamy přeskočí.
 */
if (!defined('ABSPATH')) exit;

function hd_import_menu() {
    add_management_page('Import Herního deníku', 'Import Herního deníku', 'manage_options', 'hd-import', 'hd_import_page');
}
add_action('admin_menu', 'hd_import_menu');

/** Najdi post daného typu podle původního ID ze zálohy. */
function hd_find_by_src($type, $src_id) {
    if (!$src_id) return 0;
    $q = get_posts([
        'post_type' => $type, 'posts_per_page' => 1, 'post_status' => 'any',
        'meta_key' => 'hd_src_id', 'meta_value' => $src_id, 'fields' => 'ids',
    ]);
    return $q ? (int) $q[0] : 0;
}

/** weight (číselná váha, i desetinná) → obtížnost. Stejné hranice jako v původní appce. */
function hd_import_diff($w) {
    $w = is_numeric($w) ? (float) $w : 0;
    if ($w <= 0) return '';
    if ($w < 2.5) return 'lehka';
    if ($w < 3.5) return 'stredni';
    return 'tezka';
}

/** Nahraj obálku (URL nebo data:) a nastav jako náhledový obrázek. Vrací true/false. */
function hd_import_cover($url, $post_id, $title) {
    if (!$url) return false;
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    if (strpos($url, 'data:') === 0) {
        if (!preg_match('~^data:(image/[a-z0-9.+-]+);base64,(.+)$~is', $url, $m)) return false;
        $ext = ['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][strtolower($m[1])] ?? 'jpg';
        $data = base64_decode($m[2]);
        if ($data === false) return false;
        $up = wp_upload_bits(sanitize_title($title ?: 'obalka') . '.' . $ext, null, $data);
        if (!empty($up['error'])) return false;
        $ft = wp_check_filetype($up['file']);
        $att = wp_insert_attachment(['post_mime_type'=>$ft['type'],'post_title'=>$title,'post_status'=>'inherit'], $up['file'], $post_id);
        if (is_wp_error($att) || !$att) return false;
        wp_update_attachment_metadata($att, wp_generate_attachment_metadata($att, $up['file']));
        set_post_thumbnail($post_id, $att);
        return true;
    }
    $att = media_sideload_image($url, $post_id, $title, 'id');
    if (is_wp_error($att)) return false;
    set_post_thumbnail($post_id, $att);
    return true;
}

function hd_import_page() {
    echo '<div class="wrap"><h1>Import Herního deníku</h1>';
    echo '<p>Nahraj zálohu <code>.json</code> z původní appky. Vytvoří hry, hráče a partie. Spuštění je bezpečné i opakovaně – co už existuje (podle původního ID), se přeskočí.</p>';

    if (!empty($_POST['hd_import_go']) && check_admin_referer('hd_import')) {
        hd_run_import();
    }

    $max = size_format(wp_max_upload_size());
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('hd_import');
    echo '<table class="form-table"><tr><th>Soubor zálohy (.json)</th><td><input type="file" name="hd_file" accept="application/json,.json" required> <p class="description">Limit nahrávání serveru: ' . esc_html($max) . '</p></td></tr>';
    echo '<tr><th>Obálky</th><td><label><input type="checkbox" name="hd_covers" value="1" checked> Stáhnout obálky her z webu (může chvíli trvat; když se nějaká nestáhne, hra se přesto vytvoří)</label></td></tr>';
    echo '<tr><th>Přepsat existující</th><td><label><input type="checkbox" name="hd_overwrite" value="1"> Aktualizovat údaje u her/hráčů/partií, které už existují (např. oprava obtížnosti). Obálky se u existujících stáhnou jen tam, kde chybí.</label></td></tr></table>';
    submit_button('Spustit import', 'primary', 'hd_import_go');
    echo '</form></div>';
}

function hd_run_import() {
    if (empty($_FILES['hd_file']['tmp_name'])) { echo '<div class="notice notice-error"><p>Nebyl nahrán žádný soubor.</p></div>'; return; }
    $raw = file_get_contents($_FILES['hd_file']['tmp_name']);
    // odstraň případný UTF-8 BOM (Windows) – jinak json_decode selže
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    $raw = trim($raw);
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['games'])) { echo '<div class="notice notice-error"><p>Soubor nevypadá jako platná záloha (chybí „games").</p></div>'; return; }

    $do_covers = !empty($_POST['hd_covers']);
    $overwrite = !empty($_POST['hd_overwrite']);
    @set_time_limit(0);

    $map_player = []; $map_game = [];
    $n_pl = 0; $n_g = 0; $n_p = 0; $skip = 0; $upd = 0; $cov_ok = 0; $cov_fail = 0; $fails = [];

    // 1) HRÁČI
    foreach ((array)($data['players'] ?? []) as $pl) {
        $src = $pl['id'] ?? '';
        $exist = hd_find_by_src('hrac', $src);
        if ($exist && !$overwrite) { $map_player[$src] = $exist; $skip++; continue; }
        if ($exist) { $id = $exist; $upd++; }
        else {
            $id = wp_insert_post(['post_type'=>'hrac','post_status'=>'publish','post_title'=>($pl['name'] ?: ($pl['nick'] ?: 'Hráč'))]);
            if (is_wp_error($id)) continue;
            $n_pl++;
        }
        update_post_meta($id, 'hd_src_id', $src);
        update_post_meta($id, 'nick', $pl['nick'] ?? '');
        update_post_meta($id, 'color', $pl['color'] ?? '#eeb088');
        update_post_meta($id, 'emoji', $pl['emoji'] ?? '');
        $map_player[$src] = $id;
    }

    // 2) HRY
    foreach ((array)($data['games'] ?? []) as $g) {
        $src = $g['id'] ?? '';
        $exist = hd_find_by_src('hra', $src);
        if ($exist && !$overwrite) { $map_game[$src] = $exist; $skip++; continue; }
        if ($exist) { $id = $exist; $upd++; }
        else {
            $id = wp_insert_post(['post_type'=>'hra','post_status'=>'publish','post_title'=>($g['name'] ?: 'Hra')]);
            if (is_wp_error($id)) continue;
            $n_g++;
        }
        update_post_meta($id, 'hd_src_id', $src);
        update_post_meta($id, 'players_min', $g['minPlayers'] ?? '');
        update_post_meta($id, 'players_max', $g['maxPlayers'] ?? '');
        update_post_meta($id, 'time_min', $g['minTime'] ?? '');
        update_post_meta($id, 'time_max', $g['maxTime'] ?? '');
        update_post_meta($id, 'difficulty', hd_import_diff($g['weight'] ?? 0));
        update_post_meta($id, 'weight', $g['weight'] ?? '');
        update_post_meta($id, 'year', $g['year'] ?? '');
        update_post_meta($id, 'publisher', $g['publisher'] ?? '');
        update_post_meta($id, 'bgg_url', $g['bggUrl'] ?? '');
        update_post_meta($id, 'pub_url', $g['pubUrl'] ?? '');
        update_post_meta($id, 'youtube', $g['youtube'] ?? '');
        update_post_meta($id, 'notes', $g['notes'] ?? '');
        update_post_meta($id, 'desc_checked', !empty($g['descChecked']) ? '1' : '');
        $desc = $g['desc'] ?? [];
        update_post_meta($id, 'desc_priprava', $desc['priprava']['text'] ?? '');
        update_post_meta($id, 'desc_prubeh',  $desc['prubeh']['text'] ?? '');
        update_post_meta($id, 'desc_konec',   $desc['konec']['text'] ?? '');
        update_post_meta($id, 'desc_bodovani',$desc['bodovani']['text'] ?? '');
        // pozice ořezu (pro budoucí využití)
        update_post_meta($id, 'img_x', $g['imgX'] ?? '');
        update_post_meta($id, 'img_y', $g['imgY'] ?? '');
        // obálku stahuj u nové hry, nebo u existující jen když ještě žádnou nemá
        if ($do_covers && !empty($g['image']) && !has_post_thumbnail($id)) {
            if (hd_import_cover($g['image'], $id, $g['name'] ?? '')) $cov_ok++;
            else { $cov_fail++; $fails[] = $g['name'] ?? $src; }
        }
        $map_game[$src] = $id;
    }

    // 3) PARTIE
    foreach ((array)($data['plays'] ?? []) as $p) {
        $src = $p['id'] ?? '';
        $exist = hd_find_by_src('partie', $src);
        if ($exist && !$overwrite) { $skip++; continue; }
        $gid = $map_game[$p['gameId'] ?? ''] ?? 0;
        $gname = $gid ? get_the_title($gid) : 'Partie';
        $title = trim($gname . ' ' . ($p['date'] ?? ''));
        if ($exist) { $id = $exist; wp_update_post(['ID'=>$id, 'post_title'=>$title]); $upd++; }
        else {
            $id = wp_insert_post(['post_type'=>'partie','post_status'=>'publish','post_title'=>$title]);
            if (is_wp_error($id)) continue;
            $n_p++;
        }
        update_post_meta($id, 'hd_src_id', $src);
        update_post_meta($id, 'game', $gid);
        update_post_meta($id, 'play_date', $p['date'] ?? '');
        update_post_meta($id, 'note', $p['note'] ?? '');
        $players = array_values(array_filter(array_map(function($x) use ($map_player){ return $map_player[$x] ?? 0; }, (array)($p['playerIds'] ?? []))));
        $winners = array_values(array_filter(array_map(function($x) use ($map_player){ return $map_player[$x] ?? 0; }, (array)($p['winnerIds'] ?? []))));
        update_post_meta($id, 'players', $players);
        update_post_meta($id, 'winners', $winners);
    }

    echo '<div class="notice notice-success"><p><strong>Hotovo!</strong> Nově vytvořeno: ' .
        $n_g . ' her, ' . $n_pl . ' hráčů, ' . $n_p . ' partií.' .
        ($upd ? ' Aktualizováno existujících: ' . $upd . '.' : '') .
        ($skip ? ' Přeskočeno (už existovalo): ' . $skip . '.' : '') .
        ($do_covers ? ' Obálky: ' . $cov_ok . ' nově staženo, ' . $cov_fail . ' se nepodařilo.' : '') .
        '</p>';
    if ($fails) echo '<p>Bez obálky (můžeš doplnit ručně): ' . esc_html(implode(', ', array_slice($fails, 0, 60))) . '</p>';
    echo '<p><a class="button button-primary" href="' . esc_url(home_url('/')) . '">Otevřít Hernu →</a></p></div>';
}
