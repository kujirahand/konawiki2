<?php

/** konawiki plugins -- 最近編集したページを列挙する
 * - [書式] #recent(件数)
 * - [引数]
 * -- 件数 .. 表示する件数
 * - [使用例] #recent
 * - [備考] なし
 */

function plugin_recent_convert($params)
{
	konawiki_setPluginDynamic(true);

    $res = "<h5>".konawiki_lang('Recently updated').":</h5>";
    if (!isset($params[0])) $params[0] = 10;
    $count = intval($params[0]);
    if ($count < 1) $count = 10;
    $sql = "SELECT name, mtime FROM logs ".
        // " WHERE id > 2". // FrontPage, MenuBar
        " ORDER BY mtime DESC LIMIT {$count}";
    $res .= "<ul>";
    $r = konawiki_query($sql);
    if ($r == FALSE) {
        return "";
    }
    $baseurl = konawiki_public("baseurl");
    foreach ($r as $e) {
        $name  = $e['name'];
        if ($name == 'SideBar' || $name == 'MenuBar' || 
            $name == 'FrontPage' || $name == 'NaviBar' || 
            $name == 'GlobBar') { continue; }

        $mtime = intval($e['mtime']);
        $mtime_ = konawiki_date_html($mtime);
        $nameurl = konawiki_getPageURL2($name);
        $name_ = preg_replace_callback(
          '#([0-9a-zA-Z\/\-\_]{15,})#',
          function ($m) {
            return substr($m[1], 0, 15) . "..";
          }, $name);
        $name_ = htmlspecialchars($name_);
        $link = "<a href='{$nameurl}'>{$name_}</a><span class='memo'>…</span>$mtime_";
        $res .= "<li>$link</li>\n";
    }
    $res .= "</ul>";
    return $res;
}


