<?php
/** konawiki plugins -- プラグインの一覧＆ヘルプを表示するプラグイン
 * - [書式] #pluginslist
 * - [引数] なし
 * - [使用例] #pluginslist
 * - [備考] なし
 */

include_once(KONAWIKI_DIR_LIB."/konawiki_parser.inc.php");

function plugin_pluginslist_convert($params)
{
	konawiki_setPluginDynamic(true);
	
    $pid = "pluginslist_";
    // check mode
    $mode = konawiki_param("{$pid}mode", "");
    $name = konawiki_param("{$pid}name", "");
    if ($mode == "more") {
        return _plugin_pluginslist_convert_more($name);
    }
    
    $path = KONAWIKI_DIR_PLUGINS."/";
    $files = glob("{$path}*.inc.php");
    natsort($files);
    $list = array();
    foreach ($files as $fullpath) {
        $file = basename($fullpath);
        // ファイルがプラグインの形式とマッチするかどうか
        if (!preg_match("#^(.*)\.inc\.php$#", $file, $m)) {
            continue;
        }
        // 名前を追加
        $pname = urldecode($m[1]);
        $body = file_get_contents($fullpath);
        konawiki_parser_token($body, "/**");
        $info = konawiki_parser_token($body, "*/");
        // 公開可能？
        if (strpos($info,"非公開") !== FALSE) {
            continue;
        }
        // 要約を取得
        $params = explode("\n", $info."\n");
        $desc = array_shift($params);
        konawiki_parser_token($desc, "--");
        $desc = htmlspecialchars(trim($desc));
        // 詳細解説ページへのリンク
        $pname_u = urlencode($pname);
        $url = konawiki_getPageURL2(konawiki_getPage(), FALSE, FALSE, "{$pid}mode=more&{$pid}name={$pname_u}");
        $list[] = "<li><a href='{$url}'>{$pname}</a> -- {$desc}</li>";
    }
    $filelist = join("\n", $list);
    $pcount = count($list);
    return <<< EOS__
<ul>
{$filelist}
</ul>
<div class="memo">公開プラグインの個数 $pcount 個が利用可能です。</div>
EOS__;
}

function _plugin_pluginslist_convert_more($pname)
{
    $path = KONAWIKI_DIR_PLUGINS."/";
    $full = $path . urlencode($pname) . ".inc.php";
    if (!file_exists($full)) {
        return "[$pname] というプラグインはありません。";
    }
    $body = file_get_contents($full);
    konawiki_parser_token($body, "/**");
    $info = konawiki_parser_token($body, "*/");
    // 要約を取得
    $params = array();
    $a = explode("\n", $info."\n");
    $desc = array_shift($a);
    konawiki_parser_token($desc, "--");
    $desc = htmlspecialchars(trim($desc));
    foreach ($a as $line) {
        $line = trim($line);
        if (substr($line, 0, 1) == "*") $line = trim(substr($line, 1));
        if ($line == "") continue;
        $params[] = $line;
    }
    // back to list
    $url = konawiki_getPageURL(konawiki_getPage());
    $param_str = join("\n", $params);
    $str = <<< EOS__
** [$pname]プラグインの使い方
{$desc}
{$param_str}
----
- [[→プラグイン一覧に戻る:$url]]
EOS__;
    return konawiki_parser_convert($str);
}

?>
