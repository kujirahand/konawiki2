<?php
/* vim:set expandtab ts=4 sts=4 sw=4: */
/** konawiki plugins -- カウンタのあるページの人気ランキングを表示する
 * - [書式] #popular([件数])
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
    if (!isset($params[1])) $params[1] = "90";
    $count = intval($params[0]);
    if ($count < 1) $count = 10;
    $timelimit = intval($params[1]);
    if ($timelimit < 1) $timelimit = 0;
   
    // check cache
    $cap = konawiki_lang('Popular pages');
    if ($timelimit > 0) {
        $cap .= " <span class='memo'>({$timelimit}days)</span>";
    }
    $r = popular_getCache($timelimit, $count, $cap);
    
    $res = "<h5>$cap</h5>";
    $res .= "<ul>";
    if (!$r) {
        return "{$res}<li>none</li></ul>\n";
    }
    $baseurl = konawiki_public("baseurl");
    foreach ($r as $e) {
        $log_id = $e['log_id'];
        $name  = konawiki_getPageNameFromId($log_id);
        if ($name == "") continue;
        $nameurl = konawiki_getPageURL2($name);
        $name_ = htmlspecialchars($name);
        $c = isset($e["total"]) ? $e["total"] : 0;
        $link = "<a href='{$nameurl}'>{$name_}</a><span class='memo'>($c)</span>";
        $res .= "<li>$link</li>\n";
    }
    $res .= "</ul>";
    return $res;
}



function popular_makeRanking($timelimit, $count, $cache_key) {
    // make ranking
    $db = konawiki_getSubDB();
    $result = array();
    if ($timelimit >= 1) {
        // time limit ranking
        $t = time() - $timelimit * 60 * 60 * 24;
        $sql = "SELECT log_id, sum(value) as total ".
            " FROM mcounter_day ".
            " WHERE log_id > 2 AND mtime >= $t ".
            " GROUP BY log_id ".
            " ORDER BY total DESC LIMIT {$count}";
        $result = $db->array_query($sql);
        // no result
        if (!$result) {
            $sql = "SELECT * FROM mcounter_total ".
                " WHERE log_id > 2 ".
                " ORDER BY total DESC LIMIT {$count}";
            $result = $db->array_query($sql);
        }
    } else {
        // total ranking
        $sql = "SELECT * FROM mcounter_total ".
            " WHERE log_id > 2 ".
            " ORDER BY total DESC LIMIT {$count}";
        $result = $db->array_query($sql);
    }
    // make cache
    if ($result) {
        $body = json_encode($result);
        $pname = "popular";
        $sql_rm = 
            "DELETE FROM sublogs WHERE ".
            " plug_name='$pname' AND plug_key='$cache_key' LIMIT 1";
        $db->exec($sql_rm);
        $now = time();
        $sql_ins = 
            "INSERT INTO sublogs ".
            "(log_id,plug_name,plug_key,body,ctime,mtime) VALUES".
            "(     0,'$pname' ,'$cache_key' ,'$body',$now,$now)";
        $r = $db->exec($sql_ins);
    }
    return $result;
}

function popular_getCache($timelimit,$count,&$cap) {
    // cache interval
    $t = time() - (24 * 60 * 60);
    $db = konawiki_getSubDB();
    $pname = "popular";
    $pkey  = "{$timelimit},{$count}";
    $sql = 
        "SELECT * FROM sublogs WHERE ".
        " plug_name='$pname' AND plug_key='$pkey' ".
        " AND ctime > $t LIMIT 1";
    $r = $db->array_query($sql);
    if (isset($r[0]["body"])) {
        $cap = "{$cap}<span class='memo'>#</span>";
        $body = $r[0]["body"];
        return json_decode($body, TRUE);
    } else {
        return popular_makeRanking($timelimit,$count,$pkey);
    }
}



