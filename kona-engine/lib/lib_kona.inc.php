<?php
/**
 * ---------------------------------------------------------------------
 * konawiki の基本ライブラリ
 * ---------------------------------------------------------------------
 */

require_once dirname(__DIR__).'/konawiki_version.inc.php';
require_once __DIR__.'/konawiki_options.inc.php';

//----------------------------------------------------------------------
/**
 * ディレクトリを設定し初期化する
 * @return void
 */
function konawiki_init()
{
    global $konawiki, $public, $private;

    // check options (ref) konawiki_options.inc.php
    $public['KONAWIKI_VERSION'] = KONAWIKI_VERSION;
    $public['KONAWIKI_CONFIG_VER'] = 1008;
    konawiki_checkOptions();
    $engineDir = $private['dir.engine'];
    // set directory path
    define('KONAWIKI_DIR_LIB',      dirname(__FILE__));
    define("KONAWIKI_DIR_ACTION",   $engineDir."/action");
    define("KONAWIKI_DIR_TEMPLATE", $engineDir."/template");
    define("KONAWIKI_DIR_DEF_RES",  $engineDir."/resource");
    define("KONAWIKI_DIR_PLUGINS",  $engineDir."/plugins");
    define("KONAWIKI_DIR_HELP",     $engineDir."/help");
    define("KONAWIKI_DIR_FW_SIMPLE", $engineDir."/fw_simple");
    // public area
    define("KONAWIKI_DIR_SKIN",     $private['dir.skin']);
    define("KONAWIKI_URI_SKIN",     $private['uri.skin']);
    // private area
    define("KONAWIKI_DIR_DATA",     $private['dir.data']);
    // public area
    define("KONAWIKI_DIR_BASE",     $private['dir.base']);
    define("KONAWIKI_DIR_ATTACH",   $private['dir.attach']);
    define("KONAWIKI_URI_ATTACH",   $private['uri.attach']);

    // for template
    global $DIR_TEMPLATE_CACHE, $DIR_TEMPLATE, $FW_TEMPLATE_PARAMS;
    if (empty($private['dir.cache'])) {
        $private['dir.cache'] = dirname($private['dir.data']).'/cache';
    }
    $DIR_TEMPLATE_CACHE = $private['dir.cache'];
    $DIR_TEMPLATE = $engineDir.'/template';
    require_once(KONAWIKI_DIR_FW_SIMPLE.'/fw_template_engine.lib.php');
    if (!is_writable($DIR_TEMPLATE_CACHE)) {
        echo 'The dir.cache not writable. Please make cache dir.';
        exit;
    }

    // other lib
    require_once(KONAWIKI_DIR_LIB.'/html.inc.php');
    require_once(KONAWIKI_DIR_LIB.'/konawiki_parser.inc.php');
    require_once(KONAWIKI_DIR_LIB.'/useragent.inc.php');
    // データベース関連のライブラリを取り込む
    require_once(KONAWIKI_DIR_FW_SIMPLE.'/fw_database.lib.php');
    require_once(KONAWIKI_DIR_LIB.'/konawiki_db.inc.php');
    // 認証関連のライブラリを取り込む
    require_once(KONAWIKI_DIR_LIB.'/konawiki_auth.inc.php');

    // init config
    konawiki_init_config();
    konawiki_start_session();
    konawiki_parseURI();
    // Initialize Database
    konawiki_initDB(); // @see ./konawiki_db.inc.php

    // set public info
    konawiki_set_public_info();
    // action
    if ($public['action'] != 'file') {
        konawiki_auth_read(); // @see ./konawiki_auth.inc.php
    }
    konawiki_execute_action();
}

/**
 * URIパラメータを解析してグローバル変数にセットする
 * @return void
 */
function konawiki_parseURI()
{
    // parameter's format
    // index.php?page&action&stat&params ...
    global $public, $private, $konawiki;
    //--------------------------------------------------------------
    // get page / action / stat
    $qs = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    $qa = explode('&', $qs.'&&&');
    // パラメータに key=val があればWiki規則に合わない値なので無視する
    $qa2 = [];
    foreach ($qa as $a) {
        if (strpos($a, '=') === FALSE) {
            $qa2[] = $a;
        }
    }
    list($page, $action, $stat) = $qa2;
    $page = urldecode($page);
    $action = urldecode($action);
    $stat = urldecode($stat);

    // check page action stat parameter
    if (isset($_GET['page'])) { $page = $_GET['page']; }
    if (isset($_GET['action'])) { $action = $_GET['action']; }
    if (isset($_GET['stat'])) { $stat = $_GET['stat']; }

    // set default page & action
    // page
    if ($page == '') {
        $FrontPage = konawiki_public('FrontPage');
        $page = $FrontPage;
    }
    // action
    if ($action == '') { $action = 'show'; }

    // set to $_GET
    $_GET['page'] = $page;
    $_GET['action'] = $action;
    $_GET['stat'] = $stat;

    // Check Action pattern
    if (!preg_match('#^[a-zA-Z0-9_]+$#', $action)) {
        $_GET['action'] = '__INVALID__';
    }
    // Check invalid status
    if ($stat != '' && !preg_match('#^[a-zA-Z0-9_]*$#', $stat)) {
        $_GET['stat'] = '__INVALID__';
    }

    // encode params and set to public
    $public['page']     = htmlspecialchars($page);
    $public['page_raw'] = $page;
    $public['action']   = htmlspecialchars($action);
    $public['stat']     = htmlspecialchars($stat);
    $public['pagelink'] = konawiki_getPageLink($page,"dir");
    $keyword = "[[".urlencode($page)."]]";
    $public['backlink'] = konawiki_getPageURL($page, "backlink", "");

    // old resource
    $public['rsslink'] = '';
    // set default Page
    $_GET['DEF_PAGE'] = $page;
}


/**
 * アクションを実行する
 * @return void
 */
