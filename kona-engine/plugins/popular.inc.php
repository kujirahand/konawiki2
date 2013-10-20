<?php
/* vim:set expandtab ts=4 sts=4 sw=4: */
/** konawiki plugins -- カウンタのあるページの人気ランキングを表示する
 * - [書式] #popular([件数][,期限])
 * - [引数]
 * -- 件数  .. 表示する件数
 * -- 期限  .. ここに指定した日数内のランキングだけ集計する
 * - [使用例] #popular
 * - [備考] counter プラグインと組み合わせて使う
 */

function plugin_popular_convert($params)
{
	konawiki_setPluginDynamic(true);
	
    if (!isset($params[0])) $params[0] = "10";
    if (!isset($params[1])) $params[1] = "0";
    $count = intval($params[0]);
    if ($count < 1) $count = 10;
    $timelimit = intval($params[1]);
    if ($timelimit < 1) $timelimit = 0;
    
    $where_limit = "";
    $cap = "人気のページ";
    if ($timelimit >= 1) {
        $t = time() - $timelimit * 60 * 60 * 24;
        $where_limit = " AND mtime >= $t";
        $cap = "ここ{$timelimit}日の人気ページ";
        if ($timelimit == 7) {
            $cap = "週間人気ページ";
        }
        else if ($timelimit == 30 || $timelimit == 31) {
            $cap = "月間人気ページ";
        }
    }
    
    $sql = "SELECT log_id, ctime FROM sublogs WHERE".
        " plug_name='counter'".
        " AND log_id > 2".
        $where_limit.
        " ORDER BY ctime DESC LIMIT {$count}";
    
    $db = konawiki_getSubDB();
    $res = "<h5>$cap:</h5>";
    $res .= "<ul>";
    $r = $db->array_query($sql);
    if ($r == FALSE) {
        return "{$res}<ul><li>なし</li></ul>\n";
    }
    $baseurl = konawiki_public("baseurl");
    foreach ($r as $e) {
        $log_id = $e['log_id'];
        $name  = konawiki_getPageNameFromId($log_id);
        if ($name == "") continue;
        $c = intval($e['ctime']);
        $nameurl = konawiki_getPageURL2($name);
        $name_ = htmlspecialchars($name);
        $link = "<a href='{$nameurl}'>{$name_}</a><span class='memo'>($c)</span>";
        $res .= "<li>$link</li>\n";
    }
    $res .= "</ul>";
    return $res;
}


?>
