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

/** Modal editoru obrázku (jen pro editory, na Herně a v detailu hry). */
function hd_cover_modal() {
    if (!current_user_can('edit_posts')) return;
    if (!(is_front_page() || is_singular('hra'))) return;
    ?>
    <div class="hd-modal" id="hdCoverModal" hidden>
      <div class="hd-modal-bg js-close-cover"></div>
      <div class="hd-modal-card" role="dialog" aria-modal="true" aria-label="Obrázek hry">
        <button type="button" class="hd-modal-x js-close-cover">×</button>
        <h2>🖼 Obrázek hry</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
          <input type="hidden" name="action" value="hd_save_cover">
          <?php wp_nonce_field('hd_save_cover', 'hd_cover_nonce'); ?>
          <input type="hidden" name="game" id="hdCoverGame">
          <input type="hidden" name="img_x" id="hdCoverX" value="50">
          <input type="hidden" name="img_y" id="hdCoverY" value="50">
          <input type="hidden" name="img_zoom" id="hdCoverZoom" value="1">
          <input type="hidden" name="img_size" id="hdCoverSize" value="">
          <input type="hidden" name="remove" id="hdCoverRemove" value="">
          <div class="imgstage" id="hdStage"><div class="ph">🎲</div></div>
          <div class="zoombar">
            <button type="button" class="zoombtn" id="hdZoomOut" title="Oddálit">−</button>
            <span>Přiblížení (nebo kolečkem myši)</span>
            <button type="button" class="zoombtn" id="hdZoomIn" title="Přiblížit">+</button>
          </div>
          <div class="hd-cover-tools">
            <label class="btn small ghost">📁 Nahrát vlastní<input type="file" name="cover_file" id="hdCoverFile" accept="image/*" hidden></label>
            <button type="button" class="btn small ghost" id="hdCoverClear">Odebrat obrázek</button>
          </div>
          <label class="hd-fld">nebo URL obrázku<input type="url" name="img_url" id="hdCoverUrl" placeholder="https://…"></label>
          <p class="hd-hint">Táhni obrázkem pro posun výřezu, přibliž kolečkem myši nebo tlačítky −/+.</p>
          <div class="hd-modal-actions">
            <button type="button" class="btn back js-close-cover">Zrušit</button>
            <button type="submit" class="btn">Uložit obrázek</button>
          </div>
        </form>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'hd_cover_modal');

/** Zpracování uložení obrázku. */
function hd_handle_save_cover() {
    if (empty($_POST['hd_cover_nonce']) || !wp_verify_nonce($_POST['hd_cover_nonce'], 'hd_save_cover')) wp_die('Neplatný požadavek.');
    $gid = intval($_POST['game'] ?? 0);
    if (!$gid || get_post_type($gid) !== 'hra' || !current_user_can('edit_post', $gid)) wp_die('Na úpravu obrázku nemáš oprávnění.');
    $back = wp_get_referer() ?: get_permalink($gid);

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    if (!empty($_POST['remove'])) {
        delete_post_thumbnail($gid);
        foreach (['img_x','img_y','img_zoom','img_size'] as $k) delete_post_meta($gid, $k);
        wp_safe_redirect(add_query_arg('hd_cover', 'ok', $back)); exit;
    }

    if (!empty($_FILES['cover_file']['name'])) {
        $att = media_handle_upload('cover_file', $gid);
        if (!is_wp_error($att)) set_post_thumbnail($gid, $att);
    } elseif (!empty($_POST['img_url'])) {
        $att = media_sideload_image(esc_url_raw($_POST['img_url']), $gid, null, 'id');
        if (!is_wp_error($att)) set_post_thumbnail($gid, $att);
    }
    update_post_meta($gid, 'img_x', floatval($_POST['img_x'] ?? 50));
    update_post_meta($gid, 'img_y', floatval($_POST['img_y'] ?? 50));
    update_post_meta($gid, 'img_zoom', floatval($_POST['img_zoom'] ?? 1));
    update_post_meta($gid, 'img_size', sanitize_text_field($_POST['img_size'] ?? ''));

    wp_safe_redirect(add_query_arg('hd_cover', 'ok', $back)); exit;
}
add_action('admin_post_hd_save_cover', 'hd_handle_save_cover');

/** Modal Importu (zatím jen upozornění – paste-import přijde v další fázi). */
function hd_import_modal() {
    if (!current_user_can('edit_posts') || !is_front_page()) return;
    ?>
    <div class="hd-modal" id="hdImportModal" hidden>
      <div class="hd-modal-bg js-close-import"></div>
      <div class="hd-modal-card">
        <button type="button" class="hd-modal-x js-close-import">×</button>
        <h2>📋 Import hry</h2>
        <p style="line-height:1.5">Import údajů vložením obsahu stránky ze <strong>Zatrolených</strong> / <strong>Mindoku</strong> (jako v původní appce) připravuji do příští aktualizace.</p>
        <p style="line-height:1.5">Zatím přidávej hry tlačítkem <strong>♟️ Přidat deskovku</strong> a obrázek nastav tužkou ✏️ na obálce.</p>
        <div class="hd-modal-actions"><button type="button" class="btn js-close-import">Rozumím</button></div>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'hd_import_modal');

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

/** Smazání hry z webu (do koše – vratné). */
function hd_handle_delete_game() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id || get_post_type($id) !== 'hra') wp_die('Neplatná hra.');
    if (!current_user_can('delete_post', $id)) wp_die('Na smazání hry nemáš oprávnění.');
    check_admin_referer('hd_delete_' . $id);
    wp_trash_post($id);
    wp_safe_redirect(add_query_arg('hd_del', 'ok', home_url('/')));
    exit;
}
add_action('admin_post_hd_delete_game', 'hd_handle_delete_game');
