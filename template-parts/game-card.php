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
?>
<a class="card game-card" href="<?php the_permalink(); ?>">
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
  </div>
</a>
