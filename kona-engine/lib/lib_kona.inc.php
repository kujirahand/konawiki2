<?php
#vim:set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
/**
 * ---------------------------------------------------------------------
 * konawiki の基本ライブラリ
 * ---------------------------------------------------------------------
 */

// データベース関連のライブラリを取り込む
require_once dirname(__FILE__).'/konawiki_db.inc.php';
// 認証関連のライブラリを取り込む
require_once dirname(__FILE__).'/konawiki_auth.inc.php';

//----------------------------------------------------------------------
/**
 * check GPC --- $_GET and $_POST and $_COOKIE
 */
if(get_magic_quotes_gpc()){
	$_GET       = array_map("strip_text_slashes",$_GET);
	$_POST      = array_map("strip_text_slashes",$_POST);
	$_COOKIE    = array_map("strip_text_slashes",$_COOKIE);
}
function strip_text_slashes($arg){
	if(!is_array($arg)){
		$arg = stripslashes($arg);
	}elseif(is_array($arg)){
		$arg = array_map("strip_text_slashes",$arg);
	}
	return $arg;
}
//----------------------------------------------------------------------

/**
 * URIパラメータを解析してグローバル変数にセットする
 * @return void
 */
function konawiki_parseURI()
{
	// p1  : /path/page/NAME/ACTION/STAT?param=xxx
	// p2  : /path/page/NAME?action=action&param=xxx
	// p3  : /path/index.php?NAME/ACTION/STAT&param=xxx
	// p4  : /path/index.php?NAME&action=ACTION&stat=STAT&param=xxx
	// p5  : /path/
	
	// PATH_INFO で処理するかどうか
	if (!defined("KONAWIKI_USE_PATH_INFO")) {
	    define("KONAWIKI_USE_PATH_INFO", FALSE);
	    $scriptname = "index.php";
	}
	$host   = $_SERVER['HTTP_HOST'];
	$uri    = $_SERVER['REQUEST_URI'];
	
	// DIR + SCRIPT + PARAM
	if (preg_match("#^(.*?){$scriptname}[\/\?]?(.*)$#", $uri, $m)) {
	    $dir   = $m[1];
	    $param = $m[2];
	}
	else if (preg_match('#^(.*?)(\?.*)$#', $uri, $m)) {
	    $dir    = $m[1];
	    $param  = $m[2];
	}
	else if (!preg_match("#{$scriptname}#", $uri)) { // SCRIPT省略
	    $dir = $uri;
	    $param = "";
	}
	else {
	    echo "想定外のURI:".$uri;
	    exit;
	}
	// flag
	$flag = (KONAWIKI_USE_PATH_INFO) ? "/" : "?";
	
	//--------------------------------------------------------------
	// get PATH_INFO
	$c = substr($param, 0, 1);
	if ($c == "?" || $c == "/") {
	    $param = substr($param, 1);
	}
	// set path
	$query = "";
	$path_args = array();
	$a = preg_split('#[\/\?\&]#', $param);
	foreach($a as $p) {
	    if (strpos($p, "=") !== FALSE) {
	        list($key,$val) = explode("=", $p);
	    } else {
	        $key = $p;
	        $val = NULL;
	    }
      $key = urldecode($key);
      $val = urldecode($val);
	    if ($val == NULL) {
	        $path_args[] = $key;
	    }
	    else {
	        $_GET[$key] = $val;
	    }
	}
	// push dummy
	array_push($path_args, FALSE, FALSE, FALSE); // set dummy params
	
	// Set default value
	// page
	if (konawiki_param('page', FALSE) === FALSE) {
	    $FrontPage = konawiki_public('FrontPage');
	    $_GET['page'] = $path_args[0] ? $path_args[0] : $FrontPage;
	}
	// action
	if (konawiki_param('action', FALSE) === FALSE) {
	    $_GET['action'] = $path_args[1] ? $path_args[1] : 'show';
	}
	// stat
	if (konawiki_param('stat', FALSE) === FALSE) {
	    $_GET['stat'] = $path_args[2] ? $path_args[2] : '';
	}
	// file ?
  if (isset($GET["action"])) {
      if ($_GET["action"] == "file") {
          $_GET["page"] = $_GET["stat"];
          $_GET["stat"] = "";
      }
  }
	if (isset($_GET['page'])) {
	    // for - AllowEncodedSlashes Off
	    $_GET['page'] = str_replace('%252F', '/', konawiki_param('page'));
	    $_GET['page'] = str_replace('%26', '&', konawiki_param('page'));
	    $_GET['page'] = str_replace('%2F', '/', konawiki_param('page'));
	    $_GET['stat'] = str_replace('%252F', '/', konawiki_param('stat'));
	    $_GET['stat'] = str_replace('%26', '&', konawiki_param('stat'));
	    $_GET['stat'] = str_replace('%2F', '/', konawiki_param('stat'));
	}
	
	// baseuri
	$baseurl  = "http://{$host}{$dir}{$scriptname}{$flag}"; // BASE URI
	konawiki_addPublic('baseurl', $baseurl);
	konawiki_addPublic('scriptname', $scriptname);
	
	// set action and status params
	global $konawiki;
	$page   = konawiki_param('page');
	$action = konawiki_param('action');
	$stat   = konawiki_param('stat');
	
	$konawiki['public']['page']     = htmlspecialchars($page);
	$konawiki['public']['page_raw'] = $page;
	$konawiki['public']['action']   = htmlspecialchars($action);
	$konawiki['public']['stat']     = htmlspecialchars($stat);
	$konawiki['public']['pagelink'] = konawiki_getPageLink($page,"dir");
	$keyword = "[[".urlencode($page)."]]";
	$konawiki['public']['backlink'] = konawiki_getPageURL($page, "backlink", "");
	
	// set resource url
	$res = $konawiki['public']['resourceurl'] = 
	    dirname($baseurl)."/skin/default/resource";
	$rss_uri = konawiki_getPageURL("get","rss2");
	$rss_gif = getResourceURL("img/rss.gif");
	$konawiki['public']['rsslink'] = 
	    "<a href='{$rss_uri}'><img src='{$rss_gif}' alt='RSS'/></a>";
	
}

