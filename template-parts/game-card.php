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
<div class="game-card" id="g<?php echo $id; ?>"
     data-fav="<?php echo (is_user_logged_in() && hd_is_fav($id)) ? '1' : '0'; ?>"
     data-name="<?php echo esc_attr(mb_strtolower(get_the_title())); ?>"
     data-pmin="<?php echo esc_attr(hd_meta($id, 'players_min')); ?>"
     data-pmax="<?php echo esc_attr(hd_meta($id, 'players_max')); ?>"
     data-time="<?php echo esc_attr($t_for_filter); ?>"
     data-diff="<?php echo esc_attr($d ? $d['n'] : ''); ?>"
     data-weight="<?php echo esc_attr(hd_meta($id, 'weight')); ?>"
     data-pub="<?php echo esc_attr($pub); ?>"
     data-plays="<?php echo (int) $plays; ?>">
  <div class="thumb">
    <a class="thumb-link" href="<?php the_permalink(); ?>"><?php echo hd_cover_inner($id); ?></a>
    <?php if (current_user_can('edit_post', $id)): ?>
      <button type="button" class="thumb-edit js-edit-cover" <?php echo hd_cover_data($id); ?> title="Změnit obrázek">✏️</button>
    <?php endif; ?>
  </div>
  <div class="body">
    <div class="title-row">
      <a class="title-link" href="<?php the_permalink(); ?>"><h3><?php the_title(); ?><?php if ($checked) echo ' <span class="chk" title="Popis zkontrolován">✅</span>'; ?></h3></a>
      <?php if (is_user_logged_in()): ?>
        <button type="button" class="fav-btn js-fav <?php echo hd_is_fav($id) ? 'on' : ''; ?>" data-game="<?php echo $id; ?>" title="Moje hry"><?php echo hd_is_fav($id) ? '♥' : '♡'; ?></button>
      <?php endif; ?>
    </div>
    <div class="meta">
      <?php if ($pl) echo '<span class="pill plpill">👥 ' . esc_html($pl) . '</span>'; ?>
      <?php if ($tl) echo '<span class="pill plpill">⏱ ' . esc_html($tl) . '</span>'; ?>
      <?php if ($d) echo '<span class="pill diff-' . $d['n'] . '">🧠 ' . esc_html($d['label']) . '</span>'; ?>
      <?php if ($pub) echo '<span class="pill">' . esc_html($pub) . '</span>'; ?>
      <?php if ($year) echo '<span class="pill">' . esc_html($year) . '</span>'; ?>
    </div>
    <div class="playcount"><?php echo $plays ? 'Odehráno ' . (int)$plays . '×' : 'Zatím nehráno'; ?><?php if (hd_can_manage() && hd_has_pending($id)) echo ' <span class="pending-badge">✎ návrh</span>'; ?></div>
    <?php if (is_user_logged_in()): ?>
      <div class="card-actions">
        <button type="button" class="icon-btn ic-play js-open-play" data-game="<?php echo $id; ?>" title="Zapsat do Deníku">🎲</button>
        <?php if (current_user_can('edit_post', $id)): ?>
          <button type="button" class="icon-btn ic-edit js-edit-game" data-hd="<?php echo hd_game_edit_json($id); ?>" title="Editovat info o hře">✏️</button>
        <?php endif; ?>
        <?php if (current_user_can('delete_post', $id)): ?>
          <a class="icon-btn ic-del js-del-game" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_delete_game&id=' . $id), 'hd_delete_' . $id)); ?>" data-name="<?php echo esc_attr(get_the_title()); ?>" title="Smazat hru ze seznamu">🗑️</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
