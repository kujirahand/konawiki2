<?php
// include search module
include_once(KONAWIKI_DIR_ACTION."/search.inc.php");

function action_backlink_()
{
    $page = konawiki_getPage();
    $_GET["title"] = $_GET["name"] = "草花";
    $_GET["keyword"] = $page;
    $_GET["backlink"] = "backlink";
    action_search_backlink();
}

?>
