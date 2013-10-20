<?php
/** konawiki plugins -- 楽天アフェリエイト表示用プラグイン
 * - [書式] #rakuten(key1,key2,key3...)
 * - [引数]
 * -- key1,key2,key3 ... 商品のキー:最低金額:ジャンルID(キーだけも可能)
 * - [使用例] #rakuten(ネットブック:500:550159)
 * -- #rakuten(ネットブック:10000:550159, デジカメ:10000:213264)
 * - [備考] 設定ファイルに、$private["rakuten"]["affiliateId"]にアフェリエイトIDと $private["rakuten"]["developerId"]をしこんでおく。
 * - PHP5限定
 */

function plugin_rakuten_convert($params)
{
    $params[0] = isset($params[0]) ? $params[0] : "カメラ";
    
    $set = konawiki_private("rakuten");
    if (!$set){
        $set = array(
            "developerId"=>"b71ee8035007998751b128ecbde4b017",
            "affiliateId"=>"044452b5.032a41b9.044452b6.5cdd037e"
        );
    }
    return plugin_rakuten__getCode($set, $params);
}

function plugin_rakuten__getCode($set, $keys)
{
    // デベロッパIDとアフェリエイトIDを指定する
    $developerId = $set["developerId"];
    $affiliateId = $set["affiliateId"];
    //---
    $cache_file   = KONAWIKI_DIR_DATA."/cache_rakuten.txt";
    $cache_period = 3 * 60 * 60; // 3時間までキャッシュを使う
    $keywords     = array();
    foreach ($keys as $row) {
        $keywords[] = explode(":", $row);
    }
    $aff_count   = count($keywords);    // キーワードの表示数
    //----------------------------------------------------------------------
    // APIの結果キャッシュがあれば読み込み
    $cache = array("_time"=>time());
    if (file_exists($cache_file)) {
        $tmp = unserialize( file_get_contents($cache_file) );
        if (time() - $tmp["_time"] < $cache_period) {
            $cache = $tmp;
        }
    }
    //----------------------------------------------------------------------
    // ログをHTMLとして作成
    $html = "";
    for ($i = 0; $i < $aff_count; $i++) {
        $keyword = $keywords[ $i ][0];
        $price   = $keywords[ $i ][1];
        $genreid = $keywords[ $i ][2];
        if (!$keyword) { continue; }
        // echo "[$keyword][$price]";
        // 楽天商品検索APIの結果を得る
        $keyword_enc = urlencode($keyword);
        $api = "http://api.rakuten.co.jp/rws/2.0/rest?"
            . "developerId=$developerId"
            . "&operation=ItemSearch"
            . "&version=2009-04-15"
            . "&keyword=$keyword_enc"
            . "&sort=%2BitemPrice"
            . "&imageFlag=1"
            . "&hits=5";
        if ($price  ) { $api .= "&minPrice=$price";  }
        if ($genreid) { $api .= "&genreId=$genreid"; }
        //
        if ( !$cache[$keyword] ) { // キャッシュが空なら取得する
            $cache[$keyword] = file_get_contents($api);
            file_put_contents($cache_file, serialize($cache));
        }
        // XMLを解析する
        $xml = simplexml_load_string( $cache[$keyword] );
        // XMLの名前空間を登録する
        $xml->registerXPathNamespace("itemSearch",
            "http://api.rakuten.co.jp/rws/rest/ItemSearch/2009-04-15");
        // 指定の階層のタグを取り出す
        $items = $xml->xpath(
            "//Response/Body/itemSearch:ItemSearch/Items/Item");
        if ($items) {
            $html .= "<div style='text-align:center;'>";
            $key = htmlspecialchars($keyword);
            $html .= "<div style='font-size:10px;font-weight:bold;color:#444444;background-color:#ffcccc;padding:4px;'>格安{$key}チェック</div>";
            foreach ($items as $item) {
                $image = $item->mediumImageUrl;
                if (!$image) {
                    $image = $item->smallImageUrl;
                }
                $price   = $item->itemPrice;
                $iname_r = $iname = $item->itemName;
                $iname   = mb_strimwidth($iname, 0, 62, '..');
                $link    = "http://hb.afl.rakuten.co.jp/hgc/$affiliateId/?pc="
                         . urlencode( $item->itemUrl );
                $alt   = htmlspecialchars($iname_r, ENT_QUOTES);
                $html .= "<div>";
                $html .= "<div style='font-size:10px;font-weight:normal;color:#4444ff;background-color:#ffcccc;padding:2px;'><a href='$link'>$iname</a></div>";
                $html .= "<img src='white.gif' height='3'><br/>";
                $html .= "<a href='$link'><img src='$image' /></a><br/>";
                $html .= "<small>".$price."円</small>";
                $html .= "<img src='white.gif' height='7'><br/>";
                $html .= "</div>";
            }
            $html .= "</div>\n";
        }
    }
    $html .= <<< EOS_
<!-- 楽天Webサービスを使う時に貼り付けなければいけないクレジット表示 -->
<div style="text-align:center;">
<a href="http://webservice.rakuten.co.jp/" target="_blank">
<img src="http://webservice.rakuten.co.jp/img/credit/200709/credit_4936.gif"
border="0" alt="楽天ウェブサービスセンター" title="楽天ウェブサービスセンター" 
width="49" height="36"/></a>
</div>
EOS_;
    return $html;
}
?>
