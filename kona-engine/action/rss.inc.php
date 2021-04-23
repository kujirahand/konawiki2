<?php

// URI => $script/get/rss
function action_rss_()
{
    $page = konawiki_getPage();
    if ($page == 'get') {
        action_rss_cmd('rss.html');
    }
    else {
        echo 'page not found.';
    }
}

function action_rss_cmd($template)
{
    header("Content-Type: application/xml; charset=UTF-8");
    $sql = "SELECT * FROM logs ".
    " WHERE name<>'FrontPage' AND name<>'MenuBar' AND name<>'SideBar'".
    "       AND name<>'NaviBar' AND name<>'GlobBar'".
    " ORDER BY ctime DESC LIMIT 20";
    $logs = db_get($sql);
    include_template($template, ["logs" => $logs]);
}


