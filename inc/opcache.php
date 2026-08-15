<?php
/**
 * Po každé změně verze šablony vyprázdní PHP OPcache.
 * Řeší problém, kdy server po aktualizaci držel starou podobu souborů
 * (např. počet hráčů se zobrazoval jen jako horní hranice).
 */
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    if (!defined('HD_VERSION')) return;
    if (get_option('hd_opcache_ver') === HD_VERSION) return; // pro tuhle verzi už vyčištěno
    if (function_exists('opcache_reset')) @opcache_reset();
    if (function_exists('wp_cache_flush')) wp_cache_flush();
    update_option('hd_opcache_ver', HD_VERSION);
}, 1);
