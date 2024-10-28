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
$scrUri = "{$https}://{$_SERVER['HTTP_HOST']}{$scrdir}";

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


