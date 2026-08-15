<?php
/**
 * Template Name: To Do
 * Hlášení chyb / úkoly – přidání (popis + fotka), admin odškrtává a maže.
 */
if (!defined('ABSPATH')) exit;
get_header();

if (!is_user_logged_in()) { echo '<p>Musíš být přihlášen.</p>'; get_footer(); return; }
$is_admin = hd_can_manage();
$flash = $_GET['hd_todo'] ?? '';

$msgs = [
    'ok'    => ['ok',  '✅ Úkol byl přidán. Díky za nahlášení!'],
    'del'   => ['ok',  '🗑️ Úkol byl smazán.'],
    'empty' => ['err', 'Napiš prosím popis problému.'],
    'err'   => ['err', 'Úkol se nepodařilo uložit.'],
];
if (isset($msgs[$flash])) echo '<div class="hd-flash ' . $msgs[$flash][0] . '">' . $msgs[$flash][1] . '</div>';

$all = get_posts(['post_type' => 'hd_todo', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC']);
$open = []; $done = [];
foreach ($all as $t) { (get_post_meta($t->ID, 'hd_done', true) === '1') ? $done[] = $t : $open[] = $t; }
?>

<h1>📝 To Do</h1>
<p class="hd-hint">Narazil/a jsi na chybu nebo něco, co by chtělo upravit? Napiš to sem – klidně přidej i fotku (screenshot z mobilu).</p>

<section class="card acc-card" style="margin-bottom:22px">
  <h2>➕ Nahlásit</h2>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="acc-form">
    <input type="hidden" name="action" value="hd_add_todo">
    <?php wp_nonce_field('hd_add_todo', 'hd_todo_nonce'); ?>
    <label class="hd-fld">Popis problému<textarea name="desc" rows="3" required placeholder="Např. Na mobilu se v deníku překrývá poznámka…"></textarea></label>
    <label class="hd-fld">Fotka <span class="hd-hint">(nepovinné)</span><input type="file" name="photo" accept="image/*"></label>
    <div class="hd-modal-actions"><button type="submit" class="btn">Odeslat</button></div>
  </form>
</section>

<?php
// vykreslení jednoho úkolu
function hd_render_todo($t, $is_admin) {
    $done = get_post_meta($t->ID, 'hd_done', true) === '1';
    $author = get_userdata($t->post_author);
    $who = $author ? $author->display_name : '—';
    $img = get_the_post_thumbnail_url($t->ID, 'large');
    ?>
    <div class="todo-item card <?php echo $done ? 'is-done' : ''; ?>">
      <div class="todo-main">
        <div class="todo-status"><?php echo $done ? '✅' : '❗'; ?></div>
        <div class="todo-body">
          <div class="todo-desc"><?php echo nl2br(esc_html($t->post_content)); ?></div>
          <div class="todo-meta"><?php echo esc_html($who); ?> · <?php echo esc_html(get_the_date('j. n. Y H:i', $t)); ?></div>
          <?php if ($img): ?>
            <a class="todo-photo" href="<?php echo esc_url($img); ?>" target="_blank" rel="noopener">
              <img src="<?php echo esc_url($img); ?>" alt="Fotka k úkolu" loading="lazy">
            </a>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($is_admin): ?>
        <div class="todo-tools">
          <a class="btn small <?php echo $done ? 'ghost' : ''; ?>" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_toggle_todo&id=' . $t->ID), 'hd_toggle_todo_' . $t->ID)); ?>"><?php echo $done ? '↩︎ Vrátit' : '✓ Vyřešeno'; ?></a>
          <a class="btn small back" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_delete_todo&id=' . $t->ID), 'hd_del_todo_' . $t->ID)); ?>" onclick="return confirm('Opravdu smazat tento úkol?')">🗑️</a>
        </div>
      <?php endif; ?>
    </div>
    <?php
}
?>

<?php if ($open): ?>
  <h2 class="todo-sec">❗ K vyřešení (<?php echo count($open); ?>)</h2>
  <?php foreach ($open as $t) hd_render_todo($t, $is_admin); ?>
<?php endif; ?>

<?php if ($done): ?>
  <h2 class="todo-sec todo-sec-done">✅ Vyřešené (<?php echo count($done); ?>)</h2>
  <?php foreach ($done as $t) hd_render_todo($t, $is_admin); ?>
<?php endif; ?>

<?php if (!$open && !$done): ?>
  <div class="empty card" style="padding:40px 20px">Zatím žádné úkoly. 🎉</div>
<?php endif; ?>

<?php get_footer(); ?>
