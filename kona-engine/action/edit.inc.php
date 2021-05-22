<?php
/*
 * Edit Action
 */
header('X-Frame-Options: SAMEORIGIN');

function action_edit_()
{
  // get page info
  $id   = konawiki_getPageId();
  $page = konawiki_getPage();
  $log  = konawiki_getLogFromId($id);
  if (isset($log['body'])) {
    $log["hash"] = md5($log["body"]);
  } else {
    $log["hash"] = "";
  }

  // check auth
  if (!konawiki_auth()) {
    konawiki_error(konawiki_lang('Sorry, You do not have permission.'));
    return;
  }

  // check freeze
  if (isset($log["freeze"]) && $log["freeze"] > 0) {
    $link = konawiki_getPageURL($page, "freeze");
    konawiki_error(
      "<div>".
      konawiki_lang('Sorry, This page is freezing.').
      "</div>".
      "<div><a href='$link'>→".
      konawiki_lang('Unfreeze').
      "</a></div>"
    );
    exit;
  }

  // check edit_token for clickjacking
  $checkResult = konawiki_checkEditToken();
  if (!$checkResult) {
    $label = konawiki_lang('Edit');
    $edit_token = konawiki_getEditToken();
    $edit_link = konawiki_getPageURL2($page, "edit", "", "edit_token=".$edit_token);
    konawiki_showMessage(
      "<div><h3>{$label}:</h3><p>".
      "<a class=\"pure-button pure-button-primary\" href=\"$edit_link\">{$label}</a>".
      "</p></div>");
    exit;
  }

  // cache
  konawiki_clearCache();
  // create auth hash
  $login_auth_hash = base64_encode(random_bytes(256));
  konawiki_setAuthHash($id, $login_auth_hash);
  // show editor
  $log["login_auth_hash"] = $login_auth_hash;
  _checkParam($log);
  include_template("edit.html", $log);
}

function _checkParam(&$log) {
    if (empty($log['id'])) $log['id'] = "";
    if (empty($log['body'])) $log['body'] = "";
    if (empty($log['mtime'])) $log['mtime'] = time();
    if (empty($log['error_message'])) $log['error_message'] = "";
    if (empty($log['conflict_body'])) $log['conflict_body'] = "";
    if (empty($log['hash'])) $log['hash'] = "";
    if (empty($log['login_auth_hash'])) $log['login_auth_hash'] = "";
    if (empty($log['private'])) $log['private'] = 0;
    $log['private_chk'] = ($log['private']) ? "checked" : "";
    $log['url_page_edit_js'] = getResourceURL('konawiki_page_edit.js', TRUE);
    if (empty($log['tag'])) $log['tag'] = "";
}

function _err($msg)
{
  konawiki_error($msg);
  exit;
}

function action_edit_update()
{
  // login check (1) auth hash
  $id = konawiki_getPageId();
  $svr_auth_hash = konawiki_getAuthHash($id);
  $usr_auth_hash = konawiki_param("login_auth_hash", "");
  if ($svr_auth_hash == $usr_auth_hash) {
    // login ok
  }
  // login check (2) auth form
  else if(!konawiki_auth()) {
    _err(konawiki_lang('Sorry, You do not have permission.'));
    exit;
  }
  konawiki_clearCache();
  $page = konawiki_getpage();
  if ($page == "") {
    _err('Page name is empty...'); exit;
  }
  $body = konawiki_param("body");
  $tag  = trim(konawiki_param('tag'));
  if (trim($body) === "") {
    $m = konawiki_param("editmode", FALSE);
    if ($m === "delete") {
      action_edit_delete();
      return;
    }
  }
  // write page
  $hash = konawiki_param("hash");
  $private = konawiki_param("private_chk", 0);
  if ($hash === "") $hash = FALSE;
  $r = konawiki_writePage($body, $err, $hash, $tag, $private);
  if (FALSE === $r) {
    $log = konawiki_getLog($page);
    $log["error_message"] = "<div class='error'>$err</div>";
    $body_ = $body;
    include_once(KONAWIKI_DIR_LIB."/konawiki_diff.inc.php");
    if (konawiki_diff_checkConflict($body_, $log["body"])) {
      $body = htmlspecialchars($body, ENT_QUOTES);
      $log["conflict_body"] =
          "<div class='desc'>".konawiki_lang('Now your writing').":</div>".
          "<textarea id='conflict_text' class='difftext' cols='84' rows='8'>{$body}</textarea>".
          "<div><input type='button' value='Change' id='conflict_copy_edit_btn'></div>".
          "<div class='desc'>".konawiki_lang('Old writing with diff').":</div>";
    }
    $log["body"] = $body_;
    _checkParam($log);
    include_template("edit.html", $log);
    return;
  }
  $pageurl = konawiki_getPageURL($page);
  konawiki_jump("{$pageurl}");
}