function konawiki_execute_action()
{
    $action = konawiki_param('action');
    $stat   = konawiki_param('stat');
    $module = KONAWIKI_DIR_ACTION."/{$action}.inc.php";
    // check action module
    if (!file_exists($module)) {
        header("HTTP/1.0 404 Not Found");
        echo "Action module Not Found.";
        exit;
    }
    // check action function
    $func   = "action_{$action}_{$stat}";
    require_once($module);
    if (!is_callable($func)) {
        header("HTTP/1.0 404 Not Found");
        if (konawiki_private('debug', FALSE)) {
            echo "<pre>Page Action Not Found: $func";
            global $konawiki;
            print_r($_GET);
            print_r($konawiki);
        } else {
            echo "Page Action Not Found.";
        }
        exit;
    }
    try {
        call_user_func($func);
    } catch (Exception $e) {
        echo "<pre>";
        print_r($e);
    }
}


/**
 * ユーザー設定ファイルを読み込む
 * @return void
 */
function konawiki_init_config()
{
    global $public;
    // include user setting
    if (konawiki_is_debug()) { // test mode
        // test directory
        check_is_writable(KONAWIKI_DIR_DATA);
        check_is_writable(KONAWIKI_DIR_ATTACH);
    }
    // Timezone
    @date_default_timezone_set( konawiki_public('timezone', 'Asia/Tokyo') );
    // echo date_default_timezone_get(); // test timezone

    // language support
    $lang = konawiki_public('lang', 'en');
    if ($lang != 'ja' && $lang != 'en') { $lang = 'en'; }
    $path = konawiki_private('dir.engine', '..')."/lang/{$lang}.inc.php";
    if (file_exists($path)) { include_once($path); }
    else {
        $lang = "en";
        $path = konawiki_private('dir.engine','..')."lang/{$lang}.inc.php";
    }
    // KonaWikiのバージョンを再設定
    $public['KONAWIKI_VERSION'] = KONAWIKI_VERSION;
}

function check_is_writable($dir)
{
    if (!is_writable($dir)) {
        if (!is_writable($dir)) {
            echo '<div style="color:red">[ERROR] The directory is not wriable. : '.$dir.'</div>';
            exit;
        }
    }
}

function konawiki_header_addStr($code)
{
    $list = konawiki_private("html.head.include", null);
    if ($list == null) $list = array();
    if (false === array_search($code, $list)) {
        $list[] = $code;
    }
    konawiki_addPrivate("html.head.include", $list);
}

function konawiki_set_public_info() {
    global $public;

    // log_id
    $log_id     = konawiki_getPageId();
    $protocol   = empty($_SERVER["HTTPS"]) ? "http://" : "https://";
    $public['baseuri'] = $protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    $public['short_url'] = konawiki_getPageURL($log_id, "go");
    $public['long_url'] = konawiki_getPageURL();

    // check skin & theme
    $skin_css  = 'skin.css';
    $theme     = konawiki_public("skin.theme", false);
    $theme_css = false;
    if ($theme) { $theme_css = getThemeURL("{$theme}.css"); }
    $public['theme.css'] = $theme_css;

    // logo & favicon.ico
    $logo    = getResourceURL(konawiki_public("logo", "logo.png"));
    $favicon = getResourceURL(konawiki_public("favicon", "favicon.ico"));
    $public['logo'] = $logo;
    $public['favicon'] = $favicon;

    // navibar
    $navibar_log = konawiki_getLog('NaviBar');
    if (isset($navibar_log["body"])) {
        $navibar = konawiki_parser_convert($navibar_log["body"], false);
    } else {
        $navibar = false;
    }
    $public['navibar'] = $navibar;

    // GlobBar
    $globbar_log = konawiki_getLog('GlobBar');
    if (!empty( $globbar_log["body"] )) {
        $globbar = konawiki_parser_convert($globbar_log["body"], false);
        $globbar .= konawiki_getEditMenu('right');
    } else {
        $globbar = false;
    }
    $public['GlobBar'] = $globbar;

    //----------------------------------------------------------------------
    // check title
    $page = konawiki_getPage();
    $title = $public['title'];
    // for search page
    $action = konawiki_public('action');
    if ($action == "search") { // no page link
        $page = 'search';
        $pagelink = 'search';
    }
    //
    $pagetitle = "$page - $title";
    if ($page == konawiki_public("FrontPage", "FrontPage")) {
        $pagetitle = $title;
    }
    $public['pagetitle'] = $pagetitle;

    // og:image
    $ogtype = konawiki_public("og:type", "website");
    $ogimage = getResourceURL(konawiki_public('ogimage','logo-large.png'));
    $ogdesc = $pagetitle;
    $public['ogtype'] = $ogtype;
    $public['ogimage'] = $ogimage;
    $public['ogdesc'] = $ogdesc;

    //----------------------------------------------------------------------
    // addtional JS/CSS
    $include_js_css = "";
    $include_list = konawiki_private("html.head.include", false);
    if ($include_list) {
        foreach ($include_list as $line) {
            $include_js_css .= "    " . $line . "\n";
        }
    }
    $public['include_js_css'] = $include_js_css;

    // CSS and JavaScript code
    $type_css = 1; $type_jss = 2;
    $css_list = [
        [$type_css, 'pure-min.css', FALSE],
        [$type_css, 'grids-responsive-min.css', FALSE],
        [$type_css, 'konawiki.css', TRUE],
        [$type_css, $skin_css, TRUE],
        [$type_css, $theme_css, TRUE],
        [$type_jss, 'jquery-3.7.0.min.js', FALSE],
        [$type_css, 'drawer.css', TRUE],
        [$type_jss, 'drawer.js', TRUE],
    ];
    $css_js = '';
    foreach ($css_list as $f) {
        $type  = $f[0]; $name  = $f[1]; $mtime = $f[2];
        if (!$name) continue;
        $path = getResourceURL($name, $mtime);
        if ($type == $type_css) {
            $css_js .= '<link rel="stylesheet" type="text/css" href="'.$path.'" />'."\n";
        } else {
            $css_js .= '<script src="'.$path.'"></script>'."\n";
        }
    }
    $public['css_js'] = $css_js;

    // meta info
    $meta = [];
    $m1 = konawiki_private('header.meta', '');
    if ($m1) { $meta[] = $m1; }
    $m2 = konawiki_private('header.analytics', '');
    if ($m2) { $meta[] = $m2; }
    $public['header_meta'] = implode("\n", $meta)."\n";

    // keywords
    $public['head_keywords'] = konawiki_getKeywords($page);
}

