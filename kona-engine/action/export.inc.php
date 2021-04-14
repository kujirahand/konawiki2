<?php
/**
 * ページの表示アクション
 */
function action_export_()
{
    if (!konawiki_auth()) {
        konawiki_error("Failed to login.");
        exit;
    }
    $p = konawiki_param("p", null);
    $f = konawiki_param("f", "json");
    if ($p !== "exe") {
      $logs = db_get("select count(*) from logs");
      $cnt = $logs[0][0];
      konawiki_showMessage(
        "Export all wiki data($cnt)<br>".
        "- <a href='index.php?all&export&p=exe&f=json'>Export JSON format</a><br>");
      exit;
    }
    // show text
    header("Content-Type:text/plain; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"wiki-all.txt\"");
    $logs   = db_get("select * from logs");
    $attach = db_get("select * from attach");
    $tags   = db_get("select * from tags");
    // 文字種類によってうまく出力ができないことが多かった
    // ので、本文はBASE64でエスケープして出力する
    $logs2 = array();
    foreach ($logs as $log) {
      $logs2[] = array(
        "id" => intval($log["id"]),
        "name" => base64_encode($log["name"]),
        "body" => base64_encode($log["body"]),
        "freeze" => isset($log["freeze"]) ? $log["freeze"] : 0,
        "private" => isset($log["private"]) ? $log["private"] : 0,
        "ctime" => intval($log["ctime"]),
        "mtime" => intval($log["mtime"]),
      );
    }
    $tags2 = array();
    if ($tags) {
      foreach ($tags as $tag) {
        $tags2[] = array(
          "log_id" => $tag["log_id"],
          "tag" => base64_encode($tag["tag"]),
        );
      }
    }
    // subdb
    $sublogs = db_get("select * from sublogs", [], 'sub');
    $subs = array();
    if ($sublogs) {
      foreach ($sublogs as $sl) {
        $subs[] = array(
          "log_id" => intval($sl["log_id"]),
          "plug_name" => $sl["plug_name"],
          "plug_key"  => $sl["plug_key"],
          "body" => base64_encode($sl["body"]),
          "ctime" => $sl["ctime"],
          "mtime" => $sl["mtime"],
        );
      }
    } 
    $a = array(
        "log.len"=> count($logs2),
        "logs"   => $logs2,
        "attach" => $attach,
        "tags"   => $tags2,
        "sublogs" => $subs,
    );
    if ($f == "json") {
      echo json_encode($a); 
    } else {
      echo "error";
    }
}