function action_edit_delete()
{
  // 削除モード
  if (!konawiki_auth()) {
    _err(konawiki_lang('Sorry, You do not have permission.'));
  }
  konawiki_clearCache();
  $page = konawiki_getPage();
  $log = konawiki_getLog($page);
  // check id
  if (!isset($log['id'])) {
    konawiki_error("Page not found...");
    return;
  }
  // check hash
  $hash = konawiki_param("hash");
  $log_hash = md5($log['body']);
  if ($hash !== $log_hash) {
    konawiki_error(
      konawiki_lang('This page edited by another person.')
    );
    return;
  }
  // execute
  $log_id = $id = $log['id'];
  db_begin();
  // get attach files
  $sql = "SELECT * FROM attach WHERE log_id=?";
  $attach_ary = db_get($sql, [$log_id]);
  // delete logs
  $sql = "DELETE FROM logs where id=?";
  db_exec($sql, [$log_id]);
  // delete log_counters
  $sql = "DELETE FROM log_counters where id=?";
  db_exec($sql, [$log_id]);
  // delete tag
  $sql = "DELETE FROM tags WHERE log_id=?";
  db_exec($sql, [$log_id]);
  // delete attach db
  $sql = "DELETE FROM attach WHERE log_id=?";
  db_exec($sql, [$log_id]);
  // delete attach counters
  foreach ($attach_ary as $row) {
    $aid = $row["id"];
    $sql = "DELETE FROM attach_counters WHERE id=?";
    db_exec($sql, [$aid]);
  }
  db_commit();
  // remove attach files
  $del_count = 0;
  $del_files = array();
  foreach ($attach_ary as $row) {
    if (empty($row['id'])) continue;
    $id = $row['id'];
    $file = KONAWIKI_DIR_ATTACH."/".$id;
    if (@unlink($file)) {
      $del_count++;
      $del_files[] = "-".htmlspecialchars($row['name']);
    }
  }
  //
  $page_ = htmlspecialchars($page);
  $body =
      konawiki_lang('Success to remove.').
      " : [$page_]<br/>".
      sprintf(konawiki_lang('Deleted %d attachment files.'), $del_files)."<br/>";
  konawiki_showMessage($body);
}

function action_edit_api__write()
{
  // login check (1) auth hash
  $id = konawiki_getPageId();
  $svr_auth_hash = konawiki_getAuthHash($id);
  $usr_auth_hash = konawiki_param("login_auth_hash", "");
  if ($svr_auth_hash == $usr_auth_hash) {
    // login ok
  }
  // login check (2) auth form
  else if (!konawiki_auth()) {
    header("Content-Type: text/plain; charset=UTF-8");
    echo "ng\nPemission denied!";
    exit;
  }
  konawiki_clearCache();
  
  $page = konawiki_getPage();
  $body = konawiki_param("body");
  $tag  = konawiki_param("tag");
  $hash = konawiki_param("hash");
  $private = konawiki_param("private_chk", 0);
  // output
  header("Content-Type: text/plain; charset=UTF-8");
  if ($hash === "") { $hash = FALSE; }
  $r = konawiki_writePage($body, $err, $hash, $tag, $private);
  if (FALSE === $r) {
    echo "ng\n$err\n";
    exit;
  }
  $hash = md5($body);
  echo "ok\n{$hash}\n";
  exit;
}

function action_edit_log()
{
    if (!konawiki_auth()) {
        konawiki_error(konawiki_lang('Sorry, You do not have permission.'));
    }
    konawiki_clearCache();

    $b_id = konawiki_param('id', FALSE);
    if ($b_id == FALSE) {
        konawiki_error('id not set.');
        return;
    }
    $page = konawiki_getPage();
    $blog = konawiki_getBackupLog($b_id);
    $clog = konawiki_getLog();
    //
    include_once(KONAWIKI_DIR_LIB."/konawiki_diff.inc.php");
    $chk = $blog["body"];
    if (konawiki_diff_checkConflict($chk, $clog["body"],"diffonly")) {
        $blog["conflict_body"] = konawiki_parser_convert($chk);
    }
    _checkParam($blog);
    include_template("edit.html", $blog);
}

