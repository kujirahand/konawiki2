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

  <div id="wikibody" class="pure-u-1 pure-u-md-19-24">
    <?php echo $body_all ?>
  </div><!-- end of #wikibody -->
    
  <div id="wikisidebar" class="pure-u-1 pure-u-md-5-24">
    <nav><?php echo konawiki_getContents("SideBar"); ?></nav>
  </div><!-- end of #wikisidebar -->
  
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






