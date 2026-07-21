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

$sections = [
    'Příprava'  => hd_meta($id, 'desc_priprava'),
    'Průběh hry'=> hd_meta($id, 'desc_prubeh'),
    'Konec hry' => hd_meta($id, 'desc_konec'),
    'Bodování'  => hd_meta($id, 'desc_bodovani'),
];

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
<p><a class="btn back" href="<?php echo esc_url(home_url('/')); ?>">← Zpět do Herny</a></p>

<article class="detail">
  <div class="detail-head">
    <div class="detail-img">
      <?php if (has_post_thumbnail()) the_post_thumbnail('large'); else echo '<span class="ph">🎲</span>'; ?>
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
        <?php if ($purl) echo '<a class="btn small" href="' . esc_url($purl) . '" target="_blank" rel="noopener">Odkaz na MINDOK</a>'; ?>
      </div>
      <?php if ($notes) echo '<div class="notes">📝 ' . nl2br(esc_html($notes)) . '</div>'; ?>
      <?php if (is_user_logged_in()): ?>
        <p class="detail-actions">
          <button type="button" class="btn js-open-play" data-game="<?php echo $id; ?>">🎲 Zapsat partii</button>
          <?php if (current_user_can('edit_post', $id)): ?>
            <a class="btn small ghost" href="<?php echo esc_url(get_edit_post_link($id)); ?>">✏️ Upravit info</a>
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </div>
  </div>

  <?php if (array_filter($sections)): ?>
    <section class="desc">
      <h2>📖 Popis hry</h2>
      <?php foreach ($sections as $title => $body): if (!$body) continue; ?>
        <div class="desc-part">
          <h3><?php echo esc_html($title); ?></h3>
          <div><?php echo nl2br(esc_html($body)); ?></div>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <?php if ($yt): $yt_id = hd_youtube_id($yt); if ($yt_id): ?>
    <section class="video">
      <h2>🎬 Video</h2>
      <div class="video-embed">
        <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($yt_id); ?>" title="YouTube" frameborder="0" allowfullscreen></iframe>
      </div>
    </section>
  <?php endif; endif; ?>

  <?php if ($plays->have_posts()): ?>
    <section class="game-plays">
      <h2>📖 Odehrané partie</h2>
      <?php while ($plays->have_posts()): $plays->the_post();
        $pid = get_the_ID();
        $players = (array) hd_meta($pid, 'players', []);
        $winners = (array) hd_meta($pid, 'winners', []);
        $pdate = hd_meta($pid, 'play_date');
      ?>
        <div class="play-row">
          <span class="pdate"><?php echo esc_html(hd_format_date($pdate)); ?></span>
          <span class="pplayers">
            <?php foreach ($players as $hp) { echo hd_player_avatar($hp, 26); if (in_array((string)$hp, array_map('strval',$winners), true)) echo '🏆'; } ?>
          </span>
        </div>
      <?php endwhile; wp_reset_postdata(); ?>
    </section>
  <?php endif; ?>

  <p style="margin-top:30px"><a class="btn back" href="<?php echo esc_url(home_url('/')); ?>">← Zpět do Herny</a></p>
</article>

<?php endwhile; get_footer(); ?>
