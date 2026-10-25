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
// attach テーブル自体を旧定義で作り直して再現する。カラムを落とす方法
// (DROP COLUMN) は SQLite 3.35+ が必要で環境依存になるため使わない
// (Codexレビュー指摘)。また ensure の static キャッシュは $force で
// 再確認させるので、他のテストファイルの実行順にも依存しない。
// ---------------------------------------------------------------------
$cols = db_get("PRAGMA table_info(attach)");
$names = [];
foreach ($cols as $c) { $names[] = $c['name']; }
test_assert(__LINE__, in_array('comment', $names, TRUE),
    'attachfiles: fresh DB has comment column (SQL)');

$now = time();
db_exec("ALTER TABLE attach RENAME TO attach_mig_backup");
db_exec("CREATE TABLE attach ("
    . "id INTEGER PRIMARY KEY, log_id INTEGER, name TEXT,"
    . " ext TEXT, ctime INTEGER, mtime INTEGER)");
$legacy_id = db_insert(
    "INSERT INTO attach (log_id,name,ext,ctime,mtime) VALUES (?,?,?,?,?)",
    [$page_id, 'legacy.txt', 'text/plain', $now, $now]);
test_assert(__LINE__, $legacy_id > 0, 'attachfiles: legacy row inserted');

test_eq(__LINE__, konawiki_attach_ensure_comment_column(TRUE), TRUE,
    'attachfiles: ensure adds missing column');
$cols = db_get("PRAGMA table_info(attach)");
$names = [];
foreach ($cols as $c) { $names[] = $c['name']; }
test_assert(__LINE__, in_array('comment', $names, TRUE),
    'attachfiles: comment column restored by ensure');
$row = db_get1("SELECT * FROM attach WHERE id=?", [$legacy_id]);
test_eq(__LINE__, konawiki_attach_row_comment($row), '',
    'attachfiles: legacy row comment defaults to empty');
// 元のテーブルに戻し、ensure のキャッシュも実テーブルに合わせ直す
db_exec("DROP TABLE attach");
db_exec("ALTER TABLE attach_mig_backup RENAME TO attach");
test_eq(__LINE__, konawiki_attach_ensure_comment_column(TRUE), TRUE,
    'attachfiles: ensure after table restore');
test_eq(__LINE__, konawiki_attach_ensure_comment_column(), TRUE,
    'attachfiles: ensure is idempotent');

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
// 表示では comment カラムを移行せず、書き込みで移行する (Issue #46)
// attach テーブルを旧定義 (comment なし) で作り直して再現する。
// この節の間、他の節が作った attach 行はバックアップ側に退避されているため参照しない。
// ---------------------------------------------------------------------
require_once KONAWIKI_DIR_ACTION . '/attach.inc.php';
include_once KONAWIKI_DIR_PLUGINS . '/attachfiles.inc.php';
db_exec("ALTER TABLE attach RENAME TO attach_mig46_backup");
db_exec("CREATE TABLE attach ("
    . "id INTEGER PRIMARY KEY, log_id INTEGER, name TEXT,"
    . " ext TEXT, ctime INTEGER, mtime INTEGER)");
$legacy46_id = db_insert(
    "INSERT INTO attach (log_id,name,ext,ctime,mtime) VALUES (?,?,?,?,?)",
    [$page_id, 'legacy46.txt', 'text/plain', $now, $now]);
test_assert(__LINE__, $legacy46_id > 0, 'attachfiles: #46 legacy row inserted');

// 確認専用の関数は DDL を実行しない
test_eq(__LINE__, konawiki_attach_comment_column_available(TRUE), FALSE,
    'attachfiles: #46 available is FALSE without column');
test_eq(__LINE__, konawiki_attach_has_comment_column(), FALSE,
    'attachfiles: #46 available does not migrate');

// 添付一覧の表示では移行しない
$_GET['page'] = 'AttachFilesTest'; $_POST = [];
$list46 = konawiki_getAttachList('AttachFilesTest');
$names46 = [];
foreach ($list46 as $r) { $names46[] = $r['name']; }
test_assert(__LINE__, in_array('legacy46.txt', $names46, TRUE),
    'attachfiles: #46 getAttachList returns legacy row');
