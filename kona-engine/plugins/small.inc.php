<?php
/** konawiki plugins -- 文字を小さくするプラグイン
 * - [書式]
{{{
&small(テキスト);
}}}
 * - [引数]
 * -- テキスト
 * - [使用例] &small(ぼそっ・・・すごいんです);
{{{
&small(ぼそっ・・・すごいんです);
}}}
 * - [備考] なし
 * - [公開設定] 公開
 */
function plugin_small_convert($params)
{
    $s = join(",",$params);
    $s = konawiki_parser_tohtml(trim($s));
    return <<<EOS__
<span class="memo">$s</span>
EOS__;
}


?>
