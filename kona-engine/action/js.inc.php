<?php
/**
 * Show JavaScript file
 */
function action_js_()
{
    // get special JavaScript file
    $page = konawiki_getPage();
    $file = KONAWIKI_DIR_JS.'/'.$page;
    if (!file_exists($file)) {
      echo "[error] file not found";
      exit;
    } 
    // show text
    header("Content-Type:text/javascript; charset=UTF-8");
    include_once $file;
}

