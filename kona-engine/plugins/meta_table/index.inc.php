<?php
/** konawiki plugins -- メタテーブルプラグイン 
 */


// include lib
require_once __DIR__.'/template.inc.php';
require_once __DIR__.'/lib.inc.php';

function plugin_meta_table_convert($params) {
  // ログインが必須
  $err = plugin_meta_table_checkLogin();
  if ($err) { return $err; }
  
  // check json
  $s = array_shift($params);
  $err = plugin_meta_table_check_json($s, $json);
  if ($err) { return $err; }
  
  // モード分岐
  $m = empty($_GET['m']) ? 'list' : $_GET['m'];
  switch ($m) {
  case 'list':  return plugin_meta_table_list($json);
  case 'add':   return plugin_meta_table_add($json);
  case 'edit':  return plugin_meta_table_edit($json);
  case 'update':return plugin_meta_table_update($json);
  case 'delete':return plugin_meta_table_delete($json);
  default:
    return plugin_meta_table_list($json);
  }
  return "hoge";
}

function plugin_meta_table_list($json, $msg = '') {
  // list page
  $name = $json['name'];
  $fields = $json['fields'];
  $offset = empty($_GET['offset']) ? 0 : intval($_GET['offset']);
  $sql = 'SELECT * FROM logs WHERE name LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?';
  $rows = db_get($sql, [$name."/%", KONA_META_LIMIT, $offset]);

  $name_h = htmlspecialchars($name, ENT_QUOTES);
  $html = plugin_meta_table_menu($json);
  $html .= "<h3>{$name_h}の一覧</h3>\n";
  if ($msg) {
    $html .= "<p class='error'>$msg</p>";
  }
  if (!$rows) {
    $html .= "<p>ありません</p>";
    $rows = [];
  } else {
    $active = [];
    $inactive = [];
    foreach ($rows as $i) {
      $log_id = $i['id'];
      $private = $i['private']; // 1 is private
      $pagename = $i['name'];
      $html_pagename = htmlspecialchars($pagename.$body, ENT_QUOTES);
      $mname = substr($i['name'], strlen($name.'/'));
      $url_mname = urlencode($mname);
      $html_mname = htmlspecialchars($mname, ENT_QUOTES);
      $linkshow = konawiki_getPageURL($pagename);
      $link = konawiki_getPageURL(FALSE, 'show', '', "m=edit&mname=$url_mname&m2=edit");
      $r = '';
      $r .= "<li>";
      $r .= "<a href='$link' class='pure-button'>📝</a> ";
      $r .= "<a href='$link'>$html_pagename</a>";
      $r .= "</li>";
      if ($private) {
        $inactive[] = $r;
      } else {
        $active[] = $r;
      }
    }
    // make html
    $html .= <<<EOS
<style>
.mt_list {
  padding: 6px;
}
.mt_list li {
  border-bottom: 1px solid silver;
  padding: 6px;
}
</style>
EOS;
    $html .= "<ul class='mt_list'>";
    $html .= implode("\n", $active);
    $html .= "</ul>";
    if ($inactive) {
      $html .= "<h4>非表示になっている{$name_h}一覧</h4>";
      $html .= "<ul class='mt_list'>";
      $html .= implode("\n", $inactive);
      $html .= "</ul>";
    }
  }
  // next page
  $html .= "<p>";
  if ($offset > 0) {
    $offset3 = $offset - KONA_META_LIMIT;
    $prev= konawiki_getPageURL(FALSE, 'show', '', "m=list&offset=$offset3");
    $html .= " [<a href='$prev'>前へ</a>] ";
  }
  if (count($rows) == KONA_META_LIMIT) {
    $offset2 = $offset + KONA_META_LIMIT;
    $next = konawiki_getPageURL(FALSE, 'show', '', "m=list&offset=$offset2");
    $html .= " [<a href='$next'>次へ</a>] ";
  }
  $html .= "</p>\n";
  return $html;
}

function plugin_meta_table_add($json, $msg = '') {
  $name = $json['name'];
  $html_name = htmlspecialchars($name);
  $fields = $json['fields'];
  $action = konawiki_getPageURL(FALSE);
  $html_page = htmlspecialchars(konawiki_getPage(), ENT_QUOTES);
  //
  $html = plugin_meta_table_menu($json);
  $html .= "<h2>{$html_name}の追加</h2>\n";
  if ($msg) {
    $html .= "<p class='error'>$msg</p>\n";
  }
  $html .= "<form method='get' action='$action' class='pure-form'>\n";
  $html .= "<p><label>{$html_name}の名前:";
  $html .= "   <input type='text' name='mname'></label>";
  $html .= "   <input type='hidden' name='page' value='$html_page'></label>";
  $html .= "   <input type='hidden' name='action' value='show'></label>";
  $html .= "   <input type='hidden' name='m' value='edit'></label>";
  $html .= "   <input type='hidden' name='m2' value='new'></label>";
  $html .= "   <input class='pure-button' type='submit' value='確認'></p>";
  $html .= "</form>\n";
  return $html;
}

