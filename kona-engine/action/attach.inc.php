<?php

// index.php?(page)&attach&file=(filename)
// index.php?FrontPage&attach&file=xxx.txt

function action_attach_()
{
    // check file parameter
    $file = konawiki_param("file", FALSE);

    // 特定のファイルの指定でなければフォームを表示する
    if ($file === FALSE) { // show form
        action_attach_form();
        return;
    }

    // output attach file
    $page = konawiki_getPage();
    $log_id = konawiki_getPageId($page);
    if (FALSE == $log_id) {
        header('HTTP/1.0 404 Not Found'); exit;
    }
    $sql = 
      "SELECT * FROM attach WHERE log_id=? AND name=?".
      "  LIMIT 1";
    $res = db_get1($sql, [$log_id, $file]);
    if (!isset($res['id'])) {
        header('HTTP/1.0 404 Not Found'); exit;
    }
    $id     = $res['id'];
    $mime   = $res['ext'];
    $name   = $res['name'];
    // get real ext
    $ext = "";
    if (preg_match('/(\.\w+)$/',$name, $m)) {
      $ext = $m[1];
    }
    $uri  = konawiki_private('uri.attach')."/{$id}{$ext}";
    $file = KONAWIKI_DIR_ATTACH."/{$id}{$ext}";
    if (file_exists($file)) {
      header("Location: $uri");
      exit;
    }
    // old version attachment
    $fname  = KONAWIKI_DIR_ATTACH."/".$id;
    header("Content-type: $mime");
    if (!preg_match("#^image/.+#", $mime)) {
        header("Content-Disposition: attachment; filename=\"$name\"");
    }
    readfile($fname);
}

function action_attach_form()
{
    header('X-Frame-Options: SAMEORIGIN');
    $page = konawiki_getPage();
    
    include_template('attach_form.html', [
      'list' => konawiki_getAttachList($page),
      'page_url' => konawiki_getPageURL($page),
      'edit_token' => konawiki_getEditToken(),
    ]);
}

function action_attach_write()
{
    $enabled    = konawiki_private('attach.enabled');
    if (!$enabled) {
        konawiki_error("添付ファイルが利用できない設定になっています。");
        return;
    }
    if(!konawiki_auth()) return;
    // check page
    $page = konawiki_getPage();
    $log  = konawiki_getLog($page);
    if (!isset($log["id"])) {
        $page = htmlspecialchars($page);
        konawiki_error("ページ[$page]がありません。");
        return;
    }
    $log_id = $log["id"];
    // check attach file
    $filename = trim(isset($_POST['filename']) ? $_POST['filename'] : '');
    $name = basename($_FILES['userfile']['name']);
    if ($filename == '') { $filename = $name; }
    $name_ext = "";
    if (preg_match('/(\.\w+)$/', $name, $m)) {
      $name_ext = $m[1];
    }
    if ($name_ext == $filename) {
      $filename = date('Ymd_His').$name_ext;
    }
    // check error
    $err = isset($_FILES['userfile']['error']) ? $_FILES['userfile']['error'] : 0;
    if ($err) {
      $err = intval($err);
      if ($err == 1 || $err == 2) {
        konawiki_error(
          "ファイルのアップロードエラー: エラー番号($err) ファイルが大きすぎます。");
      } else if ($err == 7) {
        konawiki_error(
          "ファイルのアップロードエラー: エラー番号($err) ディスク書き込みエラー。");
      } else {
        konawiki_error(
          "ファイルのアップロードエラー: エラー番号($err)");
      }
      exit;
    }
    // check dir
    $uploaddir = konawiki_private('dir.attach');
    if (!is_writable($uploaddir)) {
      konawiki_error(
        "アップロードフォルダが正しく設定されていません。<br>".
        "パーミッションを確認してください。");
      return;
    }
    // check ext
    $ext = konawiki_getContentType($name);
    if ($ext == "application/octet-stream" || $ext == "text/html") {
      konawiki_error(
        "アップロードできない形式です。<br>".
        "ファイル形式を確認してください。");
      return;
    }
    // check db
    db_begin();
    $mtime = time();
    // check same file, overwrite?
    $sql = 
        "SELECT * FROM attach WHERE name=? AND ".
        "log_id=?";
    $res = db_get1($sql, [$name, $log_id]);
    if (isset($res["id"])) {
      // check password
      db_rollback();
      action_attach_already_exists($res);
      return;
    } else {
      // insert into db
      $sql = 
        "INSERT INTO attach (log_id,name,ext,ctime,mtime)".
        "             VALUES(     ?,   ?,  ?,    ?,    ?)";
      $id = db_insert($sql, [
          $log_id, $filename, $ext, $mtime, $mtime
      ]);
      $sql = "INSERT INTO attach_counters (id) VALUES (?)";
      db_exec($sql, [$id]);
    }
    // copy file
    $uploadfile = $uploaddir . "/{$id}{$name_ext}";
    if (!move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
      db_rollback();
      konawiki_error("添付に失敗。アップロードエラー。");
      return;
    }
    db_commit();
    // include
    $page_link = konawiki_getPageLink();
    $name_htm = htmlspecialchars($filename);
    $name_enc = rawurlencode($filename);
    $baseurl = konawiki_public("baseurl");
    $page_ = konawiki_getPageURL();
    $attach_ = "{$baseurl}{$page_}/attach?file={$name_htm}";
    $attach_ = konawiki_getPageURL($page, "attach", "", "file={$name_enc}");
    $attach_link = "<a href='{$attach_}'>$name_htm</a>";
    $back_link = konawiki_getPageURL($page, "attach");
    // message
    $body = <<<__EOS__
<h4>添付成功</h4>
<blockquote>
<p>[{$page_link}] に [{$attach_link}] を 添付しました。</p>
<p>[<a href="{$back_link}">→一覧を確認する/→他のファイルを添付</a>]</p>
</blockquote>
__EOS__;
    header('X-Frame-Options: SAMEORIGIN');
    konawiki_showMessage($body);
}

