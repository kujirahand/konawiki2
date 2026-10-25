<?php
/**
 * 基本ライブラリの機能テスト (lib_kona.inc.php / konawiki_options.inc.php)
 * パラメータ取得、設定アクセサ、URL生成、URI解析、日時整形などを検証する。
 */
require_once __DIR__ . '/test_common.inc.php';

global $public, $private;

// ---------------------------------------------------------------------
// konawiki_param / konawiki_getArray (POST優先でGETを見る)
// ---------------------------------------------------------------------
$_POST = []; $_GET = [];
test_eq(__LINE__, konawiki_param('missing'), FALSE, 'param: default is FALSE');
test_eq(__LINE__, konawiki_param('missing', 'DEF'), 'DEF', 'param: custom default');
$_GET['k1'] = 'getval';
test_eq(__LINE__, konawiki_param('k1'), 'getval', 'param: from GET');
$_POST['k1'] = 'postval';
test_eq(__LINE__, konawiki_param('k1'), 'postval', 'param: POST wins over GET');
$_POST = []; $_GET = [];
test_eq(__LINE__, konawiki_getArray(['a' => 1], 'a', 9), 1, 'getArray: hit');
test_eq(__LINE__, konawiki_getArray(['a' => 1], 'b', 9), 9, 'getArray: default');

// ---------------------------------------------------------------------
// $public / $private アクセサ
// ---------------------------------------------------------------------
konawiki_addPublic('test.key', 'pubval');
test_eq(__LINE__, konawiki_public('test.key'), 'pubval', 'public: add and get');
test_eq(__LINE__, konawiki_public('no.key', 'd'), 'd', 'public: default');
konawiki_addPrivate('test.key', 'prival');
test_eq(__LINE__, konawiki_private('test.key'), 'prival', 'private: add and get');
test_eq(__LINE__, konawiki_private('no.key'), null, 'private: default null');
test_eq(__LINE__, konawiki_private('no.key', 'd'), 'd', 'private: custom default');
// private は空値を未設定として扱う現行挙動 (回帰検出のため記録する)
// ※ empty() 判定に起因するこの挙動はフォローアップIssueで扱う
konawiki_addPrivate('empty.key', '');
test_eq(__LINE__, konawiki_private('empty.key', 'd'), 'd', 'private: empty value returns default');

// プラグイン情報の保存
konawiki_setPluginInfo('plug', 'k', 'v');
test_eq(__LINE__, konawiki_getPluginInfo('plug', 'k'), 'v', 'plugin info: get');
test_eq(__LINE__, konawiki_getPluginInfo('plug', 'missing'), FALSE, 'plugin info: default');
test_eq(__LINE__, konawiki_getPluginInfoArray('plug'), ['k' => 'v'], 'plugin info: array');
test_eq(__LINE__, konawiki_getPluginInfoArray('noplug'), [], 'plugin info: empty array');

// デバッグフラグ
konawiki_set_debug(TRUE);
test_eq(__LINE__, konawiki_is_debug(), TRUE, 'debug: on');
konawiki_set_debug(FALSE);
// private アクセサは空値を未設定として扱うため、OFF時は falsy が返る
test_assert(__LINE__, !konawiki_is_debug(), 'debug: off');

// ---------------------------------------------------------------------
// オプションの既定値 (konawiki_checkOptions)
// test_common の初期化で設定済みの値を確認する
// ---------------------------------------------------------------------
test_eq(__LINE__, konawiki_public('FrontPage'), 'FrontPage', 'options: FrontPage');
test_eq(__LINE__, konawiki_public('title'), 'KonaWiki2', 'options: title');
test_eq(__LINE__, konawiki_public('skin'), 'default', 'options: skin');
test_eq(__LINE__, konawiki_private('ul_mark1'), '・', 'options: ul_mark1');
test_eq(__LINE__, konawiki_private('h1_mark1'), '■', 'options: h1_mark1');
test_eq(__LINE__, konawiki_private('h2_mark1'), '●', 'options: h2_mark1');
test_assert(__LINE__, konawiki_private('session.name') !== null, 'options: session.name');
test_assert(__LINE__, preg_match('/^\d+\.\d+\.\d+$/', KONAWIKI_VERSION) === 1, 'options: version constant');