/**
 * ディレクトリを設定し初期化する
 * @return void
 */
function konawiki_init()
{
  global $konawiki;
	// set directory path
	require_once dirname(__FILE__).'/path.ini.php';
	require_once(KONAWIKI_DIR_LIB.'/konadb/konadb.inc.php');
	require_once(KONAWIKI_DIR_LIB.'/html.inc.php');
	require_once(KONAWIKI_DIR_LIB.'/konawiki_parser.inc.php');
	require_once(KONAWIKI_DIR_LIB.'/useragent.inc.php');

  // init config
  konawiki_include_config_file();
  konawiki_start_session();
  konawiki_parseURI();
  // Initialize Database
  konawiki_auth_read();
  konawiki_initDB();
  konawiki_execute_action();
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
  $func   = "action_{$action}_{$stat}";
  if (file_exists($module)) {
      require_once($module);
      if (is_callable($func)) {
          call_user_func($func);
      }
      else {
          echo "<div>PAGE NOT FOUND</div>";
      }
  } else {
      echo "not found";
  }
}


/**
 * ユーザー設定ファイルを読み込む
 * @return void
 */
function konawiki_include_config_file()
{
  global $konawiki;
	// include user setting
	if (!file_exists('konawiki.ini.php')) { // test mode
	    // test directory
	    check_is_writable(KONAWIKI_DIR_DATA);
	    check_is_writable(KONAWIKI_DIR_ATTACH);
	}
  if (!konawiki_public('config.loaded.default', FALSE)) {
    konawiki_error(
      'konawiki.ini.php の書式が変わりました。'.
      'temp-konawiki.ini.php を元に修正してください。');
    exit;
  }

  // Timezone
  @date_default_timezone_set( konawiki_public('timezone', 'Asia/Tokyo') );
  // echo date_default_timezone_get(); // test timezone

  // language support
  $lang = konawiki_public('lang', 'en');
  $path = konawiki_private('dir.engine', '..')."/lang/{$lang}.inc.php";
  if (file_exists($path)) include_once($path);

	// テーマ機能を使うか ?
	if (konawiki_public("skin.theme") !== "") {
	    $skin = konawiki_public("skin");
	    $theme_init = KONAWIKI_DIR_SKIN . "/$skin/theme.inc.php";
	    if (file_exists($theme_init)) {
	        include_once($theme_init);
	    }
	}
}

