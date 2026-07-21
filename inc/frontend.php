<?php
/**
 * Front-end interaktivita: filtry Herny (JS) + zápis partie z webu.
 */
if (!defined('ABSPATH')) exit;

/** Načti skript + předej data jen tam, kde je potřeba (Herna, detail hry, Deník). */
function hd_front_assets() {
    if (is_front_page() || is_singular('hra') || is_post_type_archive('partie')) {
        wp_enqueue_script('hd-app', get_template_directory_uri() . '/assets/app.js', [], HD_VERSION, true);
    }
}
add_action('wp_enqueue_scripts', 'hd_front_assets');

/**
 * Modální formulář „Zapsat partii". Vloží se do patičky stránek, kde má smysl.
 * Otevírá se tlačítky s třídou .js-open-play (data-game = přednastavená hra).
 */
function hd_play_modal() {
    if (!is_user_logged_in()) return;
    if (!(is_front_page() || is_singular('hra') || is_post_type_archive('partie'))) return;

    $games = hd_all_games();
    $players = hd_all_players();
    $today = current_time('Y-m-d');
    ?>
    <div class="hd-modal" id="hdPlayModal" hidden>
      <div class="hd-modal-bg js-close-play"></div>
      <div class="hd-modal-card" role="dialog" aria-modal="true" aria-label="Zapsat partii">
        <button type="button" class="hd-modal-x js-close-play" aria-label="Zavřít">×</button>
        <h2>🎲 Zapsat partii</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="hd_add_play">
          <?php wp_nonce_field('hd_add_play', 'hd_play_nonce'); ?>
          <label class="hd-fld">Hra
            <select name="game" id="hdPlayGame" required>
              <option value="">— vyber hru —</option>
              <?php foreach ($games as $id => $name) printf('<option value="%d">%s</option>', $id, esc_html($name)); ?>
            </select>
          </label>
          <label class="hd-fld">Datum
            <input type="date" name="play_date" value="<?php echo esc_attr($today); ?>" required>
          </label>
          <fieldset class="hd-fld">
            <legend>Kdo hrál a kdo vyhrál 🏆</legend>
            <?php if ($players): ?>
              <div class="hd-players">
                <?php foreach ($players as $id => $name): ?>
                  <div class="hd-prow">
                    <label class="hd-pchk"><input type="checkbox" class="js-played" name="players[]" value="<?php echo (int)$id; ?>"> <?php echo hd_player_avatar($id, 24); ?> <?php echo esc_html($name); ?></label>
                    <label class="hd-wchk" title="Vyhrál"><input type="checkbox" class="js-won" name="winners[]" value="<?php echo (int)$id; ?>" disabled> 🏆</label>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="hd-hint">Nemáš zatím žádné hráče. Přidej je v administraci (👥 Hráči).</p>
            <?php endif; ?>
          </fieldset>
          <label class="hd-fld">Poznámky ke hře
            <textarea name="note" rows="3" placeholder="Jak to probíhalo, domácí pravidla…"></textarea>
          </label>
          <div class="hd-modal-actions">
            <button type="button" class="btn back js-close-play">Zrušit</button>
            <button type="submit" class="btn">Uložit partii</button>
          </div>
        </form>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'hd_play_modal');

/** Zpracování odeslaného formuláře partie. */
function hd_handle_add_play() {
    if (!is_user_logged_in()) wp_die('Pro zápis partie musíš být přihlášen.');
    if (empty($_POST['hd_play_nonce']) || !wp_verify_nonce($_POST['hd_play_nonce'], 'hd_add_play')) wp_die('Neplatný požadavek.');

    $back = wp_get_referer() ?: home_url('/');
    $gid = intval($_POST['game'] ?? 0);
    if (!$gid || get_post_type($gid) !== 'hra') { wp_safe_redirect(add_query_arg('hd_play', 'err', $back)); exit; }

    $date    = sanitize_text_field($_POST['play_date'] ?? '');
    $players = array_values(array_unique(array_map('intval', (array)($_POST['players'] ?? []))));
    $winners = array_values(array_unique(array_map('intval', (array)($_POST['winners'] ?? []))));
    $winners = array_values(array_intersect($winners, $players)); // vítěz musí být mezi hrajícími
    $note    = wp_kses_post(wp_unslash($_POST['note'] ?? ''));

    $title = trim(get_the_title($gid) . ' ' . $date);
    $id = wp_insert_post([
        'post_type'   => 'partie',
        'post_status' => 'publish',
        'post_title'  => $title,
        'post_author' => get_current_user_id(),
    ]);
    if (is_wp_error($id)) { wp_safe_redirect(add_query_arg('hd_play', 'err', $back)); exit; }

    update_post_meta($id, 'game', $gid);
    update_post_meta($id, 'play_date', $date);
    update_post_meta($id, 'players', $players);
    update_post_meta($id, 'winners', $winners);
    update_post_meta($id, 'note', $note);

    wp_safe_redirect(add_query_arg('hd_play', 'ok', get_post_type_archive_link('partie')));
    exit;
}
add_action('admin_post_hd_add_play', 'hd_handle_add_play');
