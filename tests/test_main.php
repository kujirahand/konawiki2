<?php
/**
 * KonaWiki2 機能テストの実行ランナー (Issue #34)
 * 実行方法: ./tests/test.sh  または  php tests/test_main.php go_test
 */
define('KONA2_TEST_RUNNER', TRUE);
require_once __DIR__ . '/test_common.inc.php';
global $KONA2_TEST_FILE;

echo "+==============================================\n";
echo "| KONAWIKI2 TEST\n";
if (($argc <= 1) || ($argv && $argv[1] !== 'go_test')) {
    echo "誤動作を防ぐため、`./tests/test.sh` を実行してください。\n";
    exit(1);
}

// *.test.php を全て実行する
$files = glob(__DIR__ . '/*.test.php');
sort($files);
foreach ($files as $no => $file) {
    $no_ = str_pad($no, 2, '0', STR_PAD_LEFT);
    echo "+----------------------------------------------\n";
    $KONA2_TEST_FILE = basename($file);
    $tmp_ok = $KONA2_TEST_OK;
    $tmp_ng = $KONA2_TEST_NG;
    include $file;
    $cnt_ok = $KONA2_TEST_OK - $tmp_ok;
    $cnt_ng = $KONA2_TEST_NG - $tmp_ng;
    $cnt_total = $cnt_ok + $cnt_ng;
    $cnt_ok_ = str_pad($cnt_ok, 3, '0', STR_PAD_LEFT);
    $cnt_total_ = str_pad($cnt_total, 3, '0', STR_PAD_LEFT);
    if ($cnt_ng > 0) {
        echo "| NG: $cnt_ok_/$cnt_total_ | $KONA2_TEST_FILE\n";
    } else {
        echo "| OK: $cnt_ok_/$cnt_total_ | $KONA2_TEST_FILE\n";
    }
}

// 結果を表示する
$ok_ = str_pad($KONA2_TEST_OK, 3, '0', STR_PAD_LEFT);
$total_ = str_pad($KONA2_TEST_OK + $KONA2_TEST_NG, 3, '0', STR_PAD_LEFT);
echo "+==============================================\n";
echo "| [TEST RESULT] {$ok_}/{$total_}\n";
if ($KONA2_TEST_NG > 0) {
    echo "+ ERRORS:\n";
    foreach ($KONA2_TEST_ERRORS as $err) {
        print_r($err);
    }
    exit(1);
} else {
    echo "| Success!\n";
    exit(0);
}
