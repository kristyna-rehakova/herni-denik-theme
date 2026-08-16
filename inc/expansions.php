<?php
/**
 * Detail hry – Rozšíření (list + přidat/upravit/smazat + import ze Zatrolené/Mindok).
 */
if (!defined('ABSPATH')) exit;

/** Vykreslení sekce rozšíření. */
function hd_section_expansions($id) {
    $can  = current_user_can('edit_post', $id);
    $exps = (array) hd_meta($id, 'expansions', []);
    if (!$can && !$exps) return;
    ?>
    <section class="game-section" id="rozsireni">
      <div class="sec-head">
        <h2>🧩 Rozšíření</h2>
        <?php if ($can): ?>
          <button type="button" class="btn small js-open-exp" data-game="<?php echo $id; ?>">+ Přidat</button>
          <button type="button" class="btn small ghost js-open-expimport">📋 Import</button>
        <?php endif; ?>
      </div>
      <?php if ($exps): foreach ($exps as $i => $ex):
        if (!is_array($ex) || trim((string)($ex['name'] ?? '')) === '') continue; // přeskoč prázdné/porouchané řádky
        $img = !empty($ex['image']) ? wp_get_attachment_image_url((int) $ex['image'], 'thumbnail') : '';
      ?>
        <div class="exp-card card">
          <div class="exp-thumb"><?php echo $img ? '<img src="' . esc_url($img) . '" alt="">' : '🧩'; ?></div>
          <div class="exp-info">
            <strong><?php echo esc_html($ex['name'] ?? 'Rozšíření'); ?></strong>
            <?php if (!empty($ex['year'])) echo ' <span class="muted">(' . esc_html($ex['year']) . ')</span>'; ?>
            <?php if (!empty($ex['desc'])) echo '<div class="exp-desc">' . nl2br(esc_html($ex['desc'])) . '</div>'; ?>
          </div>
          <?php if ($can): ?>
            <div class="exp-tools">
              <button type="button" class="mini-edit js-edit-exp" data-hd="<?php echo esc_attr(wp_json_encode(['idx' => $i, 'name' => $ex['name'] ?? '', 'year' => $ex['year'] ?? '', 'desc' => $ex['desc'] ?? ''])); ?>" data-game="<?php echo $id; ?>" title="Upravit">✏️</button>
              <a class="mini-edit" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_del_expansion&game=' . $id . '&idx=' . $i), 'hd_delexp_' . $id . '_' . $i)); ?>" title="Smazat" onclick="return confirm('Smazat toto rozšíření?')">🗑️</a>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </section>
    <?php
}

/** Modaly rozšíření (formulář + import) – jen editor na detailu hry. */
function hd_expansion_modals() {
    if (!is_singular('hra') || !current_user_can('edit_post', get_the_ID())) return;
    ?>
    <div class="hd-modal" id="hdExpModal" hidden>
      <div class="hd-modal-bg js-close-exp"></div>
      <div class="hd-modal-card hd-modal-wide" role="dialog" aria-modal="true">
        <button type="button" class="hd-modal-x js-close-exp">×</button>
        <h2 id="expTitle">Nové rozšíření</h2>
        <p class="hd-mobile-note">📋 Import vložením obsahu stránky funguje jen na počítači (nebo tabletu s klávesnicí). Na mobilu stačí vyplnit <strong>Název</strong> a <strong>Odkaz na Zatrolené</strong> – obrázek se načte sám. Můžeš přidat i vlastní <strong>URL obrázku</strong>.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="hd_save_expansion">
          <?php wp_nonce_field('hd_save_expansion', 'hd_exp_nonce'); ?>
          <input type="hidden" name="game" id="expGame">
          <input type="hidden" name="idx" id="expIdx" value="-1">
          <label class="hd-fld">Název<input type="text" name="name" id="expName" required></label>
          <label class="hd-fld">Odkaz na Zatrolené <span class="hd-hint">(načte obrázek rozšíření)</span><input type="url" name="exp_url" id="expUrl" placeholder="https://www.zatrolene-hry.cz/…"></label>
          <div class="gf-grid">
            <label class="hd-fld">Rok<input type="number" name="year" id="expYear"></label>
            <label class="hd-fld">URL obrázku<input type="text" name="image_url" id="expImg" placeholder="https://…"></label>
          </div>
          <label class="hd-fld">Popis<textarea name="desc" id="expDesc" rows="4"></textarea></label>
          <div class="hd-modal-actions">
            <button type="button" class="btn back js-close-exp">Zrušit</button>
            <button type="submit" class="btn">Uložit</button>
          </div>
        </form>
      </div>
    </div>

    <div class="hd-modal" id="hdExpImportModal" hidden>
      <div class="hd-modal-bg js-close-expimport"></div>
      <div class="hd-modal-card" role="dialog" aria-modal="true">
        <button type="button" class="hd-modal-x js-close-expimport">×</button>
        <h2>📋 Import rozšíření</h2>
        <p class="hd-hint">Otevři stránku rozšíření na Zatrolených/Mindoku, označ vše (Ctrl+A), zkopíruj a vlož sem.</p>
        <label class="hd-fld">Obsah stránky<textarea id="expImpText" rows="6"></textarea></label>
        <label class="hd-fld">Odkaz na stránku <span class="hd-hint">(u Zatrolených doplní i obrázek)</span><input type="url" id="expImpUrl" placeholder="https://…"></label>
        <div class="hd-modal-actions">
          <button type="button" class="btn back js-close-expimport">Zrušit</button>
          <button type="button" class="btn js-expimport-parse">Načíst →</button>
        </div>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'hd_expansion_modals');

