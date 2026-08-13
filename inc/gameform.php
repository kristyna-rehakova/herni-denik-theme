<?php
/**
 * Front-end formulář hry (Nová hra / úprava) — používá se po importu i pro ruční přidání.
 */
if (!defined('ABSPATH')) exit;

function hd_game_form_modal() {
    if (!is_user_logged_in()) return;
    if (!(is_front_page() || is_singular('hra'))) return;
    ?>
    <div class="hd-modal" id="hdGameModal" hidden>
      <div class="hd-modal-bg js-close-gameform"></div>
      <div class="hd-modal-card hd-modal-wide" role="dialog" aria-modal="true" aria-label="Hra">
        <button type="button" class="hd-modal-x js-close-gameform">×</button>
        <div class="gf-titlerow">
          <h2 id="gfTitle">Nová hra</h2>
          <button type="button" class="btn small ghost js-open-import" title="Doplnit údaje vložením obsahu stránky">📋 Import</button>
        </div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="hd_save_game">
          <?php wp_nonce_field('hd_save_game', 'hd_game_nonce'); ?>
          <input type="hidden" name="game_id" id="gfId" value="">
          <input type="hidden" name="field_src" id="gfFieldSrc" value="">
          <input type="hidden" name="desc_priprava" id="gfDP">
          <input type="hidden" name="desc_prubeh" id="gfDPr">
          <input type="hidden" name="desc_konec" id="gfDK">

          <label class="hd-fld">Název <input type="text" name="name" id="gfName" required></label>
          <div class="gf-grid">
            <label class="hd-fld">Min. hráčů<input type="number" name="players_min" id="gfPmin" min="1"></label>
            <label class="hd-fld">Max. hráčů<input type="number" name="players_max" id="gfPmax" min="1"></label>
            <label class="hd-fld">Délka od (min)<input type="number" name="time_min" id="gfTmin" min="0"></label>
            <label class="hd-fld">Délka do (min)<input type="number" name="time_max" id="gfTmax" min="0"></label>
            <label class="hd-fld">Obtížnost
              <select name="difficulty" id="gfDiff">
                <option value="">— nezadáno —</option>
                <option value="lehka">lehká</option>
                <option value="stredni">střední</option>
                <option value="tezka">těžká</option>
              </select>
            </label>
            <label class="hd-fld">Rok vydání<input type="number" name="year" id="gfYear" min="0"></label>
            <label class="hd-fld">Vydavatel<input type="text" name="publisher" id="gfPub"></label>
            <label class="hd-fld">URL obrázku<input type="text" name="image_url" id="gfImg" placeholder="https://…"></label>
          </div>
          <label class="hd-fld">Odkaz na Zatrolené<input type="text" name="bgg_url" id="gfBgg" placeholder="https://…"></label>
          <label class="hd-fld">Odkaz na web vydavatele<input type="text" name="pub_url" id="gfPubUrl" placeholder="https://…"></label>
          <label class="hd-fld">Poznámka<textarea name="notes" id="gfNotes" rows="2"></textarea></label>

          <div class="hd-modal-actions">
            <button type="button" class="btn back js-close-gameform">Zrušit</button>
            <button type="submit" class="btn">Uložit</button>
          </div>
        </form>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'hd_game_form_modal');

/** Uložení hry z front-end formuláře (nová i úprava). */
function hd_handle_save_game() {
    if (!is_user_logged_in()) wp_die('Na uložení hry musíš být přihlášen.');
    if (empty($_POST['hd_game_nonce']) || !wp_verify_nonce($_POST['hd_game_nonce'], 'hd_save_game')) wp_die('Neplatný požadavek.');

    $gid  = intval($_POST['game_id'] ?? 0);
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    if ($name === '') { wp_safe_redirect(wp_get_referer() ?: home_url('/')); exit; }

    // ČLEN: úprava existující hry = návrh ke schválení (nová hra jen pro admina)
    if (hd_is_member()) {
        if (!$gid || get_post_type($gid) !== 'hra') { wp_safe_redirect(home_url('/')); exit; }
        hd_store_pending($gid, $_POST);
        wp_safe_redirect(add_query_arg('hd_edit', 'suggested', get_permalink($gid))); exit;
    }

    // ADMIN: uloží se rovnou
    if (!hd_can_manage()) wp_die('Nemáš oprávnění.');
    if ($gid && get_post_type($gid) === 'hra') {
        wp_update_post(['ID' => $gid, 'post_title' => $name]);
    } else {
        $gid = wp_insert_post(['post_type' => 'hra', 'post_status' => 'publish', 'post_title' => $name, 'post_author' => get_current_user_id()]);
    }
    if (is_wp_error($gid) || !$gid) { wp_safe_redirect(home_url('/')); exit; }
    hd_apply_game_fields($gid, $_POST);
    wp_safe_redirect(add_query_arg('hd_saved', 'ok', get_permalink($gid)));
    exit;
}
add_action('admin_post_hd_save_game', 'hd_handle_save_game');
