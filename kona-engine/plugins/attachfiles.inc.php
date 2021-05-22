<?php

/** konawiki plugins -- 添付ファイルの一覧を表示する
 * - [書式] #attachfiles()
 * - [引数] 
 * -- name       添付名 
 * -- caption    キャプション
 * - [使用例] #attach(test.png,hogehoge) 
 * - [備考]
 */

function plugin_attachfiles_convert($params)
{
  // attachファイル一覧を得る
  $log_id = konawiki_getPageId();

  $html = "<h3>添付ファイル一覧</h3>\n";
  $html .= "<style>";
  $html .= ".docbox { padding: 8px; background-color: #f0f0f0; }";
  $html .= ".imgbox { padding: 8px; background-color: #f0f0f0; }";
  $html .= "#attachfiles a { text-decoration: none; }";
  $html .= ".imglbl { font-size:0.8em; }";
  $html .= "</style>";
  $html .= "<div id='attachfiles'>";
  $sql = 
    "SELECT * FROM attach WHERE ".
    "  log_id=? ORDER BY mtime ASC";
  $rows = db_get($sql, [$log_id]);
  if ($rows) {
    foreach ($rows as $res) {
      $id   = $res['id'];
      $mime = $res['ext'];
      $name = $res['name'];
      $name_h = htmlspecialchars($name, ENT_QUOTES);
      $ext = "";
      if (preg_match('/(\.\w+)$/',$name, $m)) {
        $ext = $m[1];
      } 
      $file_url = konawiki_private('uri.attach')."/{$id}{$ext}";
      $ext2 = strtolower($ext);
      if ($ext2 == '.jpg' || $ext2 == '.jpeg' || $ext2 == '.png' ||
          $ext2 == '.gif' || $ext2 == '.bmp') {
        $html .= "<p class='imgbox'><a href='$file_url'>";
        $html .= "<img src='$file_url' style='width:99%'><br>";
        $html .= "<span class='imglbl'>{$name_h}</span>\n";
        $html .= "</a></p>\n";
      } else {
        $name_h = htmlspecialchars($name, ENT_QUOTES);
        $html .= "<p class='docbox'><a href='$file_url'>📒 {$name_h}</a></p>\n";
      }
    }
  } else {
    $html .= '<p>添付ファイルはありません。</p>';
  }
  $html .= "</div>\n";
  return $html;
}

