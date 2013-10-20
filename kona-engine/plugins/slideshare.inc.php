<?php
/** konawiki plugins -- Slideshare に投稿したスライドを表示するプラグイン
 * - [書式] #slideshare([EmbedWordpressCode])
 * - [引数]
 * -- EmbedWordpressCode .. 省略可能、SlideshareのWordpress用のコードを記述
 * - [使用例] #slideshare(id=539818&doc=ss-1217761395321884-8&w=425)
 * - [備考] Slideshare ( http://www.slideshare.net/ )
 * - [公開設定] 公開
 */

function plugin_slideshare_convert($params)
{
    if (count($params) == 0) {
        return "[#slideshare([Embed wordpress code])]";
    }
    $p = array_shift($params);
    if (preg_match("#\[slideshare\s+(.+)\]#",$p,$m)) {
        $p = $m[1];
    }
    $p_array = explode("&", $p);
    $res = array();
    foreach ($p_array as $line) {
        $pp = explode("=", $line);
        $key = array_shift($pp);
        $val = array_shift($pp);
        $res[$key] = $val;
    }
    #
    $id  = isset($res["id"])   ? $res["id"]  : 0;
    $doc = isset($res["doc"])  ? $res["doc"] : "";
    $w   = isset($res["w"])    ? $res["w"]   : 425;
    #
    $title = "unknown";
    if (preg_match("#^([^\-]+)#", $doc, $m)) {
        $title = $m[1];
    }
    #
    return <<< EOS
<!-- slideshare -->
<div style="width:{$w}px;text-align:left" id="__ss_{$id}">
<object style="margin:0px" width="{$w}" height="355">
    <param name="movie" value="http://static.slideshare.net/swf/ssplayer2.swf?doc=$doc&stripped_title={$title}-presentation" />
    <param name="allowFullScreen" value="true"/>
    <param name="allowScriptAccess" value="always"/>
    <embed
        src="http://static.slideshare.net/swf/ssplayer2.swf?doc=$doc&stripped_title=$title-presentation"
        type="application/x-shockwave-flash"
        allowscriptaccess="always"
        allowfullscreen="true"
        width="{$w}" height="355"></embed>
</object></div>
<!-- end of slideshare -->
EOS;
}

?>
