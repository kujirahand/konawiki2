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
<iframe style="width:120px;height:240px;" marginwidth="0" marginheight="0" scrolling="no" frameborder="0" src="https://rcm-fe.amazon-adsystem.com/e/cm?ref=tf_til&t={$tcode}&m=amazon&o=9&p=8&l=as1&IS2=1&detail=1&asins={$asin}&linkId=97170724c87fa892e4a05732776c9586&bc1=000000&lt1=_blank&fc1=333333&lc1=0066C0&bg1=FFFFFF&f=ifr">
    </iframe>
EOS;
}


