<?php
/** konawiki plugins -- PREタグ
 * - [書式] {{{#pre([class]) データ }}}
 * - [引数]
 * -- class .. 定義済みのクラスを指定する
 * -- データ  .. PREタグで囲いたい文章を指定する
 * - [使用例]
{{{
_{{{#pre
hoge
hoge
_}}}
}}}

{{{#pre
hoge~
hoge
}}}
 * - [備考] ソースコードブロックのプラグインとして利用する {{{ .. }}} と違ってデータ内の展開がある
 * - [公開設定] 公開
 */

function plugin_pre_convert($params)
{
    if (!$params) return "";
    $body  = trim(array_shift($params));
    $class = array_shift($params);
    if (!preg_match("/^([0-9a-zA-Z\-\_]+)$/",$class)) {
        $class = "code";
    }
    $html = konawiki_parser_convert($body, FALSE);
    return "<pre class='$class'>{$html}</pre>";
}
?>
