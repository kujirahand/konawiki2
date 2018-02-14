<?php
$page = konawiki_getPage();
$edit_url = konawiki_getPageURL($page, 'edit', 'command');
?>
<h6>batch: (<a target="_new" href="https://kujirahand.com/konawiki/go.php?21">description</a>)</h6>
<form action="<?php echo $edit_url?>" method="post">
<input type="hidden" name="command_mode" value="batch" />
<table>
<tr>
  <td>Batch:</td>
  <td><textarea name="batch_ta" rows="10" cols="60"></textarea></td>
</tr>
<tr>
  <td>Admin password:</td>
  <td><input type="password" name="admin" value=""/></td>
</tr>
<tr>
  <td></td>
  <td><input type="submit" value="<?php echo konawiki_lang('Execute') ?>" /></td>
</tr>
</table>
</form>