/** Uložení rozšíření (nové/úprava). */
function hd_handle_save_expansion() {
    $gid = intval($_POST['game'] ?? 0);
    if (!$gid || !current_user_can('edit_post', $gid)) wp_die('Nemáš oprávnění.');
    if (empty($_POST['hd_exp_nonce']) || !wp_verify_nonce($_POST['hd_exp_nonce'], 'hd_save_expansion')) wp_die('Neplatný požadavek.');
    $idx  = intval($_POST['idx'] ?? -1);
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    if ($name === '') { wp_safe_redirect(get_permalink($gid) . '#rozsireni'); exit; }
    $exps = (array) get_post_meta($gid, 'expansions', true);
    if (!is_array($exps)) $exps = [];

    $img_att = ($idx >= 0 && isset($exps[$idx]['image'])) ? (int) $exps[$idx]['image'] : 0;
    $img_url = trim(wp_unslash($_POST['image_url'] ?? ''));
    // když není přímá URL obrázku, ale je odkaz na Zatrolené, zkus z něj vytáhnout obálku
    if (!$img_url && !$img_att) {
        $exp_url = trim(wp_unslash($_POST['exp_url'] ?? ''));
        if ($exp_url && function_exists('hd_resolve_zatrolene_cover')) $img_url = hd_resolve_zatrolene_cover($exp_url);
    }
    if ($img_url) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $att = media_sideload_image(esc_url_raw($img_url), $gid, null, 'id');
        if (!is_wp_error($att)) $img_att = (int) $att;
    }
    $entry = [
        'name'  => $name,
        'year'  => sanitize_text_field(wp_unslash($_POST['year'] ?? '')),
        'desc'  => sanitize_textarea_field(wp_unslash($_POST['desc'] ?? '')),
        'image' => $img_att,
    ];
    if ($idx >= 0 && isset($exps[$idx])) $exps[$idx] = $entry; else $exps[] = $entry;
    // pročisti pole – vyhoď prázdné/porouchané záznamy bez názvu
    $exps = array_values(array_filter($exps, function ($e) { return is_array($e) && trim((string)($e['name'] ?? '')) !== ''; }));
    update_post_meta($gid, 'expansions', $exps);
    wp_safe_redirect(get_permalink($gid) . '#rozsireni'); exit;
}
add_action('admin_post_hd_save_expansion', 'hd_handle_save_expansion');

/** Smazání rozšíření. */
function hd_handle_del_expansion() {
    $gid = intval($_GET['game'] ?? 0); $idx = intval($_GET['idx'] ?? -1);
    if (!$gid || !current_user_can('edit_post', $gid)) wp_die('Nemáš oprávnění.');
    check_admin_referer('hd_delexp_' . $gid . '_' . $idx);
    $exps = (array) get_post_meta($gid, 'expansions', true);
    if (isset($exps[$idx])) {
        if (!empty($exps[$idx]['image'])) wp_delete_attachment((int) $exps[$idx]['image'], true);
        unset($exps[$idx]);
        $exps = array_values($exps);
    }
    update_post_meta($gid, 'expansions', $exps);
    wp_safe_redirect(get_permalink($gid) . '#rozsireni'); exit;
}
add_action('admin_post_hd_del_expansion', 'hd_handle_del_expansion');

/** AJAX: rozparsuj obsah stránky rozšíření a vrať name/year/desc/image. */
function hd_ajax_expansion_parse() {
    if (!current_user_can('edit_posts')) wp_send_json_error(['msg' => 'Nemáš oprávnění.'], 403);
    check_ajax_referer('hd_import_parse', 'nonce');
    $text = wp_unslash($_POST['content'] ?? '');
    $url  = trim(wp_unslash($_POST['url'] ?? ''));
    if (trim($text) === '' && $url === '') wp_send_json_error(['msg' => 'Vlož obsah stránky nebo odkaz.']);
    $src  = hd_detect_source($text, $url);
    $data = ($src === 'mindok') ? hd_parse_mindok($text) : hd_parse_zatrolene($text, $url);
    $desc = '';
    if (!empty($data['desc'])) $desc = trim(implode("\n\n", array_filter([$data['desc']['priprava'] ?? '', $data['desc']['prubeh'] ?? '', $data['desc']['konec'] ?? ''])));
    $cover = ($src !== 'mindok' && $url) ? hd_resolve_zatrolene_cover($url) : '';
    if (empty($data['name']) && $desc === '') wp_send_json_error(['msg' => 'Nepodařilo se nic rozpoznat.']);
    wp_send_json_success([
        'name'      => $data['name'] ?? '',
        'year'      => $data['year'] ?? '',
        'desc'      => $desc,
        'image_url' => $cover,
    ]);
}
add_action('wp_ajax_hd_expansion_parse', 'hd_ajax_expansion_parse');
