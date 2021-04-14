<?php
/** konawiki plugins -- コメントを記入するプラグイン
 * - [書式] #comment([id])
 * - [引数]
 * -- id        省略可能、複数の掲示板を設置する場合に識別用に指定する
 * - [使用例] #comment
 */


include_once(KONAWIKI_DIR_LIB.'/konawiki_parser.inc.php');

// プラグインインタフェースの定義
// ※定義の中で、プラグイン名をつけてサブ関数を呼ぶのは、プラグイン#comment と #コメント で同じ処理を使うため
function plugin_comment_convert($params)
{
	return plugin_comment_convert_sub("comment", $params);
}
function plugin_comment_action($params)
{
	return plugin_comment_action_sub("comment", $params);
}

// 以下、コメントに関する処理
function plugin_comment_convert_sub($pluginname, $params)
{
    konawiki_setPluginDynamic(true);

    $pid = konawiki_getPluginInfo($pluginname, "pid", 0);
    $plug_key = $pid;
    if (count($params) >= 1) {
        $plug_key = $params[0];
    }
    // query
    $log_id = konawiki_getPageId();
    if (!$log_id) {
        return "";
    }
    // DB のプラグイン名は共通の comment とする
    $sql =
      "SELECT * FROM sublogs WHERE log_id=?".
      "  AND plug_name='comment'AND plug_key=?".
      "  LIMIT 1";
    $r = db_get($sql, [$log_id, $plug_key], 'sub');
    $logs = '';
    if (isset($r[0]['id'])) {
        $logs = $r[0]['body'];
    }
    $mode = konawiki_param('comment-mode','show');
    $pageurl = konawiki_getPageURL();
    $footer = "";
    $insert_form = plugin_comment_getInsertForm(
                       $pluginname, $pageurl, $plug_key);
    if ($mode == 'edit') {
      // --------------- 編集モードのとき
      $logs = htmlspecialchars($logs);
      $logs = 
        "<form method='post' action='$pageurl'>".
        "<textarea rows=5 cols=80 name='body'>".
        $logs.
        "</textarea><br/>".
        form_input_hidden("name", "").
        form_input_hidden("comment", "").
        form_input_hidden("plugin", $pluginname).
        form_input_hidden("pid", $plug_key).
        form_input_hidden("comment-mode", "edit").
        form_input_submit("Edit comments").
        "</form>";
      $showlink = konawiki_getPageURL(
        konawiki_getPage(), FALSE, FALSE, 
        "comment-mode=show");
      $footer = 
        "<div class='rightopt'>".
        "<a href='$showlink'>→Show comments</a>".
        "</div>";
      $insert_form = "";
    }
    else {
      // --------------- 普通表示モードのとき
      $logs = konawiki_parser_convert($logs);
      if (konawiki_isLogin_write()) { // ログインしているときだけ編集ボタンを表示
        $editlink = konawiki_getPageURL(
          konawiki_getPage(), FALSE, FALSE, 
          "comment-mode=edit");
        $footer = 
          "<div class='rightopt'>".
          "<a href='{$editlink}'>".
          "→Edit comments</a>".
          "</div>";
      } else {
        $footer = "<div class='rightopt'>&nbsp;</div>";
      }
    }
    $Comments = konawiki_lang('Comments');
    $s = <<<__EOS
<div class="comment">
    <div class="caption">$Comments:</div><div class="commentlogs">
    <div class="commentshort">
    {$logs}
    </div>
    </div>
    <div class="commentshort">
        {$insert_form}
    </div>
    <div class="referer">
{$footer}
    </div>
</div>
__EOS;
    return $s;
}

function plugin_comment_getInsertForm($pluginname, $pageurl, $pid)
{
  $Name = konawiki_lang("Name");
  $msgComment = konawiki_lang('Comments');
    return <<<EOS__
<form method="post" action="{$pageurl}" class="pure-form pure-form-stacked">
<div style="display:none">
<input type="text" name="name" />
<input type="text" name="comment" />
</div>
<div>
<input type="text" name="r_name" id="r_name" size="12" placeholder="$Name"/>
<textarea name="r_comment" cols="64" rows="3" style="padding:4px;"></textarea>
<input type="submit" value="$msgComment" class="pure-button pure-button-primary"/>
<input type="hidden" name="plugin" value="{$pluginname}"/>
<input type="hidden" name="pid" value="$pid"/>
</div></form>
EOS__;
}


