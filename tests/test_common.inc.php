<?php
/**
 * KonaWiki2 機能テスト共通基盤 (Issue #34)
 *
 * 使い方: 各 *.test.php の先頭で require_once する。
 *   require_once __DIR__ . '/test_common.inc.php';
 *   test_eq(__LINE__, 1 + 1, 2, 'example');
 *
 * 方針:
 * - Webサーバや実データには触れず、CLI でそのまま実行できるようにする。
 * - DB やファイル書き込みを伴うテストは、プロセス毎に作る一時
 *   ディレクトリ (サンドボックス) の中でだけ行い、終了時に削除する。
 * - 本体 (index.php) はルーティングやセッション開始まで行うため読み込まず、
 *   ここでは index.php と同じ初期値で $public/$private を用意してから
 *   ライブラリだけを読み込む。
 */
if (defined('KONA2_TEST_COMMON')) {
    return;
}
define('KONA2_TEST_COMMON', TRUE);

// 警告や Deprecated も隠さず表示する (本体側の互換性問題に気付けるように)
// ※新規ページ作成時の Deprecated (lib_kona.inc.php の insert 分岐) は
//   Issue #35 で追跡中の既知の問題で、修正されるまではテスト実行時に
//   その警告が表示されるが、テスト自体は合格する。
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tokyo');

// ---------------------------------------------------------------------
// konawiki_checkOptions() などが参照する $_SERVER の最小セット
// ---------------------------------------------------------------------
if (empty($_SERVER['HTTP_HOST']))    $_SERVER['HTTP_HOST'] = 'localhost';
if (empty($_SERVER['SCRIPT_NAME']))  $_SERVER['SCRIPT_NAME'] = '/index.php';
if (empty($_SERVER['REQUEST_URI']))  $_SERVER['REQUEST_URI'] = '/index.php';
if (empty($_SERVER['SERVER_NAME']))  $_SERVER['SERVER_NAME'] = 'localhost';
if (empty($_SERVER['REQUEST_SCHEME'])) $_SERVER['REQUEST_SCHEME'] = 'http';

// ---------------------------------------------------------------------
// グローバル変数の初期化 (index.php と同じ形)
// ---------------------------------------------------------------------
global $konawiki, $public, $private;
$KONA2_TEST_ROOT = dirname(__DIR__);
$KONA2_TEST_ENGINE = $KONA2_TEST_ROOT . '/kona-engine';
$konawiki = [
    'public' => [],
    'private' => [
        'dir.engine' => $KONA2_TEST_ENGINE,
        'authusers' => [],
        'users_perm' => [],
    ],
];
$public  = &$konawiki['public'];
$private = &$konawiki['private'];
$private['plugins.disable'] = ['html' => TRUE, 'htmlshow' => TRUE];

require_once $KONA2_TEST_ENGINE . '/lib/lib_kona.inc.php';
konawiki_checkOptions();

// ---------------------------------------------------------------------
// サンドボックス (テスト用の一時ディレクトリ)
// ---------------------------------------------------------------------
$KONA2_TEST_SANDBOX = sys_get_temp_dir() . '/konawiki2_test_' . getmypid() . '_' . bin2hex(random_bytes(4));
foreach (['data', 'attach', 'cache'] as $d) {
    if (!is_dir("$KONA2_TEST_SANDBOX/$d")) {
        mkdir("$KONA2_TEST_SANDBOX/$d", 0777, TRUE);
    }
}
$private['dir.data']   = "$KONA2_TEST_SANDBOX/data";
$private['dir.attach'] = "$KONA2_TEST_SANDBOX/attach";
$private['dir.cache']  = "$KONA2_TEST_SANDBOX/cache";
$private['dir.engine'] = $KONA2_TEST_ENGINE;
// テンプレートエンジンのキャッシュもサンドボックス内に出力する
// (本体の konawiki_init() と同じ向きにしておき、実cache/ を汚さない)
global $DIR_TEMPLATE, $DIR_TEMPLATE_CACHE, $FW_TEMPLATE_PARAMS;
$DIR_TEMPLATE = $KONA2_TEST_ENGINE . '/template';
$DIR_TEMPLATE_CACHE = "$KONA2_TEST_SANDBOX/cache";
$FW_TEMPLATE_PARAMS = [];

