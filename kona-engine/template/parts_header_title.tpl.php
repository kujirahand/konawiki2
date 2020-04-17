<header>
<div id="wikiheader"><div class="headerpad">
<h1>
  <a class="title" href="<?php echo $baseurl?>">
    <img id="wiki-main-logo"
     src="<?php echo $logo ?>" alt="<?php echo $title?>" />
    <?php if (konawiki_public('header.title.visible', TRUE)): ?>
    <?php echo $title; ?><br/>
    <?php endif; ?>
  </a>
  <?php if (konawiki_getPage() != konawiki_public("FrontPage")): ?>
  <div class="pagename-div">
    <span class="pagename"><?php echo $pagelink?></span>
  </div>
  <?php endif; ?>
</h1>

<div class="clear"></div>

<?php if($navibar):?>
<div id="navibar"><div class="navibar">
  <?php echo $navibar; ?>
  <div class="clear"></div>
</div></div>
<?php endif;?>

</div></div><!-- end of #wikiheader -->
</header>

<!-- drawer.begin -->
<div id="drawer_wrapper">
  <!-- ハンバーガーメニュー -->
  <div id="hamburger_icon">
        <span class="yum"></span>
        <span class="yum"></span>
        <span class="yum"></span>
    <!-- 飛び出すメニュー -->
    <nav class="menuitems">
      <?php if (konawiki_isLogin_write()): ?>
        <?php echo  konawiki_getEditMenu('top') ?>
        <?php echo konawiki_getContents("GlobBar"); ?>
      <?php else: ?>
        <?php echo konawiki_getContents("GlobBar"); ?>
        <?php echo  konawiki_getEditMenu('top') ?>
      <?php endif; ?>
    </nav>
  </div>
  <!-- 透明な背景ウィンドウ(閉じる専用) -->
  <div id="drawer_background"></div>
</div>


