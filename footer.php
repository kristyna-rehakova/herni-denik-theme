<?php if (!defined('ABSPATH')) exit; ?>
</main>
<button type="button" id="toTop" aria-label="Nahoru" title="Nahoru" hidden>↑</button>
<script>
(function(){
  var b=document.getElementById('toTop');
  function up(){window.scrollTo({top:0,behavior:'smooth'});}
  if(b){
    window.addEventListener('scroll',function(){b.hidden=window.pageYOffset<400;},{passive:true});
    b.addEventListener('click',up);
  }
  document.addEventListener('click',function(e){ if(e.target.closest('.js-scrolltop')){ e.preventDefault(); up(); } });
})();
</script>
<?php wp_footer(); ?>
</body>
</html>
