<?php
/** konawiki plugins -- プラグインのヘルプを表示するプラグイン
 * - [書式] #pluginshelp(name)
 * - [引数] name .. プラグインの名前
 * - [使用例] #pluginshelp(csv)
 * - [備考] なし
 */

include_once(KONAWIKI_DIR_LIB."/konawiki_parser.inc.php");
include_once(KONAWIKI_DIR_PLUGINS."/pluginslist.inc.php");

function plugin_pluginshelp_convert($params)
{
	konawiki_setPluginDynamic(true);
	
    $name = array_shift($params);
    if (!$name) {
        return "[#pluginshelp(プラグイン名) の書式で指定します。]";
    }
    return _plugin_pluginslist_convert_more($name);
}

?>
