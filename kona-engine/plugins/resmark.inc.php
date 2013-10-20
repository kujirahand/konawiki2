<?php
/** konawiki plugins -- 引用記号を付与する
 * - [書式] {{{#resmark ... WIKIデータ  }}}
 * - [引数]
 * -- WIKIデータ ... 引用したい文章を指定する
 * - [使用例]
{{{
_{{{#resmark
hoge
hoge
_}}}
}}}

{{{#pre
hoge~
hoge
}}}
 * - [備考] 引用記号を付加する {{{ .. }}} と違ってデータ内の展開がある
 * - [公開設定] 公開
 */

function plugin_resmark_convert($params)
{
    if (!$params) return "";
    $body  = trim(array_shift($params));
    $class = array_shift($params);
    if (!preg_match("/^([0-9a-zA-Z\-\_]+)$/",$class)) {
        $class = "code";
    }
    $class = 'resmark_q';
    $html = konawiki_parser_convert($body, FALSE);
    return "<div class='$class'>{$html}</div><!-- end of resmark -->\n";
}
?>
