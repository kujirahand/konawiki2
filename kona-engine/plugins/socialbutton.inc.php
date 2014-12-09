<?php
/** konawiki plugins -- Twitter/はてブ/Facebook/Google+のソーシャルボタンを表示する 
 * - [書式] #socialbutton
 * - [引数]
 * -- 文字列
 * - [使用例] #socialbutton
 * - [備考] なし
 */

function plugin_socialbutton_convert($params)
{
  // page url
  $name = konawiki_getPage();
  $id = konawiki_getPageId();
  $url  = konawiki_getPageURL($id, "go");
  $name_h = htmlspecialchars($name, ENT_QUOTES);
  $name_u = urlencode($name);
  $url_   = urlencode($url);
  $shorturl = htmlspecialchars($url, ENT_QUOTES);
  // embed
  $bookmark = <<<EOS__
<nav>
<style>
.sbuttons {
  border-bottom: 1px solid #d0d0d0;
  padding: 0px; margin: 0px;
  background-color: #f0f0f0;
  padding-top: 10px;
}
ul.social-button {
  list-style-type: none; margin: 0; padding: 0;
}
.social-button li {
  float: left;
  margin: 5px;
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
<li><iframe src="//www.facebook.com/plugins/like.php?href={$url_}&amp;width=90&amp;layout=button&amp;action=like&amp;show_faces=false&amp;share=false&amp;height=35" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:90px; height:35px;" allowTransparency="true"></iframe></iframe></li>

<!-- twitter -->
<li><a href="https://twitter.com/share" class="twitter-share-button" data-url="{$url_}" data-text="{$name_h} {$shorturl}">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script></li>

<!-- はてブ -->
<li><a href="http://b.hatena.ne.jp/entry/{$url_}" class="hatena-bookmark-button" data-hatena-bookmark-title="{$name_h}" data-hatena-bookmark-layout="standard-balloon" data-hatena-bookmark-lang="ja" title="このエントリーをはてなブックマークに追加"><img src="https://b.st-hatena.com/images/entry-button/button-only@2x.png" alt="このエントリーをはてなブックマークに追加" width="20" height="20" style="border: none;" /></a><script type="text/javascript" src="https://b.st-hatena.com/js/bookmark_button.js" charset="utf-8" async="async"></script></li>

<!-- g+ -->
<li><script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script><div class="g-plusone" data-size="medium" data-href="{$url_}"></div></li>
</ul><!-- social-button -->
<div style="clear:both;"></div>
</div>
</nav>
EOS__;
  return $bookmark;
}