function check_is_writable($dir)
{
    if (!is_writable($dir)) {
    	@chmod($dir, 0777);
    	if (!is_writable($dir)) {
    		echo '<div style="color:red">[ERROR] The directory is not wriable. : '.$dir.'</div>';
    		exit;
    	}
    }
}

function konawiki_insert_jquery()
{
	$uri = getResourceURL('js/jquery-1.2.6.min.js');
	return '<script type="text/javascript" src="'.$uri.'"></script>';
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
  global $konawiki;
  $konawiki['private']['debug'] = $value;
}

/**
 * show debug info
 */
function konawiki_page_debug()
{
	global $konawiki;
	if (!konawiki_is_debug()) return;
	extract($konawiki['public']);
	//$db = konawiki_getDB();
	//$db = konawiki_getSubDB();
	//$db = konawiki_getBackupDB();
	echo '<pre>';
	echo '【テストモードです--ReadMe.txtをご覧ください。】'."\n";
	//echo "url:"; print_r($_SERVER);
	echo "scriptname:$scriptname\n";
	echo "baseurl:".konawiki_public("baseurl")."\n";
	echo "page  :$page\n";
	echo "action:$action\n";
	echo "stat  :$stat\n";
  echo "sesseion: ";
  $s = print_r($_SESSION, true);
  echo htmlspecialchars($s);
	echo "konawiki: ";
  $s = var_dump($konawiki, true);
  echo htmlspecialchars($s);
	//print_r($_SERVER);
}


function konawiki_param($name, $def_value = FALSE)
{
	if (isset($_POST[$name])) {
		return $_POST[$name];
	}
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
  if (empty($public[$name])) {
    return $def;
  }
  return $public[$name];
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
		$page = konawiki_param("page");
	}
	$page_enc = rawurlencode($page);
	// for - AllowEncodedSlashes Off
	$page_enc = str_replace('%2F','%252F',$page_enc);
	$baseurl = konawiki_public("baseurl");
	if ($shortpath) {
		$name = basename($baseurl);
		$name .= (KONAWIKI_USE_PATH_INFO) ? "/" : "";
		$url = "{$name}{$page_enc}";
	} else {
		$url = "{$baseurl}{$page_enc}";
	}
	// action & stat
	if ($action || $stat) {
		if ($action == FALSE) { $action = "show"; }
		if (KONAWIKI_USE_PATH_INFO == TRUE) {
			$url .= "/$action";
		} else {
			$url .= "&amp;$action";
		}
	}
	if ($stat) {
		$stat = rawurlencode($stat);
		// for - AllowEncodedSlashes Off
		$stat = str_replace('%2F','%252F',$stat);
		if (KONAWIKI_USE_PATH_INFO == TRUE) {
			$url .= "/$stat";
		} else {
			$url .= "&amp;$stat";
		}
	}
	if ($param_str) {
		if (KONAWIKI_USE_PATH_INFO == TRUE) {
			$url .= "?$param_str";
		} else {
			$url .= "&amp;$param_str";
		}
	}
	return $url;
}

function konawiki_getPageURL2($page = FALSE, $action = FALSE, $stat = FALSE, $param_str = FALSE)
{
	return konawiki_getPageURL($page, $action, $stat, $param_str, TRUE);
}

