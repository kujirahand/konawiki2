<?php
/**
 * 添付ファイル名の変更機能テスト (Issue #22)
 * - 名前変更の検証ヘルパー (konawiki_attach_validate_rename)
 * - 名前変更ヘルパー (konawiki_attach_rename)
 * - action_attach_rename() の動作と各種拒否ケース
 * ※ このファイルは attachfiles.test.php より先に実行されるため、
 *   konawiki_attach_ensure_comment_column() や konawiki_getAttachList() は呼ばない
 */
require_once __DIR__ . '/test_common.inc.php';

test_init_db();
test_write_page('AttachRenameTest', 'attach rename test page');
$page_id = konawiki_getPageId('AttachRenameTest');
test_assert(__LINE__, $page_id !== FALSE && $page_id > 0, 'attach_rename: page created');

// ---------------------------------------------------------------------
// バリデーション
// ---------------------------------------------------------------------
test_eq(__LINE__, konawiki_attach_validate_rename('old.txt', 'new.txt'), '',
    'attach_rename: valid name');
test_eq(__LINE__, konawiki_attach_validate_rename('old.txt', '日本語の名前.txt'), '',
    'attach_rename: japanese name');
test_assert(__LINE__, konawiki_attach_validate_rename('old.txt', '') !== '',
    'attach_rename: empty name rejected');
test_assert(__LINE__, konawiki_attach_validate_rename('old.txt', 'a/b.txt') !== '',
    'attach_rename: slash rejected');
test_assert(__LINE__, konawiki_attach_validate_rename('old.txt', 'a\\b.txt') !== '',
    'attach_rename: backslash rejected');
test_assert(__LINE__, konawiki_attach_validate_rename('old', '.') !== '',
    'attach_rename: dot rejected');
test_assert(__LINE__, konawiki_attach_validate_rename('a.txt', 'a.png') !== '',
    'attach_rename: extension change rejected');
test_assert(__LINE__, konawiki_attach_validate_rename('a.txt', 'a.TXT') !== '',
    'attach_rename: extension case change rejected');
test_assert(__LINE__, konawiki_attach_validate_rename('abc', 'abc.txt') !== '',
    'attach_rename: adding extension rejected');
test_assert(__LINE__,
    konawiki_attach_validate_rename('a.txt', str_repeat('a', 252) . '.txt') !== '',
    'attach_rename: too long name rejected');

// ---------------------------------------------------------------------
// ヘルパー
// ---------------------------------------------------------------------
$now = time();
$id1 = db_insert(
    "INSERT INTO attach (log_id,name,ext,ctime,mtime) VALUES (?,?,?,?,?)",
    [$page_id, 'old.txt', 'text/plain', $now, $now + 1]);
$id2 = db_insert(
    "INSERT INTO attach (log_id,name,ext,ctime,mtime) VALUES (?,?,?,?,?)",
    [$page_id, 'exists.txt', 'text/plain', $now, $now + 2]);
test_assert(__LINE__, $id1 > 0 && $id2 > 0, 'attach_rename: rows inserted');

$before = db_get1("SELECT * FROM attach WHERE id=?", [$id1]);
test_eq(__LINE__, konawiki_attach_rename($id1, 'new.txt'), TRUE,
    'attach_rename: helper returns TRUE');
$after = db_get1("SELECT * FROM attach WHERE id=?", [$id1]);
test_eq(__LINE__, $after['name'], 'new.txt', 'attach_rename: name changed in DB');
test_eq(__LINE__, intval($after['mtime']), intval($before['mtime']),
    'attach_rename: mtime unchanged');
if (array_key_exists('comment', $before)) {
    test_eq(__LINE__, $after['comment'], $before['comment'],
        'attach_rename: comment unchanged');
}
$old = db_get1("SELECT id FROM attach WHERE log_id=? AND name=?", [$page_id, 'old.txt']);
test_assert(__LINE__, !$old, 'attach_rename: old name not found');
$found = db_get1("SELECT id FROM attach WHERE log_id=? AND name=?", [$page_id, 'new.txt']);
test_eq(__LINE__, intval($found['id']), intval($id1), 'attach_rename: new name found');

// ---------------------------------------------------------------------
// アクション
// ---------------------------------------------------------------------
require_once KONAWIKI_DIR_FW_SIMPLE . '/fw_template_engine.lib.php';
require_once KONAWIKI_DIR_ACTION . '/attach.inc.php';
if (session_status() !== PHP_SESSION_ACTIVE) { $_SESSION = []; }
$_SESSION['konawiki2_edit_token'] = 'test-token-22';

function test_attach_rename_action($page, $post) {
    $_GET = ['page' => $page];
    $_POST = $post;
    konawiki_clearCache();
    ob_start();
    action_attach_rename();
    return ob_get_clean();
}
function test_attach_rename_name($id) {
    $row = db_get1("SELECT * FROM attach WHERE id=?", [$id]);
    return $row ? $row['name'] : null;
}
function test_attach_rename_post($page, $id, $newname, $token = 'test-token-22') {
    return ['page' => $page, 'id' => (string)$id,
        'newname' => $newname, 'edit_token' => $token];
}

// 正常系
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', $id1, 'renamed.txt'));
test_contains(__LINE__, $out, 'ファイル名を変更しました', 'attach_rename: action ok message');
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action updated DB');

// トークン不一致
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', $id1, 'bad.txt', 'wrong-token'));
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action rejects bad token');

// 他ページの添付
test_write_page('AttachRenameOther', 'other page');
$other_id = konawiki_getPageId('AttachRenameOther');
$other_attach_id = db_insert(
    "INSERT INTO attach (log_id,name,ext,ctime,mtime) VALUES (?,?,?,?,?)",
    [$other_id, 'other.txt', 'text/plain', $now, $now]);
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', $other_attach_id, 'hijack.txt'));
test_eq(__LINE__, test_attach_rename_name($other_attach_id), 'other.txt',
    'attach_rename: action rejects other page attach');

// 存在しないページ
$out = test_attach_rename_action('NoSuchPage22',
    test_attach_rename_post('NoSuchPage22', $id1, 'unknown.txt'));
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action rejects unknown page');

// 同名の指定
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', $id1, 'renamed.txt'));
test_contains(__LINE__, $out, 'ファイル名は同じです', 'attach_rename: same name message');
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: same name leaves DB');

// 既存ファイルとの重複
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', $id1, 'exists.txt'));
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action rejects duplicate name');
test_eq(__LINE__, test_attach_rename_name($id2), 'exists.txt',
    'attach_rename: duplicate target untouched');

// 拡張子の変更
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', $id1, 'renamed.png'));
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action rejects extension change');

// パス区切り
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', $id1, '../evil.txt'));
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action rejects path separator');

// 存在しない id・数値でない id
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', 999999, 'x.txt'));
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action unknown id leaves data');
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', 'abc', 'x.txt'));
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action non-numeric id leaves data');
test_eq(__LINE__, test_attach_rename_name($id2), 'exists.txt',
    'attach_rename: other rows untouched');

// 後始末 (後続テストファイルへ状態を持ち越さない)
$_GET = ['page' => 'AttachRenameTest']; $_POST = [];
konawiki_clearCache();
