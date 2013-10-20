<?php
/** konawiki plugins -- ソースコードに色をつけるプラグイン
 * - [書式] {{{#code([lang]) テキスト }}}
 * - [引数]
 * -- lang 言語(php/c/java/html/js/ml/lua/lisp/sql/css/yaml)
 * -- str コード
 * -- テキスト 色を付ける内容
 * - [使用例] &color(#ff0000,赤色の文字!!);
{{{
&color(#ff0000,赤色の文字!!);
}}}
 */

function plugin_code_convert($params)
{
    konawiki_setPluginDynamic(false);
    $list = array(
      "prettify.js",
      "lang-css.js",
      "lang-sql.js",
      "lang-lisp.js",
      "lang-lua.js",
      "lang-ml.js",
      "lang-scala.js",
      "lang-yaml.js",
    );
    foreach($list as $src) {
        konawiki_header_addJS("./skin/default/resource/js/google-code-prettify/$src");
    }
    konawiki_header_addCSS("./skin/default/resource/js/google-code-prettify/prettify.css");
    //
    $lang = "";
    if (count($params) >= 2) {
        $lang = array_shift($params);
        $code = array_shift($params);
    } else {
        $code = array_shift($params);
    }
    $code = htmlspecialchars($code);
    // kick
    global $konawiki_plugin_code_kick;
    if (empty($konawiki_plugin_code_kick)) {
        $konawiki_plugin_code_kick = "<script type='text/javascript'> setTimeout(prettyPrint, 1); </script>";
    } else {
        $konawiki_plugin_code_kick = "<!-- #code already kicked -->";
    }
    // return
    return <<<EOS__
<pre class="prettyprint {$lang} code">$code</pre>
{$konawiki_plugin_code_kick}
EOS__;
}