// ブラウザ言語 (Accept-Language の先頭を見る)
$saved_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null;
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja,en-US';
test_eq(__LINE__, konawiki_getBrowserLang(), 'ja', 'browser lang: ja first');
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en';
test_eq(__LINE__, konawiki_getBrowserLang(), 'en', 'browser lang: en-US shortened');
if ($saved_lang === null) { unset($_SERVER['HTTP_ACCEPT_LANGUAGE']); }
else { $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $saved_lang; }

// ---------------------------------------------------------------------
// URL生成 (konawiki_getPageURL / getPageURL2 / getPageLink)
// ---------------------------------------------------------------------
$public['baseurl'] = 'http://localhost/index.php';
test_eq(__LINE__, konawiki_getPageURL('TestPage'), 'http://localhost/index.php?TestPage', 'url: page only');
test_eq(__LINE__, konawiki_getPageURL('My Page', 'edit', 'stat'), 'http://localhost/index.php?My+Page&edit&stat', 'url: encoded page + action + stat');
test_eq(__LINE__, konawiki_getPageURL('P', 'go'), 'http://localhost/go.php?P', 'url: go action uses go.php');
test_eq(__LINE__, konawiki_getPageURL('javascript:alert'), 'http://localhost/index.php?alert', 'url: javascript: stripped');
test_eq(__LINE__, konawiki_getPageURL2('P'), 'index.php?P', 'url2: shortpath');
test_eq(__LINE__, konawiki_getPageURL('P', FALSE, FALSE, 'a=1'), 'http://localhost/index.php?P&a=1', 'url: param_str');
test_eq(__LINE__, konawiki_baseurl(), 'http://localhost/index.php', 'url: baseurl');

$html = konawiki_getPageLink('Pg', 'normal', 'Caption');
test_eq(__LINE__, $html, '<a href="http://localhost/index.php?Pg">Caption</a>', 'pagelink: normal');
$html = konawiki_getPageLink('<b>x</b>', 'normal');
test_contains(__LINE__, $html, '&lt;b&gt;', 'pagelink: caption escaped');
// dir モードは階層ごとにリンクを作る
$html = konawiki_getPageLink('A/B/C', 'dir');
test_contains(__LINE__, $html, "<a href='http://localhost/index.php?A'>A</a>", 'pagelink dir: level1');
test_contains(__LINE__, $html, "<a href='http://localhost/index.php?A%2FB'>B</a>", 'pagelink dir: level2');
test_contains(__LINE__, $html, "<a href='http://localhost/index.php?A%2FB%2FC'>C</a>", 'pagelink dir: level3');

// 現在ページ名はエスケープされて返る
$_GET['page'] = 'A<B'; $_POST = [];
test_eq(__LINE__, konawiki_getPage(), 'A&lt;B', 'getPage: escaped');
test_eq(__LINE__, konawiki_getPageHTML(), 'A&lt;B', 'getPageHTML: escaped');

// ---------------------------------------------------------------------
// konawiki_parseURI (クエリ文字列 "?page&action&stat" の解析)
// ---------------------------------------------------------------------
$_POST = [];
$_SERVER['QUERY_STRING'] = 'MyPage&edit&stat1';
$_GET = [];
konawiki_parseURI();
test_eq(__LINE__, $_GET['page'], 'MyPage', 'parseURI: page');
test_eq(__LINE__, $_GET['action'], 'edit', 'parseURI: action');
test_eq(__LINE__, $_GET['stat'], 'stat1', 'parseURI: stat');

$_SERVER['QUERY_STRING'] = '';
$_GET = [];
konawiki_parseURI();
test_eq(__LINE__, $_GET['page'], 'FrontPage', 'parseURI: default page');
test_eq(__LINE__, $_GET['action'], 'show', 'parseURI: default action');
test_eq(__LINE__, $_GET['stat'], '', 'parseURI: default stat');

