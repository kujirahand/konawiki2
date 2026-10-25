<?php
/**
 * ページの表示アクション
 */
function action_go_()
{
    // get body
    $page = konawiki_getPage();
    $no = intval($page);
    if ($page != $no) { // need number
      $url = './index.php';
      header("location: $url");
      exit;
    }
    $r = db_get("SELECT name,private FROM logs WHERE id=?", [$no]);
    // 非公開ページは権限のないユーザーには存在しない ID と同じ扱いにする (Issue #55)
    if ($r && (empty($r[0]["private"]) || konawiki_isLogin_write())) {
      $name = $r[0]["name"];
      $url = konawiki_getPageURL($name);
      $url = str_replace('/go.php', '/index.php', $url);
      header("location: $url");
    } else {
      // header("HTTP/1.0 404 Not Found");
      $url = './index.php';
      header("location: $url");
    }
    echo "<html><body>";
    echo "<a href='$url'>$url</a>";
    echo "</body></html>";
}



