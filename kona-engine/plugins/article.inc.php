<?php
/** konawiki plugins -- 掲示板を記入するプラグイン
 * - [書式] #article([id])
 * - [引数]
 * -- id        省略可能、複数の掲示板を設置する場合に識別用に指定する
 * - [使用例] #article()
 * - [備考] ページに内に複数の掲示板を作成可能
 */

function plugin_article_convert($params)
{
	konawiki_setPluginDynamic(true);
	
	$plugin_name = "article";
  $pagemode = "{$plugin_name}-mode";
  // pid
  $pid = konawiki_getPluginInfo($plugin_name,"pid",0);
  $plug_key = $pid;
  if (count($params) >= 1) {
    $plug_key = $params[1];
  }
  // query
  $log_id = konawiki_getPageId();
  $sql =
    "SELECT * FROM sublogs WHERE log_id=?".
    "  AND plug_name=? AND plug_key=?".
    "  LIMIT 1";
  $r = db_get($sql, [$log_id, $plugin_name, $plug_key], 'sub');
  $logs = "";
  if (isset($r[0]['id'])) {
    $logs = $r[0]['body'];
  }
  $mode = konawiki_param($pagemode,'show');
  $baseurl = konawiki_baseurl();
  $footer = "";
  if ($mode == 'edit') {
    $pageurl = konawiki_getPageURL(
      FALSE, FALSE, FALSE,"$pagemode=show");
    $logs = htmlspecialchars($logs);
    $logs = "<form action='{$pageurl}' method='post'>".
            "<textarea rows=8 cols=80 name='body'>".
            $logs.
            "</textarea><br/>".
            "<input type='text' name='conf'/>".
            "←「桜」をひらがなで入力<br/>".
            form_input_hidden("name", "").
            form_input_hidden("comment", "").
            form_input_hidden("plugin", $plugin_name).
            form_input_hidden("pid", $pid).
            form_input_hidden($pagemode, "edit").
            form_input_submit("コメントの編集").
            "<span class='note'>&nbsp;※編集のためには管理者権限が必要です。</span>".
            "</form>";
    $footer = "<div class='rightopt'>".
            "<a href='$pageurl'>→コメント表示</a>".
            "</div>";
  }
  else {
    $pageurl = konawiki_getPageURL(
      FALSE, FALSE, FALSE,"$pagemode=edit");
    $logs = konawiki_parser_convert($logs);
    $footer = 
      "<div class='rightopt'>".
      "<a href='$pageurl'>→コメント編集</a>".
      "</div>";
  }
  $form_action = konawiki_getPageURL();
  $s = <<<__EOS
<div class="comment">
<div class="article">
<div class="title">BBS:</div>
{$logs}</div>
<form method="post" action="{$form_action}">
<p style="display:none">
<input type="text" name="name" />
<input type="text" name="comment" />
<input type="text" name="r_body1" />
<input type="text" name="r_body3" />
</p>
<table width="100%">
<tr>
  <td>名前:</td><td><input type="text" name="r_name" size="60"/></td>
</tr>
<tr>
  <td>件名:</td><td><input type="text" name="r_comment" size="60"/></td>
</tr>
<tr>
  <td>本文:</td><td><textarea name="r_body5" rows="4" cols="60"></textarea></td>
</tr>
<tr>
  <td>迷惑防止:</td><td><input type="text" name="conf" size="60"/>←ひらがなで「桜」と記入してください。</td>
</tr>
<tr>
  <td>&nbsp;</td><td><input type="submit" value="本文に挿入"/></td>
</tr>
</table>
<div>
<input type="hidden" name="plugin" value="{$plugin_name}"/>
<input type="hidden" name="pid" value="$pid"/>
</div>
</form>
{$footer}
</div>
__EOS;
    return $s;
}

function plugin_article_action($params)
{
  $plugin_name = 'article';
  $pagemode = "{$plugin_name}-mode";
  // check pid
  $post_pid = konawiki_param("pid", 0);
  $pid = konawiki_getPluginInfo($plugin_name,"pid",0);
  if ($pid != $post_pid) return TRUE;
  $plug_key = $pid;
  if (count($params) >= 1) {
    $plug_key = $params[1];
  }
  // check auth
  $commentmode = konawiki_param($pagemode, 'show');
  if ($commentmode == "edit") {
    konawiki_auth();
  }
  // query
  $sublog_id = 0;
  $log_id = konawiki_getPageId();
  $sql =
    "SELECT * FROM sublogs WHERE log_id=?".
    "  AND plug_name=? AND plug_key=?".
    "  LIMIT 1";
  $r = db_get($sql, [$log_id, $plugin_name, $plug_key], 'sub');
  $logs = "";
  if (isset($r[0]['id'])) {
    $logs = trim($r[0]['body'])."\n";
    $sublog_id = $r[0]['id'];
  }
    
  // get comment
  $dummy1 = konawiki_param("comment");
  $dummy2 = konawiki_param("name");
  $dummy3 = konawiki_param("r_body1");
  $dummy4 = konawiki_param("r_body3");
  $comment = konawiki_param("r_comment");
  $name    = konawiki_param("r_name");
  $body    = konawiki_param("r_body5");
  $conf    = konawiki_param("conf");
  if ($dummy1 !== "" || $dummy2 !== "" || $conf != "さくら") {
    // maybe spam
    $params["error"] = "スパムの可能性があるので書き込みません。[戻る]ボタンで戻り、迷惑防止の項目が正しいかチェックしてください。";
    return FALSE;
  }
  if ($commentmode != "edit") {
    if ($name === "") $name = "名無し";
    if ($comment == "") {
      // maybe spam
      $params["error"] = "コメントの記入が必要です。";
      return FALSE;
    }
    $mtime   = konawiki_datetime(time());
    $resid   = md5('resid-'.$name.$mtime);
    $instext = 
      "*** {$comment} -- ($name) &new($mtime);\n".
      "{$body}\n".
      "-----------\n";
    $logs = $instext.$logs;
  }
  else { // edit mode
    $logs = konawiki_param("body");
  }
  $logs = trim($logs);
  $mtime = time();
  $url = konawiki_getPageURL();
  // delete ?
  if ($logs === '' && $sublog_id > 0) {
    $sql = "DELETE FROM sublogs WHERE id=?";
    db_exec($sql, [$sublog_id], 'sub');
    konawiki_jump($url);
    return TRUE;
  }
  // insert ?
  if ($sublog_id == 0) {
    $sql = "INSERT INTO sublogs".
      " (log_id, plug_name, plug_key, body, ctime, mtime)".
      " VALUES (?,?,?,?,?,?)";
    db_exec(
      $sql, 
      [$log_id, $plugin_name, $plug_key, $logs, $mtime, $mtime],
      'sub');
    konawiki_jump($url);
    return TRUE;
  }
  // update
  $sql = 
    "UPDATE sublogs SET body=?, mtime=$mtime".
    " WHERE id=?";
  db_exec($sql, [$logs, $sublog_id], 'sub');
  konawiki_jump($url);
  return TRUE;
}