// key=val 形式のパラメータはWiki規則外として無視される
$_SERVER['QUERY_STRING'] = 'PageOnly&foo=bar';
$_GET = [];
konawiki_parseURI();
test_eq(__LINE__, $_GET['page'], 'PageOnly', 'parseURI: key=val ignored');
test_eq(__LINE__, $_GET['action'], 'show', 'parseURI: action after ignored param');

// 不正なアクション名・ステータスは __INVALID__ になる
$_SERVER['QUERY_STRING'] = 'Page&bad-action!';
$_GET = [];
konawiki_parseURI();
test_eq(__LINE__, $_GET['action'], '__INVALID__', 'parseURI: invalid action');
$_SERVER['QUERY_STRING'] = 'Page&show&bad stat!';
$_GET = [];
konawiki_parseURI();
test_eq(__LINE__, $_GET['stat'], '__INVALID__', 'parseURI: invalid stat');

// $_GET['page'] 形式があればそちらが優先される
$_SERVER['QUERY_STRING'] = 'QueryPage&show';
$_GET = ['page' => 'GetPage'];
konawiki_parseURI();
test_eq(__LINE__, $_GET['page'], 'GetPage', 'parseURI: $_GET page wins');
// 後続のテストファイルに影響しないよう、URI解析の状態を既定に戻す
$_SERVER['QUERY_STRING'] = '';
$_GET = []; $_POST = [];
konawiki_parseURI();
test_eq(__LINE__, $_GET['page'], 'FrontPage', 'parseURI: state restored to defaults');

// ---------------------------------------------------------------------
// 日時整形 (Asia/Tokyo 前提)
// ---------------------------------------------------------------------
$ts = mktime(3, 4, 5, 1, 2, 2024); // 2024-01-02 03:04:05
test_eq(__LINE__, konawiki_date($ts), '2024-01-02', 'date: Y-m-d');
test_eq(__LINE__, konawiki_time($ts), '03:04:05', 'time: H:i:s');
test_eq(__LINE__, konawiki_datetime($ts), '2024-01-02 03:04:05', 'datetime');
test_eq(__LINE__, konawiki_decode_datetime('2024-01-02 03:04:05'), $ts, 'decode_datetime: hyphen');
test_eq(__LINE__, konawiki_decode_datetime('2024/01/02 03:04:05'), $ts, 'decode_datetime: slash');
test_eq(__LINE__, konawiki_pubDate(0), 'Thu, 01 Jan 1970 09:00:00+0900', 'pubDate: epoch');
test_eq(__LINE__, konawiki_dcDate(mktime(0, 0, 0, 1, 2, 2024)), '2024-01-02T00:00:00+09:00', 'dcDate');
test_eq(__LINE__, konawiki_date_html(0), '@', 'date_html: zero is @');
$html = konawiki_date_html($ts, 'normal');
test_contains(__LINE__, $html, "<span class='date'>2024-01-02</span>", 'date_html: normal');

// ---------------------------------------------------------------------
// キーワード / システムページ / 言語
// ---------------------------------------------------------------------
test_eq(__LINE__, konawiki_getKeywords('SomePage', 'tag1'),
    'KonaWiki2,KonaWiki,Wiki,SomePage,tag1', 'keywords: page and tag');
test_eq(__LINE__, konawiki_getKeywords('FrontPage'),
    'KonaWiki2,KonaWiki,Wiki', 'keywords: FrontPage not repeated');
test_eq(__LINE__, konawiki_isSystemPage('MenuBar'), TRUE, 'system page: MenuBar');
test_eq(__LINE__, konawiki_isSystemPage('SideBar'), TRUE, 'system page: SideBar');
test_eq(__LINE__, konawiki_isSystemPage('NormalPage'), FALSE, 'system page: normal page');
test_eq(__LINE__, konawiki_lang('NoSuchKey'), 'NoSuchKey', 'lang: fallback to key');
test_eq(__LINE__, konawiki_lang('NoSuchKey', 'def'), 'def', 'lang: fallback to default');
test_eq(__LINE__, lang('NoSuchKey', 'def'), 'def', 'lang: alias');
