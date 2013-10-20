<?php
/**
 * ページの表示アクション
 */
function action_go_()
{
    // get body
    $no = intval(konawiki_getPage());
    $db = konawiki_getDB();
    $r = $db->array_query("SELECT name FROM logs WHERE id=$no");
    if ($r) {
      $name = $r[0]["name"];
      $url = konawiki_getPageURL($name);
      header("location: $url");
      exit;
    } else {
      header("HTTP/1.0 404 Not Found");
    }
}



