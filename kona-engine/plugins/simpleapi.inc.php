<?php
/** konawiki plugins -- Webサイトの画面キャプチャAPI(simpleapi)を利用するプラグイン
 * - [書式] #simpleapi(url,options..)
 * - [引数]
 * - url .. サイトのURL
 * - options ..
 * -- (width)x(height) .. 画像のサイズ
 * -- *(caption)       .. キャプション
 * - [使用例]
{{{
#simpleapi(http://nadesi.com, *なでしこのページ)
}}}
#simpleapi(http://nadesi.com, *なでしこのページ)
 * - [備考] 本家simpleapiがうまく動かないのでMozShotに変更
 * - [公開設定] 公開
 */

function plugin_simpleapi_convert($params)
{
    if (count($params) == 0) {
        return "[usage - #simpleapi(url, option)]";
    }
    $url = array_shift($params);
    // image file ?
    $caption = "";
    $w = 128;
    $h = 128;
    $attr = array();
    foreach ($params as $p) {
        if (preg_match("#(\d+)x(\d+)#",$p,$m)) {
            $w = $m[1];
            $h = $m[2];
            continue;
        }
        $p = trim($p);
        $c = mb_substr($p, 0, 1);
        if ($c == "*" || $c == "＊") {
            $caption = mb_substr($p, 1);
        }
    }
    $url = htmlspecialchars($url);
    $attr[] = "width='$w' height='$h'";
    $attr_str = join(" ", $attr);
    $img = <<<EOS
<a href="{$url}">
<img src="http://mozshot.nemui.org/shot?{$url}" $attr_str />
</a>
EOS;
    if ($caption != "") {
        $img = "<div class='imagecaption'>{$img}{$caption}</div>";
    }
    return $img."\n";
}

?>
