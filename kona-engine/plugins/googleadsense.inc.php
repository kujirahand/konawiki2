<?php
/** konawiki plugins -- Google AdSense表示用プラグイン
 * - [書式] #googleadsense(name)
 * - [引数]
 * -- name  .. あらかじめ設定しておいたAdSenseの識別名
 * - [使用例] #googleadsense(konawiki)
 * - [備考] 設定ファイル(konawiki.ini.php)などに、$private["googleadsense"]["name"] に表示したいコードをしこんでおく。
 */

function plugin_googleadsense_convert($params)
{
    $key = isset($params[0]) ? $params[0] : "default";
    $ad = konawiki_private("googleadsense");
    if (empty($ad[$key])) {
        $ad = plugin_googleadsense_convert_getCode();
    }
    $code = isset($ad[$key]) ? $ad[$key] : $ad["default"];
    return "\n<p><!-- googleadsense.begin -->\n".$code."\n<!-- googleadsense.end -->\n</p>\n";
}
/*
 * 【注意】
 * 以下は旧バージョンとの互換性のため .. 以前はプラグインを直接書き換えていた。
 * 現在は、設定ファイルに書くのを推奨。
 */

function plugin_googleadsense_convert_getCode()
{
# 以下を書き換えます。必ず、Google より取得したコードを利用します。
#-----------------------------------------------------------------------
$ad["konawiki"] = <<<EOS__
<script type="text/javascript"><!--
google_ad_client = "pub-3816223231062294";
/* konawiki */
google_ad_slot = "0471021893";
google_ad_width = 468;
google_ad_height = 60;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
EOS__;
$ad["default"] = $ad["konawiki"];
#-----------------------------------------------------------------------
$ad["nadesiko"] = <<<EOS__
<script type="text/javascript"><!--
google_ad_client = "pub-3816223231062294";
/* 468x60, 作成済み 08/02/13 */
google_ad_slot = "0533775334";
google_ad_width = 468;
google_ad_height = 60;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
EOS__;
#-----------------------------------------------------------------------
    return $ad;
}

