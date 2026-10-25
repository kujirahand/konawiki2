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

function plugin_attachfiles_arg_comment($params, $name)
{
  // ファイル名に '=' が含まれる場合にも対応するため、
  // 「ファイル名=」で始まる引数を探す。指定がなければ null、
  // 空コメントの指定があれば '' を返す (後勝ち)。
  $comment = null;
  if (!$params) {
    return $comment;
  }
  $prefix = $name . '=';
  foreach ($params as $param) {
    $param = trim((string)$param);
    if (strpos($param, $prefix) === 0) {
      $comment = trim(substr($param, strlen($prefix)));
    }
  }
  return $comment;
}

function plugin_attachfiles_resolve_comments($params, $names)
{
  // 引数 (ファイル名=コメント) を、実際に添付されているファイル名の
  // 一覧に対して解決する。「ファイル名=」に前方一致する名前が複数ある
  // 場合は最も長い名前を優先する。たとえば添付に `data` と
  // `data=2023.csv` の両方があるとき、`data=2023.csv=集計結果` は
  // `data=2023.csv` への指定として解釈し、`data` には適用しない。
  // 戻り値: [ファイル名 => コメント] (後勝ち)
  $result = array();
  if (!$params) {
    return $result;
  }
  foreach ($params as $param) {
    $param = trim((string)$param);
    if ($param === '') {
      continue;
    }
    $best = null;
    $best_comment = null;
    foreach ($names as $name) {
      $prefix = $name . '=';
      if (strpos($param, $prefix) === 0) {
        if ($best === null || strlen($name) > strlen($best)) {
          $best = $name;
          $best_comment = trim(substr($param, strlen($prefix)));
        }
      }
    }
    if ($best !== null) {
      $result[$best] = $best_comment;
      continue;
    }
    // 前方一致しない場合は先頭の '=' で分割し、添付名と完全一致する
    // 場合のみ採用する (' a.png = spaced ' のような指定への対応)
    $pos = strpos($param, '=');
    if ($pos === FALSE) {
      continue;
    }
    $name = trim(substr($param, 0, $pos));
    if ($name === '' || !in_array($name, $names, TRUE)) {
      continue;
    }
    $result[$name] = trim(substr($param, $pos + 1));
  }
  return $result;
}

function plugin_attachfiles_convert($params)
{

  // attachファイル一覧を得る
  $log_id = konawiki_getPageId();
  // 表示では comment カラムの移行 (DDL) を行わない (Issue #46)。
  // カラムが無い既存DBでも konawiki_attach_row_comment() が空文字列を返すので、
  // DBコメントの読み出しはそのまま動作する。移行は書き込み時に行われる。

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
  // 引数で指定されたファイル毎のコメント (Issue #21)
  // 実際の添付名一覧に対して最長一致で解決する
  $attach_names = array();
  if ($rows) {
    foreach ($rows as $row_for_names) {
      $attach_names[] = $row_for_names['name'];
    }
  }
  $arg_comments = plugin_attachfiles_resolve_comments($params, $attach_names);
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
