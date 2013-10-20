<?php
/** konawiki plugins -- HTMLを出力するプラグイン(デフォルトでは当然無効）
 * - [書式]
 * - #html(HTMLタグ)
 * - [引数]
 * -- HTMLタグ タグを出力する
 * - [使用例] #html(<b>test</b>)
 * - [備考] 初期状態では利用できないようになっている。設定ファイルで、$private['plugins.disable']['html'] = false; と宣言すると使える。
 * - [公開設定]公開
 */

function plugin_html_convert($params)
{
    $html = array_shift($params);
    return $html;
}

?>
