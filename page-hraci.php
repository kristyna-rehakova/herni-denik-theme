<?php
/**
 * Template Name: Hráči
 * Seznam hráčů + přidání/úprava/mazání z webu.
 */
if (!defined('ABSPATH')) exit;
get_header();

$players = get_posts(['post_type' => 'hrac', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
$can_add    = is_user_logged_in();   // přidat hráče smí každý přihlášený
$can_manage = hd_can_manage();       // upravit / smazat jen admin
$me_player  = hd_current_player_id(); // svého hráče smí upravit i člen
?>
<?php if (isset($_GET['hd_pl'])): ?>
  <div class="hd-flash ok"><?php echo $_GET['hd_pl'] === 'del' ? '🗑️ Hráč byl přesunut do koše.' : '✅ Hráč byl uložen.'; ?></div>
<?php endif; ?>

<?php if ($can_add): ?>
  <p><button type="button" class="btn big js-open-player">➕ Přidat hráče</button></p>
<?php endif; ?>

<?php if ($players): ?>
  <div class="players-grid">
    <?php foreach ($players as $p): $pid = $p->ID; ?>
      <div class="player-card">
        <?php echo hd_player_avatar($pid, 48); ?>
        <div class="who">
          <div class="nick"><?php echo esc_html(hd_player_name($pid)); ?></div>
          <?php $nick = hd_meta($pid, 'nick'); if ($nick && $nick !== get_the_title($pid)): ?>
            <div class="name"><?php echo esc_html(get_the_title($pid)); ?></div>
          <?php endif; ?>
        </div>
        <?php $mine = ($me_player && $pid == $me_player); if ($can_manage || $mine): ?>
          <div class="pl-tools">
            <?php if ($mine && !$can_manage) echo '<span class="me-tag">to jsem já</span>'; ?>
            <button type="button" class="js-edit-player" data-hd="<?php echo hd_player_edit_json($pid); ?>" title="Upravit">✏️</button>
            <?php if ($can_manage): ?>
              <a class="js-del-player" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hd_delete_player&id=' . $pid), 'hd_delplayer_' . $pid)); ?>" data-name="<?php echo esc_attr(hd_player_name($pid)); ?>" title="Smazat">🗑️</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="empty card" style="padding:50px 20px">Zatím žádní hráči. Přidej prvního!</div>
<?php endif; ?>

<?php get_footer(); ?>
