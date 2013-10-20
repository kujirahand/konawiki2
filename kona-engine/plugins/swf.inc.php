<?php
/** konawiki plugins -- Flash(SWF形式)を表示するプラグイン
 * - [書式] #swf(filename,options..)
 * - [引数]
 * - filename .. ファイル名(添付ファイル指定可能)
 * - options ..
 * --  (width)x(height) .. サイズ
 * -- *(caption)        .. キャプション
 * - [使用例] #swf(xxx.swf,300x200,*ｘｘのFlash)
 * - [備考] なし
 * - [公開設定] 公開
 */

function plugin_swf_convert($params)
{
    if (count($params) == 0) {
        return "[usage - #swf(attachname)]";
    }
    $page = konawiki_getPage();
    $fname = $params[0];
    if (preg_match("#^(.+)\\\\(.+)$#", $fname, $m)) { //has page
        $page  = $m[1];
        $fname = $m[2];
    }
    array_shift($params);
    $baseurl = konawiki_public("baseurl");
    $file_url = konawiki_getPageURL($page) . 
        "/attach?file=" .
        rawurlencode($fname);
    // is URL
    if (preg_match('#^http.?\:\/\/#',$fname)) {
        $file_url = $fname;
    }
    $link_url = $file_url;
    // image file ?
    $caption = "";
    if (preg_match("#(\.swf)$#i", $fname)) {
        // check parameter
        $w = 300;
        $h = 200;
        foreach ($params as $p) {
            if (preg_match("#(\d+)x(\d+)#",$p,$m)) {
                $w  = $m[1];
                $h = $m[2];
                continue;
            }
            $p = trim($p);
            $c = mb_substr($p, 0, 1);
            if ($c == "*" || $c == "＊") {
                $caption = mb_substr($p, 1);
            }
        }
        $img = <<<EOS
<object width="$w" height="$h">
<param name="movie" value="$file_url"></param>
<param name="wmode" value="transparent"></param>
<embed src="$file_url" type="application/x-shockwave-flash" width="$w" height="$h"></embed>
</object>
EOS;
        if ($caption != "") {
            $img = "<div class='imagecaption'>{$img}{$caption}</div>";
        }
        return $img."\n";
    }
    else {
        return "[no swf file]";
    }
}

?>
