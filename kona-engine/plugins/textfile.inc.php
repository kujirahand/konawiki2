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
    // data フォルダ直下のテキストだけを対象とし、設定ファイル
    // (konawiki.ini.php) や DB 等の機密ファイルは読み出せないようにする
    $file = basename(str_replace('..', '', $file));
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($file === 'konawiki.ini.php' ||
        in_array($ext, ['php', 'db', 'sqlite', 'sqlite3', 'ini'], TRUE)) {
        return "[#textfile:error file not found $_file]";
    }
    $path = KONAWIKI_DIR_DATA."/".$file;
    $real = realpath($path);
    $realdir = realpath(KONAWIKI_DIR_DATA);
    if ($real === FALSE || $realdir === FALSE ||
        dirname($real) !== $realdir) { // symlink による外への参照も防ぐ
        $txt = "[#textfile:error file not found $_file]";
    } else {
        $txt = file_get_contents($real);
    }
    $html = konawiki_parser_convert($txt);
    return $html;
}