/**
 * KonaWiki のヘッダにJavaScriptのインクルードを追加する
 * @param string $url
 */
function konawiki_header_addJS($url)
{
    $code = "<script type=\"text/javascript\" src=\"$url\"></script>";
    konawiki_header_addStr($code);
}

/**
 * KonaWiki のヘッダにCSSのインクルードを追加する
 * @param string $url
 */
function konawiki_header_addCSS($url)
{
    $code = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$url\" />";
    konawiki_header_addStr($code);
}


function konawiki_is_debug()
{
    return konawiki_private("debug");
}
function konawiki_set_debug($value)
{
    global $private;
    $private['debug'] = $value;
}

/**
 * show debug info
 */
function konawiki_page_debug()
{
    global $konawiki, $public, $private;
    if (!konawiki_is_debug()) return;
    extract($public);
    echo '<hr style="margin-top: 2em;margin-bottom: 2em;">';
    echo '<h2>【DEBUG MODE】</h2>'."\n";
    konawiki2_debug_params('basic info', [
        "baseurl" => konawiki_public("baseurl"),
        "page" => $page,
        "action" => $action,
        "stat" => $stat,
    ]);
    konawiki2_debug_params('$_GET', $_GET);
    konawiki2_debug_params('$_SESSION', $_SESSION);
    konawiki2_debug_params('$_SERVER', $_SERVER);
    konawiki2_debug_params('$public', $public);
}

function konawiki2_debug_params($name, $params) 
{
    $name_h = htmlspecialchars($name, ENT_QUOTES);
    echo "<div style='border: 1px solid silver; padding: 1em; background-color: #f0f0f0;'>\n";
    echo "<h3 style='background-color: #f0f;'>$name_h</h3>\n";
    echo "<div style='padding-left:2em;'>\n";
    foreach ($params as $k => $v) {
        echo "<span style='background-color: yellow;'>".htmlspecialchars($k, ENT_QUOTES)."</span>";
        echo " =&gt; ";
        if (is_array($v)) {
            echo "<pre style='border:1px gray solid; background-color: #eee;'><code>";
            print_r($v);
            echo "</code></pre>";
        } else {
            echo htmlspecialchars($v, ENT_QUOTES);
        }
        echo "<br>";
    }
    echo "</div></div>\n\n";
}


function konawiki_param($name, $def_value = FALSE)
{
    // Check post parameters
    if (isset($_POST[$name])) {
        return $_POST[$name];
    }
    // Check get parameters
    if (isset($_GET[$name])) {
        return $_GET[$name];
    }
    return $def_value;
}

function konawiki_getArray($var, $key, $default)
{
    if (isset($var[$key])) {
        return $var[$key];
    }
    else {
        return $default;
    }
}

function konawiki_private($name, $def = null)
{
    global $private;
    if (empty($private[$name])) {
        return $def;
    }
    return $private[$name];
}

function konawiki_addPrivate($key, $value)
{
    global $private;
    $private[$key] = $value;
}

function konawiki_public($name, $def = null)
{
    global $public;
    if (isset($public[$name])) {
        return $public[$name];
    }
    return $def;
}

function konawiki_addPublic($key, $value)
{
    global $public;
    $public[$key] = $value;
}

function konawiki_getPluginInfo($plugin_name, $key, $def = FALSE)
{
    global $konawiki;
    if (isset($konawiki["plugins"][$plugin_name][$key])) {
        return $konawiki["plugins"][$plugin_name][$key];
    }
    return $def;
}

function konawiki_setPluginInfo($plugin_name, $key, $val)
{
    global $konawiki;
    if (!isset($konawiki["plugins"][$plugin_name])) {
        $konawiki["plugins"][$plugin_name] = array();
    }
    $konawiki["plugins"][$plugin_name][$key] = $val;
}

function konawiki_getPluginInfoArray($plugin_name)
{
    global $konawiki;
    if (isset($konawiki["plugins"][$plugin_name])) {
        return $konawiki["plugins"][$plugin_name];
    }
    return array();
}


function konawiki_setPluginParam($key, $val)
{
    global $plugin_params;
    $plugin_params[$key] = $val;
}

function konawiki_setPluginDynamic($is_dynamic)
{
    global $plugin_params;
    if (empty($plugin_params['flag_dynamic'])) {
        $plugin_params['flag_dynamic'] = $is_dynamic;
        return;
    }
    if ($plugin_params['flag_dynamic'] == false) {
        $plugin_params['flag_dynamic'] = $is_dynamic;
    }
}

function konawiki_info($name, $def)
{
    global $konawiki;
    if (empty($konawiki[$name])) {
        $konawiki[$name] = $def;
    }
    return $konawiki[$name];
}

function konawiki_getPageHTML()
{
    $page = konawiki_param("page");
    $page = htmlspecialchars($page);
    return $page;
}

function konawiki_getPageURL($page = FALSE, $action = FALSE, $stat = FALSE, $param_str = FALSE, $shortpath = FALSE)
{
    if ($page === FALSE) {
        $page = konawiki_public("page", 'FrontPage');
    }
    // remove "javascript:" protocol
    $page = preg_replace('/^javascript\:/', '', $page);
    $page_enc = urlencode($page);
    $baseurl = konawiki_public("baseurl");
    if ($action == 'go') {
        $action = FALSE;
        $baseurl = str_replace('/index.php', '/go.php', $baseurl);
    }
    // page
    if ($shortpath) {
        $name = basename($baseurl);
        $url = "{$name}?{$page_enc}";
    } else {
        $url = "{$baseurl}?{$page_enc}";
    }
    // action & stat
    if ($action || $stat) {
        if ($action == FALSE) { $action = "show"; }
        $url .= "&$action";
    }
    if ($stat) {
        $stat = urlencode($stat);
        $url .= "&$stat";
    }
    // param_str
    if ($param_str) {
        $url .= "&$param_str";
    }
    return $url;
}

function konawiki_getPageURL2($page = FALSE, $action = FALSE, $stat = FALSE, $param_str = FALSE)
{
    return konawiki_getPageURL($page, $action, $stat, $param_str, TRUE);
}

