<?php
/** konawiki plugins -- 全ページにあるコメントを一覧表示するプラグイン
 * - [書式] #commentallpage(perpage)
 * - [引数]
 * -- perpage 1ページの表示件数
 * - [使用例] #commentallpage(30)
 */
 
include_once(KONAWIKI_DIR_LIB.'/konawiki_parser.inc.php');

function plugin_commentallpage_convert($params)
{
	konawiki_setPluginDynamic(true);
		
	$PERPAGE = 30;
    if (count($params) >= 1) {
        $PERPAGE = intval($params[0]);
        if ($PERPAGE <= 0) { $PERPAGE = 5; }
    }
    // query
    $db = konawiki_getSubDB();
    $log_id = konawiki_getPageId();
    if (!$log_id) {
        return "";
    }
    $start = floor(intval(konawiki_param("commentallpage_p", 1))) - 1;
    if ($start < 0) $start = 0;
    $offset = $start * $PERPAGE;
    $limit  = $PERPAGE + 1;
    $sql =  "SELECT * FROM sublogs WHERE plug_name='comment'".
            " ORDER BY mtime DESC".
            " LIMIT {$limit} OFFSET {$offset}";
    $r = $db->array_query($sql);
    $flag_nextpage = (count($r) > $PERPAGE);
    if ($flag_nextpage) {
        array_pop($r);
    }
    // make logs
    $logs = "";
    foreach ($r as $row) {
        $log_id = $row['log_id'];
        $body   = $row['body'];
        $page   = konawiki_getPageNameFromId($log_id);
        $page_link = konawiki_getPageLink($page);
        $html_body = konawiki_parser_convert($body);
        $logs .= <<<__EOS__
<h6>{$page_link}</h6>
<div class="commentlogs">
{$html_body}
</div>
__EOS__;
    }
    $footer = "";
    // make footer
    if ($start > 0) {
        $param = "commentallpage_p=" . $start;
        $uri = konawiki_getPageURL(false,false,false, $param);
        $footer .= "<a href='$uri'>←前へ</a> ";
    }
    if ($flag_nextpage) {
        $param = "commentallpage_p=" . ($start + 2);
        $uri = konawiki_getPageURL(false,false,false, $param);
        $footer .= "<a href='$uri'>次へ→</a> ";
    }
    $s = <<<__EOS
<div class="comment">
{$logs}
<div>{$footer}</div>
</div>
__EOS;
    return $s;
}
