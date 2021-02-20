<?php

/** konawiki plugins -- WIKIリンク(階層)や編集リンクを生成するプラグイン
 * - [書式] #link(page[,*caption][,?paramstr][,!action])
 * - [引数] 
 * -- page        WIKI名(dir1/dir2/../pageのような名前で)
 * -- *caption    *から始めるとキャプション
 * -- ?paramstr   ?から始めるとURLに与えるパラメータ
 * -- !action     !から始めるとアクションを指定できる
 * - [使用例] #link(KonaWikiについて/WIKIの使い方) .. ページ名の階層リンクの表示
 * - [使用例] #link(FrontPage,*このページを編集,!edit) .. 編集ページのリンク
 * - [備考]
 */

function plugin_link_convert($params)
{
    if (count($params) == 0) {
        return "[書式: #link(名前)]";
    }
    $path     = false;
    $caption  = false;
    $paramstr = false;
    $action   = false;
    $type     = "dir";
    foreach ($params as $p) {
        if (substr($p,0,1) == "*") {
            $caption = substr($p, 1);
            $type = "normal";
            continue;
        }
        if (substr($p,0,1) == "?") {
            $paramstr = substr($p, 1);
            $paramstr = htmlspecialchars($paramstr, ENT_QUOTES);
            $type = "normal";
            continue;
        }
        if (substr($p,0,1) == "!") {
            $action = substr($p, 1);
            if (!preg_match('/^[a-zA-Z0-9]+$/', $action)) {
                $action = '__invalid__';
            }
            $type = "normal";
            continue;
        }
        $path = $p;
    }
    // use action
    if ($action) {
        if (!$path)    $path = konawiki_getPage();
        if (!$caption) $caption = $path;
        $url = konawiki_getPageURL($path, $action, false, $paramstr);
        $cap_html = htmlspecialchars($caption, ENT_QUOTES);
        return "<a href='{$url}'>{$cap_html}</a>";
    }
    return konawiki_getPageLink($path, $type, $caption, $paramstr);
}

?>
