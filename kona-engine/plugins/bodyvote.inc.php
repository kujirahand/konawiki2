<?php
/** konawiki plugins -- 本文中に投票結果を埋め込むプラグイン(非推奨)
 * - [書式] #bodyvote(選択肢1[カウンタ],選択肢2[カウンタ],..)
 * - [引数]
 * -- 投票アンケートの選択肢をカンマ区切りで書く
 * - [使用例] #bodyvote(青[0],白[3],赤[4])
 * - [備考] #vote プラグインを推奨。
 */

function plugin_bodyvote_convert($params)
{
	konawiki_setPluginDynamic(true);
	
	if (count($params) == 0) {
        return "[投票プラグインは、#vote(選択肢1,選択肢2..) の書式で書いてください。]";
    }
    //
    $pid = konawiki_getPluginInfo("bodyvote", "pid", 0);
    $res = "";
    $res .= "<form method='POST'>";
    $res .= form_input_hidden('vote_id', $vote_id);
    $res .= form_input_hidden('plugin','bodyvote');
    $res .= form_input_hidden('pid', $pid);
    $res .= "<table border=0 cellpadding=2 cellspacing=0>\n";
    $res .= "<tr><td>選択肢</td><td></td><td>投票</td></tr>\n";
    foreach ($params as $line) {
        $count = 0;
        $sel   = "";
        if (preg_match("#(.+)\[(\d+)\]#", $line, $m)) {
            $sel   = $m[1];
            $count = intval($m[2]);
        }
        else {
            $sel = $line;
        }
        $md5 = md5($sel);
        $sel = htmlspecialchars($sel);
        $btn = "<input type='submit' name='vote$md5' value='投票'/>\n";
        //
        $res .= "<tr><td>$sel</td><td>$count</td><td>$btn</td></tr>\n";
    }
    $res .= "</table>";
    $res .= "</form>";
    return $res;
}

function plugin_bodyvote_action($params)
{
    $post_id = konawiki_param("pid", 0);
    $pid = konawiki_getPluginInfo("bodyvote", "pid", 0);
    if ($post_id != $pid) return TRUE;
    // make params
    $res = array();
    foreach ($params as $line) {
        $count = 0;
        if (preg_match('#(.+)\[(\d+)\]#',$line, $m)) {
            $key = $m[1];
            $count = intval($m[2]);
        } else {
            $key = trim($line);
        }
        $md5 = "vote".md5($key);
        if (konawiki_param($md5)) {
            $count++;
        }
        $res[] = "{$key}[{$count}]";
    }
    $vote = "#bodyvote(".join(",",$res).")";
    # insert to raw text
    $res = konawiki_swapRawText('/^#bodyvote/', $vote, TRUE, $pid);
    # write text
    if (konawiki_writePage($res, $err)) {
        $url = konawiki_getPageURL();
        konawiki_jump($url);
        return TRUE;
    } else {
        global $plugin_params;
        $plugin_params["error"] = $err;
        return FALSE;
    }
    return TRUE;
}




?>
