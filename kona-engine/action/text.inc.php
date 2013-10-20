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
    // show text
    header("Content-Type:text/plain; charset=UTF-8");
    //header("Content-Disposition: attachment; filename=\"{$page}.txt\"");
    header("Content-Disposition: inline");
    echo $log["body"];
}

?>
