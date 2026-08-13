<?php
/**
 * Detail hry.
 */
if (!defined('ABSPATH')) exit;
get_header();

while (have_posts()): the_post();
$id = get_the_ID();
$pl = hd_players_label($id);
$tl = hd_time_label($id);
$d  = hd_diff($id);
$pub = hd_meta($id, 'publisher');
$year = hd_meta($id, 'year');
$checked = hd_meta($id, 'desc_checked') === '1';
$yt = hd_meta($id, 'youtube');
$bgg = hd_meta($id, 'bgg_url');
$purl = hd_meta($id, 'pub_url');
$notes = hd_meta($id, 'notes');

$parts = [
    'priprava' => 'Příprava',
    'prubeh'   => 'Průběh hry',
    'konec'    => 'Konec hry',
    'bodovani' => 'Bodování',
];
$can = hd_can_manage();          // plná editace obsahu = jen admin
$is_member = hd_is_member();      // člen může navrhnout úpravu hry

// partie k této hře
$plays = new WP_Query([
    'post_type'      => 'partie',
    'posts_per_page' => -1,
    'meta_query'     => [
        'game_clause' => ['key' => 'game', 'value' => $id],
        'date_clause' => ['key' => 'play_date', 'compare' => 'EXISTS'],
    ],
    'orderby'        => ['date_clause' => 'DESC'],
]);
?>
<?php if (isset($_GET['hd_cover']) && $_GET['hd_cover'] === 'ok'): ?>
  <div class="hd-flash ok">🖼 Obrázek byl uložen.</div>
<?php endif; ?>
<?php if (isset($_GET['hd_saved']) && $_GET['hd_saved'] === 'ok'): ?>
  <div class="hd-flash ok">✅ Hra byla uložena.</div>
<?php endif; ?>
<?php if (isset($_GET['hd_edit'])): ?>
  <?php if ($_GET['hd_edit'] === 'suggested'): ?>
    <div class="hd-flash ok">✎ Návrh úpravy byl odeslán ke schválení. Díky!</div>
  <?php elseif ($_GET['hd_edit'] === 'approved'): ?>
    <div class="hd-flash ok">✓ Návrh byl schválen a zapsán.</div>
  <?php elseif ($_GET['hd_edit'] === 'rejected'): ?>
    <div class="hd-flash ok">Návrh byl zamítnut.</div>
  <?php endif; ?>
<?php endif; ?>
<p><a class="btn back" href="<?php echo esc_url(home_url('/')); ?>">← Zpět do Herny</a></p>

<?php hd_pending_banner($id); ?>

