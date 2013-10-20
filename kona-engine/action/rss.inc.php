<?php

// URI => $script/get/rss
function action_rss_()
{
    $page = konawiki_getPage();
    if ($page == 'get') {
        action_rss_cmd();
    }
    else {
        echo 'page not found.';
    }
}

function action_rss_cmd()
{
    header("Content-Type: application/xml; charset=UTF-8");
    include_template('rss.tpl.php');
}


