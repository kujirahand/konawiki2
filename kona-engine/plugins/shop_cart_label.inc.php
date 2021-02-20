<?php
/** konawiki plugins -- ショッピングカート
 * - [書式] #shop_cart_label([ページ名])
 * - [引数]
 * -- ページ名 --- カートを設置したページ名(省略可)
 * - [使用例] #shop_cart_label()
 * - [備考] ショッピングカートを実現するプラグイン
 * - [公開設定] 公開
 */

require_once 'shop_cart.inc.php';

function plugin_shop_cart_label_convert($params)
{
  $goto = array_shift($params);
  if ($goto == null) {
    $goto = "買い物かご";
  }
  $html = "";
  // Shopping cart
  html_css_add(".shopbox", array(
    "border"        => "1px solid silver",
    "padding"       => "8px",
    "margin-bottom" => "14px",
    "background-color" => "#fff0f0",
  ));
  html_css_add(".shopbox_btn", array(
    "width"   => "94%",
    "margin"  => "5px",
  ));
  html_css_add(".shopbox-head", array(
    "background-color" => "#ffd0d0",
    "text-align" => "center",
    "padding" => "3px",
  ));
  $html .= html_css_out("shop_cart_label_box");
  //
  $jump = konawiki_getPageURL($goto);
  // session
  $lbl = "入っていません。";
  if (isset($_SESSION[CART_ITEMS_KEY])) {
    $cnt = count($_SESSION[CART_ITEMS_KEY]);
    if ($cnt > 0) $lbl = "{$cnt}個入ってます";
  }
  $shop_token = plugin_shop_cart_getToken();
  //
  $html .= "<div class='shopbox'>";
  $html .=   "<div class='shopbox-head'>買い物カゴ</div>";
  $html .=   "<form action='$jump' method='post'>";
  $html .=   "<input class='shopbox_btn' type='submit' value='お会計'>";
  $html .=   "<input type='hidden' name='shop_token' value='$shop_token'>";
  $html .=   "{$lbl}</form>";
  $html .= "</div>";
  return $html;
}



