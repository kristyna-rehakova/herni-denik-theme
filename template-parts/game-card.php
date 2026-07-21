<?php
/**
 * Karta hry pro mřížku Herny. Očekává globální $post = hra.
 */
if (!defined('ABSPATH')) exit;
$id = get_the_ID();
$pl = hd_players_label($id);
$tl = hd_time_label($id);
$d  = hd_diff($id);
$pub = hd_meta($id, 'publisher');
$year = hd_meta($id, 'year');
$checked = hd_meta($id, 'desc_checked') === '1';
$plays = hd_play_count($id);

$t_for_filter = hd_meta($id, 'time_max') ?: hd_meta($id, 'time_min');
?>
<div class="game-card"
     data-name="<?php echo esc_attr(mb_strtolower(get_the_title())); ?>"
     data-pmin="<?php echo esc_attr(hd_meta($id, 'players_min')); ?>"
     data-pmax="<?php echo esc_attr(hd_meta($id, 'players_max')); ?>"
     data-time="<?php echo esc_attr($t_for_filter); ?>"
     data-diff="<?php echo esc_attr($d ? $d['n'] : ''); ?>"
     data-weight="<?php echo esc_attr(hd_meta($id, 'weight')); ?>"
     data-pub="<?php echo esc_attr($pub); ?>"
     data-plays="<?php echo (int) $plays; ?>">
  <a class="card-link" href="<?php the_permalink(); ?>">
    <div class="thumb">
      <?php if (has_post_thumbnail()) the_post_thumbnail('medium'); else echo '🎲'; ?>
    </div>
    <div class="body">
      <h3><?php the_title(); ?><?php if ($checked) echo ' <span class="chk" title="Popis zkontrolován">✅</span>'; ?></h3>
      <div class="meta">
        <?php if ($pl) echo '<span class="pill plpill">👥 ' . esc_html($pl) . '</span>'; ?>
        <?php if ($tl) echo '<span class="pill plpill">⏱ ' . esc_html($tl) . '</span>'; ?>
        <?php if ($d) echo '<span class="pill diff-' . $d['n'] . '">🧠 ' . esc_html($d['label']) . '</span>'; ?>
        <?php if ($pub) echo '<span class="pill">' . esc_html($pub) . '</span>'; ?>
        <?php if ($year) echo '<span class="pill">' . esc_html($year) . '</span>'; ?>
      </div>
      <div class="playcount"><?php echo $plays ? '🎲 Odehráno ' . (int)$plays . '×' : 'Zatím nehráno'; ?></div>
    </div>
  </a>
  <?php if (is_user_logged_in()): ?>
    <div class="card-actions">
      <button type="button" class="btn small js-open-play" data-game="<?php echo $id; ?>" title="Zapsat partii">🎲 Zapsat partii</button>
      <?php if (current_user_can('edit_post', $id)): ?>
        <a class="btn small ghost" href="<?php echo esc_url(get_edit_post_link($id)); ?>" title="Upravit info">✏️</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
