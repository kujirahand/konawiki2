<?php

function action_search_()
{
    $log['body'] = _action_search_getForm();
    include_template("form.tpl.php", $log);
}

function _action_search_getForm()
{
    global $konawiki;
    $title = konawiki_public('title');
    $keyword = konawiki_param("keyword", "");
    $keyword = htmlspecialchars($keyword, ENT_QUOTES);
    $page_raw = konawiki_getPage();
    $page = htmlspecialchars($page_raw, ENT_QUOTES);
    $url = konawiki_getPageURL($page, "search", "exec");
    return <<< EOS__
<h4>[{$title}]内を検索します</h4>
<form action="{$url}" method="post">
<div style="display:none">
<input type="text" name="dm1"   value=""/>
<input type="text" name="dm2"   value=""/>
<input type="text" name="name"  value="草花"/>
<input type="text" name="title" value="草花"/>
</div>
<input type="text" size=32 name="keyword" value="{$keyword}"/>
<input type="submit" value="検索">
</form>
<p>複数の語句を空白で区切って絞り込みもできます。</p>
EOS__;
}

function action_search_exec()
{
    // get param
    $dm1        = konawiki_param("dm1", "");
    $dm2        = konawiki_param("dm2", "");
    $name       = konawiki_param("name", "");
    $title      = konawiki_param("title", "");
    $keyword    = konawiki_param("keyword", "");
    $backlink   = konawiki_param("backlink", "");
    // check spam attach
    if ($dm1 != "" || $dm2 != "" || $name != "草花" ||
        $title != "草花") {
        konawiki_error("入力エラーです。[戻る]ボタンで戻ってください。");
        exit;
    }
    // search
    $db = konawiki_getDB();
    $key_ary = explode(' ', $keyword);
    $where_body = array();
    $where_name = array();
    $where_tag  = array();
    foreach ($key_ary as $key) {
        $key = $db->escape($key);
        $where_name[] = "name like '%$key%'";
        $where_body[] = "body like '%$key%'";
        $where_tag[]  = "tag = '$key'";
    }
    // タイトルの検索
    $where_str = join(" AND ", $where_name);
    $sql = "select name from logs where {$where_str} limit 31";
    $res = konawiki_query($sql);
    $keyword_ = htmlspecialchars($keyword);
    $body = "<h5>ページ名の一致：キーワード[$keyword_]</h5>";
    $body .= action_search_exec_result($res);
    // タグの検索
    $wherestr = join(" OR ", $where_tag);
    $sql= "SELECT * FROM tags WHERE $wherestr limit 31";
    $res = konawiki_query($sql);
    $log_ids = array();
    foreach ($res as $row) {
        $log_id = $row['log_id'];
        $log_ids[] = "id=$log_id";
    }
    $log_id_str = join(" OR ", $log_ids);
    if ($log_id_str != "") {
        $sql = "SELECT name from logs where $log_id_str";
        $res = konawiki_query($sql);
    } else {
        $res = array();
    }
    $keyword_ = htmlspecialchars($keyword);
    $body .= "<h5>タグの一致：キーワード[$keyword_]</h5>";
    $body .= action_search_exec_result($res);
    // 本文の検索
    $where_str = join(" AND ", $where_body);
    $sql = "select name from logs where {$where_str} limit 31";
    $res = konawiki_query($sql);
    $keyword_ = htmlspecialchars($keyword);
    $body .= "<h5>本文の一致：キーワード[$keyword_]</h5>";
    $body .= action_search_exec_result($res);
    //
    $log["body"] = _action_search_getForm() . $body;
    include_template("form.tpl.php", $log);
}

function action_search_backlink()
{
    // get param
    $keyword    = konawiki_param("keyword", "");
    $backlink   = konawiki_param("backlink", "");
    $body = "";
    // search
    $db = konawiki_getDB();
    $keyword_ = $db->escape("[[".$keyword."]]");
    $where_body = "body like '%{$keyword_}%'";
    // 本文の検索
    $sql = "select name from logs where {$where_body} limit 31";
    $res = konawiki_query($sql);
    $keyword_ = htmlspecialchars($keyword);
    $body .= "<h5>バックリンク：[$keyword_]</h5>";
    $body .= action_search_exec_result($res);
    //
    $log["body"] = $body;
    include_template("form.tpl.php", $log);
}

function action_search_tag()
{
    $keyword = konawiki_getPage();
    $body = "";
    // search
    $db = konawiki_getDB();
    $key_ary = explode(' ', $keyword);
    $where_tag  = array();
    foreach ($key_ary as $key) {
        $key = $db->escape($key);
        $where_tag[]  = "tag = '$key'";
    }
    // タグの検索
    $wherestr = join(" OR ", $where_tag);
    $sql= "SELECT * FROM tags WHERE $wherestr limit 31";
    $res = konawiki_query($sql);
    $log_ids = array();
    foreach ($res as $row) {
        $log_id = $row['log_id'];
        $log_ids[] = "id=$log_id";
    }
    $log_id_str = join(" OR ", $log_ids);
    if ($log_id_str != "") {
        $sql = "SELECT name from logs where $log_id_str";
        $res = konawiki_query($sql);
    } else {
        $res = array();
    }
    $keyword_ = htmlspecialchars($keyword);
    $body .= "<h5>[$keyword_]のタグがついているページ</h5>";
    $body .= action_search_exec_result($res);
    
    konawiki_showMessage($body);
}


function action_search_exec_result($res)
{
    $body = "";
    if (!$res) {
        return "<p>ありません</p>";
    }
    $body .= "<ul>\n";
    $res2 = array_splice($res, 0, 30);
    foreach ($res2 as $row) {
        $link = konawiki_getPageLink($row["name"]);
        $body .= "<li>$link</li>\n";
    }
    $body .= "</ul>\n";
    if (count($res2) > 30) {
        $body .= "<p>※30件以上の一致があります。</p>";
    }
    return $body;
}

?>
