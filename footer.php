<?php if (!defined('ABSPATH')) exit; ?>
</main>
<button type="button" id="toTop" aria-label="Nahoru" title="Nahoru" hidden>↑</button>
<script>
(function(){
  var b=document.getElementById('toTop');
  function up(){window.scrollTo({top:0,behavior:'smooth'});}
  if(b){
    function upd(){
      var atTop=window.pageYOffset<400;
      var atBottom=(window.innerHeight+window.pageYOffset)>=(document.documentElement.scrollHeight-90);
      b.hidden=atTop||atBottom;
    }
    window.addEventListener('scroll',upd,{passive:true});
    window.addEventListener('resize',upd,{passive:true});
    upd();
    b.addEventListener('click',up);
  }
  document.addEventListener('click',function(e){ if(e.target.closest('.js-scrolltop')){ e.preventDefault(); up(); } });
})();
</script>
<?php wp_footer(); ?>
</body>
</html>
