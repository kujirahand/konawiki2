<?php
/**
 * Wiki記法パーサの機能テスト (konawiki_parser.inc.php)
 * parse (トークン化) と convert (HTML変換) の両方を検証する。
 */
require_once __DIR__ . '/test_common.inc.php';

global $public;
$public['baseurl'] = 'http://localhost/index.php';
$_GET['page'] = 'FrontPage'; $_POST = [];

// ---------------------------------------------------------------------
// parse: 見出し / リスト / 表 / ソース行などのトークン化
// ---------------------------------------------------------------------
$tokens = konawiki_parser_parse("* Title\n** Sub\n*** SubSub\n");
test_eq(__LINE__, $tokens[0], ['cmd' => '*', 'text' => 'Title', 'level' => 1], 'parse: h1');
test_eq(__LINE__, $tokens[1], ['cmd' => '*', 'text' => 'Sub', 'level' => 2], 'parse: h2');
test_eq(__LINE__, $tokens[2], ['cmd' => '*', 'text' => 'SubSub', 'level' => 3], 'parse: h3');

// 日本語マークの見出し
$tokens = konawiki_parser_parse("■大見出し\n●中見出し\n▲小見出し\n");
test_eq(__LINE__, $tokens[0]['level'], 1, 'parse: ■ is h1');
test_eq(__LINE__, $tokens[0]['text'], '大見出し', 'parse: ■ text');
test_eq(__LINE__, $tokens[1]['level'], 2, 'parse: ● is h2');
test_eq(__LINE__, $tokens[2]['level'], 3, 'parse: ▲ is h3');

$tokens = konawiki_parser_parse("- a\n-- b\n+ one\n");
test_eq(__LINE__, $tokens[0], ['cmd' => '-', 'text' => 'a', 'level' => 1], 'parse: ul level1');
test_eq(__LINE__, $tokens[1], ['cmd' => '-', 'text' => 'b', 'level' => 2], 'parse: ul level2');
test_eq(__LINE__, $tokens[2], ['cmd' => '+', 'text' => 'one', 'level' => 1], 'parse: ol');

$tokens = konawiki_parser_parse("・項目\n");
test_eq(__LINE__, $tokens[0]['cmd'], '-', 'parse: ・ is ul');
test_eq(__LINE__, $tokens[0]['text'], '項目', 'parse: ・ text');

$tokens = konawiki_parser_parse("|a|b|\n");
test_eq(__LINE__, $tokens[0]['cmd'], '|', 'parse: table');

$tokens = konawiki_parser_parse(" source code\n");
test_eq(__LINE__, $tokens[0], ['cmd' => 'src', 'text' => 'source code'], 'parse: src line');

$tokens = konawiki_parser_parse("----\n");
test_eq(__LINE__, $tokens[0]['cmd'], 'hr', 'parse: ---- is hr');

$tokens = konawiki_parser_parse("> quote\n");
test_eq(__LINE__, $tokens[0]['cmd'], 'resmark', 'parse: > is resmark');

$tokens = konawiki_parser_parse("plain text\n");
test_eq(__LINE__, $tokens[0]['cmd'], 'plain', 'parse: plain');

// CRLF は LF に正規化される
$tokens = konawiki_parser_parse("* T\r\nbody\r\n");
test_eq(__LINE__, $tokens[0]['text'], 'T', 'parse: CRLF heading');
test_eq(__LINE__, konawiki_getRawText(), "* T\nbody\n", 'parse: raw text normalized');

// 空文字列は空のトークン列
test_eq(__LINE__, konawiki_parser_parse(''), [], 'parse: empty');

// ---------------------------------------------------------------------
// convert: HTML への変換
// ---------------------------------------------------------------------
$html = konawiki_parser_convert("* Title\n");
test_contains(__LINE__, $html, '<div class="contents">', 'convert: contents wrapper');
test_contains(__LINE__, $html, '<h1>', 'convert: h1 tag');
test_contains(__LINE__, $html, '>Title</a>', 'convert: h1 text');

$html = konawiki_parser_convert("hello\n");
test_contains(__LINE__, $html, '<p>hello<br /></p>', 'convert: paragraph with br');

$html = konawiki_parser_convert("- item1\n- item2\n");
test_contains(__LINE__, $html, '<ul>', 'convert: ul');
test_contains(__LINE__, $html, '<li>item1</li>', 'convert: li 1');
test_contains(__LINE__, $html, '<li>item2</li>', 'convert: li 2');

$html = konawiki_parser_convert("+ one\n+ two\n");
test_contains(__LINE__, $html, '<ol>', 'convert: ol');
test_contains(__LINE__, $html, '<li>one</li>', 'convert: ol li');