function konawiki_getPageLink($page = FALSE, $mode = "normal", $caption = FALSE, $paramstr = FALSE)
{
	if ($page === FALSE) {
		$page = konawiki_param("page");
	}

	$page_ = htmlspecialchars($page);
	if ($mode == "normal") {
		if ($caption == FALSE) {
			$caption = $page_;
		}
		$url = konawiki_getPageURL($page,false, false, $paramstr);
		$html = "<a href='{$url}{$paramstr}'>{$caption}</a>";
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
			$last_ = htmlspecialchars($last);
			$links[] = "<a href='{$url}{$paramstr}'>$last_</a>";
		}
		$html = join("/",$links);
	}
	return $html;
}

function konawiki_getPage()
{
	return konawiki_param("page");
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
	
	$db = konawiki_getDB();
	$page_ = $db->escape($page);
	$sql = "SELECT id FROM logs WHERE name='$page_' LIMIT 1";
	$log = $db->array_query($sql);
	if ($log == FALSE) {
		return FALSE;
	}
	$log_id = isset($log[0]['id']) ? $log[0]['id'] : FALSE;
	$konawiki_page_cache[$page] = $log_id; // save cache
	return $log_id;
}

function konawiki_getPageNameFromId($log_id)
{
	global $konawiki_pagename_cache;
	
	if (empty($konawiki_pagename_cache)) {
		$konawiki_pagename_cache = array();
	}
	if (isset($konawiki_pagename_cache[$log_id])) {
		return $konawiki_pagename_cache[$log_id];
	}
	
	$log_id = intval($log_id);
	$db = konawiki_getDB();
	$sql = "SELECT name FROM logs WHERE id=$log_id";
	$log = $db->array_query($sql);
	if ($log == FALSE) {
		return FALSE;
	}
	$konawiki_pagename_cache[$log_id] = $name = isset($log[0]['name']) ? $log[0]['name'] : FALSE;
	if ($name == FALSE) { unset($konawiki_pagename_cache[$log_id]); }
	return $name;
}

function konawiki_resourceurl()
{
	return konawiki_public('resourceurl');
}

function konawiki_query($sql)
{
	$db = konawiki_getDB();
	return $db->array_query($sql);
}

