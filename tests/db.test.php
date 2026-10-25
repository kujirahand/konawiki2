<?php
/**
 * DBまわりの機能テスト (SQLite をサンドボックス内に作って検証する)
 * - konawiki_initDB / database_set / db_* ラッパー
 * - konawiki_writePage / konawiki_getLog / konawiki_getPageId
 * - タグ、認証ハッシュ、バックアップログ
 * 旧来の Web 経由テスト kona-engine/test/kona_test_basic の内容を含む。
 */
require_once __DIR__ . '/test_common.inc.php';

test_init_db();

// ---------------------------------------------------------------------
// テーブルの初期化 (SQLファイルから作られること)
// ---------------------------------------------------------------------
test_eq(__LINE__, db_table_exists('logs'), TRUE,          'db: main.logs exists');
test_eq(__LINE__, db_table_exists('tags'), TRUE,          'db: main.tags exists');
test_eq(__LINE__, db_table_exists('log_counters'), TRUE,  'db: main.log_counters exists');
test_eq(__LINE__, db_table_exists('attach'), TRUE,        'db: main.attach exists');
test_eq(__LINE__, db_table_exists('no_such_table'), FALSE, 'db: unknown table');
test_eq(__LINE__, db_table_exists('oldlogs', 'backup'), TRUE,   'db: backup.oldlogs exists');
test_eq(__LINE__, db_table_exists('cache_logs', 'backup'), TRUE, 'db: backup.cache_logs exists');
test_eq(__LINE__, db_table_exists('users', 'users'), TRUE,      'db: users.users exists');

// SQL で投入される初期ページ
$log = konawiki_getLog('FrontPage');
test_assert(__LINE__, $log !== FALSE, 'db: FrontPage exists');
test_eq(__LINE__, $log['body'], 'Welcome to FrontPage', 'db: FrontPage body');
$row = db_get1("SELECT * FROM key_values WHERE name=?", ['version']);
test_eq(__LINE__, intval($row['value']), 106, 'db: key_values version');

// konawiki_db_getConfig
test_eq(__LINE__, intval(konawiki_db_getConfig('version')), 106, 'db: getConfig version');
test_eq(__LINE__, konawiki_db_getConfig('no_such_key'), FALSE, 'db: getConfig missing returns FALSE');
test_eq(__LINE__, konawiki_db_getConfig('no_such_key', 'dflt'), 'dflt', 'db: getConfig missing returns default');
test_eq(__LINE__, konawiki_db_getConfig("it's \"quoted\"", 'dflt'), 'dflt', 'db: getConfig quoted key returns default');

// ---------------------------------------------------------------------
// db_* ラッパーの基本動作
// ---------------------------------------------------------------------
$id = db_insert("INSERT INTO tags (log_id, tag) VALUES (?, ?)", [424242, 'direct']);
test_assert(__LINE__, $id > 0, 'db: db_insert returns id');
$row = db_get1("SELECT * FROM tags WHERE log_id=?", [424242]);
test_eq(__LINE__, $row['tag'], 'direct', 'db: db_get1');
$rows = db_get("SELECT * FROM tags WHERE log_id=?", [424242]);
test_eq(__LINE__, count($rows), 1, 'db: db_get count');
db_exec("DELETE FROM tags WHERE log_id=?", [424242]);
test_eq(__LINE__, db_get1("SELECT * FROM tags WHERE log_id=?", [424242]), FALSE, 'db: deleted');

// パラメータはプレースホルダで渡る (SQLインジェクションにならない)
$evil = "x' OR '1'='1";
test_eq(__LINE__, konawiki_getPageId($evil), FALSE, 'db: injection-like page name not found');
test_assert(__LINE__, db_table_exists('logs'), 'db: logs table survives injection attempt');

