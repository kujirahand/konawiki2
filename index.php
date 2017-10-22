<?php
/**
 * KonaWiki -- index.php (Main File)
 * @author kujirahand (http://kujirahand.com)
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
$protocol   = empty($_SERVER["HTTPS"]) ? "http://" : "https://";
$scrUri = "{$protocol}{$_SERVER['HTTP_HOST']}{$scrdir}";
// set default setting
$konawiki = array(
  'public'     => array(
    'KONAWIKI_VERSION' => '*', //kona-engine/lib/lib_kona.inc.php
    'KONAWIKI_CONFIG_VER' => 1006,
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
    'ogimage'     => 'logo-large.png', //default value for 'og:image'
    'og:type'     => 'website',
  ),
  'private'    => array(
    'auth.users'      => array(),
    'auth.users.perm' => array(),
    // init default dir & uri
    // (more) http://kujirahand.com/konawiki/index.php?12&go
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
    'data_format'         => 'Y-m-d',
    'date_format_easy'    => 'm/d',
    'time_format'         => 'H:i:s',
    'cache.mode'          => 'cache', // cache or nocache
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
$private['entry_begin']         = '<article class="entry">';
$private['entry_end']           = '</article><!-- end of entry -->';
$private['session.name']        = 'kona2';
$private['footer.analytics']    = '';
$private['para_enabled_br']     = true; // 段落内で改行が有効か
//
$private['plugins.disable'] = array();
$private['show.plugins']    = array();
//--------------------------------------------------------------------
// include user config file
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
    $conf = file_get_contents($rootDir.'/temp-konawiki.ini.php');
    file_put_contents($ini, $conf);
    require_once $ini;
  }
}
//--------------------------------------------------------------------
// include library
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
//--------------------------------------------------------------------
// function
function konawiki_getUserLang() {
  $accept = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
    ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'en';
  $lang = explode(',', $accept);
  $lang = preg_replace('#^(\w+)\-.*$#', '\1', $lang);
  return $lang[0];
}
