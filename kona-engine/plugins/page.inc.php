<?php

/** konawiki plugins -- 別のページの内容を埋め込むプラグイン
 * - [書式] #page(pagename)
 * - [引数]
 * -- pagename .. 埋め込みたいページの名前
 * - [使用例] #page(KonaWikiについて)
 * - [備考] なし
 */

function plugin_page_convert($params)
{
	konawiki_setPluginDynamic(true);
	
    if (count($params) == 0) {
        return "[書式: #page(名前)]";
    }
    global $pagestack;
    if (!isset($psagestack)) {
        $pagestack[konawiki_getPage()] = TRUE;
    }
    $page = $params[0];
    $page_ = htmlspecialchars($page);
    if (isset($pagestack[$page])) {
        return "[({$page_})は既に表示中です。]";
    }
    $log = konawiki_getLog($page);
    if (isset($log['body'])) {
        $defpage = konawiki_param('page');
        $_GET['page'] = $_POST['page'] = $page;
        $htm = konawiki_parser_convert($log['body']);
        $_GET['page'] = $_POST['page'] = $defpage;
        return $htm;
    }
    $pagestack[$page] = TRUE;
    return "[($page_)はありません]";
}


?>
