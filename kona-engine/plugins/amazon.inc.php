<?php
/** konawiki plugins -- Amazon アソシエイト用プラグイン
 * - [書式] #amazon(nnnnnnnnnn)
 * - [引数]
 * -- nnnnnnnnnn .. Amazon の ASIN を利用する
 * - [使用例] #amazon(4839925895)
 * - [備考] Amazon の associate ID は、プラグインのPHPファイルを書き換えが必要。
 */

// Amazon associate ID -- 以下を書き換える
define('PLUGIN_AMAZON_AID','text2musiccom-22');

function plugin_amazon_convert($params)
{
	konawiki_setPluginDynamic(false);
    $asin = array_shift($params);
    $code = plugin_amazon_convert_getCode($asin, PLUGIN_AMAZON_AID);
    return "\n<!-- amazon.begin -->\n".$code."\n<!-- amazon.end -->\n";
}

function plugin_amazon_convert_getCode($asin,$tcode)
{
    return <<<EOS
<iframe src="http://rcm-jp.amazon.co.jp/e/cm?t={$tcode}&o=9&p=8&l=as1&asins={$asin}&fc1=000000&IS2=1&lt1=_blank&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr&npa=1" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe>
EOS;
}

?>