function include_template($fname, $vars = FALSE)
{
	global $konawiki;
	// check skin
	$skin = $konawiki['public']['skin'];
	$path = KONAWIKI_DIR_SKIN."/{$skin}/{$fname}";
	if (!file_exists($path)) {
		$skin = "default";
		$path = KONAWIKI_DIR_SKIN."/{$skin}/{$fname}";
		if (!file_exists($path)) {
			$path = KONAWIKI_DIR_TEMPLATE . '/' . $fname;
		}
	}
	// extract variable
	extract($konawiki['public']);
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
			$path = KONAWIKI_DIR_TEMPLATE . '/' . $fname;
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
function getResourceURL($fname)
{
	// check skin dir
	// SKIN DIR
	$skin = konawiki_public("skin");
	$path = KONAWIKI_DIR_SKIN."/{$skin}/resource/{$fname}";
	$uri  = KONAWIKI_URI_SKIN."/{$skin}/resource/{$fname}";
	if (!file_exists($path)) {
		// DEFAULT SKIN DIR
		$skin = "default";
		$path = KONAWIKI_DIR_SKIN."/{$skin}/resource/{$fname}";
	  $uri  = KONAWIKI_URI_SKIN."/{$skin}/resource/{$fname}";
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
	$fmt = konawiki_private('date_format');
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
function konawiki_time($value,$mode = 'normal')
{
	$fmt = konawiki_private('time_format');
	return date($fmt, $value);
}

// mode = normal, easy
function konawiki_date_html($value,$mode='easy')
{
	if ($value === 0) {
		return "@";
	}
	if (is_int($value)) {
		$target = konawiki_date($value);
	} else {
		$target = $value;
	}
	if ($mode == 'easy') {
		return konawiki_datetime_html($value, 'easy');
	}
	$opt    = "";
	//
	$old = time() - 60 * 60 * 24 /* hour */;
	$fmt = konawiki_date($old);
	if ($fmt <= $target) {
		{
			$today = konawiki_date(time());
			if ($target == $today) {
				$target = konawiki_lang('Today');
			}
		}
		$opt = " <span class='new'>New!</span>";
	}
	//
	return "<span class='date'>{$target}</span>{$opt}";
}

function konawiki_datetime($value)
{
	$fmt1 = konawiki_private('date_format');
	$fmt2 = konawiki_private('time_format');
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
	if (is_int($value)) {
		$target = konawiki_datetime($value);
	} else {
		$target = $value;
		$value = konawiki_decode_datetime($value);
	}
	$opt    = "";
	//
	$old = time() - 60 * 60 * 24 /* hour */;
	$fmt = konawiki_datetime($old);
	if ($fmt <= $target) {
		$today = konawiki_date(time());
		$s     = konawiki_date($value);
		if ($today == $s) {
			$target = konawiki_lang('Today')." ".konawiki_time($value);
		}
		$opt = " <span class='new'>New!</span>";
	}
	if ($mode == 'easy') {
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

function konawiki_error($msg)
{
  $msg = konawiki_lang($msg);
	// show debug info
	$is_debug = konawiki_is_debug();
	if ($is_debug) {
		echo "<div id='wikimessage'><p>[Debug log]</p><pre>";
		// print_r(debug_backtrace());
		$db = konawiki_getDB();
		echo "\nSQL_LOG:\n".$db->sql_logs;
		echo "</pre></div>";
	}
	// show template
	$r = array('body'=>$msg);
	include_template("error.tpl.php", $r);
}

function konawiki_showMessage($msg)
{
	$r = array('body'=>$msg);
	include_template("form.tpl.php", $r);
}
/**
 * @param	{string} page    PageName (Raw Name)
 * @return	{array}  Log from DB or FALSE
 */
function konawiki_getLog($page = FALSE, $tablename = "logs")
{
	global $konawiki_log_cache;
	
	if ($page === FALSE) {
		$page = konawiki_getPage();
	}
	$log_id = konawiki_getPageId($page);
	if (!$log_id) {
		return FALSE;
	}
  $log = konawiki_getLogFromId($log_id, $tablename);
  return $log;
}

/** get tag
 * @arg log_id
 * @return tags array
 */
function konawiki_getTag($log_id = FALSE)
{
	$db = konawiki_getDB();
	if ($log_id === FALSE) {
		$log_id = konawiki_getPageId();
	}
	$sql = "SELECT * FROM tags WHERE log_id=$log_id";
	$res = $db->array_query($sql);
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

function konawiki_getLogFromId($log_id, $tablename = "logs")
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
	$db = konawiki_getDB();
	$sql = "SELECT * FROM {$tablename} WHERE id={$log_id} LIMIT 1";
	$res = $db->array_query($sql);
	if (!isset($res[0]['id'])) {
		return FALSE;
	}
	$konawiki_log_cache[$log_id] = $log = $res[0];
	// get tag
	$log['tag'] = join(",", konawiki_getTag($log_id));
	return $log;
}

function konawiki_getBackupLog($b_id)
{
	$db = konawiki_getBackupDB();
	$log_id = konawiki_getPageId();
	$b_id = $db->escape($b_id);
	$sql = "SELECT * FROM oldlogs WHERE log_id=$log_id AND id=$b_id";
	$res = $db->array_query($sql);
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


function konawiki_getEditMenuArray($pos)
{
	// extract variable
	$page = konawiki_getPage();
  $log = konawiki_getLog($page);
  $freeze = intval($log["freeze"]);
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
	// $menu[] = array('caption'=>'トップ', 'href'=>$front);
	$menu[] = array('caption'=>konawiki_lang('Search'),   'href'=>$search);
	// login menu
	if (konawiki_isLogin_write()) {
		// edit menu
		$menu[] = array('caption'=>'-',    'href' => '');
		$menu[] = array('caption'=>konawiki_lang('New'), 'href'=>$new);
    if ($freeze == 0) {
		  $menu[] = array('caption'=>konawiki_lang('Edit'), 'href'=>$edit);
    }
		$menu[] = array('caption'=>$label_freeze, 'href'=>$freeze_url);
		$menu[] = array('caption'=>'-',    'href' => '');
		$menu[] = array('caption'=>konawiki_lang('Attach'), 'href'=>$attach);
		$menu[] = array('caption'=>'-',    'href'=> '');
		$menu[] = array('caption'=>konawiki_lang('Logout'), 'href'=> $logout);
	}
  else if (konawiki_isLogin_read()) {
		$menu[] = array('caption'=>'-',    'href'=> '');
		$menu[] = array('caption'=>konawiki_lang('Logout'), 'href'=> $logout);
    }
	else {
		if ($login_link_visible && $pos == "bottom") {
			$menu[] = array('caption'=>'-',    'href'=> '');
			$menu[] = array('caption'=>konawiki_lang('Login'),'href'=> $login);
		}
	}
	return $menu;
}

function konawiki_getEditMenu($pos = 'bottom')
{
	$menu = array();
	$menuitems = konawiki_getEditMenuArray($pos);
	foreach ($menuitems as $row) {
		$cap    = $row['caption'];
		$href   = $row['href'];
		if ($href == "") {
			$menu[] = " - ";
			continue;
		}
		$menu[] = "<span class='adminmenu'><a href='$href'>[$cap]</a></span>";
	}

	$ret = join(" ", $menu);
	return $ret;
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
 * @return      TRUE or FALSE
 */
function konawiki_writePage($body, &$err, $hash = FALSE, $tag = FALSE, $private = 0)
{
  // clear cache
	konawiki_clearCache();
	// prepare database
	$db = konawiki_getDB();
	$page = konawiki_getPage();
	$page_ = $db->escape($page);
	$body_ = $db->escape($body);
  $private = intval($private);
	// update or insert
	$log = konawiki_getLog($page);
	$mtime = time();
	$db->begin();
	if ($log == FALSE) {
		// insert
		$sql = "INSERT INTO logs (name,body,ctime,mtime,private)".
            " VALUES ('{$page_}','{$body_}',$mtime,$mtime,$private)";
		if (!$db->exec($sql)) {
			$db->rollback();
			$err = "ログの保存に失敗しました。";
			return FALSE;
		}
		$id = $db->getLastId();
		$sql = "INSERT INTO log_counters (id,value) VALUES ".
            "($id,0)";
		if (!$db->exec($sql)) {
			$db->rollback();
			$err = "ログの保存に失敗しました。";
			return FALSE;
		}
		$log['ctime'] = $mtime;
		$log_id = $id;
	} else {
		// check conflict
		if ($hash !== FALSE) {
			$c_hash = md5($log['body']);
			if ($c_hash !== $hash) {
				$db->rollback();
				$err = "本文が衝突しています。";
				return FALSE;
			}
		}
		// UPDATE LOG
		$id = $log['id'];
		$ctime = $log['ctime'];
		if ($ctime == 0) { $ctime = $mtime; }
		$sql = "UPDATE logs SET body='{$body_}',mtime=$mtime,ctime=$ctime,private=$private ".
            "WHERE id=$id";
		$res = $db->exec($sql);
		if (!$res) {
			$db->rollback();
			$err = "ログの保存に失敗しました。";
			return FALSE;
		}
		/*
		 * ログのバックアップ
		 * 最近のログをチェック
		 */
		$log_id = $id;
		$recent_time = time() - (1*(60*30)); // before 1 hour
		$backup_db = konawiki_getBackupDB();
		$page_ = $backup_db->escape($page);
		$sql = "SELECT * FROM oldlogs WHERE name='$page_'".
            " AND mtime > $recent_time";
		$res = $backup_db->array_query($sql);
		$body_ = $backup_db->escape($log['body']);
		$mtime = $log['mtime'];
		$ctime = $log['ctime'];
		if (isset($res[0]['id'])) {
			$id = $res[0]['id'];
			$sql = "UPDATE oldlogs SET body='$body_', mtime=$mtime ".
                " WHERE id=$id";
		}
		else {
			$sql = "INSERT INTO oldlogs (log_id, name,body,ctime,mtime)".
                "VALUES($log_id,'$page_','$body_',$ctime,$mtime)";
		}
		$res = $backup_db->exec($sql);
		if (!$res) {
			$db->rollback();
			$err = "バックアップログの保存に失敗しました。";
			return FALSE;
		}
		// -----------------------------------------------------
		// キャッシュをクリアする
		// -----------------------------------------------------
		// 普通にキャッシュをクリア
		if ($backup_db) {
			// 現状、被リンク問題(@49)(#87)により、全てのキャッシュをクリアする必要がある
			/*
			$r = $backup_db->array_query("SELECT log_id FROM cache_logs WHERE log_id=$log_id");
		    if (isset($r[0]['log_id'])) {
			    @$backup_db->exec("DELETE FROM cache_logs WHERE log_id=$log_id");
		    }
		    */
			$r = $backup_db->array_query("SELECT log_id FROM cache_logs LIMIT 1");
		    if ($r) {
			    $backup_db->exec("DELETE FROM cache_logs");
		    }
		}
	}
	// タグを処理する
	if ($tag !== FALSE) {
		$r = $db->array_query("SELECT * FROM tags WHERE log_id={$log_id} LIMIT 1");
		if ($r) {
			$sql = "DELETE FROM tags WHERE log_id={$log_id}";
			if (!($db->exec($sql))) {
				$err = "タグの保存準備に失敗しました。";
				$db->rollback();
				return FALSE;
			}
		}
		if ($tag != "") {
			$tags = explode(",", $tag);
			foreach ($tags as $w) {
				$w = trim($w);
				$w_ = $db->escape($w);
				$sql = "INSERT INTO tags (log_id, tag)VALUES({$log_id}, '{$w_}')";
				if (!($db->exec($sql))) {
					$err = "タグの保存に失敗しました。".$sql;
					$db->rollback();
					return FALSE;
				}
			}
		}
	}
	//
	$db->commit();
	return TRUE;
}


/**
 * Get wiki contents
 */
function konawiki_getContents($page)
{
	include_once(KONAWIKI_DIR_LIB."/konawiki_parser.inc.php");
	$log = konawiki_getLog($page);
	if (isset($log["id"])) {
		$body = $log["body"];
		$body = konawiki_parser_convert($body);
		return $body;
	}
	$page_ = htmlspecialchars($page);
	$page_url = rawurlencode($page);
	$baseurl = konawiki_public("baseurl");
	return <<<__EOS__
<p>[<a href="{$baseurl}{$page_url}/edit">{$page_}?</a>]</p>
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

/**
 * テンプレートファイルを返す
 */
function konawiki_template($filename)
{
	$f = KONAWIKI_DIR_TEMPLATE . "/" . $filename;
	return $f;
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
	$back_db = konawiki_getBackupDB();
	@$back_db->exec("DELETE FROM cache_logs WHERE log_id=$log_id");
}
/**
 * Cache DB の全てのページをクリアする
 * @return void
 */
function konawiki_clearCacheDB_All()
{
	$back_db = konawiki_getBackupDB();
	@$back_db->exec("DELETE FROM cache_logs");
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
  return ($hash == $pass);
}

/**
 * プラグインに関する情報を返す
 */
function konawiki_parser_getPlugin($pname)
{
    $dir = KONAWIKI_DIR_PLUGINS;
    $pname_url = urlencode($pname);
    $pname_fun = str_replace('%','', $pname_url);
    $res = array(
        "file"    => "{$dir}/{$pname_url}.inc.php",
        "init"    => "plugin_{$pname_fun}_init",
        "action"  => "plugin_{$pname_fun}_action",
        "convert" => "plugin_{$pname_fun}_convert",
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




