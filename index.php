<?php
/**
 * KonaWiki -- index.php (Main File)
 * @author kujirahand (http://kujirahand.com)
 *
 */
//--------------------------------------------------------------------
// set encoding
mb_internal_encoding("UTF-8");
header("Content-Type: text/html; charset=UTF-8");
//--------------------------------------------------------------------
// define blank config
global $konawiki, $public, $private, $authusers, $usrs_perm;
$scrdir = dirname($_SERVER['SCRIPT_NAME']);
if ($scrdir == "/") $scrdir = "";
$konawiki = array(
  'public'     => array(),
  'private'    => array(
    'auth.users'      => array(),
    'auth.users.perm' => array(),
    // init default dir & uri 
    // |+ common wiki
    'dir.engine' => dirname(__FILE__).'/kona-engine',
    'dir.skin'   => dirname(__FILE__).'/skin',
    'uri.skin'   => $scrdir.'/skin',
    // |+ branch wiki
    'dir.base'   => dirname(__FILE__),
    'dir.data'   => dirname(__FILE__).'/data',
    'dir.attach' => dirname(__FILE__).'/attach',
    'uri.base'   => $scrdir,
    'uri.attach' => $scrdir.'/attach',
  ),
);
$public     = &$konawiki['public'];
$private    = &$konawiki['private'];
$authusers  = &$konawiki['private']['auth.users'];
$users_perm = &$konawiki['private']['auth.users.perm'];
//--------------------------------------------------------------------
// include user config file
$rootDir = dirname(__FILE__);
$ini = $rootDir.'/konawiki.ini.php';
if (file_exists($ini)) { // user konawiki.ini.php
  include_once $ini;
}
else { // debug mode
  $ini = $rootDir.'/temp-konawiki.ini.php';
  if (!file_exists($ini)) {
    echo 'Not Found Config file: "konawiki.ini.php"';
    exit;
  }
  include_once $ini;
  $private['debug'] = true;
}
//--------------------------------------------------------------------
// include library
$engineDir = $private['dir.engine'];
if (!file_exists($engineDir)) {
  echo "Not Found system files : kona-engine dir<br>\n";
  echo "Please edit Config file : konawiki.ini.php\n";
  exit;
}
include_once $engineDir.'/lib/konawiki_lib.inc.php';
// initialize 
konawiki_init();
//--------------------------------------------------------------------