$private['db.dsn']        = "sqlite://$KONA2_TEST_SANDBOX/data/konawiki.db";
$private['subdb.dsn']     = "sqlite://$KONA2_TEST_SANDBOX/data/konawiki_sub.db";
$private['backupdb.dsn']  = "sqlite://$KONA2_TEST_SANDBOX/data/konawiki_backup.db";
$private['users_db.dsn']  = "sqlite://$KONA2_TEST_SANDBOX/data/konawiki_users.db";
$public['baseurl'] = 'http://localhost/index.php';

// 終了時にサンドボックスを削除する
register_shutdown_function(function () use ($KONA2_TEST_SANDBOX) {
    if (!is_dir($KONA2_TEST_SANDBOX)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($KONA2_TEST_SANDBOX, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        if ($f->isDir() && !$f->isLink()) { @rmdir($f->getPathname()); }
        else { @unlink($f->getPathname()); }
    }
    @rmdir($KONA2_TEST_SANDBOX);
});

// 本体ライブラリが参照する定数 (通常は konawiki_init() が定義する)
if (!defined('KONAWIKI_DIR_LIB'))     define('KONAWIKI_DIR_LIB', $KONA2_TEST_ENGINE . '/lib');
if (!defined('KONAWIKI_DIR_ENGINE'))  define('KONAWIKI_DIR_ENGINE', $KONA2_TEST_ENGINE);
if (!defined('KONAWIKI_DIR_ACTION'))  define('KONAWIKI_DIR_ACTION', $KONA2_TEST_ENGINE . '/action');
if (!defined('KONAWIKI_DIR_TEMPLATE')) define('KONAWIKI_DIR_TEMPLATE', $KONA2_TEST_ENGINE . '/template');
if (!defined('KONAWIKI_DIR_DEF_RES')) define('KONAWIKI_DIR_DEF_RES', $KONA2_TEST_ENGINE . '/resource');
if (!defined('KONAWIKI_DIR_PLUGINS')) define('KONAWIKI_DIR_PLUGINS', $KONA2_TEST_ENGINE . '/plugins');
if (!defined('KONAWIKI_DIR_HELP'))    define('KONAWIKI_DIR_HELP', $KONA2_TEST_ENGINE . '/help');
if (!defined('KONAWIKI_DIR_FW_SIMPLE')) define('KONAWIKI_DIR_FW_SIMPLE', $KONA2_TEST_ENGINE . '/fw_simple');
if (!defined('KONAWIKI_DIR_DATA'))    define('KONAWIKI_DIR_DATA', "$KONA2_TEST_SANDBOX/data");
if (!defined('KONAWIKI_DIR_ATTACH'))  define('KONAWIKI_DIR_ATTACH', "$KONA2_TEST_SANDBOX/attach");
if (!defined('KONAWIKI_DIR_BASE'))    define('KONAWIKI_DIR_BASE', $KONA2_TEST_ROOT);
if (!defined('KONAWIKI_DIR_SKIN'))    define('KONAWIKI_DIR_SKIN', $private['dir.skin']);
if (!defined('KONAWIKI_URI_SKIN'))    define('KONAWIKI_URI_SKIN', $private['uri.skin']);
if (!defined('KONAWIKI_URI_ATTACH'))  define('KONAWIKI_URI_ATTACH', $private['uri.attach']);

// ---------------------------------------------------------------------
// テストで使うライブラリを読み込む
// ---------------------------------------------------------------------
require_once $KONA2_TEST_ENGINE . '/lib/html.inc.php';
require_once $KONA2_TEST_ENGINE . '/lib/konawiki_parser.inc.php';
require_once $KONA2_TEST_ENGINE . '/lib/konawiki_diff.inc.php';
require_once $KONA2_TEST_ENGINE . '/lib/mime.inc.php';
require_once $KONA2_TEST_ENGINE . '/lib/useragent.inc.php';
require_once $KONA2_TEST_ENGINE . '/fw_simple/fw_database.lib.php';
require_once $KONA2_TEST_ENGINE . '/lib/konawiki_db.inc.php';
require_once $KONA2_TEST_ENGINE . '/lib/konawiki_auth.inc.php';
require_once $KONA2_TEST_ENGINE . '/fw_simple/fw_etc.lib.php';
require_once $KONA2_TEST_ENGINE . '/fw_simple/fw_rooting.lib.php';

// ---------------------------------------------------------------------
// テスト用ヘルパー (konawiki3 の tests と同じ流儀)
// ---------------------------------------------------------------------
global $KONA2_TEST_OK, $KONA2_TEST_NG, $KONA2_TEST_ERRORS, $KONA2_TEST_FILE;
$KONA2_TEST_OK = 0;
$KONA2_TEST_NG = 0;
$KONA2_TEST_ERRORS = [];
$KONA2_TEST_FILE = '';

// 配列なども警告なしで文字列化する (TRUE/FALSE/null も区別できる形にする)
function test_str($v)
{
    if (is_bool($v) || $v === null) {
        return var_export($v, TRUE);
    }
    if (is_scalar($v)) {
        return (string)$v;
    }
    $json = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return ($json === false) ? '[' . gettype($v) . ']' : $json;
}

function test_assert($line, $b, $desc = 'assert')
{
    global $KONA2_TEST_OK, $KONA2_TEST_NG, $KONA2_TEST_FILE, $KONA2_TEST_ERRORS;
    if (!$b) {
        $KONA2_TEST_NG++;
        $file = $KONA2_TEST_FILE;
        if ($file === '') {
            // 単体実行時は呼び出し元のファイル名を使う
            // (test_eq 等の経由でも直接呼びでも、このファイル以外で最初のフレームを探す)
            $file = '?';
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $fr) {
                if (isset($fr['file']) && $fr['file'] !== __FILE__) {
                    $file = basename($fr['file']);
                    break;
                }
            }
        }
        $KONA2_TEST_ERRORS[] = [
            'file' => $file,
            'line' => $line,
            'error' => $desc,
        ];
        return FALSE;
    }
    $KONA2_TEST_OK++;
    return TRUE;
}

