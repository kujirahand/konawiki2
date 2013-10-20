<?php
/** konawiki plugins -- Google AdSense用プラグイン＞検索ユニット
 * - [書式] #googleadsense_search_box
 * - [引数] なし
 * - [使用例] #googleadsense_search_box
 * - [備考]
 * -- googleadsense_search_result プラグインと組み合わせて使う
 */

function plugin_googleadsense_search_box_convert($params)
{
    //--------------------------------------------------------------------------
    // 以下を直接書き換える --- 将来的には設定ファイルに移したい
    //--------------------------------------------------------------------------
    return <<< __EOS__
<!-- ad.begin -->


<form action="http://www.google.co.jp/cse" id="cse-search-box">
  <div>
    <input type="hidden" name="cx" value="partner-pub-3816223231062294:topeve-xl75" />
    <input type="hidden" name="ie" value="UTF-8" />
    <input type="text" name="q" size="16" />
    <input type="submit" name="sa" value="&#x691c;&#x7d22;" />
  </div>
</form>
<script type="text/javascript" src="http://www.google.co.jp/cse/brand?form=cse-search-box&amp;lang=ja"></script> 

<!-- ad.end -->
__EOS__;
    //--------------------------------------------------------------------------
}

?>
