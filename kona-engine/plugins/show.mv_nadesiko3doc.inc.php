<?php
/** konawiki plugins -- なでしこのマニュアル用プラグイン
 * - [書式] (設定ファイルに記述)
 * - [引数]なし
 * - [使用例] なし
 * - [備考] page/show 専用のプラグイン
 * - [公開設定] 非公開
 */
//------------------------------------------------------------------------------
/* option
$konawiki['private']['show.plugins']['mv_nadesiko3doc'] = array(
        'enabled'   => TRUE,
        'file'      => 'show.mv_nadesiko3doc.inc.php',
        'entry'     => 'show_mv_nadesiko3doc',
    );
*/
//------------------------------------------------------------------------------
function show_mv_nadesiko3doc($plugin, $log)
{
    global $konawiki_show_log;
    //
    $page = konawiki_getPage();
    if (preg_match('/^plugin_\w+?\/.+/', $page)) {
      $url = "https://nadesi.com/v3/doc/index.php?";
      $url .= urlencode($page);
      //
      $log['body'] = "[[こちらに移動しました:$url]]";
    }
    $konawiki_show_log = $log;
}


