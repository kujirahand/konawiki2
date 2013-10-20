<?php
/**
 * パス情報の設定 (Set path info)
 */

global $private;

//---------------------------------------------------------
// + common wiki
// | - private area
$engineDir = $private['dir.engine'];
define("KONAWIKI_DIR_LIB",      $engineDir."/lib");
define("KONAWIKI_DIR_ACTION",   $engineDir."/action");
define("KONAWIKI_DIR_TEMPLATE", $engineDir."/template");
define("KONAWIKI_DIR_JS",       $engineDir."/js");
define("KONAWIKI_DIR_PLUGINS",  $engineDir."/plugins");
define("KONAWIKI_DIR_HELP",     $engineDir."/help");
// | - public area
define("KONAWIKI_DIR_SKIN",     $private['dir.skin']);
define("KONAWIKI_URI_SKIN",     $private['uri.skin']);

//---------------------------------------------------------
// + branch wiki
// | - private area
define("KONAWIKI_DIR_DATA",     $private['dir.data']);
// | - public area
define("KONAWIKI_DIR_BASE",     $private['dir.base']);
define("KONAWIKI_DIR_ATTACH",   $private['dir.attach']);
define("KONAWIKI_URI_ATTACH",   $private['uri.attach']);