function action_edit_removebackup()
{
    if (!konawiki_auth()) {
        konawiki_error(konawiki_lang('Sorry, You do not have permission.'));
    }
    konawiki_clearCache();

    $cmd = konawiki_param('cmd');
    if ($cmd !== "removebackup") {
        konawiki_error('コマンドの妥当性がチェックできません。');
        return;
    }
    $log_id = intval(konawiki_getPageId());
    if ($log_id === FALSE || $log_id <= 0) {
        konawiki_error('ページがありません。');
        return;
    }
    $bk = konawiki_getBackupDB();
    $sql = "DELETE FROM oldlogs WHERE log_id=$log_id";
    if ($bk->exec($sql)) {
        konawiki_showMessage('履歴を削除しました。');
        return;
    }
    else {
        konawiki_error('失敗しました。');
        return;
    }
}

function action_edit_command()
{
  if (!konawiki_auth()) {
    konawiki_error(
      konawiki_lang('Sorry, You do not have permission.'));
  }
  // check edit_token for clickjacking
  $checkResult = konawiki_checkEditToken();
  if (!$checkResult) {
    $label = konawiki_lang('Edit');
    $edit_token = konawiki_getEditToken();
    $edit_link = konawiki_getPageURL2($page, "edit", "", "edit_token=".$edit_token);
    konawiki_showMessage(
      "<div><h3>{$label}:</h3><p>".
      "<a class=\"pure-button pure-button-primary\" href=\"$edit_link\">{$label}</a>".
      "</p></div>");
    exit;
  }

  konawiki_clearCache();
  // edit command
  $mode = konawiki_param("command_mode", "");
  if ($mode == "replace_allpage") {
    __action_edit_command_replace_allpage();
  }
  else if ($mode == "renamepage") {
    __action_edit_command_renamepage();
  }
  else if ($mode == "renamepage_ex") {
    __action_edit_command_renamepage_ex();
  }
  else if ($mode == "renamepage_ex_preview") {
    __action_edit_command_renamepage_ex_preview();
  }
  else if ($mode == "batch") {
    __action_edit_command_batch();
  }
  else {
    konawiki_error(
      "Plase check the checkbox, and push run button.");
  }
}

function __action_edit_command_renamepage()
{
	konawiki_clearCache();

  // get parameter
  $newname = trim(konawiki_param("newname"));
  $admin = trim(konawiki_param("admin"));
  if ($admin !== konawiki_private("admin.key")) {
    konawiki_error("管理者キーが違います。");
    exit;
  }
  $log_id = konawiki_getPageId();
  $oldpage_htm = htmlspecialchars(konawiki_getPage());
  if (!$log_id) {
    konawiki_error("Page `{$oldpage_htm}` is not exists."   );
    exit;
  }
  $newname_htm = konawiki_getPageLink($newname);
  db_begin();
  $now = time();
  $sql = "UPDATE logs SET name=?, mtime=? WHERE id=?";
  db_exec($sql, [$newname, $now, $log_id]);
  $msg = "Change page name 「{$oldpage_htm}」to「{$newname_htm}」.";
  konawiki_showMessage($msg);
  db_commit();
}

function __action_edit_command_replace_allpage()
{
	konawiki_clearCache();
	$src   = trim(konawiki_param("src"));
  $des   = trim(konawiki_param("des"));
  $admin = trim(konawiki_param("admin"));
  
  if ($admin !== konawiki_private("admin.key")) {
    konawiki_error(konawiki_lang('Invalid Admin key.'));
    exit;
  }
  if ($src === "") {
    konawiki_error("No search key.");
    exit;
  }
  // get all pages
  $sql = "SELECT id FROM logs";
  $r = db_get($sql);
  if (!$r) {
    konawiki_error("No page name"); exit;
  }
  $count = 0;
  foreach ($r as $row) {
    $id = $row['id'];
    $log = konawiki_getLogFromId($id);
    $body = $log['body'];
    $i = strpos($body, $src);
    if ($i === FALSE) {
      continue;
    }
    $body = str_replace($src, $des, $body);
    $mtime = time();
    $sql = 
      "UPDATE logs SET body=?,mtime=? WHERE".
      " id=?";
    db_exec($sql, [$body, $mtime, $id]);
    $count++;
  }
  $msg = "Replaced {$count}pages";
  konawiki_showMessage($msg);
}

