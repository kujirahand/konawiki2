<?php
/** konawiki plugins -- 全てのページに表示するヘッダ・フッタを指定可能。
 * - [書式] (設定ファイル内に記述)
 * - [引数] なし
 * - [使用例] なし
 * - [公開設定] 非公開
 * - [備考] page/show 専用のプラグイン。設定ファイルに以下を記述する。
{{{
$konawiki['private']['show.plugins']['show.allpage'] = array(
        'enabled'   => TRUE,
        'file'      => 'show.allpage.inc.php',
        'entry'     => 'show_plugin_show_allpage_entry',
        'header.wiki' => 'xxx',                    // 全てのページの先頭に表示する文字列(WIKI記法で)
        'footer.wiki' => 'xxx',                    // 全てのページの末尾に表示する文字列(WIKI記法で)
        'header.html' => 'xxx',                    // 全てのページの先頭に表示する文字列(HTMLで)
        'footer.html' => 'xxx',                    // 全てのページの末尾に表示する文字列(HTMLで)
    );
}}}
 */

/**
 * SHOW-PLUGIN ENTRY POINT
 */
function show_plugin_show_allpage_entry($plugin, $log)
{
    global $konawiki_show_log;
    $front = konawiki_public('FrontPage');
    $page = konawiki_getPage();
    
    // header
    $log['body_header'] .= isset($plugin['header.html']) ? $plugin['header.html'] : "";
    if (isset($plugin['header.wiki']) && $plugin['header.wiki'] !== "") {
        $log['body_header'] .= konawiki_parser_convert($plugin['header.wiki']);
    }
    // footer
    $log['body_footer'] .= isset($plugin['footer.html']) ? $plugin['footer.html'] : "";
    if (isset($plugin['footer.wiki']) && $plugin['footer.wiki'] !== "") {
        $log['body_footer'] .= konawiki_parser_convert($plugin['footer.wiki']);
    }
    // set result
    $konawiki_show_log = $log;
}

?>
