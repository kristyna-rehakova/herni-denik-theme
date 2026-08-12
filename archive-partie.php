<?php
/**
 * Deník = odehrané hry, seskupeno po dnech (nejnovější nahoře) + filtry.
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
// v rámci dne seřaď podle ručního pořadí (meta 'ord'), pak podle ID
foreach ($by_day as $day => &$pids_ref) {
    usort($pids_ref, function ($a, $b) {
        $oa = (int) get_post_meta($a, 'ord', true);
        $ob = (int) get_post_meta($b, 'ord', true);
        return $oa <=> $ob ?: $a <=> $b;
    });
}
unset($pids_ref);

$all_players = hd_all_players();
$all_games   = hd_all_games();
?>
<?php if (isset($_GET['hd_play'])): ?>
  <?php if ($_GET['hd_play'] === 'ok'): ?>
    <div class="hd-flash ok">✅ Partie byla zapsána do Deníku.</div>
  <?php elseif ($_GET['hd_play'] === 'del'): ?>
    <div class="hd-flash ok">🗑️ Partie byla přesunuta do koše.</div>
  <?php else: ?>
    <div class="hd-flash err">⚠️ Partii se nepodařilo uložit (chybí hra?). Zkus to prosím znovu.</div>
  <?php endif; ?>
<?php endif; ?>

<?php if (is_user_logged_in()): ?>
  <p><button type="button" class="btn big js-open-play">🎲 Zapsat do Deníku</button></p>
<?php endif; ?>

<h1 class="page-title">📖 Odehrané hry</h1>

<?php if ($by_day): ?>
  <div class="toolbar card denik-filters">
    <select id="fPlayer" aria-label="Hráč">
      <option value="">Hráč</option>
      <?php foreach ($all_players as $id => $name) echo '<option value="' . (int)$id . '">' . esc_html($name) . '</option>'; ?>
    </select>
    <select id="fWinner" aria-label="Vítěz">
      <option value="">Vítěz 🏆</option>
      <?php foreach ($all_players as $id => $name) echo '<option value="' . (int)$id . '">' . esc_html($name) . '</option>'; ?>
    </select>
    <select id="fGame" aria-label="Hra">
      <option value="">Hra</option>
      <?php foreach ($all_games as $id => $name) echo '<option value="' . (int)$id . '">' . esc_html($name) . '</option>'; ?>
    </select>
    <span class="dater">od <input type="date" id="fFrom"></span>
    <span class="dater">do <input type="date" id="fTo"></span>
  </div>

  <div id="denikList">
  <?php foreach ($by_day as $day => $pids): ?>
    <section class="day" data-day="<?php echo esc_attr($day); ?>">
      <h2 class="day-head"><?php echo esc_html(hd_format_day($day)); ?> <span class="day-count"><?php echo count($pids); ?>×</span></h2>
      <?php foreach ($pids as $pid):
        $gid = (int) hd_meta($pid, 'game');
        $players = array_map('intval', (array) hd_meta($pid, 'players', []));
        $winners = array_map('intval', (array) hd_meta($pid, 'winners', []));
        $ext_players = (array) hd_meta($pid, 'ext_players', []);
        $ext_winners = (array) hd_meta($pid, 'ext_winners', []);
        $note = hd_meta($pid, 'note');
        $pnames = array_merge(array_map('hd_player_name', $players), $ext_players);
        $wnames = array_merge(array_map('hd_player_name', $winners), $ext_winners);
      ?>
        <div class="play-row2" id="p<?php echo $pid; ?>"
             data-players="<?php echo esc_attr(implode(',', $players)); ?>"
             data-winners="<?php echo esc_attr(implode(',', $winners)); ?>"
             data-game="<?php echo (int)$gid; ?>"
             data-date="<?php echo esc_attr(hd_meta($pid, 'play_date')); ?>">
          <?php if ($gid): ?>
            <a class="play-thumb" href="<?php echo esc_url(get_permalink($gid)); ?>"><?php echo hd_cover_inner($gid); ?></a>
          <?php else: ?>
            <div class="play-thumb">🎲</div>
          <?php endif; ?>
          <div class="info">
            <?php if ($gid): ?>
              <a class="play-name" href="<?php echo esc_url(get_permalink($gid)); ?>"><?php echo esc_html(get_the_title($gid)); ?></a>
            <?php else: ?>
              <span class="play-name">(smazaná hra)</span>
            <?php endif; ?>
            <?php if ($pnames) echo '<div class="pl-line">👥 ' . esc_html(implode(', ', $pnames)) . '</div>'; ?>
            <?php if ($wnames) echo '<div class="win-line">🏆 ' . esc_html(implode(', ', $wnames)) . '</div>'; ?>
            <?php if ($note) echo '<div class="pnote">📝 ' . nl2br(esc_html($note)) . '</div>'; ?>
          </div>
          <?php if (current_user_can('edit_post', $pid)): ?>
            <div class="play-actions">
              <button type="button" class="icon-btn ic-edit js-edit-play" data-hd="<?php echo hd_play_edit_json($pid); ?>" title="Upravit">✏️</button>
              <a class="icon-btn ic-del js-del-play" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_delete_play&id=' . $pid), 'hd_delplay_' . $pid)); ?>" data-name="<?php echo esc_attr($gid ? get_the_title($gid) : 'partii'); ?>" title="Smazat">🗑️</a>
            </div>
            <div class="play-arrows">
              <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_move_play&dir=up&id=' . $pid), 'hd_moveplay_' . $pid)); ?>" title="Nahoru">↑</a>
              <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_move_play&dir=down&id=' . $pid), 'hd_moveplay_' . $pid)); ?>" title="Dolů">↓</a>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
  </div>
  <p class="hd-noresult" id="denikEmpty" hidden>Žádná partie neodpovídá filtru.</p>
<?php else: ?>
  <div class="empty card" style="padding:50px 20px">📖 Deník je zatím prázdný. Zapiš první partii!</div>
<?php endif; ?>

<?php get_footer(); ?>
