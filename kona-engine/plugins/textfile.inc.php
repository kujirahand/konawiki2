<?php
/** konawiki plugins -- KonaWiki のヘルプを表示するためのプラグイン
 * - [書式] #textfile(テキストファイル名)
 * - [引数]
 * -- テキストファイル名        KonaWikiのdataフォルダにあるファイル名
 * - [使用例] #textfile(ヘルプのファイル名)
 * - [備考] 恒久的に使いたいテキストがあれば、data フォルダにコピーして使う
 */
 

include_once(KONAWIKI_DIR_LIB."/konawiki_parser.inc.php");

function plugin_textfile_convert($params)
{
    $file = array_shift($params);
    if (!$file) {
      return "[#textfile:error no params]";
    }
    $_file = htmlspecialchars($file);
    $file = str_replace('..', '', $file);
    $path = KONAWIKI_DIR_DATA."/".$file;
    if (file_exists($path)) {
        $txt = file_get_contents($path);
    } else {
        $txt = "[#textfile:error file not found $_file]";
    }
    $html = konawiki_parser_convert($txt);
    return $html;
}

