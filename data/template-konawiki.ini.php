<?php
//----------------------------------------------------------------------
// KonaWiki (User Config File)
// Please rename this file to "data/konawiki.ini.php"
//----------------------------------------------------------------------
// Basic setting
//----------------------------------------------------------------------
$public['title']        = 'KonaWiki2';
$public['author']       = 'kujirahand';
$public['description']  = 'KonaWiki - Wiki Clone Application';
$public['keywords']     = 'konawiki,wiki';

//----------------------------------------------------------------------
// PATH setting
//----------------------------------------------------------------------
$root = dirname(__DIR__);
$private['dir.base'] = $root;
$private['dir.data'] = __DIR__;
$private['dir.engine'] = $root.'/kona-engine';
$private['dir.skin'] = $root.'/skin';
$private['dir.attach'] = $root.'/attach';
$private['dir.cache'] = $root.'/cache';

//----------------------------------------------------------------------
// timezone --- (ex) 'Asia/Kuala_Lumpur' 'Asia/Seou' 'Asia/Taipei'
//----------------------------------------------------------------------
$public['timezone'] = 'Asia/Tokyo'; // (see PHP timezone manual)

//----------------------------------------------------------------------
// Deny to register to earch engine?
//----------------------------------------------------------------------
$public['norobot'] = false; 

//----------------------------------------------------------------------
// password & specifying user privileges
//----------------------------------------------------------------------
// * Master admin password
// (ex) 'test'                                  // plain
// (ex) '{md5}098f6bcd4621d373cade4e832627b4f6' // md5
$private['admin.key']   = 'konawiki';
$private['auth.read.enabled']   = FALSE;
$private['auth.write.enabled']  = TRUE; 

// * login setting user & password
// (ex) $authusers['username1'] = 'password';
// (ex) $authusers['username2'] = '{md5}1a1dc91c907325c69271ddf0c944bc72';
// (ex) $authusers['username3'] = '{sha1}9d4e1e23bd5b727046a9e3b4b7db57bd8d6ee684';
if (isset($authusers['username'])) unset($authusers['username']);
// username & password & privileges
$authusers['username'] = 'password';
$users_perm['username'] = array('read'=>true, 'write'=>true);

//----------------------------------------------------------------------
// options
//----------------------------------------------------------------------
// email setting (some plugin use)
// $private['webmaster.email'] = 'web@kujirahand.com';

//----------------------------------------------------------------------
// PLUGINS
//----------------------------------------------------------------------
// set plugins
$private['plugins.disable'] = array(
  'html' => TRUE,
  'htmlshow' => TRUE
);

//----------------------------------------------------------------------
// SKIN setting 
//----------------------------------------------------------------------
// skin name -- Please check <skin> folder, set folder name
// (ref) http://kujirahand.com/konawiki/go.php?7
$public['skin'] = 'default'; // 'default' or col2,col3,pink...

// logo and favicon name in skin folder
$public['logo']    = 'logo.png';
$public['favicon'] = 'favicon.ico';
$public['ogimage'] = 'logo-large.png'; // for facebook image

//----------------------------------------------------------------------
// Attachment files
//----------------------------------------------------------------------
// Can login users upload attachment files?
$private['attach.enabled'] = 1; 

//----------------------------------------------------------------------
// HTML header & fotter option
//----------------------------------------------------------------------
$private['header.meta'] = <<<EOS
<!-- code here -->
EOS;
$private['header.analytics'] = <<<EOS
<!-- code here -->
EOS;
$private['footer.analytics'] = <<<EOS
<!-- code here -->
EOS;

//----------------------------------------------------------------------
// All Page options
//----------------------------------------------------------------------
$public['noanchor'] = TRUE; // タイトルにアンカーを表示しない
/*
$konawiki['private']['show.plugins']['show.allpage'] = array(
  'enabled'   => TRUE,
  'file'      => 'show.allpage.inc.php',
  'entry'     => 'show_plugin_show_allpage_entry',
  'header.wiki' => '',  // WikiString in all pages
  'footer.wiki' => '#comment',
  'header.html' => '',     
  'footer.html' => '<br/>',
);
*/
/*
$konawiki['private']['show.plugins']['blog'] = array(
  'enabled'   => TRUE,
  'file'      => 'blogtop.inc.php',
  'entry'     => 'show_plugin_blogtop',
  'count'     => 5,    // index page list count
  'pattern'   => '*',  // like "2008/01/01"
  'header.code' => '',
);
*/

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
$private['debug'] = FALSE;
// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
