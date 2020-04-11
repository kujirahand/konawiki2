<header>
<div id="wikiheader"><div class="headerpad">
    
<!-- #wikiheader h1 -->  
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

<!-- global navi -->
<div id="wrapper">
  <div id="btn-gnavi">
        <span></span>
        <span></span>
        <span></span>
    <nav class="global-navi">
      <?php echo  konawiki_getEditMenu('top') ?>
      <?php echo konawiki_getContents("MenuBar"); ?>
    </nav>
  </div>
  <div id="closeWindow"></div>
</div>
<script type="text/javascript"
 src="<?php echo getResourceURL('drawer.js')?>"></script>




