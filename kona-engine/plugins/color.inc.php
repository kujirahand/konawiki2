<?php
/** konawiki plugins -- 文字に色をつけるプラグイン
 * - [書式]
{{{
&color(#RRGGBB,テキスト);
}}}
 * - [引数]
 * -- #rrggbb  色
 * -- テキスト 色を付ける内容
 * - [使用例] &color(#ff0000,赤色の文字!!);
{{{
&color(#ff0000,赤色の文字!!);
}}}
 */

function plugin_color_convert($params)
{
	konawiki_setPluginDynamic(false);
	
    $color = array_shift($params);
    $s = join(",",$params);

    $s = konawiki_parser_tohtml(trim($s));
    $color = trim((string)$color);
    // 色として正しい形式だけを許可し、style 属性への注入を防ぐ
    if (!preg_match('/^(#[0-9a-fA-F]{3}|#[0-9a-fA-F]{4}|#[0-9a-fA-F]{6}|#[0-9a-fA-F]{8}|[a-zA-Z]{1,32})$/', $color)) {
        $color = 'inherit';
    }
    $color = konawiki_parser_tohtml($color);
    return <<<EOS__
<span style="color:$color;">$s</span>
EOS__;
}


?>
