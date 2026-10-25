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
      // 認証を無効にしたWikiでも、匿名セッション用のCSRFトークンを発行する。
      'edit_token' => konawiki_getEditToken(TRUE),
    ]);
}

function action_attach_write()
{
    // CSRF対策: アップロードには edit_token を必須とする
    if (!konawiki_checkEditToken()) {
        konawiki_error("編集エラー。ページを戻って画面を更新してください。");
        return;
    }
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
    $comment = trim(isset($_POST['comment']) ? $_POST['comment'] : '');
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
    // アップロード時のファイル名も、名前変更 (Issue #22) と同じ検証を行う (Issue #48)
    // 実ファイルは元ファイルの拡張子で {id}{拡張子} として保存され、ダウンロードは
    // DB の名前の拡張子から実ファイルを組み立てるため、拡張子は元ファイルと
    // 完全に一致させる。検証対象は、ファイル名欄が空の場合に使われる
    // 元ファイル名も含む、最終的な保存名 $filename とする。
    $filename_err = konawiki_attach_validate_rename($name, $filename);
    if ($filename_err !== '') {
      konawiki_error($filename_err);
      return;
    }
    // check comment (ファイル自体の検証を通過してから行う)
    if (mb_strlen($comment) > 500) {
      konawiki_error("コメントが長すぎます。(500文字まで)");
      return;
    }
    // check db
    db_begin();
    $mtime = time();
    // 同名のファイルが既にある場合は、連番を付けて保存する (Issue #22)
    // 重複チェックは、実際に保存される名前 (指定されたファイル名) で行う
    $filename_req = $filename;
    $filename = konawiki_attach_unique_name($log_id, $filename);
    // insert into db
    $has_comment_col = konawiki_attach_ensure_comment_column();
    if (!$has_comment_col && $comment !== '') {
      // comment カラムを用意できない状態で、入力されたコメントを
      // 無言で落としてアップロードを成功させない (Codexレビュー指摘)
      db_rollback();
      konawiki_error("コメントを保存できませんでした。時間をおいて再度お試しください。");
      return;
    }
    if ($has_comment_col) {
      $sql =
        "INSERT INTO attach (log_id,name,ext,comment,ctime,mtime)".
        "             VALUES(     ?,   ?,  ?,      ?,    ?,    ?)";
      $id = db_insert($sql, [
          $log_id, $filename, $ext, $comment, $mtime, $mtime
      ]);
    } else {
      $sql =
        "INSERT INTO attach (log_id,name,ext,ctime,mtime)".
        "             VALUES(     ?,   ?,  ?,    ?,    ?)";
      $id = db_insert($sql, [
          $log_id, $filename, $ext, $mtime, $mtime
      ]);
    }
    $sql = "INSERT INTO attach_counters (id) VALUES (?)";
    db_exec($sql, [$id]);
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
    $numbered = '';
    if ($filename !== $filename_req) {
      $filename_req_htm = htmlspecialchars($filename_req);
      $numbered = "<p>(同名のファイルがあったため「{$filename_req_htm}」は「{$name_htm}」になりました)</p>";
    }
    $body = <<<__EOS__
<h4>添付成功</h4>
<blockquote>
<p>[{$page_link}] に [{$attach_link}] を 添付しました。</p>
{$numbered}
<p>[<a href="{$back_link}">→一覧を確認する/→他のファイルを添付</a>]</p>
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
    // 表示名と元ファイルの拡張子は一致するとは限らない。
    konawiki_removeAttachFiles($id);
    db_commit();
    $body = "<p>(id:{$id})「{$name_html}」を削除しました。</p>".
        "<p><a href='{$backlink}'>→「{$page_htm}」の添付へ戻る</a></p>";
    header('X-Frame-Options: SAMEORIGIN');
    konawiki_showMessage($body);
}