// ---------------------------------------------------------------------
// ページの書き込みと読み出し (旧 kona_test_basic_write 相当)
// ---------------------------------------------------------------------
$_GET['page'] = 'kona_test/basic/hoge';
// (1) insert
$err = '';
test_eq(__LINE__, konawiki_writePage('hoge', $err), TRUE, 'write: insert');
$log = konawiki_getLog();
test_eq(__LINE__, $log['body'], 'hoge', 'write: insert body');
$page_id = konawiki_getPageId('kona_test/basic/hoge');
test_assert(__LINE__, $page_id !== FALSE && $page_id > 0, 'write: page id assigned');
// (2) update
$err = '';
test_eq(__LINE__, konawiki_writePage('fuga', $err), TRUE, 'write: update');
$log = konawiki_getLog();
test_eq(__LINE__, $log['body'], 'fuga', 'write: update body');
test_eq(__LINE__, konawiki_getPageId('kona_test/basic/hoge'), $page_id, 'write: page id stable');
// (3) ハッシュが違う更新は競合として拒否される
$err = '';
test_eq(__LINE__, konawiki_writePage('piyo', $err, md5('not-current')), FALSE, 'write: hash conflict rejected');
test_ne(__LINE__, $err, '', 'write: hash conflict sets error');
test_eq(__LINE__, konawiki_getLog()['body'], 'fuga', 'write: body unchanged after conflict');
// (4) 正しいハッシュなら更新できる
$err = '';
test_eq(__LINE__, konawiki_writePage('piyo', $err, md5('fuga')), TRUE, 'write: correct hash accepted');
test_eq(__LINE__, konawiki_getLog()['body'], 'piyo', 'write: body after hash update');
// (5) 更新するとバックアップDBに旧ログが残る
$backups = db_get("SELECT * FROM oldlogs WHERE name=?", ['kona_test/basic/hoge'], 'backup');
test_assert(__LINE__, count($backups) >= 1, 'write: backup log recorded');

// 存在しないページ
test_eq(__LINE__, konawiki_getLog('NoSuchPageXYZ'), FALSE, 'read: unknown page is FALSE');
test_eq(__LINE__, konawiki_getPageId('NoSuchPageXYZ'), FALSE, 'read: unknown page id is FALSE');
test_eq(__LINE__, konawiki_getLogFromId(999999), FALSE, 'read: unknown log id is FALSE');
$info = konawiki_getPageInfoById($page_id);
test_eq(__LINE__, $info['name'], 'kona_test/basic/hoge', 'read: page info by id');
test_eq(__LINE__, konawiki_getPageNameFromId($page_id), 'kona_test/basic/hoge', 'read: page name by id');

// ---------------------------------------------------------------------
// タグ
// ---------------------------------------------------------------------
test_eq(__LINE__, test_write_page('TagTestPage', 'body', 'alpha, beta ,gamma'), TRUE, 'tag: write with tags');
$tag_id = konawiki_getPageId('TagTestPage');
test_eq(__LINE__, konawiki_getTag($tag_id), ['alpha', 'beta', 'gamma'], 'tag: trimmed list');
test_eq(__LINE__, konawiki_getLog('TagTestPage')['tag'], 'alpha,beta,gamma', 'tag: log tag string');
// タグを付け直すと置き換わる
$_GET['page'] = 'TagTestPage';
$err = '';
test_eq(__LINE__, konawiki_writePage('body2', $err, FALSE, 'only'), TRUE, 'tag: rewrite');
test_eq(__LINE__, konawiki_getTag($tag_id), ['only'], 'tag: replaced');
// タグ引数 FALSE の更新ではタグは保持される
$err = '';
test_eq(__LINE__, konawiki_writePage('body3', $err), TRUE, 'tag: rewrite without tag arg');
test_eq(__LINE__, konawiki_getTag($tag_id), ['only'], 'tag: preserved when arg is FALSE');

// ---------------------------------------------------------------------
// 認証ハッシュ (log_auth_hash)
// ---------------------------------------------------------------------
konawiki_setAuthHash($tag_id, 'hash123');
test_eq(__LINE__, konawiki_getAuthHash($tag_id), 'hash123', 'authhash: set and get');
konawiki_setAuthHash($tag_id, 'hash456');
test_eq(__LINE__, konawiki_getAuthHash($tag_id), 'hash456', 'authhash: updated');
test_eq(__LINE__, konawiki_getAuthHash(999999), '', 'authhash: unknown page is empty');
