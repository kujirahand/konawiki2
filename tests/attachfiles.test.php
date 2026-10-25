<?php
/**
 * attachfiles プラグインのファイル毎コメント機能テスト (Issue #21)
 * - attach テーブルの comment カラムと既存DB向け遅延マイグレーション
 * - コメントの保存ヘルパー (konawiki_attach_set_comment など)
 * - #attachfiles でのDBコメント表示と引数 (ファイル名=コメント) の優先
 * - コメントのHTMLエスケープ (XSS対策)
 */
require_once __DIR__ . '/test_common.inc.php';

test_init_db();
test_write_page('AttachFilesTest', 'attachfiles test page');
$page_id = konawiki_getPageId('AttachFilesTest');
test_assert(__LINE__, $page_id !== FALSE && $page_id > 0, 'attachfiles: page created');

// ---------------------------------------------------------------------
// 既存DB (comment カラムなし) からの遅延マイグレーション
// ※ ensure の static キャッシュのため、この節は他の ensure 呼び出しより
//   先に実行する必要がある
// ---------------------------------------------------------------------
$cols = db_get("PRAGMA table_info(attach)");
$names = [];
foreach ($cols as $c) { $names[] = $c['name']; }
test_assert(__LINE__, in_array('comment', $names, TRUE),
    'attachfiles: fresh DB has comment column (SQL)');

// カラムを落として旧DBを再現し、旧形式の行を入れる
db_exec("ALTER TABLE attach DROP COLUMN comment");
$now = time();
$legacy_id = db_insert(
    "INSERT INTO attach (log_id,name,ext,ctime,mtime) VALUES (?,?,?,?,?)",
    [$page_id, 'legacy.txt', 'text/plain', $now, $now]);
test_assert(__LINE__, $legacy_id > 0, 'attachfiles: legacy row inserted');

test_eq(__LINE__, konawiki_attach_ensure_comment_column(), TRUE,
    'attachfiles: ensure adds missing column');
$cols = db_get("PRAGMA table_info(attach)");
$names = [];
foreach ($cols as $c) { $names[] = $c['name']; }
test_assert(__LINE__, in_array('comment', $names, TRUE),
    'attachfiles: comment column restored by ensure');
$row = db_get1("SELECT * FROM attach WHERE id=?", [$legacy_id]);
test_eq(__LINE__, konawiki_attach_row_comment($row), '',
    'attachfiles: legacy row comment defaults to empty');
test_eq(__LINE__, konawiki_attach_ensure_comment_column(), TRUE,
    'attachfiles: ensure is idempotent');
db_exec("DELETE FROM attach WHERE id=?", [$legacy_id]);

// ---------------------------------------------------------------------
// コメント保存ヘルパー
// ---------------------------------------------------------------------
test_eq(__LINE__, konawiki_attach_row_comment([]), '',
    'attachfiles: row_comment of empty row');
test_eq(__LINE__, konawiki_attach_row_comment(['comment' => null]), '',
    'attachfiles: row_comment of null');
test_eq(__LINE__, konawiki_attach_row_comment(['comment' => 'abc']), 'abc',
    'attachfiles: row_comment value');

$img_id = db_insert(
    "INSERT INTO attach (log_id,name,ext,comment,ctime,mtime) VALUES (?,?,?,?,?,?)",
    [$page_id, 'photo.png', 'image/png', '写真のコメント', $now, $now + 1]);
$doc_id = db_insert(
    "INSERT INTO attach (log_id,name,ext,comment,ctime,mtime) VALUES (?,?,?,?,?,?)",
    [$page_id, 'doc.pdf', 'application/pdf', '資料のコメント', $now, $now + 2]);
$evil_id = db_insert(
    "INSERT INTO attach (log_id,name,ext,comment,ctime,mtime) VALUES (?,?,?,?,?,?)",
    [$page_id, 'evil.txt', 'text/plain', '<script>alert(1)</script>', $now, $now + 3]);
test_assert(__LINE__, $img_id > 0 && $doc_id > 0 && $evil_id > 0,
    'attachfiles: rows inserted');

