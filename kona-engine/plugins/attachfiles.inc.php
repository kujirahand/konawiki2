<?php

/** konawiki plugins -- 添付ファイルの一覧を表示する
 * - [書式] #attachfiles([ファイル名=コメント,...])
 * - [引数]
 * -- ファイル名=コメント  ファイル毎に表示するコメントを指定する
 * - [使用例] #attachfiles()
 * - [使用例] #attachfiles(test.png=テスト画像,doc.pdf=説明資料)
 * - [備考]
 * -- コメントは添付画面でもファイル毎に指定でき、DBに保存される。
 *    プラグインの引数で指定したコメントがあれば、そちらを優先する。
 * -- コメントにカンマと閉じ括弧は使えない (他のプラグインと同じ制約)。
 */

function plugin_attachfiles_parse_comments($params)
{
  $comments = array();
  if (!$params) {
    return $comments;
  }
  foreach ($params as $param) {
    $param = trim((string)$param);
    if ($param === '') {
      continue;
    }
    $pos = strpos($param, '=');
    if ($pos === FALSE) {
      continue;
    }
    $name = trim(substr($param, 0, $pos));
    $comment = trim(substr($param, $pos + 1));
    if ($name === '') {
      continue;
    }
    $comments[$name] = $comment;
  }
  return $comments;
}

function plugin_attachfiles_convert($params)
{
  // 引数で指定されたファイル毎のコメント (Issue #21)
  $arg_comments = plugin_attachfiles_parse_comments($params);

  // attachファイル一覧を得る
  $log_id = konawiki_getPageId();
  // 既存DBでは comment カラムを遅延追加する
  if (function_exists('konawiki_attach_ensure_comment_column')) {
    konawiki_attach_ensure_comment_column();
  }

  $html = "<h3>添付ファイル一覧</h3>\n";
  $html .= "<style>";
  $html .= ".docbox { padding: 8px; background-color: #f0f0f0; }";
  $html .= ".imgbox { padding: 8px; background-color: #f0f0f0; }";
  $html .= "#attachfiles a { text-decoration: none; }";
  $html .= ".imglbl { font-size:0.8em; }";
  $html .= ".imgcomment { font-size:0.9em; }";
  $html .= ".doccomment { font-size:0.9em; }";
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
      // コメント: 引数指定があれば優先し、なければDB保存値を表示する
      $comment = '';
      if (function_exists('konawiki_attach_row_comment')) {
        $comment = konawiki_attach_row_comment($res);
      } else if (isset($res['comment'])) {
        $comment = (string)$res['comment'];
      }
      if (array_key_exists($name, $arg_comments)) {
        $comment = $arg_comments[$name];
      }
      $comment_h = htmlspecialchars($comment, ENT_QUOTES);
      $comment_html = '';
      $ext = "";
      if (preg_match('/(\.\w+)$/',$name, $m)) {
        $ext = $m[1];
      }
      $file_url = konawiki_private('uri.attach')."/{$id}{$ext}";
      $ext2 = strtolower($ext);
      if ($ext2 == '.jpg' || $ext2 == '.jpeg' || $ext2 == '.png' ||
          $ext2 == '.gif' || $ext2 == '.bmp') {
        if ($comment_h !== '') {
          $comment_html = "<br><span class='imgcomment'>{$comment_h}</span>\n";
        }
        $html .= "<p class='imgbox'><a href='$file_url'>";
        $html .= "<img src='$file_url' style='width:99%'><br>";
        $html .= "<span class='imglbl'>{$name_h}</span>\n";
        $html .= "</a>{$comment_html}</p>\n";
      } else {
        if ($comment_h !== '') {
          $comment_html = " <span class='doccomment'>{$comment_h}</span>";
        }
        $html .= "<p class='docbox'><a href='$file_url'>📒 {$name_h}</a>{$comment_html}</p>\n";
      }
    }
  } else {
    $html .= '<p>添付ファイルはありません。</p>';
  }
  $html .= "</div>\n";
  return $html;
}
