<?php
/**
 * Template Name: Můj účet
 * Změna hesla / e-mailu / jména + (pro adminy) přidání člena.
 */
if (!defined('ABSPATH')) exit;
get_header();

if (!is_user_logged_in()) { echo '<p>Musíš být přihlášen.</p>'; get_footer(); return; }
$u = wp_get_current_user();
$acc = $_GET['hd_acc'] ?? '';
$is_admin = hd_can_manage();
?>

<?php
// hlášky
$msgs = [
    'ok'         => ['ok',  '✅ Údaje byly uloženy.'],
    'passok'     => ['ok',  '🔒 Heslo bylo změněno. Zůstáváš přihlášen/a.'],
    'bademail'   => ['err', 'E-mail nemá platný tvar.'],
    'emailtaken' => ['err', 'Tento e-mail už používá jiný účet.'],
    'badcurrent' => ['err', 'Stávající heslo nesedí. Heslo nebylo změněno.'],
    'mismatch'   => ['err', 'Nová hesla se neshodují.'],
    'short'      => ['err', 'Nové heslo musí mít aspoň 6 znaků.'],
    'm_bad'      => ['err', 'Vyplň jméno a platný e-mail nového člena.'],
    'm_nologin'  => ['err', 'Vyplň uživatelské jméno nového člena.'],
    'm_login'    => ['err', 'Uživatelské jméno „' . esc_html($_GET['v'] ?? '') . '" už existuje. Zvol jiné.'],
    'm_email'    => ['err', 'E-mail „' . esc_html($_GET['v'] ?? '') . '" už má účet.'],
    'm_err'      => ['err', 'Účet se nepodařilo vytvořit.'],
];
if (isset($msgs[$acc])) echo '<div class="hd-flash ' . $msgs[$acc][0] . '">' . $msgs[$acc][1] . '</div>';
if ($acc === 'm_ok'):
    $tk = 'hd_newmember_' . get_current_user_id();
    $nm = get_transient($tk); delete_transient($tk);
    $ml = is_array($nm) ? ($nm['login'] ?? '') : '';
    $mp = is_array($nm) ? ($nm['pass'] ?? '') : ''; ?>
    <div class="hd-flash ok">
      ✅ Člen byl vytvořen. Předej mu přihlašovací údaje:
      <div class="acc-creds">
        <div>👤 Uživatelské jméno: <code><?php echo esc_html($ml); ?></code></div>
        <?php if ($mp): ?><div>🔑 Heslo: <code><?php echo esc_html($mp); ?></code> <span class="hd-hint">(zobrazí se jen teď – ulož si ho)</span></div>
        <?php else: ?><div class="hd-hint">Heslo sis nastavil/a ručně.</div><?php endif; ?>
        <div class="hd-hint">Přihlášení: <?php echo esc_url(wp_login_url()); ?></div>
      </div>
    </div>
<?php endif; ?>

<h1>⚙️ Můj účet</h1>

<div class="acc-grid">
  <section class="card acc-card">
    <h2>Moje údaje</h2>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="acc-form">
      <input type="hidden" name="action" value="hd_update_account">
      <?php wp_nonce_field('hd_update_account', 'hd_acc_nonce'); ?>

      <?php
        $my_pid   = hd_current_player_id($u->ID);
        $cur_nick = $my_pid ? hd_meta($my_pid, 'nick') : '';
        if ($cur_nick === '') $cur_nick = get_user_meta($u->ID, 'nickname', true);
      ?>
      <div class="acc-ro">Uživatelské jméno (nelze měnit): <strong><?php echo esc_html($u->user_login); ?></strong></div>

      <label class="hd-fld">Přezdívka <span class="hd-hint">(zobrazuje se u tvého hráče)</span><input type="text" name="nickname" value="<?php echo esc_attr($cur_nick); ?>" placeholder="Např. Kikuš"></label>

      <?php if ($is_admin): ?>
        <label class="hd-fld">Jméno a příjmení<input type="text" name="display_name" value="<?php echo esc_attr($u->display_name); ?>"></label>
        <label class="hd-fld">E-mail<input type="email" name="email" value="<?php echo esc_attr($u->user_email); ?>"></label>
      <?php else: ?>
        <div class="hd-fld">Jméno a příjmení <span class="hd-hint">(mění admin)</span><div class="pf-email-ro"><?php echo esc_html($u->display_name); ?></div></div>
        <div class="hd-fld">E-mail <span class="hd-hint">(mění admin)</span><div class="pf-email-ro"><?php echo esc_html($u->user_email); ?></div></div>
      <?php endif; ?>

      <h3 class="acc-sub">Změna hesla <span class="hd-hint">(nech prázdné, pokud heslo měnit nechceš)</span></h3>
      <label class="hd-fld">Stávající heslo<input type="password" name="current_pass" autocomplete="current-password"></label>
      <label class="hd-fld">Nové heslo<input type="password" name="new_pass" autocomplete="new-password"></label>
      <label class="hd-fld">Nové heslo znovu<input type="password" name="new_pass2" autocomplete="new-password"></label>

      <div class="hd-modal-actions"><button type="submit" class="btn">Uložit změny</button></div>
    </form>
    <p class="hd-hint">Zapomenuté heslo řeš přes <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">obnovení hesla</a> na přihlašovací stránce.</p>
  </section>

  <?php if ($is_admin): ?>
    <section class="card acc-card">
      <h2>➕ Přidat člena</h2>
      <p class="hd-hint">Vytvoří nový účet pro kamaráda. Uživatelské jméno a heslo mu pak předáš.</p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="acc-form">
        <input type="hidden" name="action" value="hd_add_member">
        <?php wp_nonce_field('hd_add_member', 'hd_member_nonce'); ?>
        <label class="hd-fld">Jméno a příjmení<input type="text" name="m_name" required placeholder="Např. Anna Nováková"></label>
        <label class="hd-fld">E-mail<input type="email" name="m_email" required placeholder="anicka@email.cz"></label>
        <label class="hd-fld">Uživatelské jméno<input type="text" name="m_login" required placeholder="anicka"></label>
        <label class="hd-fld">Heslo <span class="hd-hint">(nepovinné – když necháš prázdné, systém ho vygeneruje a ukáže)</span><input type="text" name="m_pass" placeholder="necháš-li prázdné, vygeneruje se"></label>
        <label class="acc-check"><input type="checkbox" name="m_make_player" value="1" checked> Vytvořit i hráče a spárovat přes e-mail</label>
        <div class="hd-modal-actions"><button type="submit" class="btn">Vytvořit člena</button></div>
      </form>

      <?php
        $members = get_users(['orderby' => 'display_name', 'order' => 'ASC']);
        if ($members): ?>
        <h3 class="acc-sub">Členové (<?php echo count($members); ?>)</h3>
        <ul class="acc-members">
          <?php foreach ($members as $m):
            $admin_flag = user_can($m, 'manage_options'); ?>
            <li>
              <span class="acc-m-name"><?php echo esc_html($m->display_name); ?></span>
              <span class="acc-m-login">@<?php echo esc_html($m->user_login); ?></span>
              <span class="acc-m-mail"><?php echo esc_html($m->user_email); ?></span>
              <?php if ($admin_flag) echo '<span class="me-tag">admin</span>'; ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="hd-hint">Podrobná správa účtů (mazání, změna rolí) je ve WordPressu → Uživatelé.</p>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>

<?php get_footer(); ?>
