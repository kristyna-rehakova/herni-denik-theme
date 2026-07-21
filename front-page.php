<?php
/**
 * Domovská stránka = Herna (mřížka her) s filtry, řazením a přepínačem zobrazení.
 */
if (!defined('ABSPATH')) exit;
get_header();

$games = new WP_Query([
    'post_type'      => 'hra',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

// nabídka vydavatelů pro filtr
$pubs = [];
if ($games->have_posts()) {
    foreach ($games->posts as $gp) {
        $pv = get_post_meta($gp->ID, 'publisher', true);
        if ($pv) $pubs[$pv] = true;
    }
}
$pubs = array_keys($pubs);
sort($pubs, SORT_LOCALE_STRING);
$total = $games->post_count;
?>
<div class="page-head">
  <h1 class="page-title">🏠 Herna</h1>
  <?php if (current_user_can('edit_posts')): ?>
    <a class="btn big" href="<?php echo esc_url(admin_url('post-new.php?post_type=hra')); ?>">♟️ Přidat deskovku</a>
  <?php endif; ?>
</div>

<?php if ($games->have_posts()): ?>
  <div class="toolbar card">
    <input type="search" id="hdSearch" placeholder="🔍 Hledat hru…">
    <select id="hdPlayers" aria-label="Počet hráčů">
      <option value="">Počet hráčů</option>
      <?php for ($i = 1; $i <= 8; $i++) echo '<option value="' . $i . '">' . $i . ' hráč' . ($i >= 5 ? 'ů' : ($i == 1 ? '' : 'i')) . '</option>'; ?>
    </select>
    <select id="hdTime" aria-label="Délka">
      <option value="">Délka</option>
      <option value="s">do 30 min</option>
      <option value="m">30–60 min</option>
      <option value="l">nad 60 min</option>
    </select>
    <select id="hdDiff" aria-label="Obtížnost">
      <option value="">Obtížnost</option>
      <option value="1">lehká</option>
      <option value="2">střední</option>
      <option value="3">těžká</option>
    </select>
    <?php if ($pubs): ?>
    <select id="hdPublisher" aria-label="Vydavatel">
      <option value="">Vydavatel</option>
      <?php foreach ($pubs as $pv) echo '<option value="' . esc_attr($pv) . '">' . esc_html($pv) . '</option>'; ?>
    </select>
    <?php endif; ?>
    <select id="hdSort" aria-label="Řadit" class="hd-sort">
      <option value="name">Řadit: abecedně</option>
      <option value="diff">Řadit: dle obtížnosti</option>
      <option value="plays">Řadit: dle odehrání</option>
    </select>
    <span class="hd-view">
      <button type="button" class="hd-view-btn" data-view="grid" title="Dlaždice">▦</button>
      <button type="button" class="hd-view-btn" data-view="list" title="Seznam">☰</button>
    </span>
  </div>

  <p class="hd-resultcount"><span id="hdCount"><?php echo (int)$total; ?></span> her</p>

  <div class="grid" id="hdGrid">
    <?php while ($games->have_posts()): $games->the_post();
        get_template_part('template-parts/game-card');
    endwhile; wp_reset_postdata(); ?>
  </div>
<?php else: ?>
  <div class="empty card" style="padding:50px 20px">📚 Sbírka je zatím prázdná. Přidej první hru!</div>
<?php endif; ?>

<?php get_footer(); ?>
