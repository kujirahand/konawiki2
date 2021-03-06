<?php
/**
 * ページの表示アクション
 */
function action_import_()
{
    if (!konawiki_auth()) {
        konawiki_error("Failed to login.");
        exit;
    }
    $p = konawiki_param("p", null);
    if ($p !== "exe") {
      konawiki_showMessage(
        "Init current DB, and import wiki data.<br>".
        "<form action='index.php?all&import' method='post'>".
        "DATA:<br><textarea name='json' rows='5' cols='60'></textarea><br>".
        "TYPE:<input type='radio' name='t' value='json' checked>JSON ".
        " <input type='radio' name='t' value='php'>PHP Serialize<br>".
        "Admin password:<br><input type='password' name='admin' value=''><br>".
        "<input type='submit' value='Import'>".
        "<input type='hidden' name='p' value='exe'>".
        "</form>"
      );
      exit;
    }
    $admin = konawiki_param("admin","");
    if ($admin !== konawiki_private("admin.key", "")) {
      konawiki_error("Invalid admin key.");
      exit;
    }
    $f = konawiki_param("json", "");
    $f = trim($f);
    $t = konawiki_param("t", "json");
    if ($f == "") {
      konawiki_error("no data"); exit;
    }
    if ($t == "json") {
      $a = json_decode($f, true);
      $len = ceil(strlen($f) / (1024*1024) * 10) / 10;
      if ($a == null) {
        $errno = json_last_error();
        $err = "UNKNOWN";
        switch ($errno) {
          case 1: $err="DEPTH"; break;
          case 2: $err="STATE_MISMATCH"; break;
          case 3: $err="CTRL_CHAR"; break;
          case 4: $err="SYNTAX"; break;
          case 5: $err="NO_UTF8"; break;
        }
        $msg = "<br>Please check php.ini(memory_limit/post_max_size/upload_max_filesize)";
        konawiki_error(
          "JSON DECODE ERROR (ERROR=$errno:$err)".
          "SIZE={$len}MB $msg");
        exit;
      }
    } else if ($t == "php") {
      konawiki_error("Now, not supported.");
      exit;
    }
    $fac = isset($a['logs']) && isset($a['attach']) 
                             && isset($a['tags'])
                             && isset($a['sublogs']);
    if (count($a) == 0 || $a == null || $fac == false) {
      konawiki_error("DATA FORMAT ERROR({$len}KB)");
      exit;
    }
    $logs    = $a['logs'];
    $attach  = $a['attach'];
    $tags    = $a['tags'];
    $sublogs = $a['sublogs'];
    if (!$logs)   $logs    = array();
    if (!$attach) $attach  = array();
    if (!$tags)   $tags    = array();
    if (!$sublogs)$sublogs = array();

    $pages = array();
    db_begin();
    db_exec("delete from logs");
    foreach($logs as $r) {
      $id      = intval(aa($r,'id'));
      $name    = base64_decode(aa($r,'name'));
      $name_   = htmlspecialchars($name);
      $body    = base64_decode(aa($r,'body'));
      $freeze  = intval(aa($r,'freeze'));
      $private = intval(aa($r,'private'));
      $ctime   = intval(aa($r,'ctime'));
      $mtime   = intval(aa($r,'mtime')); 
      // check
      if (isset($pages[$name])) {
        echo "<p>[$name_] pagename not unique...</p>";
        continue;
      }
      $pages[$name] = true;
      //
      $sql = "INSERT INTO logs (id, name, body, freeze, private, ctime, mtime)";
      $sql.= "VALUES(?,?,?,?,?,?,?);";
      try {
        $r = db_exec($sql,
          [$id,$name,$body,$freeze,$private,$ctime,$mtime]);
      } catch (Exception $e) {
        db_rollback();
        konawiki_error(
          "Failed to import.".
          "<pre>[$id:$name_]".$db->error."</pre>"
        );
        exit;
      }
    }
    // attach
    db_exec("delete from attach");
    foreach($attach as $r) {
      $id      = intval(aa($r,'id'));
      $log_id  = intval(aa($r,'log_id'));
      $name    = aa($r,'name');
      $ext     = aa($r,'ext');
      $ctime   = intval(aa($r,'ctime'));
      $mtime   = intval(aa($r,'mtime')); 
      $sql = 
        "INSERT INTO attach(id, log_id, name, ext, ctime, mtime)".
        "VALUES(?,?,?,?,?,?)";
      try {
        db_exec($sql, [$id,$log_id,$name,$ext,$ctime,$mtime]);
      } catch (Exception $e) {
        db_rollback();
        konawiki_error(
          "Failed to import at attachment data.".
          "<pre>".$db->error."</pre>"
        );
        exit;
      }
    }
    // tags
    db_exec("delete from tags");
    foreach($tags as $r) {
      $log_id  = intval(aa($r,'log_id'));
      $tag     = base64_decode(aa($r,'tag'));
      $sql = "INSERT INTO tags(log_id, tag)";
      $sql.= "VALUES(?,?)";
      db_exec($sql,[$log_id, $tag]);
    }
    //---
    db_begin('sub');
    db_exec("delete from sublogs");
    foreach($sublogs as $r) {
      $log_id  = intval(aa($r,'log_id'));
      $plug_name = (aa($r,'plug_name'));
      $plug_key  = (aa($r,'plug_key'));
      $body    = base64_decode(aa($r,'body'));
      $ctime   = intval(aa($r,'ctime'));
      $mtime   = intval(aa($r,'mtime')); 
      $sql = "INSERT INTO sublogs (log_id, plug_name, plug_key, body, ctime, mtime)";
      $sql.= "VALUES(?,?,?,?,?,?);";
      $db_exec($sql, 
        [$log_id,$plug_name,$plug_key,$body,$ctime,$mtime]);
    }
    //---
    db_commit('sub');
    db_commit();
    //
    $r = db_get("select count(*) from logs");
    $cnt = $r[0][0];
    konawiki_showMessage("Success to import. ({$cnt}pages)");
    konawiki_clearCacheDB();
}

function aa($r, $key, $def = 0) {
  $s =  (empty($r[$key]) ? $def : $r[$key]);
  if (is_string($s)) {
    $s = mb_convert_encoding($s, "utf-8", "utf-8,sjis,euc,auto");
  }
  return $s;
}
