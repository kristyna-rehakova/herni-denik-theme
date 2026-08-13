<?php if (!defined('ABSPATH')) exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
  <div class="wrap">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">🎲 <?php bloginfo('name'); ?></a>
    <div class="spacer"></div>
    <span class="userbox">
      <?php if (is_user_logged_in()): $u = wp_get_current_user(); ?>
        <?php echo esc_html($u->display_name); ?> · <a href="<?php echo esc_url(function_exists('hd_ucet_url') ? hd_ucet_url() : home_url('/')); ?>">⚙️ Můj účet</a> · <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Odhlásit</a>
      <?php else: ?>
        <a href="<?php echo esc_url(wp_login_url()); ?>">Přihlásit</a>
      <?php endif; ?>
    </span>
  </div>
  <nav class="nav-wrap">
    <?php
    if (has_nav_menu('primary')) {
        wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'menu_class' => 'nav', 'fallback_cb' => false]);
    } else { ?>
      <ul class="nav">
        <li class="<?php echo is_front_page() ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(home_url('/')); ?>">🏠 Herna</a></li>
        <li class="<?php echo is_post_type_archive('partie') ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(get_post_type_archive_link('partie')); ?>">📖 Deník</a></li>
        <li class="<?php echo (function_exists('hd_stats_url') && is_page(get_option('hd_stats_page_id'))) ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(function_exists('hd_stats_url') ? hd_stats_url() : home_url('/')); ?>">📊 Statistiky</a></li>
        <li class="<?php echo (function_exists('hd_hraci_url') && is_page(get_option('hd_hraci_page_id'))) ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(function_exists('hd_hraci_url') ? hd_hraci_url() : admin_url('edit.php?post_type=hrac')); ?>">👥 Hráči</a></li>
      </ul>
    <?php } ?>
  </nav>
</header>
<main>
