<?php
/** konawiki plugins -- block
 * - [書式] {{{#block([class]) データ }}}
 * - [引数]
 * -- class .. 定義済みのクラスを指定する(省略すると block クラス)
 * -- データ  .. DIVタグで囲いたい文章を指定する
 * - [使用例]
{{{
_{{{#block
hoge~
hoge
_}}}
}}}

{{{#block
hoge~
hoge
}}}
 * - [備考] ブロックを表すプラグインとして利用する
 * - [公開設定] 公開
 */

function plugin_block_convert($params)
{
    if (!$params) return "";
    $class = "block";
    if (count($params) >= 2) {
        $class = trim(array_shift($params));
    }
    $body  = trim(array_shift($params));
    if (!preg_match("/^([0-9a-zA-Z\-\_]+)$/",$class)) {
        $class = "block";
    }
    $html = konawiki_parser_convert($body, FALSE);
    return "<div class='$class'>{$html}</div>";
}
?>
