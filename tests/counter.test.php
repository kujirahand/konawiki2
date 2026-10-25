<?php
/**
 * counter プラグインの月別集計テスト
 * 旧 kona-engine/test/kona_test_counter.inc.php から移行 (Issue #57)
 */
require_once __DIR__ . '/test_common.inc.php';

test_init_db();
$log_id = 9999;
db_exec("DELETE FROM mcounter_day WHERE log_id=?", [$log_id], 'sub');

$t_may15 = strtotime("2026-05-15 12:00:00");
$t_may20 = strtotime("2026-05-20 12:00:00");
$t_jun01 = strtotime("2026-06-01 12:00:00");
foreach ([[$t_may15, 10], [$t_may20, 15], [$t_jun01, 5]] as $r) {
    db_exec("INSERT INTO mcounter_day (log_id, stime, value, mtime) VALUES (?, ?, ?, ?)", [$log_id, $r[0], $r[1], time()], 'sub');
}

include_once(KONAWIKI_DIR_PLUGINS . '/counter.inc.php');
$history = plugin_counter_getHistoryData($log_id);
test_eq(__LINE__, count($history), 2, 'counter: month count');
test_eq(__LINE__, $history[0]['month'], '2026-06', 'counter: month_0');
test_eq(__LINE__, $history[0]['count'], 5, 'counter: count_0');
test_eq(__LINE__, $history[1]['month'], '2026-05', 'counter: month_1');
test_eq(__LINE__, $history[1]['count'], 25, 'counter: count_1');

db_exec("DELETE FROM mcounter_day WHERE log_id=?", [$log_id], 'sub');
