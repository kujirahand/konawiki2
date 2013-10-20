<?php
/** konawiki plugins -- テキストエリアに文章を表示するプラグイン。
 * - [書式]
{{{
#textarea(文章);
}}}
 * - [引数]
 * -- 文章
 * - [使用例] #textarea(文章);
{{{
#textarea(文章);
}}}
 * - [備考]
 * - [公開設定] 公開
 */

function plugin_textarea_action($params)
{
}

function plugin_textarea_convert($params)
{
	$text = array_shift($params);
	$html = htmlspecialchars($text);
    return <<< EOS
<textarea style="width:90%" rows="15">
{$html}
</textarea>
EOS;
}

?>
