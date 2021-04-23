<?php
require_once __DIR__.'/rss.inc.php';

// URI => $script/get/rss2
function action_rss2_()
{
    $page = konawiki_getPage();
    if ($page == 'get') {
        action_rss_cmd('rss2.html');
    }
    else {
        echo 'page not found.';
    }
}

