<?php
/** konawiki plugins -- コメントを記入するためのプラグイン。何も画面に表示しない。
 * - [書式]
{{{
&rem(コメント);
}}}
 * - [引数]
 * -- コメント
 * - [使用例] &rem(コメント);
{{{
&rem(コメント);
}}}
 * - [備考]
 * - [公開設定] 公開
 */

function plugin_rem_action($params)
{
}

function plugin_rem_convert($params)
{
    return "";
}

?>
