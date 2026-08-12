<?php
/**
 * Detail hry – Fotky a Pravidla (nahrávání souborů).
 */
if (!defined('ABSPATH')) exit;

/** Nahraj jeden soubor z multi-file pole $_FILES[$field] na indexu $i → attachment ID (0 při chybě). */
function hd_upload_one($field, $i, $gid) {
    if (empty($_FILES[$field]['name'][$i])) return 0;
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $_FILES['hd_one'] = [
        'name'     => $_FILES[$field]['name'][$i],
        'type'     => $_FILES[$field]['type'][$i],
        'tmp_name' => $_FILES[$field]['tmp_name'][$i],
        'error'    => $_FILES[$field]['error'][$i],
        'size'     => $_FILES[$field]['size'][$i],
    ];
    $att = media_handle_upload('hd_one', $gid);
    return is_wp_error($att) ? 0 : (int) $att;
}

/* ==================== FOTKY ==================== */
function hd_section_photos($id) {
    $can = current_user_can('edit_post', $id);
    $photos = array_filter(array_map('intval', (array) hd_meta($id, 'photos', [])));
    if (!$can && !$photos) return;
    ?>
    <section class="game-section" id="fotky">
      <div class="sec-head"><h2>📷 Fotky</h2></div>
      <?php if ($photos): ?>
        <div class="photo-grid">
          <?php foreach ($photos as $att):
            $url = wp_get_attachment_image_url($att, 'large');
            if (!$url) continue; ?>
            <div class="photo-item">
              <a href="<?php echo esc_url(wp_get_attachment_url($att)); ?>" target="_blank" rel="noopener">
                <img src="<?php echo esc_url($url); ?>" alt="" loading="lazy">
              </a>
              <?php if ($can): ?>
                <a class="photo-del" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_del_photo&game=' . $id . '&att=' . $att), 'hd_delphoto_' . $id . '_' . $att)); ?>" title="Smazat fotku" onclick="return confirm('Smazat tuto fotku?')">×</a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($can): ?>
        <form class="upload-row" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
          <input type="hidden" name="action" value="hd_add_photos">
          <input type="hidden" name="game" value="<?php echo $id; ?>">
          <?php wp_nonce_field('hd_addphotos_' . $id, 'hd_photos_nonce'); ?>
          <input type="file" name="photos[]" accept="image/*" multiple>
          <button type="submit" class="btn small">Nahrát fotky</button>
        </form>
      <?php endif; ?>
    </section>
    <?php
}

function hd_handle_add_photos() {
    $gid = intval($_POST['game'] ?? 0);
    if (!$gid || !current_user_can('edit_post', $gid)) wp_die('Nemáš oprávnění.');
    if (empty($_POST['hd_photos_nonce']) || !wp_verify_nonce($_POST['hd_photos_nonce'], 'hd_addphotos_' . $gid)) wp_die('Neplatný požadavek.');
    $ids = array_filter(array_map('intval', (array) get_post_meta($gid, 'photos', true)));
    if (!empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
        for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
            $att = hd_upload_one('photos', $i, $gid);
            if ($att) $ids[] = $att;
        }
    }
    update_post_meta($gid, 'photos', array_values(array_unique($ids)));
    wp_safe_redirect(get_permalink($gid) . '#fotky'); exit;
}
add_action('admin_post_hd_add_photos', 'hd_handle_add_photos');

function hd_handle_del_photo() {
    $gid = intval($_GET['game'] ?? 0); $att = intval($_GET['att'] ?? 0);
    if (!$gid || !$att || !current_user_can('edit_post', $gid)) wp_die('Nemáš oprávnění.');
    check_admin_referer('hd_delphoto_' . $gid . '_' . $att);
    $ids = array_values(array_diff(array_filter(array_map('intval', (array) get_post_meta($gid, 'photos', true))), [$att]));
    update_post_meta($gid, 'photos', $ids);
    wp_delete_attachment($att, true);
    wp_safe_redirect(get_permalink($gid) . '#fotky'); exit;
}
add_action('admin_post_hd_del_photo', 'hd_handle_del_photo');