function action_attach_comment()
{
    if (!konawiki_checkEditToken()) {
        konawiki_error("編集エラー。ページを戻って画面を更新してください。");
        return;
    }
    if (!konawiki_auth()) return;
    $id = konawiki_param('id', 0);
    if (!is_numeric($id)) {
        konawiki_error("更新エラー。id が不正です。");
        return;
    }
    $id = intval($id);
    $comment = trim((string)konawiki_param('comment', ''));
    if (mb_strlen($comment) > 500) {
        konawiki_error("コメントが長すぎます。(500文字まで)");
        return;
    }
    $sql = "SELECT * FROM attach WHERE id=?";
    $info = db_get1($sql, [$id]);
    if (!isset($info['id'])) {
        konawiki_error("更新エラー。id が不正です。");
        return;
    }
    // 表示中のページの添付ファイルであることを確認する
    // (ページが特定できない場合も、所属不明として更新しない)
    $log_id = konawiki_getPageId();
    if ($log_id === FALSE || intval($info['log_id']) !== intval($log_id)) {
        konawiki_error("更新エラー。このページの添付ファイルではありません。");
        return;
    }
    if (!konawiki_attach_set_comment($id, $comment)) {
        konawiki_error("更新エラー。コメントを保存できませんでした。");
        return;
    }
    $p = konawiki_getPageInfoById($info['log_id']);
    $page = $p['name'];
    $url = konawiki_getPageURL($page, 'attach');
    $body = "<p class='box'><a href='$url'>コメントを更新しました</a></p>";
    konawiki_showMessage($body);
}

/**
 * 添付ファイルの名前を変更する (Issue #22)
 */
function action_attach_rename()
{
    if (!konawiki_checkEditToken()) {
        konawiki_error("編集エラー。ページを戻って画面を更新してください。");
        return;
    }
    if (!konawiki_auth()) return;
    $id = konawiki_param('id', 0);
    if (!is_scalar($id) || !preg_match('/^\d+$/', (string)$id)) {
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
    // 表示中のページの添付ファイルであることを確認する
    // (ページが特定できない場合も、所属不明として更新しない)
    $log_id = konawiki_getPageId();
    if ($log_id === FALSE || intval($info['log_id']) !== intval($log_id)) {
        konawiki_error("更新エラー。このページの添付ファイルではありません。");
        return;
    }
    $newname = trim((string)konawiki_param('newname', ''));
    $p = konawiki_getPageInfoById($info['log_id']);
    $page = $p['name'];
    $url = konawiki_getPageURL($page, 'attach');
    // 同名の場合は更新しない
    if ($newname === (string)$info['name']) {
        $body = "<p class='box'><a href='$url'>ファイル名は同じです</a></p>";
        konawiki_showMessage($body);
        return;
    }
    $err = konawiki_attach_validate_rename($info['name'], $newname);
    if ($err !== '') {
        konawiki_error($err);
        return;
    }
    // 同名がある場合は連番を付けた名前にする (Issue #22)
    $unique = konawiki_attach_unique_name($info['log_id'], $newname, $id);
    if (!konawiki_attach_rename($id, $unique, $info['log_id'])) {
        // 同時変更で重複が発生した場合も、その旨を表示する
        $sql = "SELECT id FROM attach WHERE log_id=? AND name=? AND id<>?";
        $dup = db_get1($sql, [$info['log_id'], $unique, $id]);
        if (isset($dup['id'])) {
            konawiki_error("変更エラー。同じ名前のファイルが既に添付されています。");
            return;
        }
        konawiki_error("更新エラー。ファイル名を変更できませんでした。");
        return;
    }
    $body = "<p class='box'><a href='$url'>ファイル名を変更しました</a>";
    if ($unique !== $newname) {
        // 連番が付いた場合は利用者に最終的な名前を伝える
        $unique_htm = htmlspecialchars($unique);
        $body .= "(同名のファイルがあったため「{$unique_htm}」になりました)";
    }
    $body .= "</p>";
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
    // 表示のみでは comment カラムの移行 (DDL) を実行しない。
    // カラムが無い既存DBではコメントは空として表示され、移行は書き込み時に行われる (Issue #46)
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


