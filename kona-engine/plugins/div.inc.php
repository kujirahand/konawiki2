<?php
/** konawiki plugins -- DIVタグ
 * - [書式] {{{#div([class]) データ }}}
 * - [引数]
 * -- class .. 定義済みのクラスを指定する
 * -- データ  .. DIVタグで囲いたい文章を指定する
 * - [使用例]
{{{
_{{{#div
hoge~
hoge
_}}}
}}}

{{{#div
hoge~
hoge
}}}
 * - [備考] ソースコードブロックのプラグインとして利用する
 * - [公開設定] 公開
 */

function plugin_div_convert($params)
{
    if (!$params) return "";
    $class = "code";
    if (count($params) >= 2) {
        $class = trim(array_shift($params));
    }
    $body  = trim(array_shift($params));
    if (!preg_match("/^([0-9a-zA-Z\-\_]+)$/",$class)) {
        $class = "code";
    }
    $html = konawiki_parser_convert($body, FALSE);
    return "<div class='$class'>{$html}</div>";
}
?>
