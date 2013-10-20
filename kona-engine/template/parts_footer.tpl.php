<!-- footer.begin -->
<div id="wikifooter">
<div class="menu"><?php echo  konawiki_getEditMenu() ?></div>
<?php if (isset($ctime_html)): ?>
  <?php if ($ctime_html != $mtime_html):?>
    <div class="pageinfo">
      <?php echo konawiki_lang('Created time','Created').": ".$ctime_html?>
      /<?php echo konawiki_lang('Updated time','Updated').': '.$mtime_html?></div>
  <?php else: ?>
      <?php echo konawiki_lang('Created time','Created').": ".$ctime_html?>
  <?php endif; ?>
<?php endif ?>
<div class="menu"><?php
  // URLの表示
  $id = konawiki_getPageId();
  $page = htmlspecialchars(konawiki_param("page"), ENT_QUOTES);
  $short_url = konawiki_getPageURL($id, "go");
  $long_url = konawiki_getPageURL2();
  echo "<p><a href='$short_url'>$short_url</a></p>";
  echo "<p><span style='background-color:#ffffcc;color:gray;'>";
  echo "<a href='$long_url'>$page</a></span></p>";
?></div>
<?php if (konawiki_public("text.link.visible")): ?>
  <div class="pageinfo">
    <ul>
      <li><a href="<?php echo konawiki_getPageURL(konawiki_getPage(),"text")?>">テキスト形式で見る</a></li>
      <li><a href="<?php echo konawiki_getPageURL(konawiki_getPage(),"","","noanchor=1")?>">アンカーなしで表示</a></li>
      <li><a href="<?php echo konawiki_getPageURL(konawiki_getPage(),"bloghtml")?>">ブログ用HTMLを見る</a></li>
    </ul>
  </div>
<?php endif ?>

<div><?php echo $title?> by <?php echo $author?> <?php echo $rsslink?></div>
<div><a href="http://konawiki.aoikujira.com">konawiki <?php echo $KONAWIKI_VERSION?></a></div>
</div>
<?php
    $s = konawiki_private("footer.analytics");
    if ($s) { echo "<!-- analytics -->\n{$s}\n"; }
?>
<!-- footer.end -->
<?php 
// for DEBUG
if (konawiki_is_debug()) { 
  echo "<div class='message'>";
  konawiki_page_debug(); 
  echo "</div>";
} 
?>
</body>
</html>
