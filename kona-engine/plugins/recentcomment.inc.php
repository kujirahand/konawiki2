<?php
/** konawiki plugins -- 最近つけられたコメントを表示する
 * - [書式] #recentcomment([件数][,body])
 * - [引数]
 * -- 件数 .. 何件表示するかを表示
 * -- body .. コメントの本文を表示するかどうか
 * - [使用例] #recentcomment(10, body)
 * - [備考] comment プラグインと組み合わせて使う
 */
function plugin_recentcomment_convert($params)
{
	konawiki_setPluginDynamic(true);
	$db = konawiki_getSubDB();
    $res = "<h5>最新コメント:</h5>";
    # --- params
    $logCount = 10;
    $showComment = FALSE;
    foreach ($params as $row) {
        if (is_numeric($row)) {
            $logCount = intval($row);
        }
        else if ($row == 'body') {
            $showComment = TRUE;
        }
    }
    # --- count
    $count = $logCount;
    if ($count < 1) $count = 10;
    $field = "log_id,mtime";
    if ($showComment) $field .= ",body";
    $sql = "SELECT {$field} FROM sublogs ".
        " WHERE plug_name='comment' OR plug_name='article'".
        " ORDER BY mtime DESC LIMIT {$count}";
    $res .= "<ul>";
    $r = $db->array_query($sql);
    if ($r == FALSE) {
        return "";
    }
    $baseurl = konawiki_public("baseurl");
    foreach ($r as $e) {
        if (empty($e['log_id'])) continue;
        $log_id  = $e['log_id'];
        $name = konawiki_getPageNameFromId($log_id);
        $mtime = intval($e['mtime']);
        $mtime_ = konawiki_date_html($mtime);
        $nameurl = konawiki_getPageURL2($name);
        $name_ = preg_replace(
            '/([0-9a-zA-Z\/\-\_]{15,})/e',
            'substr(\'$1\',0,15).".."', $name);
        $name_ = htmlspecialchars($name_);
        $link = "<a href='{$nameurl}'>{$name_}</a> ($mtime_)";
        if ($showComment) {
            $body = konawiki_parser_convert($e['body']);
            $res .= "<li>$link<br/>$body</li>\n";
        }else{
            $res .= "<li>$link</li>\n";
        }
    }
    $res .= "</ul>";
    return $res;
}


?>
