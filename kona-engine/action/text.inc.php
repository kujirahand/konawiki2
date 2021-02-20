<?php
/**
 * ページの表示アクション
 */
function action_text_()
{
    // get body
    $page = konawiki_getPage();
    $log = konawiki_getLog($page);
    if ($log == FALSE) {
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
        exit;
    }
    // check PRIVATE ?
    if (isset($log['private']) && $log['private']) {
        $log["body"] = konawiki_lang('Private Page.')."\n";
    }
    // show text
    header("Content-Type:text/plain; charset=UTF-8");
    header("Content-Disposition: inline");
    echo $log["body"];
}