test_eq(__LINE__, konawiki_attach_has_comment_column(), FALSE,
    'attachfiles: #46 getAttachList does not migrate');

// #attachfiles の表示でも移行しない
$html = plugin_attachfiles_convert([]);
test_contains(__LINE__, $html, 'legacy46.txt',
    'attachfiles: #46 plugin shows legacy row');
test_eq(__LINE__, konawiki_attach_has_comment_column(), FALSE,
    'attachfiles: #46 plugin does not migrate');

// カラムが無い行のコメントは空文字列
$legacy46_row = null;
foreach ($list46 as $r) {
    if ($r['name'] === 'legacy46.txt') { $legacy46_row = $r; }
}
test_eq(__LINE__, konawiki_attach_row_comment($legacy46_row), '',
    'attachfiles: #46 legacy row comment is empty');

// 書き込み経路では移行する。表示の確認で FALSE がキャッシュされていても、
// 「カラムが無い」ことと「移行を試みて失敗した」ことは分けて保持されるため、
// 同じプロセス内でも ensure が移行を試みる (agyレビュー指摘)
test_eq(__LINE__, konawiki_attach_set_comment($legacy46_id, '書き込みで移行'), TRUE,
    'attachfiles: #46 set_comment migrates');
test_eq(__LINE__, konawiki_attach_has_comment_column(), TRUE,
    'attachfiles: #46 column added by write');
$row = db_get1("SELECT * FROM attach WHERE id=?", [$legacy46_id]);
test_eq(__LINE__, $row['comment'], '書き込みで移行',
    'attachfiles: #46 comment saved after migration');
test_eq(__LINE__, konawiki_attach_comment_column_available(), TRUE,
    'attachfiles: #46 available shares cache with ensure');

// 元のテーブルに戻し、キャッシュも実テーブルに合わせ直す
db_exec("DROP TABLE attach");
db_exec("ALTER TABLE attach_mig46_backup RENAME TO attach");
test_eq(__LINE__, konawiki_attach_ensure_comment_column(TRUE), TRUE,
    'attachfiles: #46 ensure after table restore');
$row = db_get1("SELECT * FROM attach WHERE id=?", [$doc_id]);
test_eq(__LINE__, $row['name'], 'doc.pdf',
    'attachfiles: #46 original rows restored');
$_GET = ['page' => 'AttachFilesTest']; $_POST = [];

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

// ファイル名に '=' を含む場合は「ファイル名=」の前方一致で解決する
test_eq(__LINE__, plugin_attachfiles_arg_comment(['data=2023.csv=集計結果'], 'data=2023.csv'),
    '集計結果', 'attachfiles: arg_comment with = in filename');
test_eq(__LINE__, plugin_attachfiles_arg_comment(['a.png=x'], 'b.png'),
    null, 'attachfiles: arg_comment not specified is null');
test_eq(__LINE__, plugin_attachfiles_arg_comment(['a.png='], 'a.png'),
    '', 'attachfiles: arg_comment empty specified');

// 引数の解決は実際の添付名一覧に対して最長一致で行う (Codexレビュー指摘)
// `data` と `data=2023.csv` が両方あるとき、後者への指定を前者に適用しない
test_eq(__LINE__, plugin_attachfiles_resolve_comments(
    ['data=2023.csv=集計結果'], ['data', 'data=2023.csv']),
    ['data=2023.csv' => '集計結果'], 'attachfiles: resolve longest match wins');
test_eq(__LINE__, plugin_attachfiles_resolve_comments(
    ['data=短い方'], ['data', 'data=2023.csv']),
    ['data' => '短い方'], 'attachfiles: resolve plain name');
test_eq(__LINE__, plugin_attachfiles_resolve_comments(
    ['doc.pdf=資料', ' a.png = spaced '], ['doc.pdf', 'a.png']),
    ['doc.pdf' => '資料', 'a.png' => 'spaced'], 'attachfiles: resolve spaced name');
test_eq(__LINE__, plugin_attachfiles_resolve_comments([], ['a.png']), [],
    'attachfiles: resolve no args');
