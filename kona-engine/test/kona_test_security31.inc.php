<?php
/**
 * セキュリティ監査 (konawiki2) の回帰テスト
 * 実行方法: 設定で $private["debug"] = TRUE; にした上で
 *           index.php?security31&test にアクセスする
 */
function kona_test_security31() {
  global $private;

  // ---------------------------------------------------------------
  // (1) パスワード照合: 正誤判定と、緩い比較で通ってしまう入力の拒否
  // ---------------------------------------------------------------
  $saved_users = isset($private['authusers']) ? $private['authusers'] : null;
  $private['authusers'] = [
    'sectest' => '{md5}' . md5('s3cret'),
    'sectest_raw' => 'plainpass',
  ];
  test_eq(_konawiki_auth_check_password('sectest', 's3cret'), TRUE,
    'auth: md5 correct password');
  test_eq(_konawiki_auth_check_password('sectest', 'wrong'), FALSE,
    'auth: md5 wrong password');
  // md5 の "0e..." 同士の緩い比較 (magic hash) で通らないこと
  // 保存値 md5('QNKCDZO') に対し、別パスワード '240610708' の md5 も
  // 0e... 形式だが文字列としては異なるため、一致してはいけない
  $private['authusers']['magic'] = '{md5}0e830400451993494058024219903391';
  test_eq(_konawiki_auth_check_password('magic', '240610708'), FALSE,
    'auth: md5 magic hash must not match');
  test_eq(_konawiki_auth_check_password('sectest_raw', 'plainpass'), TRUE,
    'auth: raw correct password');
  test_eq(_konawiki_auth_check_password('sectest_raw', '0'), FALSE,
    'auth: raw wrong password');
  if ($saved_users === null) {
    unset($private['authusers']);
  } else {
    $private['authusers'] = $saved_users;
  }

  // ---------------------------------------------------------------
  // (2) Wikiリンク: URLリンクのキャプションがエスケープされること
  // ---------------------------------------------------------------
  $html = konawiki_parser_makeWikiLink('<b>bold</b>:https://example.com/');
  test_eq(strpos($html, '<b>bold</b>') === FALSE, TRUE,
    'wikilink: url caption is escaped');
  test_eq(strpos($html, '&lt;b&gt;') !== FALSE, TRUE,
    'wikilink: url caption escaped entity present');

  // ---------------------------------------------------------------
  // (3) color プラグイン: 不正な色指定が style に注入されないこと
  // ---------------------------------------------------------------
  include_once(KONAWIKI_DIR_PLUGINS . '/color.inc.php');
  $html = plugin_color_convert(['red;}</style><script>alert(1)</script>', 'text']);
  test_eq(strpos($html, '<script>') === FALSE, TRUE,
    'color: script is not injected');
  test_eq(strpos($html, 'color:inherit;') !== FALSE, TRUE,
    'color: invalid color falls back to inherit');
  $html = plugin_color_convert(['#ff0000', 'text']);
  test_eq(strpos($html, 'color:#ff0000;') !== FALSE, TRUE,
    'color: valid hex color passes');

  // ---------------------------------------------------------------
  // (4) textfile プラグイン: 設定ファイルを読めないこと
  // ---------------------------------------------------------------
  include_once(KONAWIKI_DIR_PLUGINS . '/textfile.inc.php');
  $out = plugin_textfile_convert(['konawiki.ini.php']);
  test_eq(strpos($out, 'admin.key') === FALSE, TRUE,
    'textfile: konawiki.ini.php is not readable');
  $out = plugin_textfile_convert(['../konawiki.ini.php']);
  test_eq(strpos($out, 'admin.key') === FALSE, TRUE,
    'textfile: traversal to ini is not readable');

  // ---------------------------------------------------------------
  // (5) vote プラグイン: 選択肢がエスケープされること (初期選択肢)
  // ---------------------------------------------------------------
  include_once(KONAWIKI_DIR_PLUGINS . '/vote.inc.php');
  $html = plugin_vote_convert(['sectest-vote', '<b>sel</b>']);
  test_eq(strpos($html, '<td><b>sel</b></td>') === FALSE, TRUE,
    'vote: selection is escaped');

  // ---------------------------------------------------------------
  // (6) 認証ハッシュ照合の前提: 空ハッシュは保存されていないこと
  //     (未発行ページでは getAuthHash が空文字を返す仕様の確認)
  // ---------------------------------------------------------------
  test_eq(konawiki_getAuthHash(999999) === '', TRUE,
    'auth hash: unknown page has empty hash');
}
