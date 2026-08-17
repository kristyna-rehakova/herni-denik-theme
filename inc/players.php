<?php
/**
 * Front-end sekce Hráči: stránka + formulář (nový/úprava) + mazání.
 */
if (!defined('ABSPATH')) exit;

/** Jednou vytvoř stránku „Hráči". */
function hd_ensure_hraci_page() {
    $existing = (int) get_option('hd_hraci_page_id');
    if ($existing && get_post_status($existing) === 'publish') return;
    $id = wp_insert_post([
        'post_type'   => 'page',
        'post_title'  => 'Hráči',
        'post_name'   => 'hraci',
        'post_status' => 'publish',
        'post_content'=> '',
    ]);
    if ($id && !is_wp_error($id)) {
        update_post_meta($id, '_wp_page_template', 'page-hraci.php');
        update_option('hd_hraci_page_id', $id);
    }
}
add_action('init', 'hd_ensure_hraci_page');

function hd_hraci_url() {
    $id = (int) get_option('hd_hraci_page_id');
    return $id ? get_permalink($id) : admin_url('edit.php?post_type=hrac');
}

/** JSON hráče pro předvyplnění formuláře. */
function hd_player_edit_json($id) {
    $d = [
        'id'    => $id,
        'name'  => get_the_title($id),
        'nick'  => hd_meta($id, 'nick'),
        'color' => hd_meta($id, 'color', '#eeb088'),
        'emoji' => hd_meta($id, 'emoji'),
        'email' => hd_meta($id, 'email'),
    ];
    return esc_attr(wp_json_encode($d));
}

/** Modal formuláře hráče (na stránce Hráči, jen pro editory). */
function hd_player_modal() {
    if (!is_user_logged_in()) return;
    if (!is_page((int) get_option('hd_hraci_page_id'))) return;
    $swatches = ['#eeb088','#cb1515','#e8873b','#e0b400','#5e8b6e','#3f9a52','#3a86c8','#5b6cc9','#9b5bc9','#c95b9e','#8a6d3b','#607d8b'];
    $emojis = ['🦉','🐺','🦊','🐻','🐼','🐸','🦁','🐯','🐨','🐰','🐧','🦄','🐲','🌟','🎲','🍀','🔥','⚡','🎯','👑'];
    ?>
    <div class="hd-modal" id="hdPlayerModal" hidden>
      <div class="hd-modal-bg js-close-player"></div>
      <div class="hd-modal-card" role="dialog" aria-modal="true" aria-label="Hráč">
        <button type="button" class="hd-modal-x js-close-player">×</button>
        <h2 id="pfTitle">Nový hráč</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="hd_save_player">
          <?php wp_nonce_field('hd_save_player', 'hd_player_nonce'); ?>
          <input type="hidden" name="player_id" id="pfId" value="">
          <div class="pf-preview"><span class="avatar" id="pfAvatar" style="width:56px;height:56px;font-size:24px;background:#eeb088">?</span></div>
          <label class="hd-fld">Přezdívka<input type="text" name="nick" id="pfNick" placeholder="Např. Kikuš"></label>
          <label class="hd-fld">Jméno a příjmení<input type="text" name="name" id="pfName" required></label>
          <?php if (hd_can_manage()): ?>
            <label class="hd-fld">E-mail <span class="hd-hint">(nepovinné – spáruje hráče s jeho účtem)</span><input type="email" name="email" id="pfEmail" placeholder="jmeno@email.cz"></label>
          <?php else: ?>
            <div class="hd-fld">E-mail <span class="hd-hint">(nastavuje admin)</span><div class="pf-email-ro" id="pfEmailRO">—</div></div>
          <?php endif; ?>
          <label class="hd-fld">Barva
            <span class="pf-colors">
              <input type="color" name="color" id="pfColor" value="#eeb088">
              <span class="swatches">
                <?php foreach ($swatches as $c) echo '<button type="button" class="swatch js-swatch" data-c="' . esc_attr($c) . '" style="background:' . esc_attr($c) . '" title="' . esc_attr($c) . '"></button>'; ?>
              </span>
            </span>
          </label>
          <label class="hd-fld">Ikonka <span class="hd-hint">(emoji, nech prázdné = iniciála)</span>
            <input type="text" name="emoji" id="pfEmoji" maxlength="2" placeholder="🦉">
          </label>
          <span class="emoji-presets">
            <button type="button" class="emoji-opt js-emoji" data-e="">Aa</button>
            <?php foreach ($emojis as $em) echo '<button type="button" class="emoji-opt js-emoji" data-e="' . esc_attr($em) . '">' . $em . '</button>'; ?>
          </span>
          <div class="hd-modal-actions" style="margin-top:14px">
            <button type="button" class="btn back js-close-player">Zrušit</button>
            <button type="submit" class="btn">Uložit</button>
          </div>
        </form>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'hd_player_modal');

/** Uložení hráče. */
function hd_handle_save_player() {
    if (!is_user_logged_in()) wp_die('Musíš být přihlášen.');
    if (empty($_POST['hd_player_nonce']) || !wp_verify_nonce($_POST['hd_player_nonce'], 'hd_save_player')) wp_die('Neplatný požadavek.');
    $back = hd_hraci_url();
    $pid  = intval($_POST['player_id'] ?? 0);
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    if ($name === '') { wp_safe_redirect($back); exit; }
    $nick = sanitize_text_field(wp_unslash($_POST['nick'] ?? ''));
    if ($nick !== '' && hd_nick_taken($nick, $pid)) { wp_safe_redirect(add_query_arg('hd_pl', 'nickdup', $back)); exit; }

    if ($pid && get_post_type($pid) === 'hrac') {
        if (!hd_can_manage() && $pid != hd_current_player_id()) wp_die('Upravit můžeš jen svého hráče.');
        wp_update_post(['ID' => $pid, 'post_title' => $name]);
    } else {
        $pid = wp_insert_post(['post_type' => 'hrac', 'post_status' => 'publish', 'post_title' => $name]);
    }
    if (is_wp_error($pid) || !$pid) { wp_safe_redirect($back); exit; }

    update_post_meta($pid, 'nick', $nick);
    update_post_meta($pid, 'color', sanitize_hex_color(wp_unslash($_POST['color'] ?? '')) ?: '#eeb088');
    update_post_meta($pid, 'emoji', sanitize_text_field(wp_unslash($_POST['emoji'] ?? '')));
    if (hd_can_manage() && isset($_POST['email'])) update_post_meta($pid, 'email', sanitize_email(wp_unslash($_POST['email'])));

    wp_safe_redirect(add_query_arg('hd_pl', 'ok', $back));
    exit;
}
add_action('admin_post_hd_save_player', 'hd_handle_save_player');

/** Smazání hráče (do koše). */
function hd_handle_delete_player() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id || get_post_type($id) !== 'hrac') wp_die('Neplatný hráč.');
    if (!hd_can_manage()) wp_die('Mazat hráče může jen admin.');
    check_admin_referer('hd_delplayer_' . $id);
    wp_trash_post($id);
    wp_safe_redirect(add_query_arg('hd_pl', 'del', hd_hraci_url()));
    exit;
}
add_action('admin_post_hd_delete_player', 'hd_handle_delete_player');
