<?php
/** konawiki plugins -- PHPのコードを見やすく表示するプラグイン
 * - [書式]
 * - #phpcode(code)
 * - [引数]
 * -- code PHPのプラグラム
 * - [使用例] {{{#phpcode ... }}}
 * - [公開設定]公開
 */

function plugin_phpcode_convert($params)
{
    $code = array_pop($params);
    $html = "<div class=\"code\">".  highlight_string($code, true) . "</div>";
    return $html;
}

