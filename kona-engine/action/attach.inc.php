<?php

// index.php?(page)&attach&file=(filename)
// index.php?FrontPage&attach&file=xxx.txt

function action_attach_()
{
    // check file parameter
    $file = konawiki_param("file", FALSE);
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
    $db = konawiki_getDB();
    $file_ = $db->escape($file);
    $log_id_ = $db->escape($log_id);
    $sql = "SELECT * FROM attach WHERE log_id=$log_id_ AND ".
      "name='$file_'";
    $res = $db->array_query($sql);
    if (!isset($res[0]['id'])) {
        header('HTTP/1.0 404 Not Found'); exit;
    }
    $id     = $res[0]['id'];
    $mime   = $res[0]['ext'];
    $name   = $res[0]['name'];
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

function action_attach_form_parts()
{
    $page       = konawiki_getPage();
    $page_link  = konawiki_getPageLink($page);
    $baseurl    = konawiki_public("baseurl");
    $enabled    = konawiki_private('attach.enabled');

    $res = "<!-- attach form -->\n";

    if (!$enabled) {
        $res .= "<div class='error'>添付ファイルの機能は利用できない設定になっています。</div>";
        return $res;
    }
    $res .= <<<EOS__
<h4>「{$page_link}」にファイルの添付</h4>
<p>「{$page_link}」にファイルを添付します。</p>
<form
    enctype="multipart/form-data"
    action="{$baseurl}"
    method="POST">
<p>
  <input type="file" name="userfile" size=40
    style="border:solid 1px black;padding:8px;margin:4px;"/>
  <input type="submit" value="ファイルを添付">
  <input type="hidden" name="page" value="{$page}"/>
  <input type="hidden" name="action" value="attach"/>
  <input type="hidden" name="stat" value="write"/>
</p>
</form>
EOS__;
    return $res;
}

function action_attach_form()
{
    // show form
    $baseurl    = konawiki_public("baseurl");
    $page       = konawiki_getPageHTML();
    $page_link  = konawiki_getPageLink();
    $page_enc   = konawiki_getPageURL($page);
    $list = konawiki_getAttachList($page);
    if ($list) {
        $dlink = "<table border=1 cellpadding=4>\n".
            "<tr><td>直リンク</td><td>日付</td><td>削除</td>".
            "<td>Wikiに貼る時(小)</td><td>Wikiに貼る時(中)</td><td>Wikiに貼る時(原寸)</td></tr>\n";
        foreach ($list as $line) {
            $id     = $line['id'];
            $name   = $line['name'];
            $name_  = rawurlencode($name);
            $ext    = $line['ext'];
            $link   = konawiki_getPageURL($page, 'attach', '', "file=$name_");
            $name_h = htmlspecialchars($name);
            $con    = "<a href='$link'>$name_h</a>";
            $link_delete = konawiki_getPageURL($page, 'attach', 'delete');
            $button = "<form action='$link_delete' method='post'>".
                "<input type='hidden' name='id' value='$id'/>".
                "<input type='submit' value='削除'/></form>";
            $date   = konawiki_date($line['mtime']);
            $ref1   = "<input type='text' size=20 value='#ref($name_h,w=300,*{$name_})'onclick='this.select()'/>";
            $ref2   = "<input type='text' size=20 value='#ref($name_h,w=500,*{$name_})'onclick='this.select()'/>";
            $ref3   = "<input type='text' size=20 value='#ref($name_h,*{$name_})'onclick='this.select()'/>";
            $dlink .=
                "<tr><td>$con</td>".
                    "<td>$date</td>".
                    "<td>$button</td>".
                    "<td>$ref1</td>".
                    "<td>$ref2</td>".
                    "<td>$ref3</td>".
                "</tr>";
        }
        $dlink .= "</table>";
    }
    else {
        $dlink = '<p>ありません</p>';
    }
    $form = action_attach_form_parts();
    $body = <<<_EOS_
{$form}
<br/>
<!-- attach list -->
<h4>「{$page_link}」のファイル一覧</h4>
{$dlink}
<br/>
_EOS_;
    // include
    include_template("form.tpl.php", array('body'=>$body));
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
    $name = basename($_FILES['userfile']['name']);
    $name_ext = "";
    if (preg_match('/(\.\w+)$/', $name, $m)) {
      $name_ext = $m[1];
    }
    // check dir
    $uploaddir = KONAWIKI_DIR_ATTACH;
    if (!is_writable($uploaddir)) {
      konawiki_error(
        "アップロードフォルダが正しく設定されていません。<br>".
        "パーミッションを確認してください。");
      return;
    }
    // check ext
    $ext = konawiki_getContentType($name);
    if ($ext == "application/octet-stream") {
      konawiki_error(
        "アップロードできない形式です。<br>".
        "ファイ形式を確認してください。");
      return;
    }
    // check db
    $db = konawiki_getDB();
    $db->begin();
    $mtime = time();
    // check same file, overwrite?
    $name_ = $db->escape($name);
    $sql = "SELECT * FROM attach WHERE name='$name_' AND ".
        "log_id=$log_id;";
    $res = $db->array_query($sql);
    if (isset($res[0]["id"])) {
        // check password
        $db->rollback();
        action_attach_already_exists($res[0]);
        return;
    }
    else {
        // insert into db
        $sql = "INSERT INTO attach (log_id,name,ext,ctime,mtime)".
            "VALUES($log_id,'$name_','$ext',$mtime,$mtime)";
        if (!$db->exec($sql)) {
            $db->rollback();
            konawiki_error("添付に失敗。データベースエラー(1/2)。");
            return;
        }
        $id = $db->getLastId();
        $sql = "INSERT INTO attach_counters (id) VALUES ($id)";
        if (!$db->exec($sql)) {
            $db->rollback();
            konawiki_error("添付に失敗。データベースエラー(2/2)。");
            return;
        }
    }
    // copy file
    $uploadfile = $uploaddir . "/{$id}{$name_ext}";
    if (!move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
        $db->rollback();
        konawiki_error("添付に失敗。アップロードエラー。");
        return;
    }
    chmod($uploadfile, 0644); // ファイル権限の修正(FTP経由でバックアップできるように)
    $db->commit();
    // include
    $page_link = konawiki_getPageLink();
    $name_htm = htmlspecialchars($name);
    $name_enc = rawurlencode($name);
    $baseurl = konawiki_public("baseurl");
    $page_ = konawiki_getPageURL();
    $attach_ = "{$baseurl}{$page_}/attach?file={$name_htm}";
    $attach_ = konawiki_getPageURL($page, "attach", "", "file={$name_enc}");
    $attach_link = "<a href='{$attach_}'>$name_htm</a>";
    $back_link = konawiki_getPageURL($page, "attach");
    // message
    $form = action_attach_form_parts();
    $body = <<<__EOS__
<h4>添付成功</h4>
<blockquote>
<p>[{$page_link}] に [{$attach_link}] を 添付しました。</p>
<p>Wiki 貼り付けコード:</p>
<table>
  <tr><td>ノーマル(小)</td><td><input type="text" value="#ref($name_htm,w=300,*ここに画像タイトル)" size=40 onclick="this.select()"/></td></tr>
  <tr><td>ノーマル(中)</td><td><input type="text" value="#ref($name_htm,w=500,*ここに画像タイトル)" size=40 onclick="this.select()"/></td></tr>
  <tr><td>ノーマル(原寸)</td><td><input type="text" value="#ref($name_htm,*ここに画像タイトル)" size=40 onclick="this.select()"/></td></tr>
  <tr><td>インライン</td><td><input type="text" value="&ref($name_htm);" size=40 onclick="this.select()"/></td></tr>
</table>
<p>[<a href="{$back_link}">→一覧を確認する</a>]</p>
</blockquote>
<h6>さらに追加する場合:</h6>
{$form}
__EOS__;
    include_template("form.tpl.php", array('body'=>$body));
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
    include_template("form.tpl.php", array('body'=>$body));
}

function action_attach_delete()
{
    if (!konawiki_auth()) return;
    $baseurl = konawiki_public("baseurl");
    $id = konawiki_param('id', 0);
    if (!is_numeric($id)) {
        konawiki_error("削除エラー。id が不正です。");
        return;
    }
    $id = intval($id);
    //
    $sql = "SELECT * FROM attach WHERE id=$id;";
    $db = konawiki_getDB();
    $res = $db->array_query($sql);
    if (!isset($res[0]['id'])) {
        konawiki_error("削除エラー。id が不正です。");
        return;
    }
    //
    $db->begin();
    // ファイルがあるか確認
    $sql = "SELECT * FROM attach WHERE id=$id LIMIT 1";
    $info_ary = $db->array_query($sql);
    if (!$info_ary) {
        $db->rollback();
        konawiki_error("削除エラー。id が不正です。");
        return;
    }
    $info = $info_ary[0];
    $name = $info["name"];
    $name_html = htmlspecialchars($name);
    // 削除実行！
    $sql = "DELETE FROM attach WHERE id=$id;";
    if (!$db->exec($sql)) {
        $db->rollback();
        konawiki_error("削除エラー。id が不正です。");
        return;
    }
    // カウンタもリセット
    $sql = "DELETE FROM attach_counters WHERE id=$id";
    if (!$db->exec($sql)) {
        $db->rollback();
        konawiki_error("削除エラー。id が不正です。");
        return;
    }
    $page = konawiki_getPage();
    $page_enc = konawiki_getPageURL($page);
    $page_htm = htmlspecialchars($page);
    $name = htmlspecialchars($name);
    $backlink = konawiki_getPageURL($page, "attach");
    //
    $file = KONAWIKI_DIR_ATTACH.$id;
    $baseurl = konawiki_public("baseurl");
    @unlink($file);
    $db->commit();
    $body = "<p>(id:{$id})「{$name_html}」を削除しました。</p>".
        "<p><a href='{$backlink}'>→「{$page_htm}」の添付へ戻る</a></p>";
    include_template("form.tpl.php", array('body'=>$body));
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
    $db = konawiki_getDB();
    $sql = "SELECT * FROM attach WHERE log_id=$log_id";
    $res = $db->array_query($sql);
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
        $nameh = htmlspecialchars($name);
        $url = "{$baseurl}{$page_}/attach?file=$nameu";
        $link = "<a href='$url'>$nameh</a>";
        $list[] = $link;
    }
    return $list;
}



?>
