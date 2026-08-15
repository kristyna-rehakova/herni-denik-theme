<?php
/**
 * Deník = odehrané hry. Přepínání seskupení: po dnech / podle her. Filtry + „Moje".
 */
if (!defined('ABSPATH')) exit;
get_header();

$radit = (isset($_GET['radit']) && $_GET['radit'] === 'hry') ? 'hry' : 'dny';

$q = new WP_Query([
    'post_type'      => 'partie',
    'posts_per_page' => -1,
    'orderby'        => 'date',   // ber všechny partie (i bez meta play_date); uvnitř dne se řadí zvlášť
    'order'          => 'DESC',
    'no_found_rows'  => true,
]);
$all_pids = [];
if ($q->have_posts()) { while ($q->have_posts()) { $q->the_post(); $all_pids[] = get_the_ID(); } wp_reset_postdata(); }

$all_players = hd_all_players();
$all_games   = hd_all_games();

// řádek partie (sdílené pro obě seskupení)
if (!function_exists('hd_render_play_row')) {
    function hd_render_play_row($pid, $with_arrows, $show_game = true, $extra_class = '') {
        $gid = (int) hd_meta($pid, 'game');
        $players = array_map('intval', (array) hd_meta($pid, 'players', []));
        $winners = array_map('intval', (array) hd_meta($pid, 'winners', []));
        $ext_players = (array) hd_meta($pid, 'ext_players', []);
        $ext_winners = (array) hd_meta($pid, 'ext_winners', []);
        $note = hd_meta($pid, 'note');
        // seznam hráčů: vítězové první, pak abecedně
        $list = [];
        foreach ($players as $hp) { $list[] = ['name' => hd_player_name($hp), 'av' => hd_player_avatar($hp, 26), 'win' => in_array($hp, $winners, true)]; }
        foreach ($ext_players as $en) { $list[] = ['name' => $en, 'av' => hd_ext_avatar($en, 26), 'win' => in_array($en, $ext_winners, true)]; }
        usort($list, function ($a, $b) {
            if ($a['win'] != $b['win']) return $a['win'] ? -1 : 1;
            return strcmp(remove_accents(mb_strtolower($a['name'])), remove_accents(mb_strtolower($b['name'])));
        });
        ?>
        <div class="play-row2 <?php echo esc_attr($extra_class); ?>" id="p<?php echo $pid; ?>"
             data-players="<?php echo esc_attr(implode(',', $players)); ?>"
             data-winners="<?php echo esc_attr(implode(',', $winners)); ?>"
             data-game="<?php echo $gid; ?>"
             data-date="<?php echo esc_attr(hd_meta($pid, 'play_date')); ?>">
          <?php if ($show_game && $gid): ?>
            <a class="play-thumb" href="<?php echo esc_url(get_permalink($gid)); ?>"><?php echo hd_cover_inner($gid); ?></a>
          <?php elseif ($show_game): ?>
            <div class="play-thumb">🎲</div>
          <?php endif; ?>
          <div class="info">
            <?php if ($show_game): ?>
              <?php if ($gid): ?>
                <a class="play-name" href="<?php echo esc_url(get_permalink($gid)); ?>"><?php echo esc_html(get_the_title($gid)); ?></a>
              <?php else: ?>
                <span class="play-name">(smazaná hra)</span>
              <?php endif; ?>
            <?php else: ?>
              <span class="play-name"><?php echo esc_html(hd_format_day(hd_meta($pid, 'play_date'))); ?></span>
            <?php endif; ?>
            <?php if ($list): ?>
              <div class="pl-line2">
                <?php foreach ($list as $p): ?>
                  <span class="pl-chip <?php echo $p['win'] ? 'win' : ''; ?>">
                    <span class="pl-av-wrap"><?php echo $p['av']; ?><?php if ($p['win']) echo '<span class="win-mini">🏆</span>'; ?></span>
                    <span class="pl-nm"><?php echo esc_html($p['name']); ?></span>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <?php $pexps = (array) hd_meta($pid, 'play_expansions', []); if ($pexps) echo '<div class="pexp-line">🧩 ' . esc_html(implode(', ', $pexps)) . '</div>'; ?>
          </div>
          <?php if ($note): ?><div class="pnote-box">📝 <?php echo nl2br(esc_html($note)); ?></div><?php endif; ?>
          <?php if (is_user_logged_in()): ?>
            <div class="play-actions">
              <button type="button" class="icon-btn ic-edit js-edit-play" data-hd="<?php echo hd_play_edit_json($pid); ?>" title="Upravit">✏️</button>
              <a class="icon-btn ic-del js-del-play" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_delete_play&id=' . $pid), 'hd_delplay_' . $pid)); ?>" data-name="<?php echo esc_attr($gid ? get_the_title($gid) : 'partii'); ?>" title="Smazat">🗑️</a>
            </div>
            <?php if ($with_arrows): ?>
              <div class="play-arrows">
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_move_play&dir=up&id=' . $pid), 'hd_moveplay_' . $pid)); ?>" title="Nahoru">↑</a>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_move_play&dir=down&id=' . $pid), 'hd_moveplay_' . $pid)); ?>" title="Dolů">↓</a>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <?php
    }
}
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
  <div class="denik-topbtns">
    <button type="button" class="btn big js-open-play">🎲 Zapsat do Deníku</button>
    <?php if ($all_pids): ?>
      <button type="button" id="denikMojeToggle" class="btn big hd-fav-toggle">♥ Moje</button>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($all_pids): ?>
  <div class="denik-top">
    <div class="grp-switch">
      <a class="grp-btn <?php echo $radit === 'dny' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('radit', 'dny')); ?>">📅 Po dnech</a>
      <a class="grp-btn <?php echo $radit === 'hry' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('radit', 'hry')); ?>">🎲 Podle her</a>
    </div>
  </div>

  <?php if ($radit === 'dny'): ?>
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
  <?php endif; ?>

  <div id="denikList">
  <?php if ($radit === 'dny'):
    // seskupení po dnech
    $by_day = [];
    foreach ($all_pids as $pid) { $day = hd_meta($pid, 'play_date') ?: get_the_date('Y-m-d', $pid); $by_day[$day][] = $pid; }
    krsort($by_day);
    // uvnitř dne: ručně přeřazené (ord) mají přednost, jinak v pořadí zápisu (ID vzestupně)
    foreach ($by_day as $day => &$pids_ref) {
        usort($pids_ref, function ($a, $b) {
            $oa = (int) get_post_meta($a, 'ord', true); $ob = (int) get_post_meta($b, 'ord', true);
            return $oa <=> $ob ?: $a <=> $b;
        });
    }
    unset($pids_ref);
    foreach ($by_day as $day => $pids): ?>
      <section class="day day-card">
        <h2 class="day-head"><?php echo esc_html(hd_format_day($day)); ?> <span class="day-count"><?php echo count($pids); ?>×</span></h2>
        <?php foreach ($pids as $pid) hd_render_play_row($pid, true, true); ?>
      </section>
    <?php endforeach;
  else:
    // seskupení podle her (abecedně)
    $by_game = [];
    foreach ($all_pids as $pid) { $gid = (int) hd_meta($pid, 'game'); $by_game[$gid][] = $pid; }
    $order = [];
    foreach ($by_game as $gid => $pids) { $order[$gid] = $gid ? remove_accents(mb_strtolower(get_the_title($gid))) : 'zzzz'; }
    asort($order, SORT_STRING);
    foreach (array_keys($order) as $gid):
      $pids = $by_game[$gid];
      $gname = $gid ? get_the_title($gid) : '(smazaná hra)';
      $cnt = count($pids); ?>
      <section class="day game-group" data-game="<?php echo (int)$gid; ?>">
        <div class="gg-head">
          <?php if ($gid): ?>
            <a class="gg-thumb" href="<?php echo esc_url(get_permalink($gid)); ?>"><?php echo hd_cover_inner($gid); ?></a>
            <a class="gg-name" href="<?php echo esc_url(get_permalink($gid)); ?>"><?php echo esc_html($gname); ?></a>
          <?php else: ?>
            <span class="gg-thumb">🎲</span>
            <span class="gg-name"><?php echo esc_html($gname); ?></span>
          <?php endif; ?>
          <span class="day-count"><?php echo $cnt; ?>×</span>
        </div>
        <div class="game-plays-list">
          <?php $i = 0; foreach ($pids as $pid) { hd_render_play_row($pid, false, false, $i >= 3 ? 'pl-extra' : ''); $i++; } ?>
        </div>
        <?php if ($cnt > 3): ?>
          <div class="more-plays"><button type="button" class="btn small ghost js-more-plays" data-game="<?php echo (int)$gid; ?>" data-name="<?php echo esc_attr($gname); ?>">Zobrazit všech <?php echo $cnt; ?> →</button></div>
        <?php endif; ?>
      </section>
    <?php endforeach;
  endif; ?>
  </div>
  <p class="hd-noresult" id="denikEmpty" hidden>Žádná partie neodpovídá filtru.</p>

  <div class="hd-modal" id="hdPlaysModal" hidden>
    <div class="hd-modal-bg js-close-plays"></div>
    <div class="hd-modal-card hd-modal-wide" role="dialog" aria-modal="true">
      <button type="button" class="hd-modal-x js-close-plays">×</button>
      <h2 id="playsTitle">Partie</h2>
      <div class="hd-plays-body" id="playsBody"></div>
    </div>
  </div>
<?php else: ?>
  <div class="empty card" style="padding:50px 20px">📖 Deník je zatím prázdný. Zapiš první partii!</div>
<?php endif; ?>

<?php get_footer(); ?>
