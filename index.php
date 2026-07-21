<?php
/**
 * Obecný fallback (WordPress jej použije, když není specifičtější šablona).
 */
if (!defined('ABSPATH')) exit;
get_header();
?>
<div class="wrap-narrow">
<?php if (have_posts()): while (have_posts()): the_post(); ?>
  <article class="detail">
    <h1><?php the_title(); ?></h1>
    <div class="entry"><?php the_content(); ?></div>
  </article>
<?php endwhile; else: ?>
  <div class="empty card" style="padding:50px 20px">Nic tu není.</div>
<?php endif; ?>
</div>
<?php get_footer(); ?>
