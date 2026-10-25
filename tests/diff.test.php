<?php
/**
 * 差分・競合チェックの機能テスト (konawiki_diff.inc.php)
 * 編集保存時の競合検出 konawiki_diff_checkConflict() を検証する。
 * 競合がある場合は TRUE を返し、本文に >>>[+] / >>>[-] マークが付く。
 */
require_once __DIR__ . '/test_common.inc.php';

// 同一本文では競合なし・本文は変わらない
$body = "a\nb\nc";
test_eq(__LINE__, konawiki_diff_checkConflict($body, "a\nb\nc"), FALSE, 'diff: identical');
test_eq(__LINE__, $body, "a\nb\nc", 'diff: identical body unchanged');

// 空本文同士も競合なし
$body = '';
test_eq(__LINE__, konawiki_diff_checkConflict($body, ''), FALSE, 'diff: both empty');

// CRLF と LF の違いだけでは競合にならない
$body = "a\r\nb";
test_eq(__LINE__, konawiki_diff_checkConflict($body, "a\nb"), FALSE, 'diff: CRLF normalized');

// 1行だけ差し替えると競合あり、追加/削除マークが付く
$body = "a\nX\nc";
test_eq(__LINE__, konawiki_diff_checkConflict($body, "a\nb\nc"), TRUE, 'diff: replaced line conflicts');
test_contains(__LINE__, $body, '>>>[+] X', 'diff: added mark');
test_contains(__LINE__, $body, '>>>[-] b', 'diff: deleted mark');
test_contains(__LINE__, $body, "a\n", 'diff: common line kept');

// 末尾への行追加
$body = "a\nb";
test_eq(__LINE__, konawiki_diff_checkConflict($body, "a"), TRUE, 'diff: appended line conflicts');
test_contains(__LINE__, $body, '>>>[+] b', 'diff: append mark');

// 末尾の行削除 (現在の本文にだけある行)
$body = "a";
test_eq(__LINE__, konawiki_diff_checkConflict($body, "a\nb"), TRUE, 'diff: removed line conflicts');
test_contains(__LINE__, $body, '>>>[-] b', 'diff: remove mark');

// diffonly モードでは共通行が結果に残らない
$body = "a\nX\nc";
test_eq(__LINE__, konawiki_diff_checkConflict($body, "a\nb\nc", 'diffonly'), TRUE, 'diff: diffonly conflicts');
test_not_contains(__LINE__, $body, "\na\n", 'diff: diffonly drops common lines');
test_contains(__LINE__, $body, '>>>[+] X', 'diff: diffonly keeps marks');

// ---------------------------------------------------------------------
// konawiki_array_search
// ---------------------------------------------------------------------
test_eq(__LINE__, konawiki_array_search('b', ['a', 'b', 'c']), 1, 'array_search: found');
test_eq(__LINE__, konawiki_array_search('a', ['a', 'b', 'c'], 1), FALSE, 'array_search: before offset');
test_eq(__LINE__, konawiki_array_search('c', ['a', 'b', 'c'], 1), 2, 'array_search: from offset');
test_eq(__LINE__, konawiki_array_search('z', ['a', 'b', 'c']), FALSE, 'array_search: not found');
test_eq(__LINE__, konawiki_array_search('1', [1, 2]), FALSE, 'array_search: strict (string vs int)');
test_eq(__LINE__, konawiki_array_search(1, [1, 2]), 0, 'array_search: strict int');

// ---------------------------------------------------------------------
// 既存行の間に行を挿入した場合は追加マーク [+] が付く
// ---------------------------------------------------------------------
$body = "a\nX\nb";
test_eq(__LINE__, konawiki_diff_checkConflict($body, "a\nb"), TRUE, 'diff: middle insert conflicts');
test_contains(__LINE__, $body, '>>>[+] X', 'diff: middle insert has add mark');
test_not_contains(__LINE__, $body, '>>>[-] X', 'diff: middle insert has no sub mark');
$body = "a\nX\nY\nb";
test_eq(__LINE__, konawiki_diff_checkConflict($body, "a\nb"), TRUE, 'diff: middle multi insert conflicts');
test_contains(__LINE__, $body, '>>>[+] X', 'diff: middle multi insert X add mark');
test_contains(__LINE__, $body, '>>>[+] Y', 'diff: middle multi insert Y add mark');
test_not_contains(__LINE__, $body, '>>>[-] ', 'diff: middle multi insert has no sub mark');
