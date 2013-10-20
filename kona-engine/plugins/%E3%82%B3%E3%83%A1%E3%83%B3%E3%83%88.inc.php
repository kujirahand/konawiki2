<?php
/** konawiki plugins -- コメントを記入するプラグイン
 * - [書式] ♪コメント([id])
 * - [引数]
 * -- id        省略可能、複数の掲示板を設置する場合に識別用に指定する
 * - [使用例] ♪コメント
 */
 
include_once(KONAWIKI_DIR_LIB.'/konawiki_parser.inc.php');
include_once(KONAWIKI_DIR_PLUGINS.'/comment.inc.php');

function plugin_E382B3E383A1E383B3E38388_convert($params)
{
	return plugin_comment_convert_sub("コメント", $params);
}
function plugin_E382B3E383A1E383B3E38388_action($params)
{
	return plugin_comment_action_sub("コメント", $params);
}
