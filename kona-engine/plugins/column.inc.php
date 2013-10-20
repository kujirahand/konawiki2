<?php
/** konawiki plugins -- columnタグ
 * - [書式] {{{#column([class]) テキスト }}}
 * - [引数]
 * -- class .. 定義済みのクラスを指定する
 * -- テキスト  .. DIVタグで囲いたい文章を指定する
 * - [使用例]
{{{
_{{{#column
hoge~
hoge
_}}}
}}}

{{{#column
hoge~
hoge
}}}
 * - [備考] ソースコードブロックのプラグインとして利用する
 * - [公開設定] 公開
 */

function plugin_column_convert($params)
{
    if (!$params) return "";
    $class = "column";
    if (count($params) >= 2) {
        $class = trim(array_shift($params));
    }
    $body  = trim(array_shift($params));
    if (!preg_match("/^([0-9a-zA-Z\-\_]+)$/",$class)) {
        $class = "column";
    }
    $html = konawiki_parser_convert($body, FALSE);
    return "<div class='$class'>{$html}</div>";
}
?>
