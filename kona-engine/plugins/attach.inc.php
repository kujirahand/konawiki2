<?php

/** konawiki plugins -- WIKIリンク(階層)や編集リンクを生成するプラグイン
 * - [書式] #attach(name,caption)
 * - [引数] 
 * -- name       添付名 
 * -- caption    キャプション
 * - [使用例] #attach(test.png,hogehoge) 
 * - [備考]
 */

function plugin_attach_convert($params)
{
    if (count($params) == 0) {
        return "[書式: #link(名前)]";
    }
    $name     = array_shift($params);  
    $caption  = array_shift($params);
    $page_id  = konawiki_getPageId();

    // attachファイルのパスを得る
    $db = konawiki_getDB();
    $fname_ = $db->escape($name);
    $sql = "SELECT * FROM attach WHERE log_id=$page_id AND name='$fname_' LIMIT 1";
    $res = $db->array_query($sql);
    if (!isset($res[0]['id'])) {
      $fname_ = htmlspecialchars($fname);
      return "<div class='error'>[#ref:file not found:$fname_]</div>";
    }
    $id   = $res[0]['id'];
    $mime = $res[0]['ext'];
    $name = $res[0]['name'];
    // get real ext
    $ext = "";
    if (preg_match('/(\.\w+)$/',$name, $m)) {
      $ext = $m[1];
    }
    if (!$caption) $caption = $name;
    $caption_ = htmlspecialchars($caption, ENT_QUOTES);
    $file_url = konawiki_private('uri.attach')."/{$id}{$ext}";
    $link = "<a href='$file_url'>$caption_</a>";
    return $link;
}

