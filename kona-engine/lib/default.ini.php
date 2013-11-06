<?php
/**
 * konawiki2 初期設定ファイル
 * konawiki default setting file
 */

global $public, $private, $authusers;
$konawiki['public']['config']['version'] = 1002;

// (ex)
// ユーザーにパスワードを設定
// $authusers['test']  = 'password';
// 読み書き権限を設定 → $private['auth.read.enabled'] と $private['auth.write.enabled'] を TRUE に設定する必要がある

$public['KONAWIKI_VERSION'] = "0.4";

function kona_set_pub($key, $def) {
  global $public;
  if (!isset($public[$key])) {
    $public[$key] = $def;
  }
}
function kona_set_pri($key, $def) {
  global $private;
  if (!isset($private[$key])) {
    $private[$key] = $def;
  }
}

/**
 * Wiki public config
 */
kona_set_pub('title',       'KonaWiki2');
kona_set_pub('author',      'kujirahand');
kona_set_pub('description', 'KonaWiki Wiki App');
kona_set_pub('FrontPage',   'FrontPage');
kona_set_pub('login.link.visible',      TRUE);
kona_set_pub('text.link.visible',       FALSE);
kona_set_pub('tag.link.visible',        TRUE);
kona_set_pub('tag.link.pages.visible',  TRUE);
kona_set_pub('wikilink.desc.visible',   TRUE);
kona_set_pub('norobot', FALSE);   // 検索エンジンに登録されないようにする
kona_set_pub('noanchor', FALSE); // タイトルにアンカーを表示する

// SKINフォルダ内のlogo.pngが優先して使われます。
kona_set_pub('logo', 'logo.png'); 
kona_set_pub('favicon', 'resource/favicon.ico');
// ログイン時のメッセージ
kona_set_pub('login.message', 'Success to login!');
kona_set_pub('login.message.readonly',
  'Success to login! Thank you.'); 

/**
 * スキン（概観の変更）機能について
 */
// * konawiki 標準スキンを使う場合
// - skin フォルダの中を見て、スキン名を指定
// - default, 2column, pedia
kona_set_pub('skin', 'default');
// * tdiary のテーマを利用する場合(以下の2行を有効にする)
// $public['skin']         = 'tdiary';   //  tdiary を指定
// $public['skin.theme']   = 'noto';     //  /skin/tdiary フォルダにテーマをコピーしてフォルダ名をここに指定する


/**
 * Wiki private config
 */
// Date time Format
kona_set_pri('date_format', 'Y-m-d');
kona_set_pri('time_format', 'H:i:s');
// debug
kona_set_pri('debug', 1);
// cache
kona_set_pri('cache.mode', 'cache'); // cache or nocache
// default database
if (empty($private['db.dsn'])) {
  $private['db.dsn']              = 'sqlite://data/konawiki.db';
  $private['subdb.dsn']           = 'sqlite://data/konawiki_sub.db';
  $private['backupdb.dsn']        = 'sqlite://data/konawiki_backup.db';
}
// auth (強制上書き)
$private['auth.type']           = "form"; // "form" or "basic" (現在 basic は非サポートです)
kona_set_pri('auth.read.enabled',  FALSE);
kona_set_pri('auth.write.enabled', TRUE);
kona_set_pri('auth.realm', 'KonaWiki Authentication');
kona_set_pri('admin.key', 'konawiki');
kona_set_pri('attach.enabled', TRUE);
kona_set_pri('login.time.limit', 60 * 60 * 6); /* hour */

// plugins setting (デフォルトで無効のプラグイン)
if (empty($private['plugins.disable'])) {
  $private['plugins.disable']['html']    = TRUE;
}
// konawikiが使うJavaScriptやCSSの追加インクルード
kona_set_pri('html.head.include',
  array(
    // "<link rel='stylesheet' type='text/cs'" href='./resource/konawiki.css' />",
  )
);

// users
if (count($authusers) == 0) {
  $authusers['username'] = 'password';
}

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


/**
 * header & footer option
 */
kona_set_pub('wikibody_header', '');
kona_set_pub('wikibody_footer', '');

/**
 * footer option
 */
kona_set_pri('footer.analytics','');

/**
 * page/show plug-ins
 * ※ページを表示する時にフィルターを指定することが可能です。
 *   使い方はプラグインファイルの先頭をを見てください。
 * - plugins/blogtop.inc.php         .. ブログ風に表示するプラグイン
 * - plugins/show.nadesiko.inc.php   .. なでしこのマニュアル風プラグイン ( http://nadesi.com/man/ )
 * - plugins/show.allpage.inc.php    .. 全てのページに何かを差し込みたい場合に利用するプラグイン
 */

/**
 * plugin option
 */
// (必要なら) googleadsense plugin
// [HINT] ライセンスにより、Adsense のサイトから取得したコードをそのまま貼り付けます。
if (empty($private['googleadsense']['default'])) {
  $private['googleadsense']['default'] = <<<EOS__
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
EOS__;
}




