<?php
/**
 * Domovská stránka = Herna (mřížka her).
 */
if (!defined('ABSPATH')) exit;
get_header();

$games = new WP_Query([
    'post_type'      => 'hra',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);
?>
<h1 class="page-title">🏠 Herna</h1>

<?php if (current_user_can('edit_posts')): ?>
  <p><a class="btn big" href="<?php echo esc_url(admin_url('post-new.php?post_type=hra')); ?>">♟️ Přidat deskovku</a></p>
<?php endif; ?>

<?php if ($games->have_posts()): ?>
  <div class="grid">
    <?php while ($games->have_posts()): $games->the_post();
        get_template_part('template-parts/game-card');
    endwhile; wp_reset_postdata(); ?>
  </div>
<?php else: ?>
  <div class="empty card" style="padding:50px 20px">📚 Sbírka je zatím prázdná. Přidej první hru!</div>
<?php endif; ?>

<?php get_footer(); ?>
