<?php

// URI => $script/get/rss2
function action_rss2_()
{
    $page = konawiki_getPage();
    if ($page == 'get') {
        action_rss2_cmd();
    }
    else {
        echo 'page not found.';
    }
}

function action_rss2_cmd()
{
    header("Content-Type: application/xml; charset=UTF-8");
    include_template('rss2.tpl.php');
}


