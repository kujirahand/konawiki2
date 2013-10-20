<?php
/** konawiki plugins -- 指定ページの文字数を数えるプラグイン
 * - [書式] #countpages(pagename, limit)
 * - [引数]
 * -- pagename 文字数を数えたいページを指定する。ワイルドカードが使用可能。
 * -- limit    最大ページ数
 * - [使用例] #countpage(KonaWiki/*)
 * - [備考] 文字数を数えるだけですが、ページが増えると負荷が高いので利用は注意
 */


function plugin_countpages_convert($params)
{
	konawiki_setPluginDynamic(true);
	
    // check limit
    $limit_max = konawiki_info("plugin_countpages.limit", 200);
    $pagename = array_shift($params);
    $limit = intval(array_shift($params));
    if (!$pagename) {
        return "[$countpages(pagename)]";
    }
    $db = konawiki_getDB();
    if (!$limit) {
        $limit = $limit_max;
    }
    if ($limit > $limit_max) { $limit = $limit_max; }
    if (strpos($pagename, "*") !== FALSE) {
        $pagename = str_replace("*", "%",$pagename);
    }
    $pagename_ = $db->escape($pagename);
    if (strpos($pagename, "%") !== FALSE) {
        $where = "name LIKE '$pagename_'";
    } else {
        $where = "name = '$pagename_'";
    }
    $sql = "SELECT id FROM logs WHERE $where LIMIT $limit";
    $count_m = 0;
    $count_b = 0;
    $page_count = 0;
    $a = $db->array_query($sql);
    if ($a) {
        foreach ($a as $row) {
            $id = intval($row["id"]);
            $sql = "SELECT body FROM logs WHERE id=$id LIMIT 1";
            $r = $db->array_query($sql);
            if ($r) {
                $r = $r[0];
                $body = $r["body"];
                $count_b += strlen($body);
                $count_m += mb_strlen($body);
                $page_count++;
            }
        }
    }
    $pagename = str_replace("%", "*",$pagename);
    $page_html = htmlspecialchars($pagename, ENT_QUOTES);
    $ave = round($count_m / $page_count);
    $pages = floor($count_m / 1000);
    // 統計を出力
    return <<< EOS_
<table width="100%">
<tr>
    <td>パターン(WIKI数)</td><td>文字数</td><td>Byte数</td><td>ページ平均(文字)</td><td>総ページ数</td>
</tr>
<tr>
    <td>{$page_html}({$page_count}p)</td><td>{$count_m}</td><td>{$count_b}</td><td>{$ave}</td><td>{$pages}</td>
</tr>
</table>
EOS_;
}


?>