function plugin_comment_action_sub($pluginname, $params)
{
  global $plugin_params;
  // check pid
  $post_pid = konawiki_param("pid", 0);
  $pid = konawiki_getPluginInfo($pluginname, "pid", 0);
  $plug_key = $pid;
  if (count($params) >= 1) {
      $plug_key = $params[0];
  }
  if ($plug_key != $post_pid) return TRUE;
  // check auth
  $commentmode = konawiki_param("comment-mode");
  if ($commentmode == "edit") {
      konawiki_auth();
  }
  // query
  $sublog_id = 0;
  $log_id = konawiki_getPageId();
  $sql = 
    "SELECT * FROM sublogs WHERE log_id=?".
    "  AND plug_name='comment' AND plug_key=?".
    "  LIMIT 1";
  $r = db_get($sql, [$log_id, $plug_key], 'sub');
  $logs = "";
  if (isset($r[0]['id'])) {
      $logs = trim($r[0]['body'])."\n";
      $sublog_id = $r[0]['id'];
  }
  
  // get comment
  $dummy1 = konawiki_param("comment");
  $dummy2 = konawiki_param("name");
  $comment = konawiki_param("r_comment");
  $name    = konawiki_param("r_name");
  if ($dummy1 !== "" || $dummy2 !== "") {
    // maybe spam
    $plugin_params["error"] = "Sorry may be SPAM..";
    return FALSE;
  }
  if ($commentmode != "edit") {
    if ($name === "") $name = konawiki_lang("Nanasi");
    if ($comment == "") {
      // maybe spam
      $params["error"] = "Need to write body.";
      return FALSE;
    }
    $mtime   = konawiki_datetime(time());
    //$instext = "- $comment -- $name (&new($mtime);)";
    $comment = preg_replace('/(\r|\n)/', '', $comment);
    $instext = "|$name|$comment (&new($mtime);)|";
    $logs .= $instext . "\n";
  } else { // edit mode
    $logs = konawiki_param("body");
  }
  $logs = trim($logs);
  $mtime = time();
  
  // delete ?
  if ($logs === '' && $sublog_id > 0) {
    $sql = "DELETE FROM sublogs WHERE id=?";
    db_exec($sql, [$sublog_id], 'sub');
    $url = konawiki_getPageURL();
    konawiki_jump($url);
    return TRUE;
  }
  
  // insert ?
  if ($sublog_id == 0) {
    $sql = 
      "INSERT INTO sublogs".
      " (log_id, plug_name, plug_key, body, ctime, mtime)".
      " VALUES (?, 'comment', ?, ?, $mtime, $mtime)";
    db_exec($sql, [$log_id, $plug_key, $logs], 'sub');
    $url = konawiki_getPageURL();
    konawiki_jump($url);
    return TRUE;
  }
  // update
  $sql = 
    "UPDATE sublogs SET body=?, mtime=$mtime".
    "  WHERE id=?";
  db_exec($sql, [$logs, $sublog_id], 'sub');
  $url = konawiki_getPageURL();
  konawiki_jump($url);
  return TRUE;
}

function konawiki_comment_getLog($page)
{
    $log_id = konawiki_getPageId();
    $sql =  
      "SELECT * FROM sublogs WHERE log_id=?".
      "  AND plug_name='comment'";
    $r = db_get($sql, [$log_id], 'sub');
    if (!$r) { return ''; }
    
    $logs = "";
    foreach ($r as $line) {
      $logs .= $line['body']."\n";
    }
    if ($logs != "") {
      $logs = "||Comments|\n".$logs;
      $logs = konawiki_parser_convert($logs);
    }
    $s = <<<__EOS
<div class="comment">
    <div class="commentlogs">{$logs}</div>
</div>
__EOS;
    return $s;
}

