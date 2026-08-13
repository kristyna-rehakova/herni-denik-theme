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
<?php if (isset($_GET['hd_del']) && $_GET['hd_del'] === 'ok'): ?>
  <div class="hd-flash ok">🗑️ Hra byla přesunuta do koše.</div>
<?php endif; ?>
<?php if (isset($_GET['hd_cover']) && $_GET['hd_cover'] === 'ok'): ?>
  <div class="hd-flash ok">🖼 Obrázek byl uložen.</div>
<?php endif; ?>
<?php if (isset($_GET['hd_saved']) && $_GET['hd_saved'] === 'ok'): ?>
  <div class="hd-flash ok">✅ Hra byla uložena.</div>
<?php endif; ?>

<?php if (is_user_logged_in()): ?>
  <div class="topbtns">
    <button type="button" id="hdFavToggle" class="btn big secondary hd-fav-toggle">♥ Moje hry</button>
    <?php if (current_user_can('edit_posts')): ?>
      <button type="button" class="btn big js-open-gameform">♟️ Přidat deskovku</button>
      <button type="button" class="btn big secondary js-open-import">📋 Import</button>
    <?php endif; ?>
  </div>
<?php endif; ?>

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
  </div>

  <div class="legend-row">
    <div class="legend">
      <span class="item"><span class="lico ic-play">🎲</span> = zapsat do Deníku</span>
      <span class="item"><span class="lico ic-edit">✏️</span> = editovat info o hře</span>
      <span class="item"><span class="lico ic-del">🗑️</span> = smazat hru ze seznamu</span>
    </div>
    <div class="rowtools">
      <label class="sortlbl">Řadit:
        <select id="hdSort">
          <option value="name">abecedně</option>
          <option value="diff">dle obtížnosti</option>
          <option value="plays">dle odehrání</option>
        </select>
      </label>
      <span class="hd-view">
        <button type="button" class="hd-view-btn" data-view="grid" title="Dlaždice">▦</button>
        <button type="button" class="hd-view-btn" data-view="list" title="Seznam">☰</button>
      </span>
    </div>
  </div>

  <div class="grid" id="hdGrid">
    <?php while ($games->have_posts()): $games->the_post();
        get_template_part('template-parts/game-card');
    endwhile; wp_reset_postdata(); ?>
  </div>
<?php else: ?>
  <div class="empty card" style="padding:50px 20px">📚 Sbírka je zatím prázdná. Přidej první hru!</div>
<?php endif; ?>

<?php get_footer(); ?>
