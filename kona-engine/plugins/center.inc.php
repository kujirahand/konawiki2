<?php
/** konawiki plugins -- 文字を中央寄せする
 * - [書式]
{{{
&center(text);
}}}
 * - [引数]
 * -- text
 * -- [color]
 * - [使用例] &center(テスト, red);
{{{
&center(テスト, red);
}}}
 * - [備考]
 * - [公開設定] 公開
 */

function plugin_center_convert($params)
{
  $text = array_shift($params);
  $color = array_shift($params);

  $text = konawiki_parser_convert($text, false);
  if ($color !== null) {
    $color = "color:$color;";
  } else {
    $color = "";
  }
  $text = "<div style='text-align:center;$color'>$text</div>";
  return $text;
}