/* ==================== PRAVIDLA ==================== */
function hd_section_rules($id) {
    $can = current_user_can('edit_post', $id);
    $rules = (array) hd_meta($id, 'rules', []);
    if (!$can && !$rules) return;
    ?>
    <section class="game-section" id="pravidla">
      <div class="sec-head"><h2>📄 Pravidla</h2></div>
      <?php if ($rules): ?>
        <div class="rules-list">
          <?php foreach ($rules as $i => $r):
            $icon = ($r['kind'] ?? '') === 'pdf' ? '📕' : '🔗'; ?>
            <div class="rule-item">
              <a href="<?php echo esc_url($r['url'] ?? '#'); ?>" target="_blank" rel="noopener"><?php echo $icon . ' ' . esc_html($r['label'] ?? 'Pravidla'); ?></a>
              <?php if ($can): ?>
                <a class="rule-del" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_del_rule&game=' . $id . '&idx=' . $i), 'hd_delrule_' . $id . '_' . $i)); ?>" title="Odebrat" onclick="return confirm('Odebrat tento odkaz?')">🗑️</a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($can): ?>
        <div class="rules-add">
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="upload-row">
            <input type="hidden" name="action" value="hd_add_rule">
            <input type="hidden" name="game" value="<?php echo $id; ?>">
            <input type="hidden" name="kind" value="link">
            <?php wp_nonce_field('hd_addrule_' . $id, 'hd_rule_nonce'); ?>
            <input type="text" name="label" placeholder="Popisek (např. Oficiální pravidla)">
            <input type="url" name="url" placeholder="https://…" required>
            <button type="submit" class="btn small">+ Odkaz</button>
          </form>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="upload-row" enctype="multipart/form-data">
            <input type="hidden" name="action" value="hd_add_rule">
            <input type="hidden" name="game" value="<?php echo $id; ?>">
            <input type="hidden" name="kind" value="pdf">
            <?php wp_nonce_field('hd_addrule_' . $id, 'hd_rule_nonce'); ?>
            <input type="text" name="label" placeholder="Popisek PDF">
            <input type="file" name="pdf" accept="application/pdf" required>
            <button type="submit" class="btn small">+ Nahrát PDF</button>
          </form>
        </div>
      <?php endif; ?>
    </section>
    <?php
}

function hd_handle_add_rule() {
    $gid = intval($_POST['game'] ?? 0);
    if (!$gid || !current_user_can('edit_post', $gid)) wp_die('Nemáš oprávnění.');
    if (empty($_POST['hd_rule_nonce']) || !wp_verify_nonce($_POST['hd_rule_nonce'], 'hd_addrule_' . $gid)) wp_die('Neplatný požadavek.');
    $rules = (array) get_post_meta($gid, 'rules', true);
    if (!is_array($rules)) $rules = [];
    $label = sanitize_text_field(wp_unslash($_POST['label'] ?? ''));
    $kind  = (($_POST['kind'] ?? '') === 'pdf') ? 'pdf' : 'link';
    if ($kind === 'pdf' && !empty($_FILES['pdf']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $att = media_handle_upload('pdf', $gid);
        if (!is_wp_error($att)) $rules[] = ['kind' => 'pdf', 'url' => wp_get_attachment_url($att), 'att' => (int) $att, 'label' => $label ?: get_the_title($att)];
    } else {
        $url = esc_url_raw(wp_unslash($_POST['url'] ?? ''));
        if ($url) $rules[] = ['kind' => 'link', 'url' => $url, 'label' => $label ?: 'Pravidla'];
    }
    update_post_meta($gid, 'rules', $rules);
    wp_safe_redirect(get_permalink($gid) . '#pravidla'); exit;
}
add_action('admin_post_hd_add_rule', 'hd_handle_add_rule');

function hd_handle_del_rule() {
    $gid = intval($_GET['game'] ?? 0); $idx = intval($_GET['idx'] ?? -1);
    if (!$gid || !current_user_can('edit_post', $gid)) wp_die('Nemáš oprávnění.');
    check_admin_referer('hd_delrule_' . $gid . '_' . $idx);
    $rules = (array) get_post_meta($gid, 'rules', true);
    if (isset($rules[$idx])) {
        if (!empty($rules[$idx]['att'])) wp_delete_attachment((int) $rules[$idx]['att'], true);
        unset($rules[$idx]);
        $rules = array_values($rules);
    }
    update_post_meta($gid, 'rules', $rules);
    wp_safe_redirect(get_permalink($gid) . '#pravidla'); exit;
}
add_action('admin_post_hd_del_rule', 'hd_handle_del_rule');
