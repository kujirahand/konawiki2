<?php
/** konawiki plugins -- HTMLを出力するプラグイン(action=showのときのみ)
 * - [書式]
 * - #htmlshow(HTMLタグ)
 * - [引数]
 * -- HTMLタグ タグを出力する
 * - [使用例] #htmlshow(<b>test</b>)
 * - [公開設定]公開
 */

function plugin_html_convert($params)
{
    if ($_GET['action'] == 'show') {
      $html = array_shift($params);
      return $html;
    } else {
      return "(#htmlshow)";
    }
}

