<?php
//----------------------------------------------------------------------
// KonaWiki default setting
//----------------------------------------------------------------------
global $public, $private, $authusers;
$public['KONAWIKI_VERSION'] = "0.5";
$public['config']['version'] = 1004;
$public['config.loaded.default'] = TRUE;
//----------------------------------------------------------------------
// PUBLIC setting
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

// OTHER PUBLIC SETTING
$public['FrontPage'] = 'FrontPage';
$public['login.link.visible'] = TRUE;
$public['text.link.visible'] = FALSE;
$public['tag.link.visible'] = TRUE;
$public['tag.link.pages.visible'] = TRUE;
$public['wikilink.desc.visible'] = TRUE;
$public['noanchor'] = FALSE; // タイトルにアンカーを表示
$public['logo'] = 'logo.png'; 
$public['favicon'] = 'resource/favicon.ico';
$public['skin'] = 'default';
$public['ogimage'] = 'logo-large.png';

// header & footer option
$public['wikibody_header'] = '';
$public['wikibody_footer'] = '';

//----------------------------------------------------------------------
// PRIVATE setting
//----------------------------------------------------------------------
// Date time Format
$private['date_format'] = 'Y-m-d';
$private['time_format'] = 'H:i:s';
$private['debug'] = 1;
// cache
$private['cache.mode'] = 'cache'; // cache or nocache
// default database
$data = $private['dir.data']; // PRI SET
$private['db.dsn']              = "pdosqlite://$data/konawiki.db";
$private['subdb.dsn']           = "pdosqlite://$data/konawiki_sub.db";
$private['backupdb.dsn']        = "pdosqlite://$data/konawiki_backup.db";
// auth mode ( = "FORM" )
$private['auth.type']           = "form";
$private['auth.read.enabled']   = FALSE;
$private['auth.write.enabled']  = TRUE;
$private['auth.realm']          = 'KonaWiki Authentication';
$private['admin.key']           = 'konawiki';
$private['attach.enabled']      = TRUE;
$private['login.time.limit']    = 60 * 60 * 6; /* 6 hour */

// In Header additional setting
$private['html.head.include'] = array(
  // "<link rel='stylesheet' type='text/cs'" href='./resource/konawiki.css' />",
);

//----------------------------------------------------------
// Wiki BASIC Parameters
//----------------------------------------------------------
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
//----------------------------------------------------------
// Wiki HTML Parser Parameters
//----------------------------------------------------------
$private['header_level_from']   = 1;
$private['source_tag_begin']    = '<pre class="code">';
$private['source_tag_end']      = '</pre>';
$private['source_tag_hr']       = "<p class='clear'/><div class='underline'>&nbsp;</div>\n";
$private['entry_begin']         = '<div class="entry">';
$private['entry_end']           = '</div><!-- end of entry -->';
$private['session.name']        = 'kona2';
$private['footer.analytics']    = '';

//----------------------------------------------------------
// Plugin setting
//----------------------------------------------------------
// plugins setting
$private['plugins.disable'] = array();
$private['plugins.disable']['html'] = TRUE;
$private['show.plugins'] = array();

// (必要なら) googleadsense plugin
// [HINT] ライセンスにより、Adsense のサイトから取得したコードをそのまま貼り付けます。
$private['googleadsense']['default'] = <<<__EOS__
<script type="text/javascript"><!--
google_ad_client = "pub-3816223231062294";
/* konawiki */
google_ad_slot = "0471021893";
google_ad_width = 468;
google_ad_height = 60;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
__EOS__;






