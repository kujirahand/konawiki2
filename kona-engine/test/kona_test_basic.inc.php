<?php
function kona_test_basic() {
  // basic test
  $page = konawiki_getPage();
  test_eq($page, $_GET['page'], 'pagename');
  
  // other test
  kona_test_basic_write();
}

function kona_test_basic_write() {
  $_GET['page'] = 'kona_test/basic/hoge';
  // (1) insert
  $r = konawiki_writePage("hoge", $err);
  if (!$r) { echo " - reason=$err\n"; }
  $log = konawiki_getLog();
  $r = test_eq($log['body'], 'hoge', 
    'konawiki_writePage:insert');
  
  // (2) update
  $r = konawiki_writePage("fuga", $err);
  if (!$r) { echo " - reason=$err\n"; }
  $log = konawiki_getLog();
  $r = test_eq($log['body'], 'fuga', 
    'konawiki_writePage:update');
}