function __action_edit_command_renamepage_ex_preview_f($oldname, $newname, &$r)
{
  if (!konawiki_auth()) {
    konawiki_error(
      konawiki_lang('Sorry, You do not have permission.'));
  }
	konawiki_clearCache();
  $oldname = str_replace("*", "%", $oldname); // wildcard を置換
  if (preg_match('/^(.*)\%(.*)$/',$oldname, $m)) {
    $m1 = $m[1];
    $m2 = $m[2];
  } else {
    $m1 = $oldname;
    $m2 = "";
  }
  $sql = 
    "SELECT * FROM logs WHERE ".
    "name LIKE ? ORDER BY name LIMIT 100";
  $res = db_get($sql, [$oldname]);
  $r = array();
  $conflict = FALSE;
  foreach ($res as $line) {
    $name = $line["name"];
    $new  = $name;
    if ($m1 !== "") $new = str_replace($m1, $newname, $new);
    if ($m2 !== "") $new = str_replace($m2, $newname, $new);
    if (isset($r[$name])) { // ページ衝突
      $conflict = TRUE;
    } else {
      $findname_sql = 
        "SELECT id,name FROM logs ".
        "  WHERE name=? LIMIT 1";
      $findname_res = db_get1($findname_sql, [$new]);
      if(isset($findname_res['id'])) {
        $conflict = TRUE;
      }
    }
    $r[$name] = ($conflict) ? "[ERROR] $new" : $new;
  }
  return $conflict;
}

function action_edit_removetag()
{
  if (!konawiki_auth()) {
    konawiki_error(
      konawiki_lang('Sorry, You do not have permission.'));
  }
	konawiki_clearCache();
	// parameters
  $tag    = konawiki_param("tag", FALSE);
  $log_id = intval(konawiki_param("log_id", 0));
  if ($log_id < 0 || $tag == FALSE) {
    konawiki_error("Invalid parameters."); return;
  }
  // remove tag
  $tag_html = htmlspecialchars($tag);
  $sql = "DELETE FROM tags WHERE log_id=? AND tag=?";
  db_exec($sql, [$log_id, $tag]);
  $backlink = konawiki_getPageLink();
  $insert_url = konawiki_getPageURL2(konawiki_getPage(),"edit","inserttag","log_id=$log_id&tag=".urlencode($tag));
  $undolink = "<small><a href='$insert_url'>".konawiki_lang('Undo the tag.')."</a></small>";
  $msg1 = sprintf(konawiki_lang('Removed tag [%s].'), $tag_html);
  konawiki_showMessage("<p>{$msg1}</p><p>{$backlink}</p><p>{$undolink}</p>");
}

function action_edit_inserttag()
{
  if (!konawiki_auth()) {
    konawiki_error(
      konawiki_lang('Sorry, You do not have permission.'));
  }
	konawiki_clearCache();
	// parameters
  $tag    = konawiki_param("tag", FALSE);
  $log_id = intval(konawiki_param("log_id", 0));
  if ($log_id < 0 || $tag == FALSE) {
    konawiki_error("Invalid parameters."); return;
  }
  // check tag already exists
  $tag_html = htmlspecialchars($tag);
  $sql = "SELECT * FROM tags WHERE log_id=? AND tag=?";
  $res = db_get($sql, [$log_id, $tag]);
  if ($res) { // already exists
    konawiki_error("Tag'{$tag_html}' already exists."); 
    return;
  }
  // log exists?
  $log = konawiki_getLogFromId($log_id);
  if (!isset($log['id'])) {
    konawiki_error("Invalid log_id");
    exit;
  }
  // insert tag
  $sql = "INSERT INTO tags (log_id,tag)VALUES(?,?)";
  db_exec($sql, [$log_id, $tag]);
  $backlink = konawiki_getPageLink();
  if (empty($undolink)) { $undolink = ""; }
  konawiki_showMessage(
    "<p>Tag '{$tag_html}': ".
    "Successd to insert →{$backlink}</p>".
    "<p>{$undolink}</p>");
}


