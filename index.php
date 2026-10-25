<?php
/**
 * KonaWiki2 -- index.php (Main File)
 * @author kujirahand (https://kujirahand.com)
 */

//--------------------------------------------------------------------
// set encoding
//--------------------------------------------------------------------
mb_internal_encoding("UTF-8");
header("Content-Type: text/html; charset=UTF-8");

//--------------------------------------------------------------------
// define blank config
//--------------------------------------------------------------------
global $konawiki, $public, $private;
$scrdir = dirname($_SERVER['SCRIPT_NAME']);
if ($scrdir == "/") $scrdir = "";
$https = empty($_SERVER["HTTPS"]) ? "http" : "https";
// Host ヘッダは検証してから URL 組み立てに使う (#32)
$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
if (!preg_match('/\A(?:\[[0-9A-Fa-f:.]+\]|[A-Za-z0-9_.\-]+)(?::[0-9]{1,5})?\z/', $httpHost)) {
  $httpHost = 'localhost';
}
$scrUri = "{$https}://{$httpHost}{$scrdir}";

// set default setting
$konawiki = [
    'public' => [],
    'private' => [
        'dir.engine' => __DIR__.'/kona-engine',
        'authusers' => [],
        'users_perm' => [],
    ]
];
$public     = &$konawiki['public'];
$private    = &$konawiki['private'];
$authusers  = &$private['authusers'];
$users_perm = &$private['users_perm'];

// plugins
$private['plugins.disable'] = array(
    'html' => TRUE,
    'htmlshow' => TRUE,
);

//--------------------------------------------------------------------
// include user config file
//--------------------------------------------------------------------
$rootDir = dirname(__FILE__);
$ini = $rootDir.'/data/konawiki.ini.php';
if (file_exists($ini)) {
  include_once $ini;
} else {
  // check old path
  $ini2 = $rootDir.'/konawiki.ini.php';
  if (file_exists($ini2)) { // user konawiki.ini.php
    include_once $ini2;
  } else {
    // There is currently no configuration file, so copy the template.
    $conf = file_get_contents($rootDir.'/data/template-konawiki.ini.php');
    file_put_contents($ini, $conf);
    require_once $ini;
  }
}
//--------------------------------------------------------------------
// check initial setup (#32)
// 管理キーとログインユーザーがどちらも未設定の場合、既定資格情報の
// まま運用されるのを防ぐため、セットアップ案内を表示して終了する。
// (書き込み認証を無効にしている場合は対象外)
//--------------------------------------------------------------------
$adminKey = isset($private['admin.key']) ? $private['admin.key'] : '';
$writeAuthEnabled = !isset($private['auth.write.enabled'])
  || $private['auth.write.enabled'] != FALSE;
if (($adminKey === '' || $adminKey === 'konawiki')
    && empty($authusers) && $writeAuthEnabled) {
  header("Content-Type: text/html; charset=UTF-8");
  echo "<h1>KonaWiki2 セットアップが必要です / Setup required</h1>\n";
  echo "<p>設定ファイル <code>data/konawiki.ini.php</code> で、"
    . "<code>\$private['admin.key']</code> (管理キー) と "
    . "<code>\$authusers</code> (ログインユーザー) を設定してください。</p>\n";
  echo "<p>Please set <code>\$private['admin.key']</code> and "
    . "<code>\$authusers</code> in <code>data/konawiki.ini.php</code>. "
    . "パスワードは <code>{password_hash}</code> 形式 (password_hash) の利用を推奨します。</p>\n";
  exit;
}
//--------------------------------------------------------------------
// check template engine
//--------------------------------------------------------------------
$engineDir = $private['dir.engine'];
if (!file_exists($engineDir.'/fw_simple/fw_template_engine.lib.php')) {
  echo "<h1>Not Found template engine : <a href='./kona-engine/setup-template.php'>Click here to install.</a></h1>\n";
  exit;
}
//--------------------------------------------------------------------
// include library
//--------------------------------------------------------------------
$engineDir = $private['dir.engine'];
if (!file_exists($engineDir)) {
  echo "Not Found system files : kona-engine dir<br>\n";
  echo "Please edit Config file : konawiki.ini.php\n";
  exit;
}
// include main library
include_once $engineDir.'/lib/lib_kona.inc.php';
// initialize
konawiki_init();


