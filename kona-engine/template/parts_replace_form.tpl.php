<?php
$baseurl = konawiki_baseurl();
?>
<div id="replaceFormDiv" style="display:none;">
  <h6><a name="replaceFor">Replace Text:</a></h6>
  <form action="<?php echo $baseurl?>FrontPage/edit/command" method="post">
    <table>
    <tr>
      <td></td>
      <td><input type="checkbox" name="command_mode" value="replace_allpage"/> All Pages</td>
    </tr>
    <tr>
      <td>Search Text:</td>
      <td><input type="text" name="src" value=""/></td>
    </tr>
    <tr>
      <td>Replace Text:</td>
      <td><input type="text" name="des" value=""/></td>
    </tr>
    <tr>
      <td>Admin passowrd:</td>
      <td><input type="password" name="admin" value=""/></td>
    </tr>
    <tr>
      <td></td>
      <td><input type="submit" value="<?php echo konawiki_lang('Execute')?>" /></td>
    </tr>
    </table>
  </form>
</div><!-- end of #replaceFormDiv -->
