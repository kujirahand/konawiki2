<?php
/**
 * ページの表示アクション
 */
include_once(KONAWIKI_DIR_LIB.'/konawiki_parser.inc.php');

function action_bloghtml_()
{
    // get body
    $page = konawiki_getPage();
    $log = konawiki_getLog($page);
    if ($log == FALSE) {
        header("HTTP/1.0 404 Not Found");
        echo "/* 404 Not Found */";
        exit;
    }
    // check PRIVATE ?
    if (isset($log['private']) && $log['private']) {
        $body = konawiki_lang('Private Page.');
        $page = "private_page";
    } else {
        $_GET['noanchor'] = 1;
        $body = konawiki_parser_convert($log["body"]);
        $body = str_replace('<pre class="code">', '<pre class="brush:java; wrap-lines:false">', $body);
        $body = trim($body);
    }
    // show text
    header("Content-Type:text/plain; charset=UTF-8");
    header("Content-Disposition: inline; filename=\"{$page}.txt\"");
    echo <<< EOS
{$body}

EOS;
}

?>