<article class="detail">
  <div class="detail-head" id="g<?php echo $id; ?>">
    <div class="detail-img">
      <?php echo hd_cover_inner($id, '<span class="ph">🎲</span>'); ?>
      <?php if (current_user_can('edit_post', $id)): ?>
        <button type="button" class="thumb-edit js-edit-cover" <?php echo hd_cover_data($id); ?> title="Změnit obrázek">✏️</button>
      <?php endif; ?>
    </div>
    <div class="detail-info">
      <h1><?php the_title(); ?><?php if ($checked) echo ' <span class="chk" title="Popis zkontrolován">✅</span>'; ?></h1>
      <div class="meta">
        <?php if ($pl) echo '<span class="pill plpill">👥 ' . esc_html($pl) . '</span>'; ?>
        <?php if ($tl) echo '<span class="pill plpill">⏱ ' . esc_html($tl) . '</span>'; ?>
        <?php if ($d) echo '<span class="pill diff-' . $d['n'] . '">🧠 ' . esc_html($d['label']) . '</span>'; ?>
        <?php if ($pub) echo '<span class="pill">' . esc_html($pub) . '</span>'; ?>
        <?php if ($year) echo '<span class="pill">' . esc_html($year) . '</span>'; ?>
      </div>
      <div class="links">
        <?php if ($bgg) echo '<a class="btn small" href="' . esc_url($bgg) . '" target="_blank" rel="noopener">Odkaz na Zatrolené</a>'; ?>
        <?php if ($purl) echo '<a class="btn small" href="' . esc_url($purl) . '" target="_blank" rel="noopener">Odkaz na Mindok</a>'; ?>
      </div>
      <?php if ($notes) echo '<div class="notes">📝 ' . nl2br(esc_html($notes)) . '</div>'; ?>
      <?php if (is_user_logged_in()): ?>
        <p class="detail-actions">
          <button type="button" class="btn big js-open-play" data-game="<?php echo $id; ?>">🎲 Zapsat partii</button>
          <button type="button" class="btn big ghost js-game-stats">📊 Statistiky</button>
          <?php if ($can): ?>
            <button type="button" class="btn big ghost js-edit-game" data-hd="<?php echo hd_game_edit_json($id); ?>">✏️ Upravit info</button>
          <?php elseif ($is_member): ?>
            <button type="button" class="btn big ghost js-edit-game" data-hd="<?php echo hd_game_edit_json($id); ?>">✏️ Navrhnout úpravu</button>
          <?php endif; ?>
        </p>
        <?php if ($is_member && hd_has_pending($id)): ?>
          <div class="notes">✎ Tvůj návrh úpravy čeká na schválení adminem.</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php
    // zobraz sekci Popis, pokud má editor práva (vidí i prázdné) nebo je aspoň jedna vyplněná
    $has_any = false;
    foreach ($parts as $k => $t) { if (hd_meta($id, 'desc_' . $k) || hd_meta($id, 'desc_' . $k . '_note')) { $has_any = true; break; } }
  ?>
  <?php if ($can || $has_any): ?>
    <section class="game-section" id="popis">
      <div class="sec-head">
        <h2>📖 Popis hry</h2>
        <?php if ($can): ?>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="check-form">
            <input type="hidden" name="action" value="hd_toggle_checked">
            <input type="hidden" name="game_id" value="<?php echo $id; ?>">
            <?php wp_nonce_field('hd_toggle_checked', 'hd_check_nonce'); ?>
            <label class="check-toggle"><input type="checkbox" name="desc_checked" class="js-check-toggle" data-game="<?php echo $id; ?>" value="1" <?php checked($checked); ?>> Zkontrolováno</label>
          </form>
        <?php endif; ?>
      </div>
      <?php foreach ($parts as $k => $title):
        $text = hd_meta($id, 'desc_' . $k);
        $note = hd_meta($id, 'desc_' . $k . '_note');
        $imgs = array_filter(array_map('intval', (array) hd_meta($id, 'desc_' . $k . '_images', [])));
        if (!$can && !$text && !$note && !$imgs) continue;
        $imgs_data = [];
        foreach ($imgs as $a) { $u = wp_get_attachment_image_url($a, 'thumbnail'); if ($u) $imgs_data[] = ['id' => $a, 'url' => $u]; }
      ?>
        <div class="desc-part card" id="sekce-<?php echo esc_attr($k); ?>">
          <div class="desc-head">
            <h3><?php echo esc_html($title); ?></h3>
            <?php if ($can): ?>
              <button type="button" class="mini-edit js-edit-desc"
                data-game="<?php echo $id; ?>" data-key="<?php echo esc_attr($k); ?>"
                data-title="<?php echo esc_attr($title); ?>"
                data-text="<?php echo esc_attr($text); ?>" data-note="<?php echo esc_attr($note); ?>"
                data-images="<?php echo esc_attr(wp_json_encode($imgs_data)); ?>"
                title="Upravit sekci">✏️</button>
            <?php endif; ?>
          </div>
          <?php if ($text): ?>
            <div class="desc-text"><?php echo nl2br(esc_html($text)); ?></div>
          <?php elseif ($can): ?>
            <div class="desc-text muted">— zatím prázdné, klikni na ✏️ —</div>
          <?php endif; ?>
          <?php if ($note) echo '<div class="desc-note">💡 ' . nl2br(esc_html($note)) . '</div>'; ?>
          <?php if ($imgs): ?>
            <div class="desc-imgs">
              <?php foreach ($imgs as $a) { $u = wp_get_attachment_image_url($a, 'large'); if ($u) echo '<a href="' . esc_url(wp_get_attachment_url($a)) . '" target="_blank" rel="noopener"><img src="' . esc_url($u) . '" alt="" loading="lazy"></a>'; } ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <?php $yt_id = hd_youtube_id($yt); if ($can || $yt_id): ?>
    <section class="game-section" id="video">
      <div class="sec-head">
        <h2>🎬 Video</h2>
        <?php if ($can): ?>
          <button type="button" class="btn small js-toggle" data-target="videoForm"><?php echo $yt_id ? '✏️ Upravit' : '+ Přidat'; ?></button>
        <?php endif; ?>
      </div>
      <?php if ($can): ?>
        <form id="videoForm" class="sec-body sec-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" hidden>
          <input type="hidden" name="action" value="hd_save_video">
          <?php wp_nonce_field('hd_save_video', 'hd_video_nonce'); ?>
          <input type="hidden" name="game_id" value="<?php echo $id; ?>">
          <label class="hd-fld">Odkaz na YouTube<input type="text" name="youtube" value="<?php echo esc_attr($yt); ?>" placeholder="https://www.youtube.com/watch?v=…"></label>
          <div class="hd-modal-actions"><button type="submit" class="btn">Uložit</button></div>
        </form>
      <?php endif; ?>
      <?php if ($yt_id): ?>
        <div class="video-embed">
          <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($yt_id); ?>" title="YouTube" frameborder="0" allowfullscreen></iframe>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php
    if (function_exists('hd_section_photos'))     hd_section_photos($id);
    if (function_exists('hd_section_rules'))       hd_section_rules($id);
    if (function_exists('hd_section_expansions'))  hd_section_expansions($id);
  ?>

  <?php if ($plays->have_posts()): ?>
    <section class="game-section game-plays">
      <h2>📖 Odehrané hry</h2>
      <div class="sec-body">
      <?php while ($plays->have_posts()): $plays->the_post();
        $pid = get_the_ID();
        $players = (array) hd_meta($pid, 'players', []);
        $winners = array_map('strval', (array) hd_meta($pid, 'winners', []));
        $ext_players = (array) hd_meta($pid, 'ext_players', []);
        $ext_winners = (array) hd_meta($pid, 'ext_winners', []);
        $pdate = hd_meta($pid, 'play_date');
      ?>
        <div class="gp-row">
          <span class="pdate"><?php echo esc_html(hd_format_date($pdate)); ?></span>
          <span class="pplayers">
            <?php foreach ($players as $hp) { $w = in_array((string)$hp, $winners, true); echo '<span class="pl-chip' . ($w ? ' win' : '') . '" title="' . esc_attr(hd_player_name($hp)) . ($w ? ' 🏆' : '') . '">' . hd_player_avatar($hp, 28) . ($w ? '<span class="win-badge">🏆</span>' : '') . '</span>'; } ?>
            <?php foreach ($ext_players as $en) { if (trim((string)$en) === '') continue; $w = in_array($en, $ext_winners, true); echo '<span class="pl-chip' . ($w ? ' win' : '') . '" title="' . esc_attr($en) . ' (host)' . ($w ? ' 🏆' : '') . '">' . hd_ext_avatar($en, 28) . ($w ? '<span class="win-badge">🏆</span>' : '') . '</span>'; } ?>
          </span>
        </div>
      <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </section>
  <?php endif; ?>

  <p style="margin-top:30px"><a class="btn back" href="<?php echo esc_url(home_url('/')); ?>">← Zpět do Herny</a></p>
