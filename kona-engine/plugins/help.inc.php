<?php
/** konawiki plugins -- KonaWiki のヘルプを表示するためのプラグイン
 * - [書式] #help(ヘルプのファイル名)
 * - [引数]
 * -- ヘルプのファイル名        KonaWikiのhelpフォルダにあるファイル名
 * - [使用例] #help(ヘルプのファイル名)
 * - [備考] 恒久的に使いたいテキストがあれば、help フォルダにコピーしておくと便利。
 */
 

include_once(KONAWIKI_DIR_LIB."/konawiki_parser.inc.php");

function plugin_help_convert($params)
{
    $file = array_shift($params);
    if (!$file) {
    	$file = "FirstGuide.txt";
    }
    $path = KONAWIKI_DIR_HELP."/".$file;
    if (file_exists($path)) {
        $txt = file_get_contents($path);
    } else {
        $txt = "FILE NOT FOUND:$file";
    }
    $html = konawiki_parser_convert($txt);
    return $html;
}


?>
