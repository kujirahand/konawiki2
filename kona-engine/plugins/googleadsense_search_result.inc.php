<?php
/** konawiki plugins -- Google AdSense用プラグイン＞検索ユニット(表示結果)
 * - [書式] #googleadsense_search_result
 * - [引数] なし
 * - [使用例] #googleadsense_search_result
 * - [備考]
 * -- googleadsense_search_box プラグインと組み合わせて使う
 */

function plugin_googleadsense_search_result_convert($params)
{
    //--------------------------------------------------------------------------
    // 以下を直接書き換える --- 将来的には設定ファイルに移したい
    //--------------------------------------------------------------------------
    return <<< __EOS__
<!-- ad.begin -->

<div id="cse-search-results"></div>
<script type="text/javascript">
  var googleSearchIframeName = "cse-search-results";
  var googleSearchFormName = "cse-search-box";
  var googleSearchFrameWidth = 800;
  var googleSearchDomain = "www.google.co.jp";
  var googleSearchPath = "/cse";
</script>
<script type="text/javascript" src="http://www.google.com/afsonline/show_afs_search.js"></script>

<!-- ad.end -->
__EOS__;
}

?>
