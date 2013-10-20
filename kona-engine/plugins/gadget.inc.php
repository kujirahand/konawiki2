<?php
/** konawiki plugins -- 各種ガジェット貼り付け用プラグイン
 * - [書式]
{{{
#gadget(CODE)
}}}
 * - [引数]
 * -- CODE ... ブログ貼り付け用のコードを記述
 * - [使用例]
{{{
#ref(<script src="http://www.gmodules.com/ig/ifr?url=XXX"></script>)
}}}
 * - [備考] Googleガジェット
 * - [公開設定] 公開
 */
 
global $plugin_gadget_convert_list;

$plugin_gadget_convert_list = array(
    // google gadget
    "#^\<script src\=\"http:\/\/www\.gmodules\.com\/ig\/ifr\?url\=[^\"]+\">\<\/script>$#",
);

function plugin_gadget_convert($params)
{
    global $plugin_gadget_convert_list;
    $code = join(",",$params);
    
    // CODE チェック
    foreach ($plugin_gadget_convert_list as $regexp) {
        if (!preg_match($regexp, $code)) {
            return "[ERROR #gadget(code)]\n";
        }
    }
    return <<<EOS__
$code
EOS__;
}


?>
