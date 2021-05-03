<?php
// -----------------------------------------
// 全てのKonaWiki2のオプションをここに列挙すること
// -----------------------------------------
function konawiki_checkOptions() {
    global $private, $public;

    // Locale
    kona_check_public('lang', konawiki_getBrowserLang()); // en or ja
    kona_check_public('timezone', 'Asia/Tokyo');
    kona_check_private('data_format', 'Y-m-d');
    kona_check_private('date_format_easy', 'm/d');
    kona_check_private('time_format', 'H:i:s');

    // Site info
    kona_check_public('title', 'KonaWiki2');
    kona_check_public('author', 'KonaWiki User');
    kona_check_public('description', 'KonaWiki');
    kona_check_public('keywords', 'KonaWiki,Wiki');
    kona_check_public('norobot', FALSE);
    kona_check_public('skin', 'default');
    kona_check_public('logo', 'logo.png');
    kona_check_public('favicon', 'favicon.ico');
    kona_check_public('ogimage', 'logo-large.png');
    kona_check_public('og:type', 'website');
    
    // wiki setting
    kona_check_public('FrontPage', 'FrontPage'); // FrontPage' name ... トップページの名前
    kona_check_public('login.link.visible', TRUE);
    kona_check_public('noanchor', FALSE);
    kona_check_public('header.title.visible', TRUE);
    kona_check_private('session.name', 'kona2');
    kona_check_private('footer.analytics', '');
    kona_check_private('para_enabled_br', TRUE); // 改行と同時に強制的に<br>を挿入する
    // plugins
    kona_check_private('plugins.disable', ['html'=>TRUE, 'htmlshow'=>TRUE]);
    kona_check_private('show.plugins',[]);
    // webmaster.email
    kona_check_private('webmaster.email','');
    // debug
    kona_check_private('debug', FALSE);

    // [private]
    // # dir & uri
    $engine_dir = dirname(__DIR__);
    $root_dir = dirname($engine_dir);
    kona_check_private('dir.base', $root_dir);
    kona_check_private('dir.engine', $engine_dir);
    kona_check_private('dir.skin', $root_dir.'/skin');
    kona_check_private('dir.data', $root_dir.'/data');
    kona_check_private('dir.attach', $root_dir.'/attach');
    kona_check_private('dir.cache', $root_dir.'/cache');
    // URI
    $base_uri = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'';
    $root_uri = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']);
    kona_check_private('uri.base', $base_uri);
    kona_check_private('uri.attach', $root_uri.'/attach');
    kona_check_private('uri.skin', $root_uri.'/skin');
    kona_check_public('baseurl', $base_uri);
    // # DB setting
    $data_dir = konawiki_private('dir.data');
    kona_check_private('db.dsn', "sqlite://$data_dir/konawiki.db");
    kona_check_private('subdb.dsn', "sqlite://$data_dir/konawiki_sub.db");
    kona_check_private('backupdb.dsn', "sqlite://$data_dir/konawiki_backup.db");
    // # wiki parameters
    kona_check_private('auth.read.enabled', TRUE);
    kona_check_private('caauthche.write.enabled', TRUE);
    kona_check_private('attach.enabled', TRUE);
    kona_check_public('max_upload_size', 1024 * 1024 * 5); // 5MB
    kona_check_private('login.time.limit', 60 * 60 * 24 * 90);
    // # login users
    kona_check_private('auth.users', []);
    kona_check_private('auth.users.perm', []);
    
    // wiki parser setting
    kona_check_private('ul_mark1', '・');
    kona_check_private('ul_mark2', '≫');
    kona_check_private('h1_mark1', '■');
    kona_check_private('h1_mark2', '□');
    kona_check_private('h2_mark1', '●');
    kona_check_private('h2_mark2', '○');
    kona_check_private('h3_mark1', '▲');
    kona_check_private('h3_mark2', '△');
    kona_check_private('h4_mark1', '▼');
    kona_check_private('h4_mark2', '▽');
    kona_check_private('header_level_from', 1);
    kona_check_private('source_tag_begin', '<pre class="code">');
    kona_check_private('source_tag_end', '</pre>');
    kona_check_private('source_tag_hr', "<p class='clear'/><div class='underline'>&nbsp;</div>\n");
    kona_check_private('entry_begin', '<article class="entry">');
    kona_check_private('entry_end', '</article><!-- end of entry -->');
    
    // [cache]
    // cache => cache or nocache
    kona_check_private('cache.mode', FALSE);
    kona_check_public('cache', 'nocache');
}

function kona_check_private($name, $def_value) {
    global $private;
    if (isset($private[$name])) return;
    $private[$name] = $def_value;
}
function kona_check_public($name, $def_value) {
    global $public;
    if (isset($public[$name])) return;
    $public[$name] = $def_value;
}

function konawiki_getBrowserLang() {
    $accept = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
      ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'en';
    $lang = explode(',', $accept);
    $lang = preg_replace('#^(\w+)\-.*$#', '\1', $lang);
    return $lang[0];
  }
  
