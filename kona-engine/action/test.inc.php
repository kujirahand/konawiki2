<?php
/**
 * ページの表示アクション
 */
global $kona_test;
$kona_test = [
  'count' => 0,
  'ok' => 0,
  'errors' => [],
  'cur_file' => '?'
];

function action_test_() {
  if (!konawiki_is_debug()) {
    echo 'Please set debug setting ... $private["debug"] = TRUE; ';
    exit;
  }
  $page = konawiki_getPage();
  if ($page == 'all' || $page == '*') {
    $test_dir = konawiki_private('dir.engine', '..')."/test";
    $files = glob($test_dir.'/kona_test_*.inc.php');
    foreach ($files as $f) {
      $bf = basename($f);
      if (preg_match('#^kona_test_([a-z0-9\_]+).inc.php$#', $bf, $m)) {
        test_go($m[1]);
      }
    }
    test_result();
    return;
  } else {
    test_go($page);
    test_result();
  }
}

function test_go($page) {
  echo "<hr><pre>";
  global $kona_test;
  $test_dir = konawiki_private('dir.engine', '..')."/test";
  if (!preg_match('#^[a-z0-9\_]+$#', $page)) {
    echo "invalid test name ...";
    exit;
  }
  $fname = "kona_test_$page.inc.php";
  include_once $test_dir.'/'.$fname;
  $func_name = "kona_test_{$page}";
  if (!function_exists($func_name)) {
    echo "Invalid Test. Please define function $func_name.";
    exit;
  }
  // call test func
  $kona_test['cur_file'] = $fname;
  echo "[TEST] $fname:\n";
  call_user_func($func_name);
}

function test_eq($value, $expect, $name = '?') {
  global $kona_test;
  $kona_test['count']++;
  if ($value === $expect) {
    $kona_test['ok']++;
    echo " <span style='color:green'>- OK: $name</span>\n";
    return TRUE;
  } else {
    $kona_test['errors'][] = [
      'file' => $kona_test['cur_file'],
      'name' => $name,
      'value' => $value,
      'expect' => $expect,
    ];
    echo " <span style='color:red'>- <b>NG</b>: $name</span>\n";
    return FALSE;
  }
}

function test_result() {
  global $kona_test;
  $ok = $kona_test['ok'];
  $count = $kona_test['count'];
  if ($ok == $count) {
    $style = 'color:green;';
  } else {
    $style = 'color:red;';
  }
  echo "<h1 style='$style'>Test result</h1>";
  echo "<h2 style='$style'>result=$ok/$count</h2>";
  if ($kona_test['errors']) {
    print_r($kona_test['errors']);
  }
}

