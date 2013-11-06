<?php

$ko_lang['Mini help'] = <<< __EOS__
<!-- *****簡易ヘルプここから***** -->
<style type="text/css"><!-- #easyhelp td {vertical-align: top;} --></style>
<a id="easyhelplink" href="javascript:void(0);" class="date" onclick="$('#easyhelp').show('slow');$('#easyhelplink').hide('slow');return false;">簡易ヘルプを開く</a>
<div id="easyhelp" class="date" style="display:none;">
<table valign="top" style="border: 1px solid gray; line-height: 1.1em; padding:6px;">
<tr><td width="50%">
<b>見出し</b><br/>
　行頭に「■」、「●」、「▲」もしくは「*」、「**」、「***」。
</td><td width="50%">
<b>強調</b><br/>
　'' 強調したい文字列 ''。
</td></tr>
<tr><td>
<b>リスト</b><br/>
　行頭に「・」、「・・」、「・・・」もしくは「-」、「--」、「---」。番号付きは行頭に「+」、「++」、「+++」。
</td><td>
<b>リンク</b><br/>
　[[ページ名]]、[[タイトル:http://example.com]]、[[タイトル:WIKIページ名]]。URLは自動的にリンク。
</td></tr>
<tr><td>
<b>引用</b><br/>
　行頭に「&gt;」。
</td><td>
<b>改行</b><br/>
　行末に「~」。
</td></tr>
<tr><td>
<b>ソースコード</b><br/>
　行頭に半角スペースもしくは<br/>
{{{<br/>
ソースコード<br/>
}}}
</td><td>
<b>表</b><br/>
　段落の頭に「|」記号<br/>
|商品名  |値段 <br/>
|せっけん|400円<br/>
|はみがき|300円<br/>
</td></tr></table>
<a href="javascript:void(0);" onclick="$('#easyhelp').hide('slow');$('#easyhelplink').show('slow');return false;">簡易ヘルプを閉じる</a>
</div>
<!-- *****簡易ヘルプここまで***** -->
__EOS__;


