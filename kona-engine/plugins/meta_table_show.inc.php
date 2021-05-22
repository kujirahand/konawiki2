<?php
/** konawiki plugins -- メタテーブルプラグイン 
 * - [書式] {{{#meta_table_show }}}
 * - [引数]
 * - [使用例]
 * - [備考] メタアイテムを表示する
 * - [公開設定] 公開
 */

// require_once __DIR__.'/meta_table/index.inc.php';
// require_once __DIR__.'/attachfiles.inc.php';

function plugin_meta_table_show_convert($params) {
  // meta info
  $log_id = konawiki_getPageId();
  $sublog = db_get1('SELECT * FROM sublogs WHERE log_id=? AND plug_name=?',
    [$log_id, 'meta_table'], 'sub');
  if (!$sublog) { return '<!-- no meta_table data -->'; }
  $meta_obj = json_decode($sublog['body'], TRUE);
  $page_html = htmlspecialchars(konawiki_getPage(), ENT_QUOTES);
  $html = "<h1>$page_html</h1>\n";
  if ($meta_obj) {
    $html .= "<table>";
    foreach ($meta_obj as $key => $val) {
      $key_h = htmlspecialchars($key);
      $val_h = htmlspecialchars($val);
      $html .= "<tr>";
      $html .= "<th>$key_h</th><td>$val_h</td>";
      $html .= "</tr>";
    }
    $html .= '</table>'."\n\n";
  }
  return $html;
}

