<?php
include konawiki_template('parts_header.tpl.php');

$link = array(
  array(
    'url'   => konawiki_getPageURL('MenuBar', 'edit'),
    'label' => konawiki_lang('Edit MenuBar'),
  ),
  array(
    'url'   => konawiki_getPageURL('SideBar', 'edit'),
    'label' => konawiki_lang('Edit SideBar'),
  ),
  array(
    'url'   => konawiki_getPageURL('NaviBar', 'edit'),
    'label' => konawiki_lang('Edit NaviBar'),
  )
);
?>
<div id="wikimessage"><div class="bodypad">
<div class="message">
<h4>新規</h4>

<form action="<?php echo $baseurl?>" class="pure-form">
<p>
  <input type="text" name="page" value="<?php echo htmlspecialchars($page); ?>" size="40" id="title_txt"/>
  <input type="hidden" name="action" value="edit" />
  <input type="submit" value="編集" class="pure-button pure-button-primary" />
</p>
</form>

<div class="contents">
<ul>
<?php
  foreach ($link as $row) {
    $url = $row['url'];
    $lbl = $row['label'];
    echo "<p><a class='pure-button' href='$url'>$lbl</a></p>";
  }
?>
</ul>
</div>

</div><!-- message -->
</div></div>
<p class="clear"></p>
<?php
include konawiki_template('parts_footer.tpl.php');
?>
