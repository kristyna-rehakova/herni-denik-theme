<?php
/**
 * Editace detailu hry z webu: sekce popisu (text + poznámka) a video (YouTube).
 */
if (!defined('ABSPATH')) exit;

/** Okna editace – jen pro editory na detailu hry. */
function hd_detail_modals() {
    if (!is_singular('hra') || !current_user_can('edit_post', get_the_ID())) return;
    ?>
    <div class="hd-modal" id="hdDescModal" hidden>
      <div class="hd-modal-bg js-close-desc"></div>
      <div class="hd-modal-card hd-modal-wide" role="dialog" aria-modal="true">
        <button type="button" class="hd-modal-x js-close-desc">×</button>
        <h2 id="descTitle">Upravit sekci</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
          <input type="hidden" name="action" value="hd_save_desc">
          <?php wp_nonce_field('hd_save_desc', 'hd_desc_nonce'); ?>
          <input type="hidden" name="game_id" id="descGame">
          <input type="hidden" name="key" id="descKey">
          <label class="hd-fld">Text<textarea name="text" id="descText" rows="7"></textarea></label>
          <label class="hd-fld">Poznámka <span class="hd-hint">(nepovinné – tip, domácí pravidlo…)</span><textarea name="note" id="descNote" rows="2"></textarea></label>
          <div class="hd-fld">Obrázky k sekci
            <div id="descImages" class="desc-imgs-edit"></div>
            <input type="file" name="images[]" accept="image/*" multiple>
          </div>
          <div class="hd-modal-actions">
            <button type="button" class="btn back js-close-desc">Zrušit</button>
            <button type="submit" class="btn">Uložit</button>
          </div>
        </form>
      </div>
    </div>

    <div class="hd-modal" id="hdVideoModal" hidden>
      <div class="hd-modal-bg js-close-video"></div>
      <div class="hd-modal-card" role="dialog" aria-modal="true">
        <button type="button" class="hd-modal-x js-close-video">×</button>
        <h2>🎬 Video</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="hd_save_video">
          <?php wp_nonce_field('hd_save_video', 'hd_video_nonce'); ?>
          <input type="hidden" name="game_id" id="videoGame">
          <label class="hd-fld">Odkaz na YouTube<input type="text" name="youtube" id="videoUrl" placeholder="https://www.youtube.com/watch?v=…"></label>
          <p class="hd-hint">Nech prázdné pro odebrání videa.</p>
          <div class="hd-modal-actions">
            <button type="button" class="btn back js-close-video">Zrušit</button>
            <button type="submit" class="btn">Uložit</button>
          </div>
        </form>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'hd_detail_modals');

/** Uložení sekce popisu. */
function hd_handle_save_desc() {
    if (empty($_POST['hd_desc_nonce']) || !wp_verify_nonce($_POST['hd_desc_nonce'], 'hd_save_desc')) wp_die('Neplatný požadavek.');
    $gid = intval($_POST['game_id'] ?? 0);
    $key = sanitize_key($_POST['key'] ?? '');
    if (!$gid || get_post_type($gid) !== 'hra' || !current_user_can('edit_post', $gid)) wp_die('Nemáš oprávnění.');
    if (!in_array($key, ['priprava','prubeh','konec','bodovani'], true)) wp_die('Neplatná sekce.');
    update_post_meta($gid, 'desc_' . $key, sanitize_textarea_field(wp_unslash($_POST['text'] ?? '')));
    update_post_meta($gid, 'desc_' . $key . '_note', sanitize_textarea_field(wp_unslash($_POST['note'] ?? '')));
    // ruční úprava sekce = nejvyšší priorita (import ji nepřepíše)
    $fs = (array) get_post_meta($gid, 'field_src', true);
    $fs['desc_' . $key] = 'manual';
    update_post_meta($gid, 'field_src', $fs);

    // obrázky sekce: odebrání + nahrání nových
    $imgs = array_filter(array_map('intval', (array) get_post_meta($gid, 'desc_' . $key . '_images', true)));
    $remove = array_map('intval', (array) ($_POST['remove_images'] ?? []));
    if ($remove) {
        foreach ($remove as $rid) { if (in_array($rid, $imgs, true)) wp_delete_attachment($rid, true); }
        $imgs = array_values(array_diff($imgs, $remove));
    }
    if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name']) && function_exists('hd_upload_one')) {
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            $att = hd_upload_one('images', $i, $gid);
            if ($att) $imgs[] = $att;
        }
    }
    update_post_meta($gid, 'desc_' . $key . '_images', array_values(array_unique($imgs)));

    wp_safe_redirect(get_permalink($gid) . '#sekce-' . $key);
    exit;
}
add_action('admin_post_hd_save_desc', 'hd_handle_save_desc');

/** Uložení odkazu na video. */
function hd_handle_save_video() {
    if (empty($_POST['hd_video_nonce']) || !wp_verify_nonce($_POST['hd_video_nonce'], 'hd_save_video')) wp_die('Neplatný požadavek.');
    $gid = intval($_POST['game_id'] ?? 0);
    if (!$gid || get_post_type($gid) !== 'hra' || !current_user_can('edit_post', $gid)) wp_die('Nemáš oprávnění.');
    update_post_meta($gid, 'youtube', esc_url_raw(trim(wp_unslash($_POST['youtube'] ?? ''))));
    wp_safe_redirect(get_permalink($gid));
    exit;
}
add_action('admin_post_hd_save_video', 'hd_handle_save_video');

/** Přepnutí „Zkontrolováno". */
function hd_handle_toggle_checked() {
    $gid = intval($_POST['game_id'] ?? 0);
    if (!$gid || get_post_type($gid) !== 'hra' || !current_user_can('edit_post', $gid)) wp_die('Nemáš oprávnění.');
    if (empty($_POST['hd_check_nonce']) || !wp_verify_nonce($_POST['hd_check_nonce'], 'hd_toggle_checked')) wp_die('Neplatný požadavek.');
    update_post_meta($gid, 'desc_checked', !empty($_POST['desc_checked']) ? '1' : '');
    wp_safe_redirect(get_permalink($gid) . '#popis');
    exit;
}
add_action('admin_post_hd_toggle_checked', 'hd_handle_toggle_checked');

/** AJAX varianta „Zkontrolováno" – bez přenačtení stránky. */
function hd_ajax_toggle_checked() {
    $gid = intval($_POST['game'] ?? 0);
    if (!$gid || get_post_type($gid) !== 'hra' || !current_user_can('edit_post', $gid)) wp_send_json_error();
    check_ajax_referer('hd_toggle_checked', 'nonce');
    $val = !empty($_POST['checked']) ? '1' : '';
    update_post_meta($gid, 'desc_checked', $val);
    wp_send_json_success(['checked' => $val]);
}
add_action('wp_ajax_hd_toggle_checked', 'hd_ajax_toggle_checked');
