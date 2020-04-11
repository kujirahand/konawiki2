<?php
/**
 * Show JavaScript file
 */
function action_js_()
{
    // get special JavaScript file
    $page = konawiki_getPage();
    // check file name
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $page)) {
        echo '[ERROR] file not found';
        exit;
    }
    $file = KONAWIKI_DIR_DEF_RES.'/'.$page;
    if (!file_exists($file)) {
      echo "[error] file not found";
      exit;
    } 
    // show text
    header("Content-Type:text/javascript; charset=UTF-8");
    include_once $file;
}

