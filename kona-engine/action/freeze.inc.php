<?php

function action_freeze_()
{
    // get page info
    $id   = konawiki_getPageId();
    if ($id <= 0) {
      konawiki_error("Page not found...");
      return;
    }
    $page = konawiki_getPage();
    $log  = konawiki_getLogFromId($id);
    $freeze = $log["freeze"];
    $label = ($freeze == 0) ? konawiki_lang('Freeze') 
                            : konawiki_lang('Unfreeze');
    $labelComp = ($freeze == 0) 
        ? konawiki_lang('Completed to Freeze.') 
        : konawiki_lang('Completed to Unfreeze.');
    $msg_back = konawiki_lang('Back');
    // check auth
    if (!konawiki_auth()) {
        konawiki_error("Sorry, You do not have permission.");
        return;
    }
    // check password
    $pass = konawiki_param("pass", "");
    $err = "";
    if ($pass != "") {
      $adminkey = konawiki_private("admin.key");
      if (konawiki_checkPassword($pass, $adminkey)) {
        _freeze($freeze, $id);
        konawiki_showMessage(
          "<h3>{$labelComp}</h3>".
          "<div><a href='index.php?$id&go'>→{$msg_back}</a></div>"
        );
        exit;
      }
      $err = konawiki_lang('Invalid password.')."<br>";
    }
    // freeze
    _form($label, $page, $err);
}

function _form($label, $page, $err) 
{
    // freeze form
    $page2 = urlencode($page);
    $msg_pass = konawiki_lang('Please input admin password.');
    $html = <<< EOS
<h3>{$label}</h3>
<div>
<span class="error">$err</span>
{$msg_pass}<br>
<form action="index.php?{$page2}&amp;freeze" method="POST">
<input type="password" name="pass">
<input type="submit" value="$label">
</form>
</div>
EOS;
    konawiki_showMessage($html);
}

function _err($msg)
{
    konawiki_error($msg);
    exit;
}

function _freeze($freeze, $id)
{
  konawiki_clearCache();
  $act = ($freeze == 0) ? 1 : 0;
  $sql = "UPDATE logs SET freeze=$act WHERE id=$id";
  $db = konawiki_getDB();
  $db->begin();
  $db->exec($sql); 
  $db->commit();  
}


