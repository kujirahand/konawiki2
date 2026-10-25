<?php
/**
 * MIME判定・HTMLヘルパー・UserAgent判定の機能テスト
 * (mime.inc.php / html.inc.php / useragent.inc.php)
 */
require_once __DIR__ . '/test_common.inc.php';

// ---------------------------------------------------------------------
// mime_content_type_e: 拡張子から MIME を判定する (大文字小文字を区別しない)
// ---------------------------------------------------------------------
test_eq(__LINE__, mime_content_type_e('photo.jpg'), 'image/jpeg', 'mime: jpg');
test_eq(__LINE__, mime_content_type_e('photo.PNG'), 'image/png', 'mime: PNG upper');
test_eq(__LINE__, mime_content_type_e('doc.pdf'), 'application/pdf', 'mime: pdf');
test_eq(__LINE__, mime_content_type_e('page.html'), 'text/html', 'mime: html');
test_eq(__LINE__, mime_content_type_e('memo.txt'), 'text/plain', 'mime: txt');
test_eq(__LINE__, mime_content_type_e('data.csv'), 'text/comma-separated-values', 'mime: csv');
test_eq(__LINE__, mime_content_type_e('style.css'), 'text/css', 'mime: css');
test_eq(__LINE__, mime_content_type_e('sound.mp3'), 'audio/mp3', 'mime: mp3');
test_eq(__LINE__, mime_content_type_e('movie.mp4'), 'video/mp4', 'mime: mp4');
test_eq(__LINE__, mime_content_type_e('archive.tar.zip'), 'application/zip', 'mime: last extension wins');
test_eq(__LINE__, mime_content_type_e('file.unknown'), 'application/octet-stream', 'mime: unknown extension');
test_eq(__LINE__, mime_content_type_e('noext'), 'application/octet-stream', 'mime: no extension');

// ---------------------------------------------------------------------
// html.inc.php: フォーム部品
// ---------------------------------------------------------------------
test_eq(__LINE__, form_tag('/x'), "<form action='/x' method='post'>\n", 'form: tag default post');
test_eq(__LINE__, form_tag('/x', 'get'), "<form action='/x' method='get'>\n", 'form: tag get');
test_eq(__LINE__, form_input_hidden('n', 'v'), "<input type='hidden' name='n' value='v'/>", 'form: hidden');
$html = form_input_text('name1', 'val', ['size' => '10']);
test_contains(__LINE__, $html, "type='text'", 'form: text type');
test_contains(__LINE__, $html, "value='val'", 'form: text value');
test_contains(__LINE__, $html, "size='10'", 'form: text option');
$html = form_input_password('pw', '');
test_contains(__LINE__, $html, "type='password'", 'form: password type');
test_eq(__LINE__, form_label('n', 'cap'), "<label for='n'>cap</label>", 'form: label');
$html = form_input_submit('Send');
test_contains(__LINE__, $html, "type='submit'", 'form: submit type');
test_contains(__LINE__, $html, "value='Send'", 'form: submit caption');

// CSS/JS のまとめ出力 (同じ uid では2回目以降は空)
html_css_add('.testbox', ['color' => 'red', 'margin' => '1em']);
$css = html_css_out('test-uid-1');
test_contains(__LINE__, $css, '.testbox {', 'html_css: selector');
test_contains(__LINE__, $css, 'color:red;', 'html_css: property');
test_eq(__LINE__, html_css_out('test-uid-1'), '', 'html_css: same uid suppressed');
$js = html_js_out('test-js-1', 'alert(1);');
test_contains(__LINE__, $js, 'alert(1);', 'html_js: code');
test_eq(__LINE__, html_js_out('test-js-1', 'alert(1);'), '', 'html_js: same uid suppressed');

// ---------------------------------------------------------------------
// useragent.inc.php: スマートフォン判定など
// ※ useragent_is_docomo() 等のガラケー判定は ereg() を使う旧実装のため対象外
// ---------------------------------------------------------------------
$saved_ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)';
test_eq(__LINE__, kona_getUserAgent(), $_SERVER['HTTP_USER_AGENT'], 'ua: raw string');
test_eq(__LINE__, useragent_is_iPhone(), TRUE, 'ua: iPhone');
test_eq(__LINE__, useragent_is_android(), FALSE, 'ua: iPhone is not android');
test_eq(__LINE__, useragent_is_smartphone(), TRUE, 'ua: iPhone is smartphone');

$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 14; Pixel 8)';
test_eq(__LINE__, useragent_is_android(), TRUE, 'ua: android');
test_eq(__LINE__, useragent_is_iPhone(), FALSE, 'ua: android is not iPhone');
test_eq(__LINE__, useragent_is_smartphone(), TRUE, 'ua: android is smartphone');

$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)';
test_eq(__LINE__, useragent_is_iPad(), TRUE, 'ua: iPad');

$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
test_eq(__LINE__, useragent_is_iPhone(), FALSE, 'ua: desktop not iPhone');
test_eq(__LINE__, useragent_is_android(), FALSE, 'ua: desktop not android');
test_eq(__LINE__, useragent_is_smartphone(), FALSE, 'ua: desktop not smartphone');

if ($saved_ua === null) { unset($_SERVER['HTTP_USER_AGENT']); }
else { $_SERVER['HTTP_USER_AGENT'] = $saved_ua; }
