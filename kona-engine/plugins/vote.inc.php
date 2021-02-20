<?php
/** konawiki plugins -- アンケート投票プラグイン
 * - [書式] #vote(識別子,選択肢1,選択肢2,..)
 * - [引数]
 * -- 識別子 .. アンケートの識別子
 * -- 選択肢1, 選択肢2 .. 選択肢を指定する
 * - [使用例] #vote(好きな色,赤,青,黄色)
 * - [備考] なし
 * - [公開設定] 公開
 */

function plugin_vote_convert($params)
{
	konawiki_setPluginDynamic(true);
	// check parameter
    $vote_id   = array_shift($params);
    $selection = $params;
    $log_id = konawiki_getPageId();
    $action = konawiki_getPageURL();
    // db からデータを得る
    $body = plugin_vote_getLog($vote_id, $selection);
    $vote_mode = konawiki_param("vote-mode", "show");
    // --- 編集画面の表示
    if ($vote_mode == "edit") {
        $body_html = htmlspecialchars($body);
        $res = "";
        $res .= form_tag($action);
        $res .= form_input_hidden("plugin","vote");
        $res .= form_input_hidden("vote-mode","write-csv");
        $res .= form_input_hidden("vote_id",htmlspecialchars($vote_id, ENT_QUOTES));
        $res .= "<textarea cols='50' rows='4' name='csv'>$body_html</textarea>\n";
        if (konawiki_isLogin_write()) {
            $res .= form_input_submit("編集");
        }
        $res .= "</form>";
        return $res;
    }
    
    // --- 一般表示
    $vote_id_htm = htmlspecialchars($vote_id, ENT_QUOTES);
    $vote_hash = md5($vote_id);
    $url = konawiki_getPageURL();
    $res = "<div class='memo'>{$vote_id_htm}<a name='{$vote_hash}' href='{$url}#{$vote_hash}'>&dagger;</a></div>\n";
    $res .= "<table>";
    $rows = explode("\n", trim($body));
    $i = 0;
    foreach ($rows as $line) {
        list($k, $v) = explode(",", $line.",,"); 
        //----------------------------------
        // 投票フォームの作成
        //----------------------------------
        $frm = "<form action='{$action}#{$vote_hash}' method='post'>".
            form_input_hidden("plugin","vote").
            form_input_hidden("vote_id",$vote_id_htm).
            form_input_hidden("vote-mode","write").
            form_input_hidden("vote-param",$i).
            form_input_submit("投票").
            "</form>";
        $res .= "<tr><td>$k</td><td>$v</td><td>$frm</td></tr>\n";
        $i++;
    }
    $res .= "</table>";
    $showlink = konawiki_getPageURL(konawiki_getPage(), FALSE, FALSE, "vote-mode=edit");
    $res .= "<div class='rightopt'><a href='$showlink'>→CSV</a></div>\n";
    return $res;
}
//----------------------------------------------------------------------
/**
 * 書き込みがあった場合 (TRUE or FLASE) を返す
 * plugin_xxx_action($param)
 */
function plugin_vote_action($params)
{
    global $plugin_params;
    
    // check params
    $vote_id    = array_shift($params);
    $selection  = $params;
    
    // フォームの中に２つ以上の #vote があった場合の処理
    $w_vote_id  = konawiki_param("vote_id","");
    if ($vote_id != $w_vote_id) {
        return TRUE;
    }
    // モードで処理を分ける
    $vote_mode = konawiki_param("vote-mode","");
    if ($vote_mode == "write") {
        $vote_param = intval(konawiki_param("vote-param", 0));
        $csv = plugin_vote_csv2array(plugin_vote_getLog($vote_id, $selection));
        $csv[$vote_param][1] = intval($csv[$vote_param][1]) + 1;
        plugin_vote_setLog($vote_id, plugin_vote_array2csv($csv));
    }
    else if ($vote_mode == "write-csv") {
        if (konawiki_auth()) {
            plugin_vote_setLog($vote_id, konawiki_param("csv", ""));
        }
    } else {
        $plugin_params['error'] = "未定義の投稿";
        return FALSE;
    }
    
    return TRUE;
}
//----------------------------------------------------------------------
/* 内部で使う使い捨てメソッド */
function plugin_vote_csv2array($csv)
{
    $r = array();
    $csv_array = explode("\n", $csv);
    foreach ($csv_array as $line) {
        $lines = explode(",", $line);
        $r[] = $lines;
    }
    return $r;
}
function plugin_vote_array2csv($csv)
{
    $r = "";
    foreach ($csv as $line) {
        $r .= join(",", $line)."\n";
    }
    return $r;
}
//----------------------------------------------------------------------
function plugin_vote_getLog($vote_id, $selection)
{
    // [format] CSV
    $db        = konawiki_getSubDB();
    $vote_id_  = $db->escape($vote_id);
    $log_id    = konawiki_getPageId();
    $sql =
        "SELECT * FROM sublogs WHERE log_id=$log_id".
        " AND plug_name='vote' AND plug_key='$vote_id_' LIMIT 1";
    $r = $db->array_query($sql);
    $body = "";
    if (isset($r[0]['body'])) {
        $body = $r[0]['body'];
    } else {
        $body = "";
        foreach ($selection as $n) {
            $body .= "$n,0\n";
        }
    }
    return $body;
}

function plugin_vote_setLog($vote_id, $csv)
{
    global $plugin_params;
    $db        = konawiki_getSubDB();
    $vote_id_  = $db->escape($vote_id);
    $log_id    = konawiki_getPageId();
    $csv_      = $db->escape(trim($csv));
    // log exists ?
    $sql =
        "SELECT * FROM sublogs WHERE log_id=$log_id".
        " AND plug_name='vote' AND plug_key='$vote_id_' LIMIT 1";
    $r = $db->array_query($sql);
    $now = time();
    if ($r) {
        $id = $r[0]['id'];
        $sql =
            "UPDATE sublogs SET body='$csv_', mtime=$now WHERE id=$id";
    }
    else {
        $sql =
            "INSERT INTO sublogs".
            "   (log_id,plug_name,plug_key,body,ctime,mtime) ".
            "   VALUES ($log_id,'vote','$vote_id_','$csv_',$now,$now)";
    }
    if (!$db->exec($sql)) {
        $plugin_params['error'] = "DBの書き込みに失敗";
        return FALSE;
    }
    return TRUE;
}


?>
