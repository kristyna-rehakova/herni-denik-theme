<?php
/**
 * Template Name: Statistiky
 * Přehled – kolik her, partií, hráčů + nejhranější hry a žebříček hráčů.
 */
if (!defined('ABSPATH')) exit;
get_header();
$s = hd_compute_stats();
?>
<h1 class="page-title">📊 Statistiky</h1>

<div class="stat-grid">
  <div class="stat"><span class="num"><?php echo (int)$s['games']; ?></span><span class="lbl">her ve sbírce</span></div>
  <div class="stat"><span class="num"><?php echo (int)$s['plays']; ?></span><span class="lbl">odehraných partií</span></div>
  <div class="stat"><span class="num"><?php echo (int)$s['players']; ?></span><span class="lbl">hráčů</span></div>
</div>

<div class="stat-cols">
  <section class="card stat-box">
    <h2>🏆 Žebříček hráčů</h2>
    <?php if ($s['rank']): ?>
      <table class="stat-table">
        <thead><tr><th>#</th><th>Hráč</th><th>Výher</th><th>Odehráno</th></tr></thead>
        <tbody>
        <?php foreach ($s['rank'] as $i => $r): ?>
          <tr>
            <td class="rank"><?php echo $i + 1; ?>.</td>
            <td class="player-cell"><?php echo hd_player_avatar($r['id'], 30); ?> <?php echo esc_html(hd_player_name($r['id'])); ?></td>
            <td><strong><?php echo (int)$r['won']; ?></strong></td>
            <td><?php echo (int)$r['played']; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="hint">Zatím žádní hráči.</p>
    <?php endif; ?>
  </section>

  <section class="card stat-box">
    <h2>🎲 Nejhranější hry</h2>
    <?php if ($s['top_games']): ?>
      <ol class="top-games">
        <?php foreach ($s['top_games'] as $tg): ?>
          <li>
            <a href="<?php echo esc_url(get_permalink($tg['id'])); ?>">
              <span class="tg-thumb"><?php echo has_post_thumbnail($tg['id']) ? get_the_post_thumbnail($tg['id'], 'thumbnail') : '🎲'; ?></span>
              <span class="tg-name"><?php echo esc_html(get_the_title($tg['id'])); ?></span>
            </a>
            <span class="tg-count"><?php echo (int)$tg['count']; ?>×</span>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <p class="hint">Zatím žádná odehraná partie.</p>
    <?php endif; ?>
  </section>
</div>

<?php get_footer(); ?>
