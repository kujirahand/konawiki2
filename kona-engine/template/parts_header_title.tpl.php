<header>
<div id="wikiheader"><div class="headerpad">
  
  <h1 id="wiki-title-top">
    <span class="logo">
      <a href="<?php echo $baseurl?>">
        <img src="<?php echo $logo ?>" alt="<?php echo $title?>" />
        <?php if(konawiki_public('header.title.visible')) { echo $title; } ?>
      </a>
      <?php if (konawiki_getPage() != konawiki_public("FrontPage")): ?>
      <!-- pagename -->
        <span class="memo">/</span>
        <span class="pagename"><?php echo $pagelink?></span>
      </div>
      <?php endif; ?>
    </span>
  </h1>
  
  <div class="menu">
    <div class='description'><?php echo  konawiki_getEditMenu('top') ?></div>
  </div>

  <div class="clear"></div>

  <?php if($navibar):?>
  <div id="navibar"><div class="navibar">
    <?php echo $navibar; ?>
    <div class="clear"></div>
  </div></div>
  <?php endif;?>

</div></div><!-- end of #wikiheader -->
</header>

