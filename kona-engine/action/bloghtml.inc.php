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
        $body = "** ページ一覧\n".
            "#ls\n";
        $log = array(
            'id'            => 0,
            'body'          => $body,
            'body_header'   => '',
            'body_footer'   => '',
            'ctime'         => time(),
            'mtime'         => time(),
        );
    }
    $_GET['noanchor'] = 1;
    $body = konawiki_parser_convert($log["body"]);
    $body = str_replace('<pre class="code">', '<pre class="brush:java; wrap-lines:false">', $body);
    $body = trim($body);
    // show text
    header("Content-Type:text/plain; charset=UTF-8");
    header("Content-Disposition: inline; filename=\"{$page}.txt\"");
    echo <<< EOS
{$body}

EOS;
}

?>
