<?php
/** konawiki plugins -- ページにナビを表示するプラグイン(BLOG用)
 * - [書式] #blognavi([pattern],header])
 * - [引数]
 * -- pattern .. ナビのパターン
 * -- header .. ヘッダに表示する文
 * - [使用例] #blognavi()
 * - [備考] blogtop プラグインと組み合わせて使う
 * - [公開設定] 公開
 */
 
function plugin_blognavi_convert($params)
{
    global $konawiki_parser_depth;
    if ($konawiki_parser_depth >= 2) return "";
    
    konawiki_setPluginDynamic(true);
    
    // check params
    $pat = isset($params[0]) ? $params[0] : "";
    $FrontPage = konawiki_public('FrontPage');
    // db
    $where = '';
    $order = 'ctime';
    $params = [$FrontPage];
    if ($pat) {
        $pat_ = $pat;
        $pat_ = str_replace('*','%',$pat_);
        if (strpos($pat_, '%') === FALSE) {
            $pat .= '%';
        }
        $order = 'name';
        $where = "AND name like ?";
        $params[] = $pat_;
    }
    $sql =
        "SELECT id,name,ctime FROM logs ".
        "   WHERE name!=?  $where".
        "   ORDER BY $order DESC";
    $rows = db_get($sql, $params);
    $rows_count = count($rows);
    if ($rows_count == 0) return "";
    $page = konawiki_getPage();
    $prev = null;
    $cur  = null;
    $next = null;
    for ($i = 0; $i < $rows_count; $i++) {
        $row = $rows[$i];
        if ($row['name'] == $page) {
            if ($i >= 1) {
                $prev = $rows[$i-1];
            }
            $cur = $row;
            if ($i < ($rows_count-1)) {
                $next = $rows[$i+1];
            }
        }
    }
    $navi = array();
    // -----------------------
    // left
    $link = "*";
    if ($next != null) {
        $name  = $next['name'];
        $name_ = htmlspecialchars($name);
        $url   = konawiki_getPageURL($name);
        $link = "<a href='{$url}'>←前</a>";
    }
    $s = "<li class='navi_left'>$link</li>";
    array_push($navi, $s);
    // -----------------------
    // right
    $link = "*";
    if ($prev != null) {
        $name  = $prev['name'];
        $name_ = htmlspecialchars($name);
        $url   = konawiki_getPageURL($name);
        $link = "<a href='{$url}'>次→</a>";
    }
    $s = "<li class='navi_right'>$link</li>";
    array_push($navi, $s);
    // -----------------------
    // top
    $name = konawiki_public("FrontPage");
    $name_= htmlspecialchars($name);
    $url  = konawiki_getPageURL($name);
    $link = "<li class='navi_none'><a href='{$url}'>↑{$name_}</a></li>";
    array_push($navi, $link);
    $res = join(' ', $navi);
    $navi_ = "<ul class='topnavi'>{$res}</ul>";
    //
    $url = konawiki_getPageURL();
    $name = konawiki_getPage();
    $log = konawiki_getLog($name);
    $name_ = konawiki_getPageLink($name, "dir");
    if (isset($log['ctime']) && is_int($log['ctime'])) {
      $date = konawiki_date($log['ctime']);
    } else {
      $date = ''; 
    }
    // bookmark
    $name_u = urlencode($name);
    $url_   = urlencode($url);
    $bookmark = "";
    // header navi
    $frontpage = konawiki_public('FrontPage');
    $link = konawiki_getPageURL2($frontpage, FALSE, FALSE);
    $toplink = "<a href='$link'>[↑]</a> ";
    $head = <<< __EOS__
<nav>$navi_</nav>
<header>
<div class="topnavipagename">{$toplink} &nbsp; {$name_} &nbsp; {$bookmark}</div>
</header>
__EOS__;
    if (isset($params[1])) {
        $head .= konawiki_parser_convert($params[1])."\n";
    }
    return $head;
}


