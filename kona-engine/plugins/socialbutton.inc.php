<?php
/** konawiki plugins -- Twitter/はてブ/Facebook/Google+のソーシャルボタンを表示する 
 * - [書式] #socialbutton
 * - [引数]
 * -- 文字列
 * - [使用例] #md5(あいうえお)
 * - [備考] なし
 */

function plugin_socialbutton_convert($params)
{
    // page url
    $url  = konawiki_getPageURL();
    $name = konawiki_getPage();
    $name_u = urlencode($name);
    $url_   = urlencode($url);
    $fbroot = plugin_soscialbutton_fb();
    // embed
    $bookmark = <<<EOS__
<nav>
{$fbroot}
<style>
.sbuttons {
  border-bottom: 1px solid #d0d0d0;
  padding: 1px; margin: 10px;
  background-color: #f0f0f0;
}
ul.social-button {
  list-style-type: none; margin: 0; padding: 0;
}
.social-button li {
  float: left;
  margin: 0 5px 0 0;
  padding: 0;
}
.social-button iframe.twitter-share-button {
  width: 90px !important;
}
</style>
<!-- buttons -->
<div class="sbuttons">
<ul class="social-button">
<!-- fb -->
<li><div class="fb-like" data-href="{$url_}" data-send="false" data-layout="button_count" data-width="130" data-show-faces="false"></div></li>
<!-- はてブ -->
<li><a href="http://b.hatena.ne.jp/entry/{$url_}" class="hatena-bookmark-button" data-hatena-bookmark-title="{$name_u}" data-hatena-bookmark-layout="standard" title="このエントリーをはてなブックマークに追加"><img src="http://b.st-hatena.com/images/entry-button/button-only.gif" alt="このエントリーをはてなブックマークに追加" width="20" height="20" style="border: none;" /></a><script type="text/javascript" src="http://cdn-ak.b.st-hatena.com/js/bookmark_button.js" charset="utf-8" async="async"></script></li>
<!-- twitter -->
<li><a href="https://twitter.com/share" class="twitter-share-button" data-url="{$url_}" data-text="{$name_u}" data-lang="jp" data-count="horizontal">Tweet</a><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script></li>
<!-- g+ -->
<li><script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script><div class="g-plusone" data-size="medium" data-href="{$url_}"></div></li>
</ul><!-- social-button -->
<div style="clear:both;"></div>
</div>
</nav>
EOS__;
    return $bookmark;
}

// once output for facebook
global $plugin_socialbutton_fb_count;
$plugin_socialbutton_fb_count = 0;
function plugin_soscialbutton_fb() {
    global $plugin_socialbutton_fb_count;
    if ($plugin_socialbutton_fb_count > 0) return "";
    $plugin_socialbutton_fb_count++;
    $b =<<<EOS
<!-- for fb -->
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/ja_JP/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
EOS;
  return $b;
}

