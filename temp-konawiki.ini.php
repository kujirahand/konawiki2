<?php
/**
 * KonaWiki - User Config File
 * Please rename this file to "konawiki.ini.php"
 */
// ---------------------------------------------------------------------
// Basic setting
// ---------------------------------------------------------------------
$public['title']        = 'KonaWiki2';
$public['author']       = 'kujirahand';
$public['description']  = 'KonaWiki - Wiki Clone Application';
// lang = ja:Japanese, en:English
$public['lang']         = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0]; 
$public['timezone']     = 'Asia/Tokyo'; // see PHP timezone 
//  --- (ex) 'Asia/Kuala_Lumpur' 'Asia/Seou' 'Asia/Taipei' 

// Allow to register to earch engine?
// ja: 以下を true にすると検索エンジンに登録されなくなります
$public['norobot']      = false; 

// Master admin password
// plane text) 'test' / md5 text '{md5}098f6bcd4621d373cade4e832627b4f6'
$private['admin.key']   = 'konawiki';

// ---------------------------------------------------------------------
// SKIN setting 
// ---------------------------------------------------------------------
// skin name -- Please check <skin> folder, write folder name
// ja: <skin> フォルダの中を見て、スキン名(フォルダ名)を指定
$public['skin'] = 'default';

// ja: 非推奨ですが、tdiaryのテーマを使う場合、以下の二行を有効にします
// ja: $public['skin']         = 'tdiary';   // tdiary を指定
// ja: $public['skin.theme']   = 'noto';     // skinフォルダにテーマをコピー

// logo and favicon
$public['logo']         = 'logo.png';
$public['favicon']      = 'favicon.ico';

// ---------------------------------------------------------------------
// PATH setting 
// ---------------------------------------------------------------------
/*
// For Multi-install
// moreinfo ---> http://konawiki.aoikujira.com/index.php?41&go
// (example)
$truncDir = dirname(dirname(__FILE__)).'/konawiki';
$truncUri = dirname(dirname($_SERVER['SCRIPT_NAME'])).'/konawiki';
if (substr($truncUri, 0, 2) == "//") $truncUri = substr($truncUri,1);
$private['dir.engine'] = $truncDir.'/kona-engine';
$private['dir.skin']   = $truncDir.'/skin';
$private['uri.skin']   = $truncUri.'/skin';
*/

// ---------------------------------------------------------------------
// Database setting
// ---------------------------------------------------------------------
$data = $private['dir.data'];
// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
// (Default) SQLite with PHP.PDO
if (phpversion("pdo_sqlite") !== FALSE) {
    $private['db.dsn']              = "pdosqlite://$data/konawiki.db";
    $private['subdb.dsn']           = "pdosqlite://$data/konawiki_sub.db";
    $private['backupdb.dsn']        = "pdosqlite://$data/konawiki_backup.db";
}
// (for old php version 4/5) maybe need to install PECL SQLite(1.0.3)
else if (phpversion("SQLite") !== FALSE || phpversion("sqlite") !== FALSE) {
    $private['db.dsn']              = "sqlite://$data/konawiki.db";
    $private['subdb.dsn']           = "sqlite://$data/konawiki_sub.db";
    $private['backupdb.dsn']        = "sqlite://$data/konawiki_backup.db";
}
// (Could not use SQLite) show help
else {
    echo "<pre>";
    echo "<a href='http://konawiki.aoikujira.com/index.php?21&go'>";
    echo "Could not use SQLite, Please set Database Setting.</a>\n";
}
/*
// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
// If you use MySQL, please write MySQL info. (Can set same DB)
$private['db.dsn']       = 'mysql://username:password@localhost/dbname';
$private['subdb.dsn']    = $private['db.dsn'];
$private['backupdb.dsn'] = $private['db.dsn'];
// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
*/

// ---------------------------------------------------------------------
// Authorization setting 
// ---------------------------------------------------------------------/
// Can login users write attachment files?
$private['attach.enabled'] = TRUE; 
// If you use every users permission, set TRUE.
$private['auth.read.enabled']   = FALSE; 
$private['auth.write.enabled']  = TRUE; 
// Users list (Username & Password)
$authusers['username'] = 'password';
$users_perm['username'] = array('read'=>true, 'write'=>true); 
// If use users_perm, must set "auth.read.enabled" 
//   and "auth.write.enabled" to TRUE. 
// ...
// $authusers['username1'] = 'password1';
// $authusers['username2'] = 'password2';
// $authusers['username3'] = 'password3';
// (*) You can set Hash Type Password.
// $authusers['username1'] = '{md5}1a1dc91c907325c69271ddf0c944bc72';
// $authusers['username1'] = '{sha1}9d4e1e23bd5b727046a9e3b4b7db57bd8d6ee684';

/**
 * footer option
 */
$konawiki['private']['footer.analytics'] = <<<EOS
<!-- analytics code here -->
EOS;

/**
 * Page Options
 * If want to show all pages something use show.allpage plugins. 
 */
/*
$konawiki['private']['show.plugins']['show.allpage'] = array(
        'enabled'   => TRUE,
        'file'      => 'show.allpage.inc.php',
        'entry'     => 'show_plugin_show_allpage_entry',
        'header.wiki' => '',                    // 全てのページの先頭に表示する文字列(WIKI記法で)
        'footer.wiki' => '#comment',            // 全てのページの末尾に表示する文字列(WIKI記法で)
        'header.html' => '',                    // 全てのページの先頭に表示する文字列(HTMLで)
        'footer.html' => '<br/>',               // 全てのページの末尾に表示する文字列(HTMLで)
    );
*/

/**
 * Blog plugins
 */
/*
$konawiki['private']['show.plugins']['blog'] = array(
        'enabled'   => TRUE,
        'file'      => 'blogtop.inc.php',
        'entry'     => 'show_plugin_blogtop',
        'count'     => 5,    // index page list count
        'pattern'   => '20*',// like "2008/01/01"
        'header.code' => '',
    );
*/

$public['noanchor'] = TRUE; // タイトルにアンカーを表示しない

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
$private['debug'] = FALSE;
// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>



