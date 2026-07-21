<?php
/**
 * Meta boxy pro editaci v adminu (Fáze 1 – správa probíhá v wp-adminu).
 */
if (!defined('ABSPATH')) exit;

/* ---------- pomocné pro vykreslení polí ---------- */
function hd_field_text($id, $key, $label, $type = 'text') {
    printf('<p><label><strong>%s</strong><br><input type="%s" name="%s" value="%s" style="width:100%%"></label></p>',
        esc_html($label), esc_attr($type), esc_attr($key), esc_attr(hd_meta($id, $key)));
}
function hd_field_textarea($id, $key, $label, $rows = 4) {
    printf('<p><label><strong>%s</strong><br><textarea name="%s" rows="%d" style="width:100%%">%s</textarea></label></p>',
        esc_html($label), esc_attr($key), $rows, esc_textarea(hd_meta($id, $key)));
}
function hd_field_select($id, $key, $label, $options) {
    $cur = hd_meta($id, $key);
    echo '<p><label><strong>' . esc_html($label) . '</strong><br><select name="' . esc_attr($key) . '" style="width:100%">';
    foreach ($options as $val => $lbl) {
        printf('<option value="%s"%s>%s</option>', esc_attr($val), selected($cur, $val, false), esc_html($lbl));
    }
    echo '</select></label></p>';
}
function hd_field_multiselect($id, $key, $label, $options) {
    $cur = (array) hd_meta($id, $key, []);
    echo '<p><label><strong>' . esc_html($label) . '</strong> <span style="color:#888">(Ctrl/⌘ = víc)</span><br><select name="' . esc_attr($key) . '[]" multiple size="6" style="width:100%">';
    foreach ($options as $val => $lbl) {
        printf('<option value="%s"%s>%s</option>', esc_attr($val), in_array((string)$val, array_map('strval', $cur), true) ? ' selected' : '', esc_html($lbl));
    }
    echo '</select></label></p>';
}

