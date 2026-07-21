<?php
/**
 * Vlastní typy obsahu: Hra, Partie, Hráč
 */
if (!defined('ABSPATH')) exit;

function hd_register_cpts() {

    register_post_type('hra', [
        'labels' => [
            'name' => 'Hry', 'singular_name' => 'Hra', 'add_new' => 'Přidat hru',
            'add_new_item' => 'Přidat hru', 'edit_item' => 'Upravit hru',
            'menu_name' => '🎲 Hry (Herna)',
        ],
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-games',
        'supports' => ['title', 'thumbnail', 'author'],
        'rewrite' => ['slug' => 'hra'],
        'show_in_rest' => true,
    ]);

    register_post_type('partie', [
        'labels' => [
            'name' => 'Partie', 'singular_name' => 'Partie', 'add_new' => 'Zapsat do Deníku',
            'add_new_item' => 'Zapsat do Deníku', 'edit_item' => 'Upravit záznam',
            'menu_name' => '📖 Deník',
        ],
        'public' => true,
        'has_archive' => true, // /partie/ = Deník
        'menu_icon' => 'dashicons-book',
        'supports' => ['title', 'author'],
        'rewrite' => ['slug' => 'partie'],
        'show_in_rest' => true,
    ]);

    register_post_type('hrac', [
        'labels' => [
            'name' => 'Hráči', 'singular_name' => 'Hráč', 'add_new' => 'Přidat hráče',
            'add_new_item' => 'Přidat hráče', 'edit_item' => 'Upravit hráče',
            'menu_name' => '👥 Hráči',
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-groups',
        'supports' => ['title'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'hd_register_cpts');

/**
 * Po aktivaci šablony jednou přepiš přepisovací pravidla (hezké URL).
 */
function hd_flush_rewrite() {
    hd_register_cpts();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'hd_flush_rewrite');