function action_attach_already_exists($attach)
{
    extract($attach);
    $baseurl = konawiki_public("baseurl");
    $page = konawiki_getPage();
    $page_enc = urlencode($page);
    $page_htm = htmlspecialchars($page);
    $name = htmlspecialchars($name);
    $body = <<<__EOS__
<h1>添付に失敗</h1>
<p>[{$page_htm}] には既に [{$name}] が添付されています。
名前を変更するか削除してください。</p>
<blockquote>
<form action="{$baseurl}">
<input type="hidden" name="page" value="{$page}"/>
<input type="hidden" name="action" value="attach"/>
<input type="hidden" name="stat" value="delete"/>
<input type="hidden" name="id" value="{$id}"/>
<input type="submit" value="ファイルを削除する"/>
</form>
<form action="{$baseurl}">
<input type="hidden" name="page" value="{$page}"/>
<input type="hidden" name="action" value="attach"/>
<input type="hidden" name="stat" value=""/>
<input type="submit" value="別の名前で投稿する"/>
</form>
</blockquote>
__EOS__;
    header('X-Frame-Options: SAMEORIGIN');
    konawiki_showMessage($body);
}

function action_attach_up()
{
    if (!konawiki_checkEditToken()) {
        konawiki_error("編集エラー。ページを戻って画面を更新してください。");
        return;
    }
    if (!konawiki_auth()) return;
    $baseurl = konawiki_public("baseurl");
    $id = konawiki_param('id', 0);
    if (!is_numeric($id)) {
        konawiki_error("更新エラー。id が不正です。");
        return;
    }
    $id = intval($id);
    $sql = "SELECT * FROM attach WHERE id=?";
    $info = db_get1($sql, [$id]);
    if (!isset($info['id'])) {
        konawiki_error("更新エラー。id が不正です。");
        return;
    }
    $name = $info["name"];
    $name_html = htmlspecialchars($name);
    // 更新実行
    $sql = "UPDATE attach SET mtime=? WHERE id=?";
    db_exec($sql, [time(), $id]);
    $p = konawiki_getPageInfoById($info['log_id']);
    $page = $p['name'];
    $url = konawiki_getPageURL($page, 'attach');
    $body = "<p class='box'><a href='$url'>日付を更新しました</a></p>";
    konawiki_showMessage($body);
}

function action_attach_delete()
{
    if (!konawiki_checkEditToken()) {
        konawiki_error("削除エラー。ページを戻って画面を更新してください。");
        return;
    }
    if (!konawiki_auth()) return;
    $baseurl = konawiki_public("baseurl");
    $id = konawiki_param('id', 0);
    if (!is_numeric($id)) {
        konawiki_error("削除エラー。id が不正です。");
        return;
    }
    $id = intval($id);
    $sql = "SELECT * FROM attach WHERE id=?";
    $info = db_get1($sql, [$id]);
    if (!isset($info['id'])) {
        konawiki_error("削除エラー。id が不正です。");
        return;
    }
    db_begin();
    $name = $info["name"];
    $name_html = htmlspecialchars($name);
    // 削除実行！
    $sql = "DELETE FROM attach WHERE id=?";
    db_exec($sql, [$id]);
    // カウンタもリセット
    $sql = "DELETE FROM attach_counters WHERE id=?";
    db_exec($sql, [$id]);
    // 
    $page = konawiki_getPage();
    $page_enc = konawiki_getPageURL($page);
    $page_htm = htmlspecialchars($page);
    $name = htmlspecialchars($name);
    $backlink = konawiki_getPageURL($page, "attach");
    //
    $file = KONAWIKI_DIR_ATTACH.'/'.$id;
    $baseurl = konawiki_public("baseurl");
    @unlink($file);
    db_commit();
    $body = "<p>(id:{$id})「{$name_html}」を削除しました。</p>".
        "<p><a href='{$backlink}'>→「{$page_htm}」の添付へ戻る</a></p>";
    header('X-Frame-Options: SAMEORIGIN');
    konawiki_showMessage($body);
}

function konawiki_getContentType($filename)
{
    include_once(KONAWIKI_DIR_LIB."/mime.inc.php");
    $mime = mime_content_type_e($filename);
    return $mime;
}

function konawiki_getAttachList($page)
{
    $log_id = konawiki_getPageId($page);
    if ($log_id == FALSE) {
        return FALSE;
    }
    $sql = "SELECT * FROM attach WHERE log_id=? ORDER BY mtime ASC";
    $res = db_get($sql, [$log_id]);
    return $res;
}

function konawiki_getAttachListLink($page)
{
    $res = konawiki_getAttachList($page);
    if ($res === FALSE) {
        return FALSE;
    }
    $page_ = urlencode($page);
    $baseurl = konawiki_public("baseurl");
    $list = array();
    foreach ($res as $line) {
        $name = $line["name"];
        $nameu = urlencode($name);
        $nameh = htmlspecialchars($name, ENT_QUOTES);
        $url = "{$baseurl}{$page_}/attach?file=$nameu";
        $link = "<a href='$url'>$nameh</a>";
        $list[] = $link;
    }
    return $list;
}


