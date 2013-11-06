<?php
//----------------------------------------------------------------------
// KonaWiki (User Config File)
// Please rename this file to "konawiki.ini.php"
//----------------------------------------------------------------------
// PATH setting
//----------------------------------------------------------------------
// If use Multi-Install, please write pathinfo in here.
// moreinfo ---> http://konawiki.aoikujira.com/index.php?41&go
// (ex)
/*
$truncDir = dirname(dirname(__FILE__)).'/konawiki';
$truncUri = dirname(dirname($_SERVER['SCRIPT_NAME'])).'/konawiki';
if (substr($truncUri, 0, 2) == "//") $truncUri = substr($truncUri,1);
$private['dir.engine'] = $truncDir.'/kona-engine';
$private['dir.skin']   = $truncDir.'/skin';
$private['uri.skin']   = $truncUri.'/skin';
*/
//----------------------------------------------------------------------
// INCLUDE DEFAULT SETTING
include_once($private['dir.engine'].'/lib/default.ini.php');
//----------------------------------------------------------------------
// Basic setting
//----------------------------------------------------------------------
$public['title']        = 'KonaWiki2';
$public['author']       = 'kujirahand';
$public['description']  = 'KonaWiki - Wiki Clone Application';
$public['keywords']     = 'konawiki,wiki';

// language --- (ex) ja:Japanese, en:English
$lang = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']); 
$public['lang'] = $lang[0];
// timezone --- (ex) 'Asia/Kuala_Lumpur' 'Asia/Seou' 'Asia/Taipei'
$public['timezone'] = 'Asia/Tokyo'; // (see PHP timezone manual)

// Deny to register to earch engine?
$public['norobot'] = false; 

// * Master admin password
// (ex) 'test'                                  // plain
// (ex) '{md5}098f6bcd4621d373cade4e832627b4f6' // md5
$private['admin.key']   = 'konawiki';

// * login setting user & password
$private['auth.read.enabled']   = FALSE; 
$private['auth.write.enabled']  = TRUE; 
// (ex) $authusers['username1'] = '{md5}1a1dc91c907325c69271ddf0c944bc72';
// (ex) $authusers['username1'] = '{sha1}9d4e1e23bd5b727046a9e3b4b7db57bd8d6ee684';
$authusers['username'] = 'password';
$users_perm['username'] = array('read'=>true, 'write'=>true);

//----------------------------------------------------------------------
// SKIN setting 
//----------------------------------------------------------------------
// skin name -- Please check <skin> folder, write folder name
$public['skin'] = 'col2'; // 'default';
// logo and favicon
$public['logo']    = 'logo.png';
$public['favicon'] = 'favicon.ico';
$public['ogimage'] = 'logo-large.png';

//----------------------------------------------------------------------
// Attachment file
//----------------------------------------------------------------------
// Can login users upload attachment files?
$private['attach.enabled'] = 1; 

//----------------------------------------------------------------------
// Footer option
//----------------------------------------------------------------------
$private['footer.analytics'] = <<<EOS
<!-- analytics code here -->
EOS;

//----------------------------------------------------------------------
// Page options
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