test_eq(__LINE__, konawiki_attach_set_comment($doc_id, '更新したコメント'), TRUE,
    'attachfiles: set_comment');
$row = db_get1("SELECT * FROM attach WHERE id=?", [$doc_id]);
test_eq(__LINE__, $row['comment'], '更新したコメント',
    'attachfiles: comment updated in DB');
test_eq(__LINE__, konawiki_attach_set_comment($doc_id, ''), TRUE,
    'attachfiles: set_comment clear');
$row = db_get1("SELECT * FROM attach WHERE id=?", [$doc_id]);
test_eq(__LINE__, $row['comment'], '', 'attachfiles: comment cleared');
konawiki_attach_set_comment($doc_id, '資料のコメント');

// ---------------------------------------------------------------------
// 引数パース (ファイル名=コメント)
// ---------------------------------------------------------------------
include_once KONAWIKI_DIR_PLUGINS . '/attachfiles.inc.php';
test_eq(__LINE__, plugin_attachfiles_parse_comments([]), [],
    'attachfiles: parse no args');
test_eq(__LINE__, plugin_attachfiles_parse_comments(['a.png=hello']),
    ['a.png' => 'hello'], 'attachfiles: parse basic');
test_eq(__LINE__, plugin_attachfiles_parse_comments(['a.png=x=y']),
    ['a.png' => 'x=y'], 'attachfiles: parse keeps = in comment');
test_eq(__LINE__, plugin_attachfiles_parse_comments([' a.png = spaced ']),
    ['a.png' => 'spaced'], 'attachfiles: parse trims');
test_eq(__LINE__, plugin_attachfiles_parse_comments(['noequals', '=noname', '']),
    [], 'attachfiles: parse ignores invalid params');

// ---------------------------------------------------------------------
// #attachfiles の表示 (DBコメント)
// ---------------------------------------------------------------------
$_GET['page'] = 'AttachFilesTest'; $_POST = [];
$html = plugin_attachfiles_convert([]);
test_contains(__LINE__, $html, 'photo.png', 'attachfiles: image name shown');
test_contains(__LINE__, $html, '写真のコメント', 'attachfiles: image DB comment shown');
test_contains(__LINE__, $html, 'imgcomment', 'attachfiles: image comment class');
test_contains(__LINE__, $html, 'doc.pdf', 'attachfiles: doc name shown');
test_contains(__LINE__, $html, '資料のコメント', 'attachfiles: doc DB comment shown');
test_contains(__LINE__, $html, 'doccomment', 'attachfiles: doc comment class');
// XSS: コメントはエスケープして表示する
test_contains(__LINE__, $html, '&lt;script&gt;alert(1)&lt;/script&gt;',
    'attachfiles: comment is escaped');
test_not_contains(__LINE__, $html, '<script>alert(1)</script>',
    'attachfiles: raw script not emitted');

// ---------------------------------------------------------------------
// #attachfiles の表示 (引数コメントがDBより優先)
// ---------------------------------------------------------------------
$html = plugin_attachfiles_convert(['doc.pdf=引数のコメント']);
test_contains(__LINE__, $html, '引数のコメント', 'attachfiles: arg comment shown');
test_not_contains(__LINE__, $html, '資料のコメント',
    'attachfiles: arg comment overrides DB comment');
test_contains(__LINE__, $html, '写真のコメント',
    'attachfiles: other file keeps DB comment');
// 空コメントを引数で指定するとDBコメントを隠せる
$html = plugin_attachfiles_convert(['photo.png=']);
test_not_contains(__LINE__, $html, '写真のコメント',
    'attachfiles: empty arg comment hides DB comment');

// パーサ経由でも引数が届く
$html = konawiki_parser_convert("#attachfiles(doc.pdf=パーサー経由)\n");
test_contains(__LINE__, $html, 'パーサー経由', 'attachfiles: via parser arg comment');
$html = konawiki_parser_convert("#attachfiles\n");
test_contains(__LINE__, $html, '資料のコメント', 'attachfiles: via parser DB comment');
