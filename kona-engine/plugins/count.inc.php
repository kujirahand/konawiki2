<?php
/** konawiki plugins -- 本文の文字数を数えるプラグイン
 * - [書式]
{{{
&count([sss]);
}}}
 * - [引数]
 * -- sss        省略可能、数えたい文章を書く。
 * - [使用例] &count(いろはにほへと);
{{{
&count(いろはにほへと);
}}}
 * - [備考] KonaWiki の編集中にエディタでEnterキーを押すと即時に計算される。
 */
 
function plugin_count_init()
{
}

function plugin_count_action($params)
{
}

function plugin_count_convert($params)
{
    konawiki_setPluginDynamic(false);
    if (count($params) == 0) {
        $text = konawiki_getRawText();
        return "(" .mb_strlen($text) . "ch," . strlen($text) ."B)";
    }
    $text = $params[0];
    return htmlspecialchars($text).
        "(" . mb_strlen($text) . "ch" . strlen($text) . "B)";
}


