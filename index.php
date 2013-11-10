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
$scrUri = "http://{$_SERVER['HTTP_HOST']}{$scrdir}";
// set default setting
$konawiki = array(
  'public'     => array(
    'KONAWIKI_VERSION' => '0.51',
    'KONAWIKI_CONFIG_VER' => 1005,
    'title'       => 'KonaWiki2',
    'author'      => 'KonaWiki User',
    'description' => 'KonaWiki - Wiki Clone Application',
    'keywords'    => 'KonaWiki, Wiki',
    'lang'        => konawiki_getUserLang(), // ja/en 
    'timezone'    => 'Asia/Tokyo',
    'norobot'     => false,
    'FrontPage'   => 'FrontPage',
    'login.link.visible' => true,
    'noanchor'    => true,
    'skin'        => 'default',
    'ogimage'     => 'logo-large.png',
    'wikibody_header' => '',
    'wikibody_footer' => '',
  ),
  'private'    => array(
    'auth.users'      => array(),
    'auth.users.perm' => array(),
    // init default dir & uri 
    // (more) http://konawiki.aoikujira.com/index.php?41&go
    // |+ common wiki
    'dir.engine' => dirname(__FILE__).'/kona-engine',
    'dir.skin'   => dirname(__FILE__).'/skin',
    'uri.skin'   => $scrUri.'/skin',
    // |+ branch wiki
    'dir.base'   => dirname(__FILE__),
    'dir.data'   => dirname(__FILE__).'/data',
    'dir.attach' => dirname(__FILE__).'/attach',
    'uri.base'   => $scrUri,
    'uri.attach' => $scrUri.'/attach',
    //
    'data_format' => 'Y-m-d',
    'time_format' => 'H:i:s',
    'cache.mode'  => 'cache', // cache or nocache
    'auth.read.enabled'   => false,
    'auth.write.enabled'  => true,
    'auth.realm'          => 'KonaWiki Authentication',
    'admin.key'           => 'konawiki',
    'attach.enabled'      => true,
    'login.time.limit'    => 60 * 60 * 6/*hours*/,
  ),
);
$public     = &$konawiki['public'];
$private    = &$konawiki['private'];
$authusers  = &$konawiki['private']['auth.users'];
$users_perm = &$konawiki['private']['auth.users.perm'];
// Database PATH
$data = $private['dir.data']; // PRI SET
$private['db.dsn']              = "pdosqlite://$data/konawiki.db";
$private['subdb.dsn']           = "pdosqlite://$data/konawiki_sub.db";
$private['backupdb.dsn']        = "pdosqlite://$data/konawiki_backup.db";
//--------------------------------------------------------------------
// Markup setting
$private['ul_mark1'] = '・';
$private['ul_mark2'] = '≫';
$private['h1_mark1'] = '■';
$private['h1_mark2'] = '□';
$private['h2_mark1'] = '●';
$private['h2_mark2'] = '○';
$private['h3_mark1'] = '▲';
$private['h3_mark2'] = '△';
$private['h4_mark1'] = '▼';
$private['h4_mark2'] = '▽';
// |- Parser Parameters
$private['header_level_from']   = 1;
$private['source_tag_begin']    = '<pre class="code">';
$private['source_tag_end']      = '</pre>';
$private['source_tag_hr']       = "<p class='clear'/><div class='underline'>&nbsp;</div>\n";
$private['entry_begin']         = '<div class="entry">';
$private['entry_end']           = '</div><!-- end of entry -->';
$private['session.name']        = 'kona2';
$private['footer.analytics']    = '';
//
$private['plugins.disable'] = array();
$private['show.plugins']    = array();
//--------------------------------------------------------------------
// include user config file
$rootDir = dirname(__FILE__);
$ini = $rootDir.'/konawiki.ini.php';
if (file_exists($ini)) { // user konawiki.ini.php
  include_once $ini;
}
else { // debug mode
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
include_once $engineDir.'/lib/lib_kona.inc.php';
// initialize 
konawiki_init();
//--------------------------------------------------------------------
// function
function konawiki_getUserLang() {
  $lang = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
  return $lang[0];
}





