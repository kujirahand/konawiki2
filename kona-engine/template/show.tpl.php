<?php
// ---------------------------------------------------------------------
// template main body
// ---------------------------------------------------------------------
// show.tpl.php
// action/show.inc.php から呼び出される
// ---------------------------------------------------------------------
// header
include_once(getSkinPath('parts_header.tpl.php'));
// ---------------------------------------------------------------------
?>
<div class="clear"></div>

<div id="wikicontent" class="pure-g">

  <div id="wikibody" class="pure-u-1 pure-u-md-17-24">
    <?php echo $body_all ?>
  </div><!-- end of #wikibody -->
    
  <div id="wikinavi" class="pure-u-1 pure-u-md-7-24">
    <div id="menubar" class="menubar">
      <nav><?php echo konawiki_getContents("MenuBar"); ?></nav>
    </div><!-- end of .menubar -->
  </div><!-- end of #wikinavi -->
  
</div><!-- end of #wikicontent -->


<div class="clear"></div>

<?php
// ---------------------------------------------------------------------
// footer
include_once(getSkinPath('parts_footer.tpl.php'));
// ---------------------------------------------------------------------