function plugin_meta_table_edit($json, $msg = '') {
  $name = $json['name'];
  $html_name = htmlspecialchars($name);
  $fields = $json['fields'];
  $mname = isset($_GET['mname']) ? $_GET['mname'] : '';
  if ($mname == '') { return plugin_meta_table_add($json, '名前が空です。'); }
  $pagename = "$name/$mname";
  
  // query data
  $log = db_get1('SELECT * FROM logs WHERE name=? LIMIT 1', [$pagename]);
  $ctime = time();
  $meta_obj = [];
  // check new page
  $m2 = isset($_GET['m2']) ? $_GET['m2'] : 'new';
  if ($m2 == 'new') { // 既存ページがあったら警告する
    if ($log) {
      return plugin_meta_table_add($json, '既に同じ名前の物件があります');
    }
    // insert new page data
    $body = 
      "#rem(このページは自動で作成されたページです。)\n".
      "#rem(--- 以下を削除しないでください ---)\n".
      "#meta_table_show\n".
      "#attachfiles\n".
      "#rem(--- ここまで ---)\n\n";
    $id = db_insert('INSERT INTO logs (name, body, ctime, mtime) VALUES (?,?,?,?)',
      [$pagename, $body, $ctime, $ctime]);
    $log = [
      'id' => $id, 'name' => $pagenam, 
    ];
    $sublog_id = db_insert(
      'INSERT INTO sublogs (log_id, plug_name, ctime, mtime)'.
      'VALUES(?, ?, ?, ?)',
      [$id, 'meta_table', $ctime, $ctime], 'sub');
  } else {
    $id = $log['id'];
    // meta info load
    $meta = db_get1(
      'SELECT * FROM sublogs WHERE plug_name=? AND log_id=? LIMIT 1',
      ['meta_table', $id], 'sub');
    if (!$meta) {
      db_insert(
        'INSERT INTO sublogs (log_id, plug_name, ctime, mtime)'.
        'VALUES(?, ?, ?, ?)',
        [$id, 'meta_table', $ctime, $ctime], 'sub');
    } else {
      $meta_obj = json_decode($meta['body'], TRUE);
    }

  }
  // select
  $select = $json['select'];
  // fields
  $inputs = '';
  foreach ($fields as $f) {
    $html_f = htmlspecialchars($f, ENT_QUOTES);
    $name_f = 'meta_'.bin2hex($f);
    $val = isset($meta_obj[$f]) ? $meta_obj[$f] : '';
    $html_val = htmlspecialchars($val, ENT_QUOTES);
    if (isset($select[$f])) {
      $options = $select[$f];
      // select box
      $inputs .= "<label for='$name_f'>{$html_f}</label>\n";
      $inputs .= "<select name='$name_f' id='$name_f'>\n";
      foreach ($options as $opt) {
        $opt_h = htmlspecialchars($opt, ENT_QUOTES);
        $selected = ($opt == $val) ? "selected" : "";
        $inputs .= "<option value='$opt_h' $selected>$opt_h</option>\n";
      }
      $inputs .= "</select>\n";
    } else {
      // normal text box
      $inputs .= "<label for='$name_f'>{$html_f}<label>";
      $inputs .= "<input type='text' id='$name_f' name='$name_f' value='$val'>";
    }
  }
  //
  $html = plugin_meta_table_menu($json);
  $html .= meta_table_template("edit.inc.html", [
    "action" => konawiki_getPageURL(FALSE, 'show', '', 'm=update'),
    "name" => $name,
    "mname" => $mname,
    "pagename" => $pagename,
    "inputs" => $inputs,
    "attach_link" => konawiki_getPageURL($pagename, 'attach'),
    "show_link" => konawiki_getPageURL($pagename),
    "edit_page_name" => konawiki_getPage(),
    "private" => $log['private'],
    "action_delete" => konawiki_getPageURL(FALSE, 'show', '', 'm=delete'),
    "error_msg" => $msg,
    "edit_token" => konawiki_getEditToken(),
  ]);
  return $html;
}