$html = konawiki_parser_convert("|a|b|\n|c|d|\n");
test_contains(__LINE__, $html, "<table class='grid'>", 'convert: table');
test_contains(__LINE__, $html, '<tr><td>a</td><td>b</td></tr>', 'convert: table row1');
test_contains(__LINE__, $html, '<tr><td>c</td><td>d</td></tr>', 'convert: table row2');

$html = konawiki_parser_convert(" code <x>\n");
test_contains(__LINE__, $html, '<pre class="code">code &lt;x&gt;', 'convert: src escaped');

$html = konawiki_parser_convert("{{{code <x>\n}}}\n");
test_contains(__LINE__, $html, '<pre class="code">code &lt;x&gt;', 'convert: source block escaped');

$html = konawiki_parser_convert("> quote\n");
test_contains(__LINE__, $html, "<div class='resmark'>", 'convert: resmark');

// flag_isContents=FALSE では contents の div が付かない
$html = konawiki_parser_convert("hello\n", FALSE);
test_not_contains(__LINE__, $html, '<div class="contents">', 'convert: no wrapper without flag');

// ---------------------------------------------------------------------
// tohtml: インライン記法とエスケープ
// ---------------------------------------------------------------------
test_eq(__LINE__, konawiki_parser_tohtml('a <b> & "c"'), 'a &lt;b&gt; &amp; &quot;c&quot;', 'tohtml: escaped');
test_eq(__LINE__, konawiki_parser_tohtml("''strong''"), '<strong>strong</strong>', 'tohtml: strong');
test_eq(__LINE__, konawiki_parser_tohtml('``mark``'), "<span class='marker'>mark</span>", 'tohtml: marker');
test_eq(__LINE__, konawiki_parser_tohtml('%%red%%'), "<span class='red'>red</span>", 'tohtml: red');
test_eq(__LINE__, konawiki_parser_tohtml('plain 日本語'), 'plain 日本語', 'tohtml: plain text');

// URL の自動リンク (末尾の ~ はリンクに含めない: Issue #23 の仕様)
$html = konawiki_parser_tohtml('see https://example.com/foo~ end');
test_contains(__LINE__, $html, "<a href='https://example.com/foo'>", 'tohtml: url link without trailing ~');
$html = konawiki_parser_tohtml('see https://example.com/foo');
test_eq(__LINE__, $html, "see <a href='https://example.com/foo'>https://example.com/foo</a>", 'tohtml: url link');

// ソース表示では HTML がエスケープされる
test_eq(__LINE__, konawiki_parser_tosource('<a href="x">'), '&lt;a href=&quot;x&quot;&gt;', 'tosource: escaped');

// ---------------------------------------------------------------------
// URL チェック (許可プロトコル以外は無効化される)
// ---------------------------------------------------------------------
test_eq(__LINE__, konawiki_parser_checkURL('https://example.com/?a=1&b=2'), 'https://example.com/?a=1&amp;b=2', 'checkURL: https kept and escaped');
test_eq(__LINE__, konawiki_parser_checkURL('http://example.com/'), 'http://example.com/', 'checkURL: http kept');
$html = konawiki_parser_checkURL("javascript:alert(1)");
test_contains(__LINE__, $html, '?__WIKILINK_ERROR__', 'checkURL: javascript is rejected');
test_not_contains(__LINE__, $html, 'javascript:', 'checkURL: javascript scheme removed');

// ---------------------------------------------------------------------
// Wikiリンク (DBにページがあるかどうかで表示が変わる)
// ---------------------------------------------------------------------
test_init_db();
test_eq(__LINE__, test_write_page('ParserLinkPage', 'linked'), TRUE, 'wikilink: prepare page');
$_GET['page'] = 'FrontPage';

$html = konawiki_parser_makeWikiLink('ParserLinkPage');
test_eq(__LINE__, $html, "<a href='index.php?ParserLinkPage'>ParserLinkPage</a>", 'wikilink: existing page');

$html = konawiki_parser_makeWikiLink('NoSuchLinkPage');
test_contains(__LINE__, $html, "<span class='none'>NoSuchLinkPage</span>", 'wikilink: missing page gets none class');

$html = konawiki_parser_makeWikiLink('click:https://example.com/');
test_eq(__LINE__, $html, "<a href='https://example.com/'>click</a>", 'wikilink: caption with url');

$html = konawiki_parser_makeWikiLink('https://example.com/');
test_eq(__LINE__, $html, "<a href='https://example.com/'>https://example.com/</a>", 'wikilink: bare url');

$html = konawiki_parser_makeWikiLink('別名:ParserLinkPage');
test_contains(__LINE__, $html, '>別名</a>', 'wikilink: caption with wiki page');

// パーサ経由でも [[...]] がリンクになる
$html = konawiki_parser_convert("see [[ParserLinkPage]]\n");
test_contains(__LINE__, $html, "<a href='index.php?ParserLinkPage'>ParserLinkPage</a>", 'wikilink: via convert');
