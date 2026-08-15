<?php
/**
 * To Do / hlášení chyb. Kdokoli přihlášený může přidat úkol (popis + fotka),
 * admin je odškrtává a maže. V hlavičce se ukazuje ✔️ (vše hotovo) / ❗ (něco čeká).
 */
if (!defined('ABSPATH')) exit;

/** Vlastní typ obsahu pro úkoly (neveřejný, spravuje se z front-endu). */
add_action('init', function () {
    register_post_type('hd_todo', [
        'label'        => 'To Do',
        'public'       => false,
        'show_ui'      => false,
        'show_in_rest' => false,
        'supports'     => ['editor', 'thumbnail', 'author', 'title'],
    ]);
});

/** Jednou vytvoř stránku „To Do". */
add_action('init', function () {
    $existing = (int) get_option('hd_todo_page_id');
    if ($existing && get_post_status($existing) === 'publish') return;
    $id = wp_insert_post([
        'post_type'   => 'page',
        'post_title'  => 'To Do',
        'post_name'   => 'todo',
        'post_status' => 'publish',
        'post_content'=> '',
    ]);
    if ($id && !is_wp_error($id)) {
        update_post_meta($id, '_wp_page_template', 'page-todo.php');
        update_option('hd_todo_page_id', $id);
    }
});

function hd_todo_url() {
    $id = (int) get_option('hd_todo_page_id');
    return $id ? get_permalink($id) : home_url('/');
}

/** Počet nevyřešených úkolů (pro indikátor v hlavičce). */
function hd_todo_open_count() {
    $q = new WP_Query([
        'post_type'      => 'hd_todo',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [
            'relation' => 'OR',
            ['key' => 'hd_done', 'compare' => 'NOT EXISTS'],
            ['key' => 'hd_done', 'value' => '1', 'compare' => '!='],
        ],
    ]);
    return (int) $q->post_count;
}

/** Přidání úkolu (kdokoli přihlášený) – popis + volitelná fotka. */
function hd_handle_add_todo() {
    if (!is_user_logged_in()) wp_die('Musíš být přihlášen.');
    if (empty($_POST['hd_todo_nonce']) || !wp_verify_nonce($_POST['hd_todo_nonce'], 'hd_add_todo')) wp_die('Neplatný požadavek.');
    $back = hd_todo_url();

    $desc = trim(wp_kses_post(wp_unslash($_POST['desc'] ?? '')));
    if ($desc === '') { wp_safe_redirect(add_query_arg('hd_todo', 'empty', $back)); exit; }

    $title = mb_substr(trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($desc))), 0, 70);
    $tid = wp_insert_post([
        'post_type'    => 'hd_todo',
        'post_status'  => 'publish',
        'post_title'   => $title ?: 'Úkol',
        'post_content' => $desc,
        'post_author'  => get_current_user_id(),
    ]);
    if (is_wp_error($tid) || !$tid) { wp_safe_redirect(add_query_arg('hd_todo', 'err', $back)); exit; }

    // volitelná fotka – nahráváme ručně (funguje i pro členy bez práva upload_files)
    if (!empty($_FILES['photo']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $overrides = ['test_form' => false, 'mimes' => [
            'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
            'webp' => 'image/webp', 'heic' => 'image/heic',
        ]];
        $moved = wp_handle_upload($_FILES['photo'], $overrides);
        if (!empty($moved['file']) && empty($moved['error'])) {
            $att = wp_insert_attachment([
                'post_mime_type' => $moved['type'],
                'post_title'     => 'To Do foto',
                'post_status'    => 'inherit',
            ], $moved['file'], $tid);
            if (!is_wp_error($att) && $att) {
                wp_update_attachment_metadata($att, wp_generate_attachment_metadata($att, $moved['file']));
                set_post_thumbnail($tid, $att);
            }
        }
    }

    wp_safe_redirect(add_query_arg('hd_todo', 'ok', $back)); exit;
}
add_action('admin_post_hd_add_todo', 'hd_handle_add_todo');

/** Odškrtnutí / vrácení úkolu (jen admin). */
function hd_handle_toggle_todo() {
    $tid = intval($_GET['id'] ?? 0);
    if (!$tid || !hd_can_manage()) wp_die('Nemáš oprávnění.');
    check_admin_referer('hd_toggle_todo_' . $tid);
    if (get_post_meta($tid, 'hd_done', true) === '1') delete_post_meta($tid, 'hd_done');
    else update_post_meta($tid, 'hd_done', '1');
    wp_safe_redirect(hd_todo_url()); exit;
}
add_action('admin_post_hd_toggle_todo', 'hd_handle_toggle_todo');

/** Smazání úkolu (jen admin) – i s fotkou. */
function hd_handle_delete_todo() {
    $tid = intval($_GET['id'] ?? 0);
    if (!$tid || !hd_can_manage()) wp_die('Nemáš oprávnění.');
    check_admin_referer('hd_del_todo_' . $tid);
    $att = get_post_thumbnail_id($tid);
    if ($att) wp_delete_attachment((int) $att, true);
    wp_delete_post($tid, true);
    wp_safe_redirect(add_query_arg('hd_todo', 'del', hd_todo_url())); exit;
}
add_action('admin_post_hd_delete_todo', 'hd_handle_delete_todo');
