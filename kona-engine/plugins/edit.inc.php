<?php

/** konawiki plugins -- ページの編集リンクを表示する
 * - [書式]
{{{
&edit([page][,msg]);
}}}
 * - [引数]
 * -- page          省略可能,編集したいページの名前
 * -- msg           省略可能,リンクのタイトル
 * - [使用例] &edit(KonaWikiについて,概要ページの編集);
{{{
&edit(KonaWikiについて,概要ページの編集);
}}}
 * - [備考] なし
 */

function plugin_edit_convert($params)
{
    # list($page, $message) = $params;
    $page = array_shift($params);
    $message = array_shift($params);

    $page = htmlspecialchars($page, ENT_QUOTES);
    $message = htmlspecialchars($message, ENT_QUOTES);
    $link = konawiki_getEditLink($page, $message);
    return $link;
}

