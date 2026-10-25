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
// 制御文字・引用符はダウンロード時の Content-Disposition ヘッダーを壊すため拒否
test_assert(__LINE__, konawiki_attach_validate_rename('a.txt', "b\n.txt") !== '',
    'attach_rename: newline rejected');
test_assert(__LINE__, konawiki_attach_validate_rename('a.txt', "b\r.txt") !== '',
    'attach_rename: CR rejected');
test_assert(__LINE__, konawiki_attach_validate_rename('a.txt', "b\t.txt") !== '',
    'attach_rename: tab rejected');
test_assert(__LINE__, konawiki_attach_validate_rename('a.txt', 'b".txt') !== '',
    'attach_rename: double quote rejected');
test_assert(__LINE__, konawiki_attach_validate_rename('a.txt', "b'.txt") !== '',
    'attach_rename: single quote rejected');
// 拡張子なし・複数のドットは末尾の拡張子が同じなら可
test_eq(__LINE__, konawiki_attach_validate_rename('abc', 'def'), '',
    'attach_rename: no extension rename ok');
test_eq(__LINE__, konawiki_attach_validate_rename('a.tar.gz', 'b.tar.gz'), '',
    'attach_rename: multi dot name ok');

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

// comment カラムは #21 を含むベースでは新規DBのスキーマに必ず含まれる。
// (ensure は後続の attachfiles.test.php の移行テストに影響するため、ここでは呼ばない)
$cols = [];
foreach (db_get("PRAGMA table_info(attach)") as $c) { $cols[] = $c['name']; }
test_assert(__LINE__, in_array('comment', $cols, TRUE),
    'attach_rename: comment column exists in schema');
db_exec("UPDATE attach SET comment=? WHERE id=?", ['保持するコメント', $id1]);

$before = db_get1("SELECT * FROM attach WHERE id=?", [$id1]);
test_eq(__LINE__, konawiki_attach_rename($id1, 'new.txt'), TRUE,
    'attach_rename: helper returns TRUE');
$after = db_get1("SELECT * FROM attach WHERE id=?", [$id1]);
test_eq(__LINE__, $after['name'], 'new.txt', 'attach_rename: name changed in DB');
test_eq(__LINE__, intval($after['mtime']), intval($before['mtime']),
    'attach_rename: mtime unchanged');
test_eq(__LINE__, $after['comment'], '保持するコメント',
    'attach_rename: comment unchanged');
// ヘルパーを直接呼んでも、検証・重複ガードが働くこと
test_eq(__LINE__, konawiki_attach_rename($id1, 'new.png'), FALSE,
    'attach_rename: helper rejects extension change');
test_eq(__LINE__, konawiki_attach_rename($id1, "bad\n.txt"), FALSE,
    'attach_rename: helper rejects control char name');
test_eq(__LINE__, konawiki_attach_rename($id1, 'exists.txt', $page_id), FALSE,
    'attach_rename: helper rejects duplicate with log_id guard');
test_eq(__LINE__, konawiki_attach_rename(999999, 'x.txt'), FALSE,
    'attach_rename: helper rejects unknown id');
$row_chk = db_get1("SELECT name FROM attach WHERE id=?", [$id1]);
test_eq(__LINE__, $row_chk['name'], 'new.txt',
    'attach_rename: helper rejections leave DB unchanged');
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

// 存在しない id・数値でない id・整数でない id
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', 999999, 'x.txt'));
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action unknown id leaves data');
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', 'abc', 'x.txt'));
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action non-numeric id leaves data');
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', '1.9', 'float.txt'));
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action float-like id leaves data');
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', '1e2', 'exp.txt'));
test_eq(__LINE__, test_attach_rename_name($id1), 'renamed.txt',
    'attach_rename: action exponent-like id leaves data');
test_eq(__LINE__, test_attach_rename_name($id2), 'exists.txt',
    'attach_rename: other rows untouched');

// ---------------------------------------------------------------------
// 実ファイル・参照プラグイン・後続操作との統合
// ---------------------------------------------------------------------
// 実ファイルは {id}{拡張子} で置かれ、改名しても移動しない
if (!is_dir(KONAWIKI_DIR_ATTACH)) { mkdir(KONAWIKI_DIR_ATTACH, 0777, TRUE); }
$phys = KONAWIKI_DIR_ATTACH . "/{$id1}.txt";
file_put_contents($phys, "attach body");
$_GET = ['page' => 'AttachRenameTest']; $_POST = [];
require_once KONAWIKI_DIR_ENGINE . '/plugins/ref.inc.php';
$ref_new = plugin_ref_convert(['renamed.txt']);
test_contains(__LINE__, $ref_new, "/{$id1}.txt", 'attach_rename: #ref new name resolves to physical file');
test_contains(__LINE__, $ref_new, 'renamed.txt', 'attach_rename: #ref shows new name');
$ref_old = plugin_ref_convert(['new.txt']);
test_contains(__LINE__, $ref_old, 'file not found', 'attach_rename: #ref old name no longer resolves');
// 改名しても実ファイルは同じ場所に残る
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', $id1, 'final.txt'));
test_contains(__LINE__, $out, 'ファイル名を変更しました', 'attach_rename: second rename ok');
test_assert(__LINE__, file_exists($phys), 'attach_rename: physical file stays after rename');
$ref_fin = plugin_ref_convert(['final.txt']);
test_contains(__LINE__, $ref_fin, "/{$id1}.txt", 'attach_rename: #ref final name resolves');
// 日付更新 (stat=up) が改名後も働く
db_exec("UPDATE attach SET mtime=? WHERE id=?", [1000, $id1]);
$_GET = ['page' => 'AttachRenameTest'];
$_POST = ['page' => 'AttachRenameTest', 'id' => (string)$id1,
    'edit_token' => 'test-token-22'];
ob_start(); action_attach_up(); ob_get_clean();
$row_up = db_get1("SELECT mtime FROM attach WHERE id=?", [$id1]);
test_assert(__LINE__, intval($row_up['mtime']) > 1000,
    'attach_rename: date update works after rename');
// 改名したファイルを削除できる
$id3 = db_insert(
    "INSERT INTO attach (log_id,name,ext,ctime,mtime) VALUES (?,?,?,?,?)",
    [$page_id, 'del-target.txt', 'text/plain', $now, $now]);
$out = test_attach_rename_action('AttachRenameTest',
    test_attach_rename_post('AttachRenameTest', $id3, 'del-renamed.txt'));
test_eq(__LINE__, test_attach_rename_name($id3), 'del-renamed.txt',
    'attach_rename: rename target for delete');
$_GET = ['page' => 'AttachRenameTest'];
$_POST = ['page' => 'AttachRenameTest', 'id' => (string)$id3,
    'edit_token' => 'test-token-22'];
ob_start(); action_attach_delete(); ob_get_clean();
$gone = db_get1("SELECT id FROM attach WHERE id=?", [$id3]);
test_assert(__LINE__, !$gone, 'attach_rename: delete works after rename');
@unlink($phys);

// 後始末 (後続テストファイルへ状態を持ち越さない)
$_GET = ['page' => 'AttachRenameTest']; $_POST = [];
konawiki_clearCache();