test_eq(__LINE__, plugin_attachfiles_resolve_comments(
    ['ghost.txt=x'], ['a.png']), [],
    'attachfiles: resolve unknown name ignored');
$eq_id = db_insert(
    "INSERT INTO attach (log_id,name,ext,comment,ctime,mtime) VALUES (?,?,?,?,?,?)",
    [$page_id, 'data=2023.csv', 'text/csv', 'DB側コメント', $now, $now + 4]);
test_assert(__LINE__, $eq_id > 0, 'attachfiles: = filename row inserted');
$data_id = db_insert(
    "INSERT INTO attach (log_id,name,ext,comment,ctime,mtime) VALUES (?,?,?,?,?,?)",
    [$page_id, 'data', 'text/plain', 'data用コメント', $now, $now + 5]);
test_assert(__LINE__, $data_id > 0, 'attachfiles: prefix filename row inserted');

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

// '=' を含むファイル名でも引数コメントが付く
$_GET['page'] = 'AttachFilesTest'; $_POST = [];
$html = plugin_attachfiles_convert(['data=2023.csv=集計結果']);
test_contains(__LINE__, $html, '集計結果', 'attachfiles: = filename arg comment shown');
test_not_contains(__LINE__, $html, 'DB側コメント',
    'attachfiles: = filename arg comment overrides DB');
// 前方一致する別ファイル (`data`) へは誤って適用されない (Codexレビュー指摘)
test_not_contains(__LINE__, $html, '2023.csv=集計結果',
    'attachfiles: arg comment not misapplied to prefix file');
test_contains(__LINE__, $html, 'data用コメント',
    'attachfiles: prefix file keeps its DB comment');

// ---------------------------------------------------------------------
// コメント更新アクション (action_attach_comment)
// この節では edit_token の照合を検証対象にするため、書き込み認証は
// 明示的に無効化して konawiki_auth() を通過させる (節の終わりで復元する)。
// ※ 以前はオプション既定値のタイポで auth.write.enabled が未設定だった
//   ことに依存していたが、#36 の修正で既定値 (TRUE) が効くようになった。
// 描画 (konawiki_showMessage / konawiki_error) は出力として捕捉する。
$test_saved_auth_write = isset($private['auth.write.enabled'])
    ? $private['auth.write.enabled'] : null;
$private['auth.write.enabled'] = FALSE;
// ---------------------------------------------------------------------
require_once KONAWIKI_DIR_FW_SIMPLE . '/fw_template_engine.lib.php';
require_once KONAWIKI_DIR_ACTION . '/attach.inc.php';
if (session_status() !== PHP_SESSION_ACTIVE) { $_SESSION = []; }
$_SESSION['konawiki2_edit_token'] = 'test-token-21';

function test_attach_comment($page, $post) {
    $_GET = ['page' => $page];
    $_POST = $post;
    konawiki_clearCache();
    ob_start();
    action_attach_comment();
    return ob_get_clean();
}
function test_attach_comment_value($id) {
    $row = db_get1("SELECT * FROM attach WHERE id=?", [$id]);
    return $row ? $row['comment'] : null;
}

// 正常系: 自ページの添付のコメントを更新できる
$out = test_attach_comment('AttachFilesTest', [
    'page' => 'AttachFilesTest', 'id' => (string)$doc_id,
    'comment' => 'アクションで更新', 'edit_token' => 'test-token-21']);
test_contains(__LINE__, $out, 'コメントを更新しました', 'attachfiles: action ok message');
test_eq(__LINE__, test_attach_comment_value($doc_id), 'アクションで更新',
    'attachfiles: action updated DB');

// トークン不一致では更新されない
$out = test_attach_comment('AttachFilesTest', [
    'page' => 'AttachFilesTest', 'id' => (string)$doc_id,
    'comment' => '不正な更新', 'edit_token' => 'wrong-token']);
test_eq(__LINE__, test_attach_comment_value($doc_id), 'アクションで更新',
    'attachfiles: action rejects bad token');

