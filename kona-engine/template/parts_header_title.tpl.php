<header><div id="wikiheader"><div class="headerpad">
<?php if(!useragent_is_smartphone()): ?>
  <h1>
    <span class="logo">
      <a href="<?php echo $baseurl?>">
        <img src="<?php echo $logo ?>" alt="<?php echo $title?>" />
        <?php echo $title?>
      </a>
      <?php if (konawiki_getPage() != konawiki_public("FrontPage")): ?>
      <span class="memo">/</span>
      <span class="pagename"><?php echo $pagelink?></span>
      <?php endif; ?>
    </span>
  </h1>
  <div class="menu">
    <div class='description'><?php echo $description?></div>
    <div class='description'><?php echo  konawiki_getEditMenu('top') ?></div>
  </div>
<?php else: ?>
  <div class="logo">
    <a href="<?php echo $baseurl?>">
      <img src="<?php echo $logo ?>" alt="<?php echo $title?>" />
      <?php echo $title?>
    </a>
  </div>
  <?php if (konawiki_getPage() != konawiki_public("FrontPage")): ?>
  <div class="pagename"><?php echo $pagelink?>
    <span class="memo">(<a href="<?php echo $backlink?>">*</a>)</span>
  </div>
  <?php endif; ?>
<?php endif; ?>
  <div class="clear"></div>
<?php if($navibar):?>
  <div id="navibar"><div class="navibar">
    <?php echo $navibar; ?>
    <div class="clear"></div>
  </div></div>
<?php else: ?>
<?php endif;?>
</div></div><!-- end of #wikiheader --></header>