function plugin_meta_table_update($json) {
  $name = $json['name'];
  $fields = $json['fields'];
  $mname = isset($_POST['mname']) ? $_POST['mname'] : '';
  if ($mname == '') {
    return plugin_meta_table_list($json, 
      '失敗。編集先が不明です。やり直してください。');
  }
  $pagename = "$name/$mname";
  
  if (!konawiki_checkEditToken()) {
    return plugin_meta_table_list($json, 
      '失敗。ブラウザを二重に開いていませんか？もう一度、やり直してください。');
  }
  
  // query data
  $log = db_get1('SELECT * FROM logs WHERE name=? LIMIT 1', [$pagename]);
  if (!$log) {
    return plugin_meta_table_edit($json);
  }
  $log_id = $log['id'];
  
  // update meta data
  $meta_obj = [];
  foreach ($fields as $f) {
    $name_f = 'meta_'.bin2hex($f);
    if (isset($_POST[$name_f])) {
      $meta_obj[$f] = $_POST[$name_f];
    } else {
      $meta_obj[$f] = '';
    }
  }
  // update contents
  $meta_json = json_encode($meta_obj);
  db_exec('UPDATE sublogs SET body=?,mtime=? WHERE log_id=? AND plug_name=?',
    [$meta_json, time(), $log_id, 'meta_table'], 'sub');
  // update logs mtime
  db_exec('UPDATE logs SET mtime=? WHERE id=?',
    [time(), $log_id]);

  $mname_enc = urlencode($mname);
  $url = konawiki_getPageURL(FALSE, 'show', '', "m=edit&mname=$mname_enc&m2=edit");
  $url_attach = konawiki_getPageURL($pagename, 'attach'); 
  $html = plugin_meta_table_menu($json);
  $html .= "<br>";
  $html .= "<div class='menubox'>";
  $html .= "<div style='padding:1em;'>正しく保存しました。</div>";
  $html .= "<p><a href='$url' class='pure-button'>確認する</a><p>";
  $html .= "<p><a href='$url_attach' class='pure-button'>ファイルを添付</a><p>";
  $html .= "</div>\n";
  return $html;
}

function plugin_meta_table_delete($json) {
  $name = $json['name'];
  $fields = $json['fields'];
  $pagename = isset($_POST['meta_pagename']) ? $_POST['meta_pagename'] : '';
  $mname= isset($_POST['mname']) ? $_POST['mname'] : '';
  if ($pagename == '') {
    return plugin_meta_table_list($json, 
      '失敗。編集先が不明です。選び直してください。');
  }
  $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : 'no';
  if ($confirm !== 'delete') {
    $_GET['mname'] = $mname;
    $_GET['m2'] = 'edit';
    return plugin_meta_table_edit($json, 
      '削除失敗。削除にチェックをいれてください。');
  }
  if (!konawiki_checkEditToken()) {
    return plugin_meta_table_list($json, 
      '失敗。ブラウザを二重に開いていませんか？もう一度、やり直してください。');
  }
  
  // query data
  $log = db_get1('SELECT * FROM logs WHERE name=? LIMIT 1', [$pagename]);
  if (!$log) {
    return plugin_meta_table_list($json);
  }
  $log_id = $log['id'];
  
  // delete attach files
  $files = db_get('SELECT * FROM attach WHERE log_id=?', [$log_id]);
  if ($files) {
    $dir_attach = konawiki_private('dir.attach', '');
    if ($dir_attach == '') {
      echo "Please set dir.attach in konawiki.ini.php";
      exit;
    }
    foreach ($files as $f) {
      $attach_id = $f['id'];
      $fname = $f['name'];
      $ext = $f['ext']; // mime
      if (preg_match('#(\.[a-zA-Z_]+)$#', $fname, $m)) {
        $ext = $m[1];
      }
      $path = $dir_attach.'/'.$attach_id.$ext;
      if (file_exists($path)) { @unlink($path); }
      db_exec(
        'DELETE FROM attach_counters WHERE id=?',
        [$attach_id]);
    }
    db_exec('DELETE FROM attach WHERE log_id=?', [$log_id]);
  }
  // delete meta data 
  db_exec('DELETE FROM sublogs WHERE log_id=?', [$log_id], 'sub');
  db_exec('DELETE FROM logs WHERE id=?', [$log_id]);
  
  // msg
  $url = konawiki_getPageURL(FALSE, 'show', '', "m=list");
  return "<a href='$url' class='pure-button'>正常に削除しました。</a>";
}