function konawiki_getPageLink($page = FALSE, $mode = "normal", $caption = FALSE, $paramstr = FALSE)
{
    // get page
    if ($page === FALSE) {
        $page = konawiki_param("page");
    }

    if ($mode == "normal") {
        if ($caption == FALSE) {$caption = $page;}
        $caption = htmlspecialchars($caption, ENT_QUOTES);
        $url = htmlspecialchars(konawiki_getPageURL($page,false, false, $paramstr), ENT_QUOTES);
        $html = "<a href=\"{$url}\">{$caption}</a>";
    }
    else if ($mode == "dir") {
        $dirs = explode("/", $page);
        $links = array();
        for ($i = 0; $i < count($dirs); $i++) {
            $d = array();
            for ($j = 0; $j <= $i; $j++) {
                $d[] = $dirs[$j];
                $last = $dirs[$j];
            }
            $dir = join("/", $d);
            $url = konawiki_getPageURL($dir, false, false, $paramstr);
            $last_ = htmlspecialchars($last, ENT_QUOTES);
            $links[] = "<a href='{$url}'>$last_</a>";
        }
        $html = join("/",$links);
    }
    return $html;
}

function konawiki_getPage()
{
    $page = konawiki_param("page");
    $page = htmlspecialchars($page, ENT_QUOTES);
    return $page;
}

/**
 * get page id from page name
 * @param $page
 * @return unknown_type
 */
function konawiki_getPageId($page = FALSE)
{
    global $konawiki_page_cache;

    if ($page == FALSE) {
        $page = konawiki_getPage();
    }

    // check cache
    if (empty($konawiki_page_cache)) {
        $konawiki_page_cache = array();
    }
    if (isset($konawiki_page_cache[$page])) {
        return $konawiki_page_cache[$page];
    }

    $sql = "SELECT id FROM logs WHERE name=? LIMIT 1";
    $log = db_get1($sql, [$page]);
    if (!$log) {
        return FALSE;
    }
    $log_id = isset($log['id']) ? $log['id'] : FALSE;
    $konawiki_page_cache[$page] = $log_id; // save cache
    return $log_id;
}

function konawiki_getPageInfoById($log_id)
{
    global $konawiki_pagename_cache;

    if (empty($konawiki_pagename_cache)) {
        $konawiki_pagename_cache = [];
    }
    if (isset($konawiki_pagename_cache[$log_id])) {
        return $konawiki_pagename_cache[$log_id];
    }

    $sql = "SELECT name,freeze,private,ctime,mtime FROM logs WHERE id=? LIMIT 1";
    $log = db_get1($sql, [$log_id]);
    if (!isset($log['name'])) {
        return FALSE;
    }
    $konawiki_pagename_cache[$log_id] = $log;
    return $log;
}

function konawiki_getPageNameFromId($log_id)
{
    $log = konawiki_getPageInfoById($log_id);
    return $log['name'];
}

function konawiki_resourceurl()
{
    return konawiki_public('resourceurl');
}

function include_template($fname, $vars = FALSE)
{
    global $public, $FW_TEMPLATE_PARAMS;
    if (preg_match('#\.html$#', $fname)) {
        $FW_TEMPLATE_PARAMS = $public;
        template_render($fname, $vars);
        return;
    }
    // --- old template ---
    // check skin
    $skin = $public['skin'];
    $path = KONAWIKI_DIR_SKIN."/{$skin}/{$fname}";
    if (!file_exists($path)) {
        $skin = "default";
        $path = KONAWIKI_DIR_SKIN."/{$skin}/{$fname}";
        if (!file_exists($path)) {
            $path = KONAWIKI_DIR_TEMPLATE.'/'.$fname;
            if (!file_exists($path)) {
                echo "<pre>";
                throw new Error('template not found.');
            }
        }
    }
    // extract variable
    extract($public);
    if ($vars) { extract($vars); }
    // include
    include($path);
}

function getSkinPath($fname)
{
    // check skin
    $skin = konawiki_public("skin");
    $path = KONAWIKI_DIR_SKIN."/{$skin}/{$fname}";
    if (!file_exists($path)) {
        // check default path
        $skin = "default";
        $path = KONAWIKI_DIR_SKIN."/{$skin}/{$fname}";
        if (!file_exists($path)) {
            $path = KONAWIKI_DIR_TEMPLATE.'/'.$fname;
        }
    }
    return $path;
}

function konawiki_getSkinPath($fname)
{
    return getSkinPath($fname);
}

/**
 * Skin 対応版
 */
function getResourceURL($fname, $use_mtime = TRUE, $skin = '')
{
    if ($skin == '') {
        $skin  = konawiki_public("skin");
    }
    // DEFAULT RESOURCE
    if ($skin == 'default') {
        $path = KONAWIKI_DIR_DEF_RES."/{$fname}";
        if (file_exists($path) && $use_mtime) {
            $mtime = filemtime($path);
            $uri  = konawiki_getPageURL($fname, 'file', '', "m=$mtime");
            return $uri;
        } else {
            $uri  = konawiki_getPageURL($fname, 'file');
            return $uri;
        }
    }
    // SKIN RESOURCE
    $path = KONAWIKI_DIR_SKIN."/{$skin}/resource/{$fname}";
    $uri  = KONAWIKI_URI_SKIN."/{$skin}/resource/{$fname}";
    // check exists
    if (!file_exists($path)) {
        if ($skin != 'default') {
            return getResourceURL($fname, $use_mtime, 'default');
        }
        $use_mtime = FALSE;
    }
    // add filemtime
    if ($use_mtime) {
        $mtime = filemtime($path);
        $uri .= "?m=$mtime";
    }
    return $uri;
}

function getThemeURL($fname)
{
    // check skin dir
    // SKIN DIR
    $skin = konawiki_public("skin");
    $theme = konawiki_public("skin.theme");
    //
    //$path = KONAWIKI_DIR_SKIN."/{$theme}/{$fname}";
    $uri  = ".".KONAWIKI_DIR_SKIN_REL."/{$skin}/{$theme}/{$fname}";
    return $uri;
}


function konawiki_date($value)
{
    $fmt = konawiki_private('date_format', "Y-m-d");
    return date($fmt, $value);
}

