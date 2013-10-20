<?php
/** konawiki plugins -- 添付ファイルや画像をリンクする
 * - [書式] ♪画像～filename
 * - [引数]
 * - filename .. ファイル名
 * - options .. 
 * -- (width)x(height) .. 画像の大きさを指定する
 * -- *(caption) || ＊(caption) .. 画像にキャプションを表示する
 * -- @link .. リンク先を指定する
 * - [使用例]
{{{
#ref(xxx.png,300x200,*猫の画像,@http://nadesi.com)
#ref(http://konawiki.aoikujira.com/resource/logo.png,60x60,*KonaWikiのロゴ,@http://konawiki.aoikujira.com/)
}}}
#ref(http://konawiki.aoikujira.com/resource/logo.png,60x60,*KonaWikiのロゴ,@http://konawiki.aoikujira.com/)
 * - [備考] なし
 */
 
include_once(KONAWIKI_DIR_LIB.'/konawiki_parser.inc.php');
include_once(KONAWIKI_DIR_PLUGINS.'/ref.inc.php');

function plugin_E794BBE5838F_convert($params)
{
	return plugin_ref_convert($params);
}
