<?php
//----------------------------------------------------------------------
// HTML COMMON HEADER FILE
//----------------------------------------------------------------------
$log_id     = konawiki_getPageId();
$protocol   = empty($_SERVER["HTTPS"]) ? "http://" : "https://";
$baseuri    = $protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
$shorturi   = $protocol.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']
              . "?{$log_id}&amp;go"; 
// check skin & theme
$skin_css  = 'skin.css';
$theme     = konawiki_public("skin.theme", false);
$theme_css = false;
if ($theme) { $theme_css = getThemeURL("{$theme}.css"); }

// logo & favicon.ico
$logo    = konawiki_public("logo", "logo.png");
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
$include_list = konawiki_private("html.head.include", false);
if ($include_list) {
  $include_js_css = "";
  foreach ($include_list as $line) {
      $include_js_css .= "    " . $line . "\n";
  }
}
//----------------------------------------------------------------------
// put  header
if (empty($rawtag)) $rawtag = "";
if ($action == "simple") {
}
else {
  include(getSkinPath('parts_header_pc.tpl.php'));
}
//----------------------------------------------------------------------


