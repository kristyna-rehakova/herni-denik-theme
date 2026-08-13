<?php
/**
 * Vzhled přihlašovací stránky (wp-login.php) v duchu šablony.
 */
if (!defined('ABSPATH')) exit;

add_filter('login_headerurl', function () { return home_url('/'); });
add_filter('login_headertext', function () { return get_bloginfo('name'); });

add_action('login_enqueue_scripts', function () {
    wp_enqueue_style('hd-login-font', 'https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&display=swap', [], null);
    ?>
    <style>
      body.login{
        background:#eef3e0;
        background-image:radial-gradient(circle at 15% 8%,#f2f5da 0,transparent 42%),radial-gradient(circle at 88% 82%,#e3edcf 0,transparent 40%);
        font-family:"Segoe UI",system-ui,-apple-system,sans-serif;color:#3f4536;
      }
      #login{width:342px;padding-top:6%}
      #login h1 a{
        background:none!important;width:auto;height:auto;text-indent:0;overflow:visible;
        font-family:'Baloo 2',"Segoe UI",sans-serif;font-size:2.1rem;font-weight:600;color:#5a4712;line-height:1.2;
      }
      #login h1 a::before{content:"🎲 "}
      #loginform,#registerform,#lostpasswordform,#resetpassform{
        background:#fff;border:1px solid #dde5cc;border-radius:16px;box-shadow:0 6px 18px rgba(70,80,50,.10);padding:26px 24px;
      }
      .login label{color:#3f4536;font-weight:600}
      .login input[type=text],.login input[type=password],.login input[type=email]{
        border:1px solid #dde5cc;border-radius:10px;padding:10px 12px;font-size:1rem;background:#fff;
      }
      .login input[type=text]:focus,.login input[type=password]:focus,.login input[type=email]:focus{
        border-color:#eeb088;box-shadow:0 0 0 3px rgba(238,176,136,.28);outline:none;
      }
      .wp-core-ui .button-primary{
        background:#eeb088;border:none;color:#3f4536;border-radius:10px;font-weight:600;
        box-shadow:none;text-shadow:none;padding:8px 20px;height:auto;transition:.15s;
      }
      .wp-core-ui .button-primary:hover,.wp-core-ui .button-primary:focus{background:#e6a274;color:#3f4536;box-shadow:none}
      .login #nav a,.login #backtoblog a{color:#5e8b6e}
      .login #nav a:hover,.login #backtoblog a:hover{color:#46503a}
      .login .message,.login .success{border-left-color:#5e8b6e;border-radius:10px}
      .login #login_error{border-left-color:#c05f49;border-radius:10px}
      .login form .forgetmenot label{font-weight:400}
      .login .privacy-policy-page-link{margin-top:14px}
      /* pryč přepínač jazyka a odkaz Zpět (web je jen pro přihlášené) */
      .login .language-switcher,.login #backtoblog{display:none}
    </style>
    <?php
});
