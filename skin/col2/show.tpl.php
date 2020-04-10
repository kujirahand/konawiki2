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

<!-- skin "col2" -->

<div id="wikicontent">

  <div id="wikibody">
    <?php echo $body_all ?>
  </div><!-- end of #wikibody -->
  
</div><!-- end of #wikicontent -->

<div id="wikinavi">
  <a href="#" id="menuLink" class="menu-link">
      <!-- Hamburger icon -->
      <span></span>
  </a>
  <div id="menubar" class="menubar">
    <nav><?php echo konawiki_getContents("MenuBar"); ?></nav>
  </div><!-- end of .menubar -->
</div><!-- end of #wikinavi -->

<div class="clear"></div>

<!-- for sidemenu -->
<link rel="stylesheet" type="text/css" href="<?php echo getResourceURL('side-menu.css') ?>" />
<script type="text/javascript" src="<?php echo getResourceURL('side-menu-ui.js');?>"></script>

<?php
// ---------------------------------------------------------------------
// footer
include_once(getSkinPath('parts_footer.tpl.php'));
// ---------------------------------------------------------------------






