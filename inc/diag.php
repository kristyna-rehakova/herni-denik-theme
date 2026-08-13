<?php
/**
 * Dočasná diagnostika: zpřístupní syrové hodnoty rozsahu + výsledek hd_players_label()
 * přes REST, ať jde ověřit, proč se v dlaždici ukazuje jen jedno číslo.
 * Až se problém vyřeší, tenhle soubor se odebere.
 */
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_field('hra', 'hd_pl', [
        'get_callback' => function ($obj) {
            $id = (int) $obj['id'];
            return [
                'pmin'  => get_post_meta($id, 'players_min', true),
                'pmax'  => get_post_meta($id, 'players_max', true),
                'tmin'  => get_post_meta($id, 'time_min', true),
                'tmax'  => get_post_meta($id, 'time_max', true),
                'label' => function_exists('hd_players_label') ? hd_players_label($id) : '(fn?)',
            ];
        },
        'schema' => null,
    ]);
});