function konawiki_pubDate($value)
{
    return date('D, d M Y H:i:s', intval($value))."+0900";
}
function konawiki_dcDate($value)
{
    $v = intval($value);
    return date('Y-m-d',$v).'T'.date('H:i:s',$v).'+09:00';
}
/*
 * $mode = 'normal' or 'easy'
 */
function konawiki_time($value, $mode = 'normal')
{
    $fmt = konawiki_private('time_format', "H:i:s");
    return date($fmt, $value);
}

// mode = normal, easy
function konawiki_date_html($value, $mode='easy')
{
    // for time=0
    if ($value === 0) return "@";
    // to_int
    if (is_int($value)) {
        $target = konawiki_date($value);
    } else {
        $target = $value;
    }
    $now = time();
    // ちょっとだけの目安表示
    if ($mode == 'easy') {
        $sa = $now - $value;
        if ($sa < 3600) { // 1h
            return "<span class='date new'>1h</span>";
        } else if ($sa < 3600 * 6) {
            return "<span class='date new'>6h</span>";
        } else if ($sa < 3600 * 12) {
            $today = konawiki_lang('Today');
            return "<span class='date new'>$today</span>";
        }
        $s = "";
        $y_now = date("Y", $now);
        $y     = date("Y", $value);
        if ($y_now == $y) {
            $dfe = konawiki_private('date_format_easy', 'm-d');
            $s = date($dfe, $value);
        } else {
            $df = konawiki_private('date_format', 'Y-m-d');
            $s = date($df, $value);
        }
        return "<span class='date'>$s</span>";
    }
    //
    // しっかりと日付を表示
    //
    $opt = "";
    $new_limit = time() - (3600 * 24) /* hour */;
    if ($value > $new_limit) {
        $opt = " <span class='new'>New!</span>";
    }
    $fmt = konawiki_private("data_format", 'Y-m-d');
    $s = date($fmt, $value);
    //
    return "<span class='date'>{$s}</span>{$opt}";
}

function konawiki_datetime($value)
{
    $fmt1 = konawiki_private('date_format', 'Y-m-d');
    $fmt2 = konawiki_private('time_format', 'H:i:s');
    return date("{$fmt1} {$fmt2}", $value);
}

function konawiki_decode_datetime($str)
{
    // parse
    $a = explode(" ", $str."  ");
    $s_date = $a[0];
    $s_time = $a[1];
    $y = 1970;
    $m = 1;
    $d = 1;
    $h = $n = $s = 0;
    // date
    if (preg_match('#(\d+)\-(\d+)\-(\d+)#',$s_date,$mm)) {
        $y = $mm[1];
        $m = $mm[2];
        $d = $mm[3];
    }
    else if (preg_match('#(\d+)\/(\d+)\/(\d+)#',$s_date,$mm)) {
        $y = $mm[1];
        $m = $mm[2];
        $d = $mm[3];
    }
    // time
    if (preg_match('#(\d+)\:(\d+)\:(\d+)#',$s_time,$mm)) {
        $h = $mm[1];
        $n = $mm[2];
        $s = $mm[3];
    }
    return mktime ($h, $n, $s, $m, $d, $y);
}
/**
 * mode = normal, easy
 */
function konawiki_datetime_html($value, $mode='normal')
{
    // to_int
    if (is_int($value)) {
        $target = konawiki_datetime($value);
    } else {
        $target = $value;
        $value = konawiki_decode_datetime($value);
    }
    $opt    = "";
    //
    $old = time() - 60 * 60 * 24 /* hour */;
    if ($mode == 'easy') {
        $fmt = konawiki_datetime($old);
        if ($fmt <= $target) {
            $today = konawiki_date(time());
            $s     = konawiki_date($value);
            if ($today == $s) {
                $target = konawiki_lang('Today')." ".konawiki_time($value);
            }
            $opt = " <span class='new'>New!</span>";
        }
        $ty = date('Y', time());
        $vy = date('Y', $value);
        $tm = date('m', time());
        $vm = date('m', $value);
        $td = date('d', time());
        $vd = date('d', $value);
        $yd = date('d', time()-24*60*60);
        $sa = time() - $value;
        $HOUR = 60 * 60;
        $DAY  = $HOUR * 24;
        // 6 hour
        if ($sa <= $HOUR * 6) {
            if ($sa < $HOUR * 1) { // 60 minutes
                $sa2 = intval($sa / 60); if ($sa2 <= 0) $sa2 = 1;
                $target = "{$sa2}m";
            } else {
                $sa2 = intval($sa / $HOUR); if ($sa2 <= 0) $sa2 = 1;
                $target = "{$sa2}h";
            }
        }
        // today
        else if ($ty == $vy && $tm == $vm && $td == $vd) {
            $target = konawiki_lang('Today').konawiki_time($value,'easy');
        }
        else if ($ty == $vy && $tm == $vm && $yd == $vd) {
            $target = konawiki_lang('Yesterday').konawiki_time($value,'easy');
        }
        // 1 week
        else if ($sa <= $DAY * 7) {
            $sa2 = intval($sa / $DAY); if ($sa2 <= 0) $sa2 = 1;
            $target = "{$sa2}d".konawiki_time($value,'easy');
        }
        else if ($ty == $vy && $tm == $vm) {
            $target = ''.$vd.'d'; // Today
        }
        // before 6 month?
        else if ($sa <= $DAY * 31 * 6) {
            $target = "{$vm}/{$vd}";
        }
        else {
            $target = "{$vy}/{$vm}/{$vd}";
        }
    }
    //
    return "<span class='date'>{$target}</span>{$opt}";
}

function konawiki_error($msg, $title = 'Error') {
    $msg = konawiki_lang($msg);
    $title = konawiki_lang($title);
    $r = array(
        'title' => $title,
        'body' => $msg
    );
    include_template("error.html", $r);
}

function konawiki_showMessage($msg)
{
    $r = array('body'=>$msg);
    include_template("form.html", $r);
}
/**
 * @param	{string} page    PageName (Raw Name)
 * @return	{array}  Log from DB or FALSE
 */
function konawiki_getLog($page = FALSE)
{
    if ($page === FALSE) {
        $page = konawiki_getPage();
    }
    $log_id = konawiki_getPageId($page);
    if (!$log_id) {return FALSE;}
    return konawiki_getLogFromId($log_id);
}

