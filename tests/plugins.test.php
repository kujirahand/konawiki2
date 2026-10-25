<?php
/**
 * プラグインの機能テスト
 * DBや外部通信に依存しないプラグインを直接・パーサ経由で検証する。
 * プラグインの解決 (konawiki_parser_getPlugin) も確認する。
 */
require_once __DIR__ . '/test_common.inc.php';

global $public;
$public['baseurl'] = 'http://localhost/index.php';
$_GET['page'] = 'FrontPage'; $_POST = [];

// ---------------------------------------------------------------------
// プラグインの解決 (ファイル名・関数名・無効化フラグ)
// ---------------------------------------------------------------------
$info = konawiki_parser_getPlugin('color');
test_eq(__LINE__, $info['convert'], 'plugin_color_convert', 'getPlugin: convert name');
test_contains(__LINE__, $info['file'], '/plugins/color.inc.php', 'getPlugin: file path');
test_eq(__LINE__, $info['disable'], FALSE, 'getPlugin: color enabled');

$info = konawiki_parser_getPlugin('html');
test_eq(__LINE__, $info['disable'], TRUE, 'getPlugin: html disabled by default config');

// パストラバーサルになる名前は除去される
$info = konawiki_parser_getPlugin('../color');
test_not_contains(__LINE__, $info['file'], '..', 'getPlugin: traversal stripped');
$info = konawiki_parser_getPlugin('a/b');
test_not_contains(__LINE__, $info['convert'], '/', 'getPlugin: slash stripped from func name');

// ---------------------------------------------------------------------
// 個別プラグイン (直接呼び出し)
// ---------------------------------------------------------------------
include_once KONAWIKI_DIR_PLUGINS . '/color.inc.php';
test_eq(__LINE__, plugin_color_convert(['#ff0000', 'text']),
    '<span style="color:#ff0000;">text</span>', 'plugin color: basic');
test_contains(__LINE__, plugin_color_convert(['red', 'a,b']),
    '>a,b</span>', 'plugin color: comma text joined');

include_once KONAWIKI_DIR_PLUGINS . '/small.inc.php';
test_eq(__LINE__, plugin_small_convert(['hello']),
    '<span class="memo">hello</span>', 'plugin small');

include_once KONAWIKI_DIR_PLUGINS . '/rem.inc.php';
test_eq(__LINE__, plugin_rem_convert(['comment']), '', 'plugin rem: outputs nothing');

include_once KONAWIKI_DIR_PLUGINS . '/md5.inc.php';
test_eq(__LINE__, plugin_md5_convert(['abc']), md5('abc'), 'plugin md5');
test_eq(__LINE__, plugin_md5_convert(['']), md5(''), 'plugin md5: empty');

include_once KONAWIKI_DIR_PLUGINS . '/clear.inc.php';
test_contains(__LINE__, plugin_clear_convert([]), 'clear', 'plugin clear');

// ---------------------------------------------------------------------
// パーサ経由のプラグイン呼び出し
// ---------------------------------------------------------------------
// ブロックプラグイン #md5(...)
$html = konawiki_parser_convert("#md5(abc)\n");
test_contains(__LINE__, $html, md5('abc'), 'plugin via parser: #md5');

// インラインプラグイン &color(...); / &small(...);
$html = konawiki_parser_convert("text &color(#ff0000,red); end\n");
test_contains(__LINE__, $html, '<span style="color:#ff0000;">red</span>', 'plugin via parser: &color');

$html = konawiki_parser_convert("&small(hello);\n");
test_contains(__LINE__, $html, '<span class="memo">hello</span>', 'plugin via parser: &small');

// 無効化されたプラグインは実行されずエスケープ表示になる
$html = konawiki_parser_convert("&html(<b>);\n");
test_contains(__LINE__, $html, '&amp;html(', 'plugin via parser: disabled plugin not executed');
test_not_contains(__LINE__, $html, '<b>', 'plugin via parser: disabled plugin html not emitted');

// 存在しないプラグインも実行されない
$html = konawiki_parser_convert("&nosuchplugin(x);\n");
test_contains(__LINE__, $html, '&amp;nosuchplugin(', 'plugin via parser: unknown plugin not executed');
