<?php
/** konawiki plugins -- テキストのベタ書き用途に使える
 * - [書式] {{{#pre([class]) データ }}}
 * - [引数]
 * -- class .. 定義済みのクラスを指定する
 * -- データ  .. PREタグで囲いたい文章を指定する
 * - [使用例]
{{{
_{{{#beta
hoge
hoge
_}}}
}}}

{{{#beta
hoge~
hoge
}}}
 * - [備考] ソースコードブロックのプラグインとして利用する。インラインのみ展開あり。改行が有効。
 * - [公開設定] 公開
 */

function plugin_beta_convert($params)
{
    konawiki_setPluginDynamic(false);

    if (!$params) return "";
    $body  = trim(array_shift($params));
    $class = array_shift($params);
    if (!preg_match("/^([0-9a-zA-Z\-\_]+)$/",$class)) {
        $class = "code";
    }
    $html = konawiki_parser_tohtml($body, FALSE);
    $html  = preg_replace("/(\r\n|\n)/","<br/>\n",$html);
    return "<div class='$class'>{$html}</div>";
}
?>
