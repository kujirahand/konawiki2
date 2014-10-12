<?php
/** konawiki plugins -- 指定日時が最新であれば new マークを表示する
 * - [書式]
{{{
&new(日時);
}}}
 * - [引数]
 * -- 日時 .. 日時
 * - [使用例] &new(2010-11-22);
{{{
&new(2010-11-22);
}}}
 * - [備考] なし
 */

function plugin_new_convert($params)
{
    konawiki_setPluginDynamic(true);
    if (count($params) < 1) {
        return "*";
    }
    $target = $params[0];
    return konawiki_datetime_html($target,"easy");
}

