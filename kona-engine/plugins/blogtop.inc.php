<?php
/* vim:set expandtab ts=4:*/
/** konawiki plugins -- BLOGのトップページっぽい表示をするプラグイン(BLOG用)
 * - [書式] #blogtop([count][,pattern][,header][,entry.footer][,article.len])
 * - [引数]
 * - count   .. 何日分表示するか
 * - pattern .. 列挙するパターンの指定
 * - header  .. ヘッダとして表示WIKIソース
 * - entry.footer .. 各エントリの最下行に差し込むWIKIソース
 * - [使用例] #blogtop()
 * - [備考] blogtop プラグインと組み合わせて使う
 * - page/show のプラグインとして利用する場合、設定ファイルに以下を記述する
 * {{{
 * $konawiki['private']['show.plugins']['blog'] = array(
 *      'enabled'   => TRUE,
 *      'file'      => 'blogtop.inc.php',
 *      'entry'     => 'show_plugin_blogtop',
 *      'count'     => 5,    // index page list count
 *      'pattern'   => '*',// like "2008/01/01"
 *      'header.code'  => '',
 *      'entry.footer' => '#googleadsense(blog)',
 *      'article.len'  => 200, // article char length (all=0)
 * );
 * }}}
 */

function plugin_blogtop_convert($params)
{
	//
	konawiki_setPluginDynamic(true);
	//
  $pname = "blogtop";
  // only once
  $c = konawiki_getPluginInfo($pname, "shown", FALSE);
  if ($c == TRUE) return "";
  konawiki_setPluginInfo($pname,"shown", TRUE);
  // check args
  $day          = intval(array_shift($params));
  $pat          = array_shift($params);
  $headede_code = array_shift($params);
  $entry_footer = array_shift($params);
  $article_len  = array_shift($params);
  if ($day == 0) $day = 5;
  // pager
  $pager = "";
  $start = intval(konawiki_param("blogtop_start", 1)) - 1; if ($start < 0) $start = 0;
  $offset = $start * $day;
  $limit  = $day + 1;
  $defpage = konawiki_param('page');
  if ($start >= 1) {
    $uri = konawiki_getPageURL(
      $defpage, FALSE, FALSE, 
      "blogtop_start=$start", TRUE);
    $pager .= "<a href='$uri'>←".konawiki_lang('Prev')."</a> ";
  }
  // sql
  $where = 'WHERE (private = 0) ';
  $order = 'id';
  $params = [];
  if ($pat) {
    $pat = str_replace('*', '%', $pat);
    if (strpos($pat, '%') === FALSE) {
        $pat .= '%';
    }
    $order = 'id';
    $where .= " AND (name like ?)";
    $params[] = $pat;
  }
  if ($day > 0) {
    $sql = "SELECT * FROM logs $where ORDER BY $order DESC ".
           "LIMIT $limit OFFSET $offset";
    $rows = db_get($sql, $params);
  } else {
    $rows = array();
  }
  if ($rows == null || count($rows) == 0) {
    return "";
  }
  // pager next
  if (count($rows) == $limit) {
    array_pop($rows);
    $p = $start + 2;
    $uri = konawiki_getPageURL($defpage, FALSE, FALSE, "blogtop_start=$p", TRUE);
    $pager .= "<a href='$uri'>".konawiki_lang('Next')."→</a> ";
  }
    
  $res = "";
  if ($pager != "") {
    $res .= "<div class='pager'>{$pager}</div>";
  }
  foreach ($rows as $log) {
    $name  = $log['name'];
    if ($name == 'FrontPage' || $name == 'MenuBar' || 
        $name == 'SideBar' || $name == 'NaviBar') continue;
    $name_ = konawiki_getPageLink($name,'dir');
    $_GET['page'] = $_POST['page'] = $name;
    $body = trim($log['body']);
    if ($article_len > 0) {
      $body = preg_replace('/#\w+/', '', $body);
      $body = mb_strimwidth($body, 0, $article_len, '...');
    }
    $pageurl = konawiki_getPageURL($name);
    $url = urlencode($pageurl);
    $date = konawiki_date(intval($log['mtime']));
    $bookmark = '';
    # get comment
    #include_once(KONAWIKI_DIR_PLUGINS."/comment.inc.php");
    # body
    $entry_begin = konawiki_private("entry_begin");
    $entry_end   = konawiki_private("entry_end");
    # $body .= "\n".$entry_footer;
    $res .= 
      "{$entry_begin}\n".
      "<h3><a href='{$pageurl}'>■</a> {$name_} <span class='date'>($date)</span> $bookmark</h3>\n".
      konawiki_parser_convert($body)."\n".
      #konawiki_comment_getLog($name)."\n".
      "<footer class='rightopt'>[<a href='$pageurl'>→Read</a>]&nbsp;</footer>".
      "{$entry_end}\n";
  }
  $_GET['page'] = $_POST['page'] = $defpage;
  if (isset($headede_code)) {
    $res = konawiki_parser_convert($headede_code).$res;
  }
  if ($pager != "") {
    $res .= "<div class='pager'>{$pager}</div>";
  }
  return $res;
}

/**
 * SHOW-PLUGIN ENTRY POINT
 */
function show_plugin_blogtop($plugin, $log)
{
    global $konawiki_show_log;
    $front = konawiki_public('FrontPage');
    $page = konawiki_getPage();
    if ($page === $front) {
        show_plugin_blogtop__front($plugin, $log);
    }
    else{
        show_plugin_blogtop__navi($plugin, $log);
    }
    $konawiki_show_log = $log;
}

function show_plugin_blogtop__front(&$plugin, &$log)
{
    $params = array(
        $plugin['count'],
        $plugin['pattern'],
        isset($plugin['header.code']) ? $plugin['header.code']   : '',
        isset($plugin['entry.footer']) ? $plugin['entry.footer'] : '',
        isset($plugin['article.len']) ? $plugin['article.len']   : 0,
        );
    $head = plugin_blogtop_convert($params);
    $log['body_header'] .= $head;
}

function show_plugin_blogtop__navi(&$plugin, &$log)
{
    include_once(KONAWIKI_DIR_PLUGINS."/blognavi.inc.php");
    $params = array(
        $plugin['pattern'],
        isset($plugin['header.code']) ? $plugin['header.code'] : '',
    );
    $head = plugin_blognavi_convert($params);
    if ($log['id'] > 0) {
        $ft = isset($plugin['entry.footer']) ? $plugin['entry.footer'] : "";
        $foot = konawiki_parser_convert($ft);
    } else {
        $foot = "";
    }
    if (empty($log['body_header'])) $log['body_header'] = '';
    if (empty($log['body_footer'])) $log['body_footer'] = '';
    $log['body_header'] .= "<div class='contents'>{$head}</div>";
    $log['body_footer'] = $foot."\n".$log['body_footer'];
}

