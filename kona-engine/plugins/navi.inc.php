<?php

/** konawiki plugins -- ナビを表示するプラグイン
 * - [書式] #navi([pattern][,(DESC|ASC)])
 * - [引数]
 * -- pattern  .. ページの上部に次のページへのナビを作る
 * -- DESC|ASC .. ページの並び方を指定する
 * - [使用例] #navi
 * - [備考] なし
 */

function plugin_navi_convert($params)
{
	global $konawiki_parser_depth;
    if ($konawiki_parser_depth >= 2) return "";
	konawiki_setPluginDynamic(true);
    $page = konawiki_getPage();
    $pattern = array_shift($params);
    $order   = array_shift($params);
    if (!$pattern) {
        // このページの親を基準に列挙する
        $dirs = explode('/', $page);
        array_pop($dirs);
        $pattern = trim(join('/', $dirs));
        if ($pattern == '') {
            $pattern = konawiki_public('FrontPage');
        }
    }
    if (!$order) {
        $order = "ASC";
    }
    $where = "name like ";
    $pattern = str_replace('*','%',$pattern);
    if (strpos($pattern, '%') === FALSE) {
        $pattern .= '%';
    }
    $db = konawiki_getDB();
    $pattern_ = $db->escape($pattern);
    $where .= "'{$pattern_}'";
    //
    $FrontPage = konawiki_public('FrontPage');
    $sql = "SELECT id,name FROM logs ".
        " WHERE {$where}".
        " ORDER BY name $order".
        " LIMIT 200"; // set limit
    $db = konawiki_getDB();
    $rows = $db->array_query($sql);
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
    // -----------------------
    // 階層を持つページなら、下層の名前のみを示す
    $navi = array();
    // -----------------------
    // prev
    $link = "*";
    if ($prev != null) {
        $name  = $prev['name'];
        $url   = konawiki_getPageURL($name);
        $dirs  = explode("/", $name);
        $name  = array_pop($dirs);
        $name_ = htmlspecialchars($name);
        $link = "<a href='{$url}'>←前</a>";
    }
    $s = "<li class='navi_left'>$link</li>";
    array_push($navi, $s);
    // -----------------------
    // next
    $link = "*";
    if ($next != null) {
        $name  = $next['name'];
        $url   = konawiki_getPageURL($name);
        $dirs  = explode("/", $name);
        $name  = array_pop($dirs);
        $name_ = htmlspecialchars($name);
        $link = "<a href='{$url}'>次→</a>";
    }
    $s = "<li class='navi_right'>$link</li>";
    array_push($navi, $s);
    // -----------------------
    // cd ../
    $link = "*";
    if ($cur != null) {
        $name  = $cur['name'];
        $dirs  = explode("/", $name);
        array_pop($dirs);
        if (count($dirs) == 0) {
            $name = konawiki_public("FrontPage");
        }
        else {
            $name = join('/',$dirs);
        }
        $url   = konawiki_getPageURL($name);
        $name_ = htmlspecialchars($name);
        $link = "<a href='{$url}'>{$name_}</a>";
    }
    $s = "<li class='navi_none'>$link</li>";
    array_push($navi, $s);
    // -----------------------
    $res = join(' ', $navi);
    $entry_begin = konawiki_private("entry_begin");
    $entry_end   = konawiki_private("entry_end");
    return "$entry_begin<ul class='topnavi'>{$res}</ul>$entry_end";
}


?>
