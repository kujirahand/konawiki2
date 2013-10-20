<?php
/** konawiki plugins -- menublock
 * - [書式] {{{#menublock([title]) リスト }}}
 * - [引数]
 * -- class .. 定義済みのクラスを指定する
 * -- リスト .. リストを指定する
 * - [使用例]
{{{
_{{{#menublock(title)
・menu1
・menu2
・menu3
_}}}
}}}

 * - [備考] メニューブロックを定義する
 * - [公開設定] 公開
 */

function plugin_menublock_convert($params)
{
    if (!$params) return "";
    $title = "&nbsp;";
    if (count($params) >= 2) {
        $title = trim(array_shift($params));
    }
    $body  = trim(array_shift($params));
    $html = konawiki_parser_convert($body, FALSE);
    return <<< EOS
<div class='menublock_top'>{$title}</div>
<div class='menublock'>{$html}</div>
<div class='menublock_bottom'>&nbsp;</div>
EOS;
}
?>
