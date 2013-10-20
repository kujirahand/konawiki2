<?php
/** konawiki plugins -- コメントを本文中に記入するプラグイン(非推奨)
 * - [書式] #bodycomment
 * - [引数] なし
 * - [使用例] #bodycomment
 * - [備考] #comment プラグインを推奨。
 */
 
function plugin_bodycomment_convert($params)
{
	konawiki_setPluginDynamic(true);
	
	$pid = konawiki_getPluginInfo("bodycomment","pid",0);
    $s = <<<__EOS
<form method="POST">
<span style="display:none">
<input type="text" name="name" />
<input type="text" name="comment" />
</span>
お名前:<input type="text" name="r_name" size=12/>
<input type="text" name="r_comment" size=60/>
<input type="submit" value="コメントを挿入"/>
<input type="hidden" name="plugin" value="bodycomment"/>
<input type="hidden" name="pid" value="$pid"/>
</form>
__EOS;
    return $s;
}

function plugin_bodycomment_action(&$params)
{
    global $plugin_params;
    $post_pid = konawiki_param("pid", 0);
    $pid = konawiki_getPluginInfo("bodycomment","pid",0);
    if ($pid != $post_pid) return TRUE;
    # get comment
    $dummy1 = konawiki_param("comment");
    $dummy2 = konawiki_param("name");
    if ($dummy1 !== "" || $dummy2 !== "") {
        // maybe spam
        $plugin_params["error"] = "スパムの可能性があるので書き込みません。";
        return FALSE;
    }
    $comment = konawiki_param("r_comment");
    $name    = konawiki_param("r_name");
    $mtime   = konawiki_datetime(time());
    $instext = "- $comment -- $name (&new($mtime);)";
    # insert to raw text
    $res = konawiki_swapRawText('/^#bodycomment/', $instext, FALSE, $pid);
    # write text
    if (konawiki_writePage($res, $err)) {
        $url = konawiki_getPageURL();
        konawiki_jump($url);
        return TRUE;
    } else {
        $plugin_params["error"] = $err;
        return FALSE;
    }
}

?>