/** get tag
 * @arg log_id
 * @return tags array
 */
function konawiki_getTag($log_id = FALSE)
{
    if ($log_id === FALSE) {
        $log_id = konawiki_getPageId();
    }
    $sql = "SELECT * FROM tags WHERE log_id=?";
    $res = db_get($sql, [$log_id]);
    if (!$res) {
        return array();
    }
    $ary = array();
    foreach ($res as $line) {
        $ary[] = $line['tag'];
    }
    return $ary;
}

function konawiki_makeTagLink($tag_str)
{
    $login = konawiki_isLogin_write();
    if ($tag_str == "" && !$login) return "";
    $tags = explode(",", $tag_str);
    // header
    $res = "<div class='taglist'><ul>\n";
    $res.= "<li>Tag:</li>";
    if ($login) {
        $page   = konawiki_getPage();
        $log_id = konawiki_getPageId();
    }
    // tags
    if ($tag_str == "") {
        $res .= "<li>".konawiki_lang('None')."</li>";
    } else {
        foreach ($tags as $tag) {
            if ($tag == "") continue;
            $url = konawiki_getPageURL($tag, 'search', 'tag');
            $tag_ = htmlspecialchars($tag);
            $icon = "";
            if ($login) {
                $del    = konawiki_getPageURL2($page, "edit", "removetag", "tag=".urlencode($tag)."&log_id=".$log_id);
                $icon   = "[<a href='$del'>x</a>]";
            }
            $res .= "<li><a href='{$url}'>{$tag_}</a>{$icon}</li>\n";
        }
    }
    // tag form
    $tagform = "";
    if ($login) {
        $msg_add = konawiki_lang('Add');
        $action = konawiki_getPageURL2(konawiki_getPage(), "edit", "inserttag");
        $form = "<input type='hidden' name='log_id' value='$log_id' />".
            "<input type='text' name='tag' value='' size='8' />".
            "<input type='submit' value='$msg_add'/>";
        $tagform = "<form action='{$action}' method='post'><div>{$form}</div></form>";
    }
    $res .= "</ul></div>{$tagform}<p class='clear'/>\n";
    return $res;
}

function konawiki_getLogFromId($log_id)
{
    global $konawiki_log_cache;
    // check log_id
    if (!is_int($log_id)) {
        $log_id = intval($log_id);
    }
    if ($log_id <= 0) {
        return FALSE;
    }
    // CACHE
    if (empty($konawiki_log_cache)) {
        $konawiki_log_cache = array();
    }
    if (isset($konawiki_log_cache[$log_id])) {
        return $konawiki_log_cache[$log_id];
    }
    //
    $sql = "SELECT * FROM logs WHERE id=? LIMIT 1";
    $log = db_get1($sql, [$log_id]);
    if (!isset($log['id'])) {
        return FALSE;
    }
    // get tag
    $log['tag'] = join(",", konawiki_getTag($log_id));

    // 大きすぎるログはキャッシュしない
    $logsize = 1024 * 30; // 30kb
    if (strlen($log['body']) < $logsize) {
        $konawiki_log_cache[$log_id] = $log;
    }

    return $log;
}

function konawiki_getBackupLog($b_id)
{
    $log_id = konawiki_getPageId();
    $sql = "SELECT * FROM oldlogs WHERE log_id=? AND id=?";
    $res = db_get($sql,[$log_id, $b_id], 'backup');
    if (isset($res[0]['id'])) {
        return $res[0];
    } else {
        return FALSE;
    }
}

/**
 * jump
 * @arg url
 */
function konawiki_jump($url)
{
    header("location: $url");
    exit;
}

function konawiki_getEditToken($force = FALSE)
{
    global $konawiki;
    if (konawiki_isLogin_write() || $force) {
        if (isset($konawiki['private']['edit_token'])) {
            $edit_token = $konawiki['private']['edit_token'];
        } else {
            $edit_token = bin2hex(random_bytes(32));
            $_SESSION['konawiki2_edit_token'] = $edit_token;
            $konawiki['private']['edit_token'] = $edit_token;
        }
    } else {
        $edit_token = 'PleaseLogin';
    }
    return $edit_token;
}

function konawiki_checkEditToken()
{
    $ses_edit_token = isset($_SESSION['konawiki2_edit_token']) ? $_SESSION['konawiki2_edit_token'] : '';
    $get_edit_token = konawiki_param('edit_token', '');
    if (($get_edit_token == '') || (!hash_equals($ses_edit_token,$get_edit_token))) {
        return FALSE;
    }
    return TRUE;
}


function konawiki_getEditMenuArray($pos)
{
    // extract variable
    $page = konawiki_getPage();
    $log = konawiki_getLog($page);
    $freeze = intval(isset($log["freeze"]) ? $log["freeze"] : 0);
    $baseurl = konawiki_public("baseurl");
    $pageurl = konawiki_getPageURL($page);
    $FrontPage = konawiki_public("FrontPage");
    $login_link_visible = konawiki_public("login.link.visible");
    //
    $search = konawiki_getPageURL2($page, "search");
    $new    = konawiki_getPageURL2($page, "new");
    $edit   = konawiki_getPageURL2($page, "edit");
    $attach = konawiki_getPageURL2($page, "attach");
    $logout = konawiki_getPageURL2($page, "logout");
    $login  = konawiki_getPageURL2($page, "login");
    $front  = konawiki_getPageURL2($FrontPage);
    $freeze_url = konawiki_getPageURL2($page, "freeze");
    //
    $label_freeze = ($freeze == 0) ? konawiki_lang('Freeze')
        : konawiki_lang('Unfreeze');
    // login ?
    $menu = array();
    // $menu = array(
    //      array('caption' => xxx, 'href' => xxx),
    //      array('caption' => xxx, 'href' => xxx),
    //      array('caption' => xxx, 'href' => xxx),
    //      array('caption' => xxx, 'href' => xxx),
    // );

    // main menu
    // login menu
    if (konawiki_isLogin_write()) {
        // edit menu
        if ($freeze == 0) {
            $menu[] = array('caption'=>konawiki_lang('Edit'), 'href'=>$edit);
            $menu[] = array('caption'=>konawiki_lang('Attach'), 'href'=>$attach);
        }
        $menu[] = array('caption'=>$label_freeze, 'href'=>$freeze_url);
        $menu[] = array('caption'=>konawiki_lang('New'), 'href'=>$new);
        $menu[] = array('caption'=>konawiki_lang('Logout'), 'href'=> $logout);
        $menu[] = array('caption'=>konawiki_lang('Search'), 'href'=>$search);
    }
    else if (konawiki_isLogin_read()) {
        $menu[] = array('caption'=>konawiki_lang('Search'), 'href'=>$search);
        $menu[] = array('caption'=>konawiki_lang('Logout'), 'href'=> $logout);
    }
    else {
        $menu[] = array('caption'=>konawiki_lang('Search'), 'href'=>$search);
        if ($login_link_visible && $pos == "bottom") {
            $menu[] = array('caption'=>konawiki_lang('Login'),'href'=> $login);
        }
    }
    return $menu;
}