/* seznam hráčů a her jako volby */
function hd_hrac_options() {
    $out = [];
    foreach (get_posts(['post_type' => 'hrac', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']) as $p) {
        $out[$p->ID] = hd_player_name($p->ID);
    }
    return $out;
}
function hd_hra_options() {
    $out = ['' => '— vyber hru —'];
    foreach (get_posts(['post_type' => 'hra', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']) as $p) {
        $out[$p->ID] = $p->post_title;
    }
    return $out;
}

/* ======================= HRA ======================= */
function hd_add_meta_boxes() {
    add_meta_box('hd_hra', 'Údaje o hře', 'hd_box_hra', 'hra', 'normal', 'high');
    add_meta_box('hd_hra_desc', 'Popis hry (pravidla)', 'hd_box_hra_desc', 'hra', 'normal', 'default');
    add_meta_box('hd_partie', 'Záznam partie', 'hd_box_partie', 'partie', 'normal', 'high');
    add_meta_box('hd_hrac', 'Profil hráče', 'hd_box_hrac', 'hrac', 'normal', 'high');
}
add_action('add_meta_boxes', 'hd_add_meta_boxes');

function hd_box_hra($post) {
    wp_nonce_field('hd_save', 'hd_nonce');
    echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">';
    hd_field_text($post->ID, 'players_min', 'Min. hráčů', 'number');
    hd_field_text($post->ID, 'players_max', 'Max. hráčů', 'number');
    hd_field_text($post->ID, 'time_min', 'Délka od (min)', 'number');
    hd_field_text($post->ID, 'time_max', 'Délka do (min)', 'number');
    hd_field_select($post->ID, 'difficulty', 'Obtížnost', ['' => '— nezadáno —', 'lehka' => 'lehká', 'stredni' => 'střední', 'tezka' => 'těžká']);
    hd_field_text($post->ID, 'year', 'Rok vydání', 'number');
    hd_field_text($post->ID, 'publisher', 'Vydavatel');
    hd_field_text($post->ID, 'youtube', 'YouTube odkaz (video)');
    hd_field_text($post->ID, 'bgg_url', 'Odkaz na Zatrolené');
    hd_field_text($post->ID, 'pub_url', 'Odkaz na web vydavatele');
    echo '</div>';
    hd_field_textarea($post->ID, 'notes', 'Poznámka', 2);
    echo '<p><label><input type="checkbox" name="desc_checked" value="1"' . checked(hd_meta($post->ID, 'desc_checked'), '1', false) . '> ✅ Popis zkontrolován</label></p>';
    echo '<p class="description">Obálka hry = „Náhledový obrázek" (vpravo).</p>';
}
function hd_box_hra_desc($post) {
    hd_field_textarea($post->ID, 'desc_priprava', 'Příprava', 4);
    hd_field_textarea($post->ID, 'desc_prubeh', 'Průběh hry', 4);
    hd_field_textarea($post->ID, 'desc_konec', 'Konec hry', 3);
    hd_field_textarea($post->ID, 'desc_bodovani', 'Bodování', 3);
}

/* ======================= PARTIE ======================= */
function hd_box_partie($post) {
    wp_nonce_field('hd_save', 'hd_nonce');
    hd_field_select($post->ID, 'game', 'Hra', hd_hra_options());
    hd_field_text($post->ID, 'play_date', 'Datum', 'date');
    hd_field_multiselect($post->ID, 'players', 'Kdo hrál', hd_hrac_options());
    hd_field_multiselect($post->ID, 'winners', 'Kdo vyhrál 🏆', hd_hrac_options());
    hd_field_textarea($post->ID, 'note', 'Poznámky ke hře', 3);
}

/* ======================= HRÁČ ======================= */
function hd_box_hrac($post) {
    wp_nonce_field('hd_save', 'hd_nonce');
    hd_field_text($post->ID, 'nick', 'Přezdívka');
    echo '<p><label><strong>Barva ikonky</strong><br><input type="color" name="color" value="' . esc_attr(hd_meta($post->ID, 'color', '#eeb088')) . '"></label></p>';
    hd_field_text($post->ID, 'emoji', 'Emoji (nepovinné)');
    // propojení na WP účet
    $users = ['' => '— host bez účtu —'];
    foreach (get_users(['fields' => ['ID', 'display_name']]) as $u) $users[$u->ID] = $u->display_name;
    hd_field_select($post->ID, 'wp_user', 'Propojit s účtem', $users);
}

/* ======================= ULOŽENÍ ======================= */
function hd_save_meta($post_id) {
    if (!isset($_POST['hd_nonce']) || !wp_verify_nonce($_POST['hd_nonce'], 'hd_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $text_keys = ['players_min','players_max','time_min','time_max','difficulty','year','publisher',
        'youtube','bgg_url','pub_url','notes','game','play_date','nick','color','emoji','wp_user',
        'desc_priprava','desc_prubeh','desc_konec','desc_bodovani','note'];
    foreach ($text_keys as $k) {
        if (isset($_POST[$k])) {
            $val = is_string($_POST[$k]) ? wp_kses_post(wp_unslash($_POST[$k])) : $_POST[$k];
            update_post_meta($post_id, $k, $val);
        }
    }
    // checkbox
    update_post_meta($post_id, 'desc_checked', isset($_POST['desc_checked']) ? '1' : '');
    // multiselecty
    foreach (['players','winners'] as $k) {
        $arr = isset($_POST[$k]) ? array_map('intval', (array) $_POST[$k]) : [];
        update_post_meta($post_id, $k, $arr);
    }

    // Partie: pokud nemá název, poskládej ho z hry + data (jen interní štítek).
    if (get_post_type($post_id) === 'partie') {
        $post = get_post($post_id);
        if ($post && trim($post->post_title) === '') {
            $gid = (int) hd_meta($post_id, 'game');
            $gname = $gid ? get_the_title($gid) : 'Partie';
            $date = hd_format_date(hd_meta($post_id, 'play_date'));
            remove_action('save_post', 'hd_save_meta');
            wp_update_post(['ID' => $post_id, 'post_title' => trim($gname . ' ' . $date)]);
            add_action('save_post', 'hd_save_meta');
        }
    }
}
add_action('save_post', 'hd_save_meta');
