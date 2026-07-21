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
    <?php
    if (has_nav_menu('primary')) {
        wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'menu_class' => 'nav', 'fallback_cb' => false]);
    } else { ?>
      <ul class="nav">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">🏠 Herna</a></li>
        <li><a href="<?php echo esc_url(get_post_type_archive_link('partie')); ?>">📖 Deník</a></li>
        <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=hrac')); ?>">👥 Hráči</a></li>
      </ul>
    <?php } ?>
    <span class="userbox">
      <?php if (is_user_logged_in()): $u = wp_get_current_user(); ?>
        <?php echo esc_html($u->display_name); ?> · <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Odhlásit</a>
      <?php else: ?>
        <a href="<?php echo esc_url(wp_login_url()); ?>">Přihlásit</a>
      <?php endif; ?>
    </span>
  </div>
</header>
<main>