function konawiki_getEditMenu($pos = 'bottom')
{
    $menu = array();
    $menuitems = konawiki_getEditMenuArray($pos);
    if ($pos == 'bottom') {
        foreach ($menuitems as $row) {
            $cap  = $row['caption'];
            $href = $row['href'];
            if ($href == "") {
                $menu[] = " - ";
                continue;
            }
            $menu[] = 
                "<span class='adminmenu'>".
                "<a class='pure-button' href='$href'>$cap</a></span>";
        }
        $ret = join(" ", $menu);
        return $ret;
    } else {
        $menu[] = '<ul>';
        foreach ($menuitems as $row) {
            $cap  = $row['caption'];
            $href = $row['href'];
            if ($href == "") {
                $menu[] = "<li>&nbsp;</li>";
                continue;
            }
            $menu[] = 
                "<li>".
                "<a href='$href'>$cap</a>".
                "</li>";
        }
        $menu[] = '</ul>';
        $ret = join(" ", $menu);
        return $ret;
    }
}


function konawiki_baseurl()
{
    return konawiki_public("baseurl");
}

function konawiki_getEditLink($page = FALSE, $message = FALSE)
{
    if ($page == FALSE) {
        $page = konawiki_getPage();
    }
    if ($message == FALSE) {
        $message = "(" . htmlspecialchars($page) . ")を編集";
    }
    $link = konawiki_getPageURL($page);
    return "【<a href='{$link}/edit'>$message</a>】";
}

/**
 * Write Text to Wiki DB
 * @return {boolean} TRUE or FALSE
 */
function konawiki_writePage($body, &$err, $hash = FALSE, $tag = FALSE, $private = 0) {
    db_begin();
    try {
        $r = _konawiki_writePage(
            $body, $err, $hash, $tag, $private
        );
        if ($r) {
            db_commit();
        } else {
            db_rollback();
        }
        return $r;
    } catch (PDOException $e) {
        db_rollback();
        $err = $e->getMessage();
        print_r($e);
        return FALSE;
    } catch (Exception $e) {
        db_rollback();
        $err = $e->getMessage();
        print_r($e);
        return FALSE;
    }
}

function _konawiki_writePage($body, &$err, $hash = FALSE, $tag = FALSE, $private = 0)
{ 
    // get log
    $page = konawiki_getPage();
    $private = intval($private);
    $log = konawiki_getLog($page);
    $mtime = time();

    // insert or update 
    if ($log == FALSE) {
        // ----------
        // insert
        // ----------
        $sql = 
            "INSERT INTO logs ".
            "      (name,body,ctime,mtime,private)".
            "VALUES(   ?,   ?,    ?,    ?,      ?)";
        $id = db_insert($sql, 
            [$page, $body, $mtime, $mtime, $private]);
        $sql = 
            "INSERT INTO log_counters (id,value) VALUES ".
            "(?, 0)";
        db_exec($sql, [$id]);
        $log['ctime'] = $mtime;
        $log_id = $id;
    } else {
        // -----------
        // update
        // -----------
        // check conflict
        if ($hash !== FALSE) {
            $c_hash = md5($log['body']);
            if ($c_hash !== $hash) {
                $err = "本文が衝突しています。";
                return FALSE;
            }
        }
        // UPDATE LOG
        $id = $log_id = $log['id'];
        $ctime = $log['ctime'];
        if ($ctime == 0) { $ctime = $mtime; }
        $sql = 
            "UPDATE logs".
            "  SET body=?, mtime=?, ctime=?, private=?".
            "  WHERE id=?";
        db_exec($sql, [
            $body, $mtime, $ctime, $private, $id
        ]);

        // ------------------
        // backup log
        // ------------------
        $log_id = $id;
        $recent_time = time() - (1*(60*30)); // before 1 hour
        $sql =
            "SELECT * FROM oldlogs WHERE".
            "  name=? AND mtime>?";
        $res = db_get($sql, [$page, $recent_time], 'backup');
        $ctime2 = $log['ctime'];
        if (isset($res[0]['id'])) {
            $id = $res[0]['id'];
            $sql = "UPDATE oldlogs SET body=?, mtime=? ".
                " WHERE id=?";
            db_exec($sql, [$body, $mtime, $id], 'backup');
        } else {
            $sql = 
                "INSERT INTO oldlogs ".
                "      (log_id, name,body,ctime,mtime)".
                "VALUES(     ?,    ?,   ?,    ?,    ?)";
            db_exec($sql, [
                $log_id, $page, $body, $ctime2, $mtime
            ], 'backup');
        }
        // -----------------------------------------------------
        // キャッシュをクリアする
        // -----------------------------------------------------
        // 現状、被リンク問題(@49)(#87)により
        // 全てのキャッシュをクリアする必要がある
        konawiki_clearCacheDB_All();
    }

    // タグを処理する
    if ($tag !== FALSE) {
        $sql = "SELECT * FROM tags WHERE log_id=? LIMIT 1";
        $r = db_get1($sql, [$log_id]);
        if ($r) {
            $sql = "DELETE FROM tags WHERE log_id=?";
            db_exec($sql, [$log_id]);
        }
        if ($tag != "") {
            $tags = explode(",", $tag);
            foreach ($tags as $w) {
                $w = trim($w);
                $sql = "INSERT INTO tags (log_id, tag)VALUES(?,?)";
                db_exec($sql, [$log_id, $w]);
            }
        }
    }

    // Update FrontPage mtime
    $FrontPage = konawiki_public("FrontPage");
    $sql = "UPDATE logs SET mtime=? WHERE name=?";
    db_exec($sql, [$mtime, $FrontPage]);

    // clear cache
    konawiki_clearCache();

    return TRUE;
}