// *.test.php を単体実行した場合も結果を表示し、失敗時は終了コード 1 にする
// (test_main.php 経由の実行時は test_main.php 側が結果表示を担う)
if (!defined('KONA2_TEST_RUNNER')) {
    register_shutdown_function(function () {
        global $KONA2_TEST_OK, $KONA2_TEST_NG, $KONA2_TEST_ERRORS;
        $total = $KONA2_TEST_OK + $KONA2_TEST_NG;
        echo "[TEST RESULT] {$KONA2_TEST_OK}/{$total}\n";
        if ($KONA2_TEST_NG > 0) {
            foreach ($KONA2_TEST_ERRORS as $err) {
                print_r($err);
            }
            exit(1);
        }
    });
}

function test_eq($line, $a, $b, $desc = 'eq')
{
    return test_assert($line, $a === $b,
        $desc . ': `' . test_str($a) . '` === `' . test_str($b) . '`');
}

function test_ne($line, $a, $b, $desc = 'ne')
{
    return test_assert($line, $a !== $b,
        $desc . ': `' . test_str($a) . '` !== `' . test_str($b) . '`');
}

function test_contains($line, $haystack, $needle, $desc = 'contains')
{
    return test_assert($line,
        is_string($haystack) && strpos($haystack, $needle) !== FALSE,
        $desc . ': `' . test_str($haystack) . '` contains `' . test_str($needle) . '`');
}

function test_not_contains($line, $haystack, $needle, $desc = 'not_contains')
{
    return test_assert($line,
        is_string($haystack) && strpos($haystack, $needle) === FALSE,
        $desc . ': `' . test_str($haystack) . '` must not contain `' . test_str($needle) . '`');
}

// テスト用 DB を初期化する (複数ファイルから呼んでも1回だけ実行)
function test_init_db()
{
    global $KONA2_TEST_DB_READY;
    if (!empty($KONA2_TEST_DB_READY)) return;
    konawiki_initDB();
    // database_get() を呼んで実際にファイルとテーブルを作る
    konawiki_getDB();
    database_get('sub');
    database_get('backup');
    database_get('users');
    $KONA2_TEST_DB_READY = TRUE;
}

// テスト用にページを1つ書く (表示中のページをそのページに切り替える)
function test_write_page($page, $body, $tag = FALSE)
{
    test_init_db();
    $_GET['page'] = $page;
    $_POST['page'] = $page;
    $err = '';
    $r = konawiki_writePage($body, $err, FALSE, $tag);
    // 戻り値を無視する呼び出しでも失敗を見逃さないよう失敗数に数える
    test_assert(__LINE__, $r, "test_write_page($page) failed: $err");
    return $r;
}