function __action_edit_command_renamepage_ex_preview()
{
    if (!konawiki_auth()) {
        konawiki_error(konawiki_lang('Sorry, You do not have permission.'));
    }
	konawiki_clearCache();
	// get parameter
    $oldname = trim(konawiki_param("oldname"));
    $newname = trim(konawiki_param("newname"));
    $admin = trim(konawiki_param("admin"));
    if ($admin !== konawiki_private("admin.key")) {
        konawiki_error("管理者キーが違います。");
        exit;
    }
    $page = konawiki_getPage();
    $edit_url = konawiki_getPageURL($page, 'edit', 'command');
    //
    $conflict = __action_edit_command_renamepage_ex_preview_f($oldname, $newname, $r);
    $body = "";
    $body .= "<h3>変更対象となるページの一覧</h3>";
    $body .= "<table>";
    $body .= "<tr><td>元の名前</td><td>新しい名前</td></tr>\n";
    foreach ($r as $old => $new) {
        $new = htmlspecialchars($new);
        $old = htmlspecialchars($old);
        $body .= "<tr><td>$old</td><td>$new</td></tr>\n";
    }
    $body .= "</table>\n";
    if ($conflict) {
        $body .= "<h3>エラー。ページ名が重複します！！</h3>\n";
    } else {
        $new = htmlspecialchars($newname);
        $old = htmlspecialchars($oldname);
        $body .= "<p>変更後を確認して[変更]ボタンをクリックしてください。</p>\n";
        $body .= "<form action='$edit_url'>\n".
            "<input type='hidden' name='newname' value='$new'/>\n".
            "<input type='hidden' name='oldname' value='$old'/>\n".
            "<input type='hidden' name='command_mode' value='renamepage_ex'/>\n".
            "<input type='hidden' name='admin' value='$admin'/>\n".
            "<input type='submit' value='変更'/>\n".
            "</form>\n\n";
    }
    konawiki_showMessage($body);
}

function __action_edit_command_renamepage_ex()
{
  if (!konawiki_auth()) {
    konawiki_error(
      konawiki_lang('Sorry, You do not have permission.'));
  }
	konawiki_clearCache();
	// get parameter
  $oldname = trim(konawiki_param("oldname"));
  $newname = trim(konawiki_param("newname"));
  $admin = trim(konawiki_param("admin"));
  if ($admin !== konawiki_private("admin.key")) {
    konawiki_error("管理者キーが違います。");
    exit;
  }
  
  $conflict = __action_edit_command_renamepage_ex_preview_f($oldname, $newname, $r);
  if ($conflict) {
    konawiki_error("コンフリクトするページがあります。");
    exit;
  }
  db_begin();
  try {
    $now = time();
    $try_msg = '';
    foreach ($r as $old => $new) {
      $old_htm = htmlspecialchars($old);
      $new_htm = htmlspecialchars($new);
      $try_msg = "$old_html =&gt; $new_htm";
      $sql = 
        "UPDATE logs SET name=?, mtime={$now} ".
        "WHERE name=?";
      db_exec($sql, [$new, $old]);
      $msg = "名前変更が完了しました。";
      konawiki_showMessage($msg);
    }
  } catch (Exception $e) {
    db_rollback();
    konawiki_error('Failed to rename: '.$try_msg);
    exit;
  }
}

function __action_edit_command_batch() {
  if (!konawiki_auth()) {
      konawiki_error(konawiki_lang('Sorry, You do not have permission.'));
  }
  konawiki_clearCache();
  // get parameter
  $admin = trim(konawiki_param("admin"));
  if ($admin !== konawiki_private("admin.key")) {
      konawiki_error("管理者キーが違います。");
      exit;
  }
  // --- batch
  $names = array();
  $text = empty($_POST['batch_ta']) ? '' : $_POST['batch_ta'];
  $lines = explode("###PAGE###", $text);
  db_begin();
  foreach ($lines as $p) {
    $p = trim($p);
    if (substr($p, 0, 1) != ':') continue;
    $sls = explode("\n", $p);
    $cmd = explode(":", $sls[0].":::");
    $cmd_mode = trim($cmd[1]);
    $name = trim($cmd[2]);
    $sls = array_slice($sls, 1);
    $body = implode("\n", $sls);
    if ($name == '') continue;
    $tm = time();
    $insert_sql = 
      "INSERT INTO logs (name, body, ctime, mtime)".
      "          VALUES (   ?,    ?,     ?,     ?)";
    if ($cmd_mode === 'w') {
      db_exec("DELETE FROM logs WHERE name=?", [$name]);
      db_exec($insert_sql, [$name, $body, $tm, $tm]);
    }
    else if ($cmd_mode === 'c') {
      $a = db_get("SELECT * FROM logs WHERE name=?", [$name]);
      if (count($a) == 0) {
        db_exec($insert_sql, [$name, $body, $tm, $tm]);
      }
    }
    $names[] = $name2;
  }
  db_commit();
  $msg = "Batch successed.(".implode(",",$names).')';
  konawiki_showMessage($msg);
}

function action_edit_preview()
{
  if (!konawiki_auth()) {
    konawiki_error(
      konawiki_lang('Sorry, You do not have permission.'));
  }
  $log["body"] = konawiki_parser_convert(konawiki_param("body"));
  include_template("preview.html", $log);
}