</article>

<?php $gs = hd_game_stats($id); ?>
<div class="hd-modal" id="hdGameStatsModal" hidden>
  <div class="hd-modal-bg js-close-gstats"></div>
  <div class="hd-modal-card" role="dialog" aria-modal="true">
    <button type="button" class="hd-modal-x js-close-gstats">×</button>
    <h2>📊 Statistiky – <?php the_title(); ?></h2>
    <p class="gs-total">Odehráno celkem: <strong><?php echo (int)$gs['total']; ?>×</strong></p>
    <?php if ($gs['rank']): ?>
      <table class="stat-table">
        <thead><tr><th>#</th><th>Hráč</th><th>Výher 🏆</th><th>Odehráno</th></tr></thead>
        <tbody>
          <?php foreach ($gs['rank'] as $i => $r): ?>
            <tr>
              <td class="rank"><?php echo $i + 1; ?>.</td>
              <td class="player-cell"><?php echo $r['ext'] ? hd_ext_avatar($r['name'], 28) : hd_player_avatar($r['id'], 28); ?> <?php echo esc_html($r['name']); ?><?php echo $r['ext'] ? ' <span class="ext-tag">host</span>' : ''; ?></td>
              <td><strong><?php echo (int)$r['won']; ?></strong></td>
              <td><?php echo (int)$r['played']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="hint">Tuhle hru jste zatím nehráli.</p>
    <?php endif; ?>

    <?php
      // seznam partií této hry – s možností úpravy / smazání
      $gs_pids = get_posts([
        'post_type'      => 'partie',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [['key' => 'game', 'value' => $id]],
      ]);
      usort($gs_pids, function ($a, $b) { return strcmp((string) hd_meta($b, 'play_date'), (string) hd_meta($a, 'play_date')); });
    ?>
    <?php if ($gs_pids): ?>
      <h3 class="gs-plays-h">📖 Partie <span class="hint">(klikni na ✏️ pro úpravu)</span></h3>
      <div class="gs-plays">
        <?php foreach ($gs_pids as $pid):
          $p_players = (array) hd_meta($pid, 'players', []);
          $p_winners = array_map('strval', (array) hd_meta($pid, 'winners', []));
          $p_ext     = (array) hd_meta($pid, 'ext_players', []);
          $p_extw    = array_map('strval', (array) hd_meta($pid, 'ext_winners', []));
        ?>
          <div class="gs-play" id="gsp<?php echo $pid; ?>">
            <span class="gsp-date"><?php echo esc_html(hd_format_date(hd_meta($pid, 'play_date'))); ?></span>
            <span class="gsp-players">
              <?php foreach ($p_players as $hp) { $w = in_array((string)$hp, $p_winners, true); echo '<span class="pl-chip' . ($w ? ' win' : '') . '" title="' . esc_attr(hd_player_name($hp)) . '">' . hd_player_avatar($hp, 26) . ($w ? '<span class="win-badge">🏆</span>' : '') . '</span>'; } ?>
              <?php foreach ($p_ext as $en) { if (trim((string)$en) === '') continue; $w = in_array($en, $p_extw, true); echo '<span class="pl-chip' . ($w ? ' win' : '') . '" title="' . esc_attr($en) . ' (host)">' . hd_ext_avatar($en, 26) . ($w ? '<span class="win-badge">🏆</span>' : '') . '</span>'; } ?>
            </span>
            <?php if (is_user_logged_in()): ?>
              <span class="gsp-actions">
                <button type="button" class="icon-btn ic-edit js-edit-play" data-hd="<?php echo hd_play_edit_json($pid); ?>" title="Upravit partii">✏️</button>
                <a class="icon-btn ic-del js-del-play" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_delete_play&id=' . $pid), 'hd_delplay_' . $pid)); ?>" data-name="<?php echo esc_attr(get_the_title($id)); ?>" title="Smazat partii">🗑️</a>
              </span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php endwhile; get_footer(); ?>
