<?php
//----------------------------------------------------------------------
// HTML COMMON HEADER FILE
//----------------------------------------------------------------------
$log_id     = konawiki_getPageId();
$baseuri    = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
$shorturi   = "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']
              . "?{$log_id}&amp;go"; 
// check skin & theme
$skin     = konawiki_public("skin", "default");
$skin_css = getResourceURL("skin.css",false);
$theme    = konawiki_public("skin.theme", false);
if ($theme) $theme_css = getThemeURL("{$theme}.css");

// logo & favicon.ico
$logo    = konawiki_public("logo",    "logo.png");
$logo    = getResourceURL($logo);
$favicon = konawiki_public("favicon", "favicon.ico");
$favicon = getResourceURL($favicon);
// navibar
$navibar_log = konawiki_getLog('NaviBar');
if (isset($navibar_log["body"])) {
	$navibar = konawiki_parser_convert($navibar_log["body"], false);
} else {
	$navibar = false;
}
//----------------------------------------------------------------------
// check title

// for search page
$action = $konawiki['public']['action'];
if ($action == "search") { // no page link
  $page = 'search';
  $pagelink = 'search';
}
//
$pagetitle = "$page - $title";
if ($page == konawiki_public("FrontPage", "FrontPage")) {
  $pagetitle = "$title";
}
$ogdesc = "{$pagetitle}";

// og:image
$ogimage = getResourceURL(konawiki_public('ogimage','logo-large.png'));
$ogimage = konawiki_public("og:image", $ogimage);
$ogtype = konawiki_public("og:type", "website");

//----------------------------------------------------------------------
// addtional JS/CSS
$include_js_css = "";
$_list = konawiki_private("html.head.include", array());
foreach ($_list as $line) {
    $include_js_css .= "    " . $line . "\n";
}
//----------------------------------------------------------------------
// put  header
if (empty($rawtag)) $rawtag = "";
if ($action == "simple") {
}
else if (useragent_is_smartphone()) {
	include(getSkinPath('parts_header_iphone.tpl.php'));
} else {
	include(getSkinPath('parts_header_pc.tpl.php'));
}
//----------------------------------------------------------------------


