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

<div id="wikicontent"><div class="contentpad">

  <div class="LayoutCenterLeft">

    <div id="wikibody"><div class="bodypad">
          <?php echo $body_all ?>
    </div></div><!-- end of #wikibody -->
    
    <div id="wikinavi"><div class="navipad">
      <div class="menubar">
          <?php echo konawiki_getContents("MenuBar"); ?>
      </div><!-- end of .menubar -->
    </div></div><!-- end of #wikinavi -->
  
  </div><!-- end of LayoutCenterLeft -->
  
  <div id="wikisidebar"><div class="barpad">
    <div class="rightbar">
        <?php echo konawiki_getContents("SideBar"); ?>
    </div>
  </div></div><!-- end of #wikisidebar -->

</div></div><!-- end of #wikicontent -->

<div class="clear"></div>
      
<?php
// ---------------------------------------------------------------------
// footer
include_once(getSkinPath('parts_footer.tpl.php'));
// ---------------------------------------------------------------------






