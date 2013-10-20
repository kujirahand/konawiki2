<?php
/** konawiki plugins -- HTMLにコメントを埋め込むためのプラグイン。<!-- xxx ->タグを埋め込む。
 * - [書式] #remhtml(コメント)
 * - [引数]
 * -- コメント
 * - [使用例] #remhtml(ここからｘｘ。)
 * - [備考] なし
 * - [公開設定] 公開
 */
 

function plugin_remhtml_action($params)
{
}

function plugin_remhtml_convert($params)
{
    $s = array_shift($params);
    $s = htmlspecialchars($s, ENT_QUOTES);
    return "<!-- {$s} -->\n";
}

?>
