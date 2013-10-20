<?php
/** konawiki plugins -- ページの要約情報をリンクとして表示するプラグイン。
 * - [書式] #pageinfo(name);
 * - [引数]
 * -- name .. ページの名前
 * - [使用例] #pageinfo(KonaWikiについて);
 * - [備考]
{{{
基本的に、WIKI記法の[[[name]]] と同じ
}}}
 */
 
function plugin_pageinfo_convert($params)
{
	konawiki_setPluginDynamic(true);
	
    $page = array_pop($params);
    if (!$page) {
        return "[書式: #pageinfo(名前)]";
    }
    return konawiki_parser_showPageDescription($page);
}


?>
