<?php
/** konawiki plugins -- 回り込みを解除するプラグイン
 * - [書式]
 * #clear
 * - [引数]
 * - [使用例] #clear(コメント);
 * - [備考]
 * - [公開設定] 公開
 */

function plugin_clear_convert($params)
{
    return "<div style='clear:both;'></div>";
}
