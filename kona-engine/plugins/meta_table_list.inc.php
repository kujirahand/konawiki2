<?php
/** konawiki plugins -- メタテーブルプラグイン 
 * - [書式] {{{ #meta_table_list }}}
 * - [引数] キー, 条件
 * - [使用例]
 * - [備考] メタアイテムを表示する
 * - [公開設定] 公開
 */

// library
require_once __DIR__.'/meta_table/lib.inc.php';
require_once __DIR__.'/meta_table/template.inc.php';

function plugin_meta_table_list_convert($params) {
  $main_key = array_shift($params);
  $cond = array_shift($params);
  if ($cond) {
    $cond_a = explode('=', $cond.'=');
    $cond_key = trim($cond_a[0]);
    $cond_val = trim($cond_a[1]);
  }
  $offset = empty($_GET['offset']) ? 0 : intval($_GET['offset']);
  $sql = 
    'SELECT * FROM logs '.
    'WHERE (name LIKE ?)AND(private=0) '.
    'ORDER BY id DESC LIMIT ? OFFSET ?';
  $rows = db_get($sql, [$main_key."/%", KONA_META_LIMIT, $offset]);
  //
  $html = '';
  $html .= meta_table_template('list_head.inc.html', []);
  $html .= '<div id="meta_table_list">'."\n";
  if (!$rows) {
    $html .= "<p class='list_item'>見当たりません。</p>";
    $rows = [];
  } else {
    $cnt = 0;
    foreach ($rows as $i) {
      $pagename = $i['name'];
      $log_id = $i['id'];
      $pagelink = konawiki_getPageURL($pagename);
      $meta = db_get1(
        'SELECT * FROM sublogs WHERE plug_name=? AND log_id=? LIMIT 1',
        ['meta_table', $log_id], 'sub');
      if (!$meta) { continue; }
      $meta_body = json_decode($meta['body'], TRUE);
      if ($cond)  {
        if (!isset($meta_body[$cond_key])) { continue; }
        if ($meta_body[$cond_key] != $cond_val) { continue; }
      }
      $mb = "<table>\n";
      foreach ($meta_body as $key => $val) {
        if ($val == '') {continue;}
        $key_h = htmlspecialchars($key, ENT_QUOTES);
        $val_h = htmlspecialchars($val, ENT_QUOTES);
        $mb .= "<tr>";
        $mb .= "<th><span class='key'>$key_h</span></th>";
        $mb .= "<td><span class='val'>".
               "<a href='$pagelink'>$val_h</a>".
               "</span></td>";
        $mb .= "</tr>\n";
      }
      $mb .= "</table>\n";
      $mname = substr($pagename, strlen($main_key)+1);
      $html .= meta_table_template('list_item.inc.html', [
        'pagename' => $pagename,
        'mname' => $mname,
        'pagelink' => $pagelink,
        'meta' => $mb,
      ]);
      $linkshow = konawiki_getPageURL($pagename);
      $cnt++;
    }
    if ($cnt == 0) {
      $html .= '<p class="list_item">見当たりません。</p>';
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
  $html .= "</div>\n";
  return $html;
 
}

