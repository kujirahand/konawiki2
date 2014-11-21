<?php
#vim:set expandtab tabstop=2 softtabstop=2 shiftwidth=2:
/** konawiki plugins -- ショッピングカート
 * - [書式] {{{#shop_cart_form() データ }}}
 * - [引数]
 * - [使用例]
{{{
#shop_cart_form()
}}}
 * - [備考] ショッピングカートを実現するプラグイン
 * - [公開設定] 公開
 */

require_once 'shop_cart.inc.php';

// ?pname=shop_cart_batch&p=rmItem&item_id=xxx&back=FrontPage

function plugin_shop_cart_batch_convert($params)
{
  $mode = array_shift($params);
  $back = konawiki_param("back", "FrontPage");
  $baseurl = konawiki_public("baseurl", "");

  switch ($mode) {
  case "add":       plugin_shop_cart_batch_add(); break;
  case "rmItem":    plugin_shop_cart_batch_rmItem(); break;
  case "rmAll":     plugin_shop_cart_batch_rmAll(); break;
  default:
    echo "error mode"; exit;
  }

  $url = konawiki_getPageURL('買い物かご');
  $pid = konawiki_getPageId('買い物かご');
  if ($pid <= 0) {
    $page = konawiki_getPage();
    $_GET['page'] = '買い物かご';
    konawiki_writePage('#shop_cart_form()');
    $_GET['page'] = $page;
  }
  header("location: $url");
}

// add item
function plugin_shop_cart_batch_add() {
  $name = konawiki_param("sci_name", "");
  $price = konawiki_param("sci_price", "");
  $count = konawiki_param("sci_count", "1");
  $back = konawiki_param("back", "FrontPage");
  $hash = konawiki_param("sci_hash", "");
  // check
  $count = mb_convert_kana($count, "a");
  $count = intval($count);
  if ($count <= 0) return;
  // hash check
  if (plugin_shop_cart_hash($name, $price) !== $hash) {
    echo "failed to check hash!!"; exit;
  }
  //
  $order_id = isset($_SESSION[CART_ITEMS_ID_KEY])
    ? intval($_SESSION[CART_ITEMS_ID_KEY]) : 1000;
  $items = $_SESSION[CART_ITEMS_KEY];
  $items[] = array(
    "name" => $name,
    "price" => $price,
    "count" => $count,
    "item_id" => $hash,
    "order_id" => $order_id,
    "back" => $back,
  );
  $_SESSION[CART_ITEMS_KEY] = $items;
  $_SESSION[CART_ITEMS_ID_KEY] = $order_id + 1;
}

// remove item
function plugin_shop_cart_batch_rmItem() {
  $order_id = konawiki_param("order_id","");
  $items = $_SESSION[CART_ITEMS_KEY];
  foreach ($items as $i => $item) {
    if ($item["order_id"] == $order_id) {
      unset($items[$i]);
      break;
    }
  }
  $_SESSION[CART_ITEMS_KEY] = $items;
}

function plugin_shop_cart_batch_rmAll() {
  $_SESSION[CART_ITEMS_KEY] = array();
}