/**
 * Get wiki contents
 */
function konawiki_getContents($page)
{
    include_once(KONAWIKI_DIR_LIB."/konawiki_parser.inc.php");
    // memory parent page
    $parent_page = konawiki_getPage();
    $parent_pageId = konawiki_getPageId();
    // set page
    $_GET["page"] = $page;
    // render
    $log = konawiki_getLog($page);
    if (isset($log["id"])) {
        $body = $log["body"];
        $body = konawiki_parser_convert($body);
        //
        $_GET["page"] = $parent_page;
        return $body;
    } else {
        $_GET["page"] = $parent_page;
    }
    // edit link
    if ($page == 'GlobBar') { return ""; }
    $url = konawiki_getPageURL($page, 'edit');
    $page_ = htmlspecialchars($page);
    $page_url = rawurlencode($page);
    $baseurl = konawiki_public("baseurl");
    // set back parent page
    $_GET['page'] = $_POST['page'] = $_GET['DEF_PAGE'];
    return <<<__EOS__
        <p>[<a href="{$url}">{$page_}?</a>]</p>
        __EOS__;
}

function konawiki_swapRawText($pattern, $ins_str, $swapmode = TRUE, $pid = 1)
{
    # insert to raw text
    $text = konawiki_getRawText();
    $text_ary = explode("\n", $text);
    $res = "";
    $skip = FALSE;
    $id = 0;
    foreach($text_ary as $line) {
        # check block
        if ($skip === TRUE) {
            if ($line === "}}}") {
                $skip = FALSE;
            }
            $res .= $line . "\n";
            continue;
        }
        if ($line == "{{{") {
            $skip = TRUE;
            $res .= $line . "\n";
            continue;
        }
        # found plug-ins
        if (preg_match($pattern,$line)) {
            $id++;
            if ($id == $pid) {
                $res .= $ins_str."\n";
                if ($swapmode == FALSE) {
                    $res .= $line."\n";
                }
                continue;
            }
        }
        $res .= $line . "\n";
    }
    $res = rtrim($res);
    return $res;
}

function konawiki_clearCache()
{
    global $konawiki_page_cache;
    global $konawiki_pagename_cache;
    global $konawiki_log_cache;

    // in memory
    $konawiki_page_cache = array();
    $konawiki_pagename_cache = array();
    $konawiki_log_cache = array();

}

/**
 * Cache DB の指定のページをクリアする($logid を省略すると表示中のページについてクリアす)
 * @param $log_id
 * @return void
 */
function konawiki_clearCacheDB($log_id = FALSE)
{
    if ($log_id == FALSE) {
        $page = konawiki_getPage();
        $log = konawiki_getLog($page);
        if (empty($log["id"])) return;
        $log_id = $log["id"];
    }
    $sql = "DELETE FROM cache_logs WHERE log_id=?";
    db_exec($sql, [$log_id], "backup");
}
/**
 * Cache DB の全てのページをクリアする
 * @return void
 */
function konawiki_clearCacheDB_All()
{
    $sql = "DELETE FROM cache_logs";
    db_exec($sql, [], "backup");
}

/**
 * ハッシュを利用したパスワードの照合
 * @param $user
 * @param $pass
 * @return bool
 */
function konawiki_checkPassword($user, $pass)
{
    $type = "";
    if (preg_match('#^\{([a-z0-9]+)\}(.+)$#', $pass, $m)) {
        $type = $m[1];
        $pass = $m[2];
    }
    if ($type == "md5" || $type == "x-php-md5") {
        $hash = md5($user);
    }
    else if ($type == "sha1" || $type == "x-php-sha1") {
        $hash = sha1($user);
    }
    else {
        $hash = $user;
    }
    return (hash_equals($hash, $pass));
}

/**
 * プラグインに関する情報を返す
 */
function konawiki_parser_getPlugin($pname)
{
    $plugins_disable = konawiki_private('plugins.disable');
    $disable = isset($plugins_disable[$pname]) ? $plugins_disable[$pname] : FALSE;

    // Sanitize path
    $pname = str_replace('/', '', $pname);
    $pname = str_replace('.', '', $pname);

    $dir = KONAWIKI_DIR_PLUGINS;
    $pname_url = urlencode($pname);
    $pname_fun = str_replace('%','', $pname_url);	
    $res = array(
        "file"    => "{$dir}/{$pname_url}.inc.php",
        "init"    => "plugin_{$pname_fun}_init",
        "action"  => "plugin_{$pname_fun}_action",
        "convert" => "plugin_{$pname_fun}_convert",
        "disable" => $disable,
    );
    return $res;
}

/**
 * return language data
 */
function konawiki_lang($key, $def = null)
{
    global $ko_lang;
    if (isset($ko_lang[$key])) {
        return $ko_lang[$key];
    }
    if ($def == null) $def = $key;
    return $def;
}

function lang($key, $def = null)
{
    return konawiki_lang($key, $def);
}

function konawiki_getKeywords($page, $rawtag = "") {
    $a = array();
    $a[] = konawiki_public('title');
    if (konawiki_public('keywords')) {
        $a[] = konawiki_public('keywords');
    }
    if ($page != konawiki_public("FrontPage")) {
        $a[] = $page;
    }
    if ($rawtag) $a[] = $rawtag;
    return implode(",", $a);
}

function konawiki_isSystemPage($page) {
    $pages = konawiki_private('system.pages.array', []);  
    if (!$pages) {
        // convert to array
        $s = konawiki_private('system.pages', '');
        $sa = explode(',', $s);
        foreach ($sa as $name) {
            $name = trim($name);
            $pages[$name] = TRUE; 
        }
        konawiki_addPrivate('system.pages.array', $pages);
    }
    if (isset($pages[$page])) {
        return TRUE;
    }
    return FALSE;
}
