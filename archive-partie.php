<?php
/**
 * Deník = archiv partií, seskupeno po dnech (nejnovější nahoře).
 */
if (!defined('ABSPATH')) exit;
get_header();

$plays = new WP_Query([
    'post_type'      => 'partie',
    'posts_per_page' => -1,
    'meta_key'       => 'play_date',
    'orderby'        => 'meta_value',
    'order'          => 'DESC',
]);

// seskupení do polí podle data
$by_day = [];
if ($plays->have_posts()) {
    while ($plays->have_posts()) { $plays->the_post();
        $pid = get_the_ID();
        $day = hd_meta($pid, 'play_date') ?: get_the_date('Y-m-d');
        $by_day[$day][] = $pid;
    }
    wp_reset_postdata();
}
krsort($by_day);
?>
<h1 class="page-title">📖 Deník</h1>

<?php if (current_user_can('edit_posts')): ?>
  <p><a class="btn big" href="<?php echo esc_url(admin_url('post-new.php?post_type=partie')); ?>">🎲 Zapsat do Deníku</a></p>
<?php endif; ?>

<?php if ($by_day): ?>
  <?php foreach ($by_day as $day => $pids): ?>
    <section class="day">
      <h2 class="day-head"><?php echo esc_html(hd_format_date($day)); ?></h2>
      <?php foreach ($pids as $pid):
        $gid = (int) hd_meta($pid, 'game');
        $players = (array) hd_meta($pid, 'players', []);
        $winners = (array) hd_meta($pid, 'winners', []);
        $note = hd_meta($pid, 'note');
      ?>
        <div class="play-row">
          <?php if ($gid): ?>
            <a class="play-thumb" href="<?php echo esc_url(get_permalink($gid)); ?>">
              <?php echo has_post_thumbnail($gid) ? get_the_post_thumbnail($gid, 'thumbnail') : '🎲'; ?>
            </a>
            <div class="play-body">
              <a class="play-name" href="<?php echo esc_url(get_permalink($gid)); ?>"><?php echo esc_html(get_the_title($gid)); ?></a>
          <?php else: ?>
            <div class="play-thumb">🎲</div>
            <div class="play-body">
              <span class="play-name">(smazaná hra)</span>
          <?php endif; ?>
              <div class="pplayers">
                <?php foreach ($players as $hp) {
                    $win = in_array((string)$hp, array_map('strval', $winners), true);
                    echo '<span class="pl-wrap' . ($win ? ' win' : '') . '" title="' . esc_attr(hd_player_name($hp)) . ($win ? ' 🏆' : '') . '">' . hd_player_avatar($hp, 28) . ($win ? ' 🏆' : '') . '</span>';
                } ?>
              </div>
              <?php if ($note) echo '<div class="pnote">📝 ' . nl2br(esc_html($note)) . '</div>'; ?>
              <?php if (current_user_can('edit_post', $pid)) echo '<a class="edit-link" href="' . esc_url(get_edit_post_link($pid)) . '">✏️</a>'; ?>
            </div>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
<?php else: ?>
  <div class="empty card" style="padding:50px 20px">📖 Deník je zatím prázdný. Zapiš první partii!</div>
<?php endif; ?>

<?php get_footer(); ?>