// 他ページの添付は更新できない
test_write_page('AttachFilesOther', 'other page');
$other_id = konawiki_getPageId('AttachFilesOther');
$other_attach_id = db_insert(
    "INSERT INTO attach (log_id,name,ext,comment,ctime,mtime) VALUES (?,?,?,?,?,?)",
    [$other_id, 'other.txt', 'text/plain', '他ページのコメント', $now, $now]);
$out = test_attach_comment('AttachFilesTest', [
    'page' => 'AttachFilesTest', 'id' => (string)$other_attach_id,
    'comment' => '乗っ取り', 'edit_token' => 'test-token-21']);
test_eq(__LINE__, test_attach_comment_value($other_attach_id), '他ページのコメント',
    'attachfiles: action rejects other page attach');

// ページが存在しない場合も所属不明として更新できない (agyレビュー高1の回帰)
$out = test_attach_comment('NoSuchPage21', [
    'page' => 'NoSuchPage21', 'id' => (string)$doc_id,
    'comment' => 'ページ不明の更新', 'edit_token' => 'test-token-21']);
test_eq(__LINE__, test_attach_comment_value($doc_id), 'アクションで更新',
    'attachfiles: action rejects unknown page');

// 500文字を超えるコメントは更新できない
$out = test_attach_comment('AttachFilesTest', [
    'page' => 'AttachFilesTest', 'id' => (string)$doc_id,
    'comment' => str_repeat('あ', 501), 'edit_token' => 'test-token-21']);
test_eq(__LINE__, test_attach_comment_value($doc_id), 'アクションで更新',
    'attachfiles: action rejects too long comment');

// 存在しない id・数値でない id は更新できない
$out = test_attach_comment('AttachFilesTest', [
    'page' => 'AttachFilesTest', 'id' => '999999',
    'comment' => 'x', 'edit_token' => 'test-token-21']);
test_eq(__LINE__, test_attach_comment_value($doc_id), 'アクションで更新',
    'attachfiles: action unknown id leaves data');
$out = test_attach_comment('AttachFilesTest', [
    'page' => 'AttachFilesTest', 'id' => 'abc',
    'comment' => 'x', 'edit_token' => 'test-token-21']);
test_eq(__LINE__, test_attach_comment_value($doc_id), 'アクションで更新',
    'attachfiles: action non-numeric id leaves data');

// ---------------------------------------------------------------------
// 実ルーティングとの対応 (Codexレビュー指摘)
// index.php?AttachFilesTest&attach&comment は konawiki_parseURI で
// action=attach / stat=comment に分解され、ディスパッチャ
// (konawiki_execute_action) が action/attach.inc.php の
// action_attach_comment() を呼ぶ規約であることを確認する。
// 認証を有効にした実経路の動作は tests/attach46_http.py で自動検証している（Issue #46）。
// ---------------------------------------------------------------------
$test_saved_qs = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
$test_saved_get = $_GET;
$_SERVER['QUERY_STRING'] = 'AttachFilesTest&attach&comment';
$_GET = [];
konawiki_parseURI();
test_eq(__LINE__, $_GET['page'], 'AttachFilesTest', 'attachfiles: route page');
test_eq(__LINE__, $_GET['action'], 'attach', 'attachfiles: route action');
test_eq(__LINE__, $_GET['stat'], 'comment', 'attachfiles: route stat');
test_assert(__LINE__, file_exists(KONAWIKI_DIR_ACTION . '/attach.inc.php'),
    'attachfiles: route action file exists');
test_assert(__LINE__,
    function_exists('action_' . $_GET['action'] . '_' . $_GET['stat']),
    'attachfiles: route resolves to action function');
if ($test_saved_qs === null) { unset($_SERVER['QUERY_STRING']); }
else { $_SERVER['QUERY_STRING'] = $test_saved_qs; }
$_GET = $test_saved_get;

// 後始末 (後続テストファイルへ状態を持ち越さない)
if ($test_saved_auth_write === null) { unset($private['auth.write.enabled']); }
else { $private['auth.write.enabled'] = $test_saved_auth_write; }
$_GET = ['page' => 'AttachFilesTest']; $_POST = [];
konawiki_clearCache();
