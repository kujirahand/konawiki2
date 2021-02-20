<?php
/**
 * ページの表示アクション
 */
function action_html_()
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
        $log["body"] = konawiki_lang('Private Page.');
    }
    // show text
    header("Content-Type:text/html; charset=UTF-8");
    echo $log["body"];
}

?>
