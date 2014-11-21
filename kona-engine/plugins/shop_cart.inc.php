<?php
/** konawiki plugins -- ショッピングカート
 * - [書式] {{{#shop_cart() データ }}}
 * - [引数]
 * - [使用例]
{{{
#shop_cart(ケーキ,300)
}}}
 * - [備考] ショッピングカートを実現するプラグイン
 * - [公開設定] 公開
 */

// shop_cart confg
define("SHOP_CART_EMAIL_SUBJECT", "[注文内容ご確認](自動配信メール)");
define("CART_ITEMS_KEY", "cart_items");
define("CART_ITEMS_ID_KEY", "cart_items_order_id");
define("CART_ITEMS_SENDTO", "cart_items_sendto");
define("CART_ITEMS_LOGIN", "cart_items_login");
define("SHOP_CART_CUSTOMER_DB", KONAWIKI_DIR_DATA."/shop_cart.db");
define("SHOP_CART_PW_SALT", "PiGtpPKh#r00Dvr11&jeimULQm");

konawiki_start_session();

if (!file_exists(SHOP_CART_CUSTOMER_DB)) {
  makeShopCartDB();
}

function plugin_shop_cart_convert($params)
{
  if (!$params) return "";
  $name  = array_shift($params);
  $price = intval(array_shift($params));

  $html = "";
  // Shopping cart
  $name_ = htmlspecialchars($name, ENT_QUOTES);
  $price_ = number_format($price);
  $inp_count = "<input name='sci_count' type='text' size=2 value=1>";
  $inp_btn = "<input type='submit' value='買い物かごに入れる'>";
  $page = konawiki_getPage();
  $url = konawiki_getPageURL($page, "plugin", "", "name=shop_cart_batch&p=add");
  $html .= html_css_out("shop_cart_box");
  $html .= "<div>";
  $html .= "<form action='$url' method='post'>";
  $html .= form_input_hidden("sci_name", $name);
  $html .= form_input_hidden("sci_price", $price);
  $html .= form_input_hidden("sci_hash", plugin_shop_cart_hash($name, $price));
  $html .= form_input_hidden("back", $page);
  $html .= "<table>";
  $html .= "<tr><td>商品</td><td>$name_</td></tr>";
  $html .= "<tr><td>値段</td><td>{$price_}円</td></tr>";
  $html .= "<tr><td>個数</td><td>{$inp_count}&nbsp;{$inp_btn}</td></tr>";
  $html .= "</table></form></div>";
  return $html;
}

function plugin_shop_cart_hash($name, $price) {
  $fixkey = "f29dfj2nFjFF";

  if (empty($_SESSION["shop_cart_pkey"])) {
    $rkey = $_SESSION["shop_cart_pkey"] = rand(10000, 99999);
  } else {
    $rkey = $_SESSION["shop_cart_pkey"];
  }

  return md5($fixkey."-".$name."-".$price."-".$rkey);
}

function makeShopCartDB() {
  $db = new PDO("sqlite:".SHOP_CART_CUSTOMER_DB);
  $db->exec("
    CREATE TABLE IF NOT EXISTS customers (
      cid   INTEGER PRIMARY KEY,
      email TEXT UNIQUE,
      data  TEXT
    );
    CREATE TABLE IF NOT EXISTS history (
      history_id INTEGER PRIMARY KEY,
      cid INTEGER,
      data TEXT
    );
  ");
}





