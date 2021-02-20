<?php
/**
 * ページの表示アクション
 */
function action_css_()
{
    // get body
    $page = konawiki_getPage();
    $log = konawiki_getLog($page);
    if ($log == FALSE) {
        header("HTTP/1.0 404 Not Found");
        echo "/* 404 CSS Not Found */";
        exit;
    }
    // check PRIVATE ?
    if (isset($log['private']) && $log['private']) {
        $log["body"] = "/* ".konawiki_lang('Private Page.')." */\n";
    }
    // show text
    header("Content-Type:text/css; charset=UTF-8");
    echo $log["body"];
}

