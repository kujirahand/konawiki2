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

function plugin_shop_cart_form_convert($params)
{
  $err = array();
  $head = "<div id='shop_cart'>\n";
  $foot = "</div><!-- end of shop_cart -->\n";
  $page = konawiki_getPage();
  //
  // モードで分岐する
  $sc_mode = konawiki_param('sc_mode', '');
  if ($sc_mode == "logout") {
    return $head . shop_cart_logout() . $foot;
  }
  if ($sc_mode == "input_customer") {
    return 
      $head .
      shop_cart_getCustomerForm() .
      $foot;
  }
  else if ($sc_mode == 'save1') {
    $err = shop_cart_save1();
    if ($err) {
      return $head .
        "<div class='error'>".implode("<br>", $err)."</div>".
        shop_cart_getCustomerForm().
        $foot;
    }
    return $head . shop_cart_confirm() . $foot;
  }
  else if ($sc_mode == "login_form") {
    return
      $head.
      shop_cart_login_form() .
      shop_cart_getTable() .
      $foot;
  }
  else if ($sc_mode == "login_try") {
    return $head . shop_cart_login_try() . $foot;
  }
  // --- その他の場合 ---
  $html = $head;
  // 買い物かごのテーブルを作る
  $html .= shop_cart_getTable();
  // お客様情報の入力
  $url = konawiki_getPageURL(
    konawiki_getPage(),
    "show", "",
    "sc_mode=input_customer");
  $html .= form_tag($url);
  $html .= form_input_submit("注文する (お客様の情報を入力)");
  $html .= "</form>\n";
  $back = konawiki_param("back", konawiki_public('FrontPage'));
  $back_url = konawiki_getPageURL($back);
  $html .= form_tag($back_url);
  $html .= form_input_submit("お買い物に戻る");
  $html .= "</form>\n";
  //
  return $html.$foot;
}

function shop_cart_getTable() {
  $page = konawiki_getPage();
  $html = "";
  $html .= "<h3>買い物かごの内容:</h3>";
  // カゴの中のアイテム  
  $total = 0;
  $items = array();
  if (isset($_SESSION['cart_items'])) {
    $items = array_merge($items, $_SESSION[CART_ITEMS_KEY]);
  }
  if (count($items) == 0) {
    return $html .
      "<div style='background-color:#f0f0f0;padding:8px;'>".
      "* かごの中に何も入っていません。</div><br>";
  }
  // カートを空にするコード
  $clearurl = konawiki_getPageURL($page, "plugin", "",
    "name=shop_cart_batch&p=rmAll&back=".urlencode($page));
  $clean_html =
    "<form method='post' action='$clearurl' onsubmit='return checkSubmit()'>".
    "<input type='submit' value='全てを空にする'>".
    "</form>";
  // HTML
  $html .= html_js_out(
    "shop_cart_form_js",
    ' function checkSubmit(){ '.
    '   return confirm("本当に実行しますか?"); '.
    ' }');
  // CSS
  html_css_add(".tbl_r", array(
    "text-align" => "right"
  ));
  $html .= html_css_out("shop_cart_form");
  // Table
  $html .= "<table>";
  $html .= "<tr><th>注文ID</th><th>商品名</th><th>単価</th>".
           "<th>個数</th><th>小計</th>".
           "<th>*</th></tr>\n";
  for ($i = 0; $i < count($items); $i++) {
    $row = $items[$i];
    $order_id = intval($row["order_id"]);
    $name     = $row["name"];
    $price    = intval($row["price"]);
    $count    = intval($row["count"]);
    $back     = $row["back"];
    $sum      = $price * $count;
    $total += intval($sum);
    $backurl = konawiki_getPageURL($back);
    $name_  = "<a href='$backurl'>". htmlspecialchars($name)."</a>";
    $price_ = number_format($price);
    $sum_   = number_format($sum);
    // remove button
    $url = konawiki_getPageURL($page, "plugin", "",
      "name=shop_cart_batch&p=rmItem&order_id=$order_id&back=".urlencode($page));
    $remove_btn = "<form method='post' action='$url'>".
      "<input type='submit' value='削除'></form>";
    //
    $html .= "<tr>".
      "<td>$order_id</td>".
      "<td>$name_</td><td class='tbl_r'>$price_</td>".
      "<td class='tbl_r'>$count</td><td class='tbl_r'>$sum_</td>".
      "<td>$remove_btn</td></tr>\n";
  }
  // 合計
  $total_ = number_format($total);
  $total_desc = "合計(送料を含まない額)";
  $html .= "<tr><th colspan='4'>$total_desc</th>".
           "<th class='tbl_r'>$total_</th><th>$clean_html</th></tr>\n";
  $html .= "</table><br>";
  return $html;
}

function shop_cart_table_text() {
  $page = konawiki_getPage();
  // カゴの中のアイテム
  $total = 0;
  $items = array();
  if (isset($_SESSION['cart_items'])) {
    $items = array_merge($items, $_SESSION[CART_ITEMS_KEY]);
  }
  if (count($items) == 0) {
    return "";
  }
  // Table
  $txt = "";
  for ($i = 0; $i < count($items); $i++) {
    $row = $items[$i];
    $order_id = intval($row["order_id"]);
    $name     = $row["name"];
    $price    = intval($row["price"]);
    $count    = intval($row["count"]);
    $sum      = $price * $count;
    $total += intval($sum);
    $price_ = number_format($price);
    $sum_ = number_format($sum);
    $txt .= "$name(￥{$price_}) x {$count}個 = ￥{$sum_}\r\n";
  }
  // 合計
  $total_ = number_format($total);
  $total_desc = "合計(送料を含まない額) ￥{$total_}-";
  $txt .= "---\r\n$total_desc\r\n";
  return $txt;
}

function shop_cart_def($name) {
  $def = konawiki_param($name, "");
  if ($def == "") {
    if (isset($_SESSION[CART_ITEMS_SENDTO][$name])) {
      $def = $_SESSION[CART_ITEMS_SENDTO][$name];
    }
  }
  return $def;
}

function shop_cart_input($name, $caption, $is_pw = FALSE) {
  $def = shop_cart_def($name);
  $html  = form_label($name, $caption)."<br>";
  $html .= ($is_pw)
             ? form_input_password($name, "")."<br>"
             : form_input_text($name, $def, array(
                 "style"=>"width:90%; margin-bottom:10px;")). "<br>";
  return $html;
}

function shop_cart_getCustomerForm() {
  $page = konawiki_getPage();
  $html = "";
  //
  $login_link = "";
  if (shop_cart_is_login()) {
    $logout_url = konawiki_getPageURL(
      konawiki_getPage(),
      "show", "",
      "sc_mode=logout");
    $login_link = " [<a href='$logout_url'>→ログアウト</a>]";
  } else {
    $login_url = konawiki_getPageURL(
      konawiki_getPage(),
      "show", "",
      "sc_mode=login_form");
    $login_link = "[<a href='$login_url'>→ログイン</a>]";
  }
  $html .= "<h3>お客様の情報: {$login_link}</h3>";
  // style
  html_css_add('.shop_cart_box', array(
    "background-color" => "#f0f0f0",
    "padding" => "12px",
  ));
  html_css_add('#shop_cart input', array(
    "font-size" => "1.2em",
  ));
  html_css_add('#shop_cart label', array(
    'padding-top' => '12px',
  ));
  $html .= html_css_out('shop_cart_input');
  //
  // konawiki_getPageURL($page, "plugin", "", "name=shop_cart_batch&p=setSender");
  $url = konawiki_getPageURL($page);
  $html .= form_tag($url);
  $html .= "<div class='shop_cart_box'>";
  $html .= shop_cart_input('sc_name', 'お名前:');
  if (!shop_cart_is_login()) {
    $html .= shop_cart_input('sc_email', 'メール:');
    $html .= form_label('sc_email2a', 'メール(確認のため再度入力してください):')."<br>";
    $email2a = shop_cart_def('sc_email2a');
    $email2b = shop_cart_def('sc_email2b');
    $html .= form_input_text('sc_email2a', $email2a, 
      array("size"=>"6", "style"=>"margin-bottom:10px;"))."@";
    $html .= form_input_text('sc_email2b', $email2b, 
      array("size"=>"12"))."<br>";
    $html .= shop_cart_input('sc_pw', '保存用パスワード(次回ご利用のため):', TRUE);
  }
  $html .= shop_cart_input('sc_tel', '電話:');
  $html .= shop_cart_input('sc_zip', '郵便番号:');
  $html .= shop_cart_input('sc_addr', '住所:');
  $html .= "</div><br>";
  $html .= "<div>もしも、お客様と送り先が異なる場合、以下をご入力ください。:</div>";
  $html .= "<div class='shop_cart_box'>";
  $html .= shop_cart_input('sc_name2', '送り先のお名前:');
  $html .= shop_cart_input('sc_tel2', '送り先の電話:');
  $html .= shop_cart_input('sc_zip2', '送り先の郵便番号:');
  $html .= shop_cart_input('sc_addr2', '送り先の住所:');
  $html .= "</div>";
  $html .= "<br/>";
  $html .= form_input_hidden('sc_mode', 'save1');
  $html .= form_input_submit('注文を確定する');
  $html .= "</form>";
  return $html;
}

function shop_cart_is_login() {
  return isset($_SESSION[CART_ITEMS_LOGIN]);
}
function shop_cart_logout() {
  unset($_SESSION[CART_ITEMS_LOGIN]);
  $url = konawiki_getPageURL();
  $link = "<a href='$url'>→戻る</a>";
  return "<h3>ログアウトしました</h3><p>$link</p>";
}

function shop_cart_save1() {
  $err = array();
  $keys = array(
    'sc_name', 'sc_email', 'sc_pw', 'sc_tel', 'sc_zip', 'sc_addr',
    'sc_email2a', 'sc_email2b',
    'sc_name2', 'sc_tel2', 'sc_zip2', 'sc_addr2');
  $info = array();
  foreach ($keys as $key) {
    $val = shop_cart_def($key);
    $val = mb_convert_kana($val, "as");
    $val = trim($val);
    $info[$key] = $val;
  }
  // check
  if ($info['sc_name'] == "") {
    $err[] = "お名前が未入力です。";
  }
  if ($info['sc_email'] == "") {
    $err[] = "メールが未入力です。";
  }
  if (!shop_cart_is_login()) {
    // email check
    if ($info['sc_email'] != $info['sc_email2a'].'@'.$info['sc_email2b']) {
      $err[] = 'メールがメール確認と一致しません。再入力ください。';
    }
    if ($info['sc_pw'] == "") {
      $err[] = "保存用パスワードが未入力です。";
    }
  }
  if ($info['sc_tel'] == "") {
    $err[] = "お電話が未入力です。";
  }
  if ($info['sc_zip'] == "") {
    $err[] = "郵便番号が未入力です。";
  }
  if (!preg_match('#^\d{3}-\d{4}$#', $info["sc_zip"])) {
    $err[] = "郵便番号は[nnn-nnnn]の形式で入力してください";
  }
  if ($info['sc_addr'] == "") {
    $err[] = "住所が未入力です。";
  }
  // save
  if (count($err) == 0) {
    $_SESSION[CART_ITEMS_SENDTO] = $info;
    $_SESSION[CART_ITEMS_LOGIN] = time();
    shop_cart_save_customer($info);
    // $i = shop_cart_get_customer($info["sc_email"]);
  }
  return $err;
}

function shop_cart_pw_hash($password) {
  $key = SHOP_CART_PW_SALT . $password;
  return hash("sha256", $key);
}

function shop_cart_save_customer($info) {
  $email = $info["sc_email"];
  $info["sc_pw"] = shop_cart_pw_hash($info["sc_pw"]);
  // check exists?
  $db = new PDO("sqlite:".SHOP_CART_CUSTOMER_DB);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $q = $db->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
  $res = $q->execute(array($email));
  $is_insert = true;
  if ($res) {
    $r = $q->fetch();
    if (isset($r["data"])) {
      $is_insert = false;
    }
  }
  // insert or update
  if ($is_insert) {
    // insert
    $q = $db->prepare("INSERT INTO customers (email, data, ctime, mtime) ".
                      "VALUES (?,?,?,?)");
    $q->execute(array($email, json_encode($info), time(), time()));
  } else {
    // update
    $q = $db->prepare("UPDATE customers SET data=?,mtime=? WHERE email=?");
    $q->execute(array(json_encode($info), time(), $email));
  }
}

// query customer data
function shop_cart_get_customer($email) {
  $db = new PDO("sqlite:".SHOP_CART_CUSTOMER_DB);
  $q = $db->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
  $res = $q->execute(array($email));
  if ($res) {
    $r = $q->fetch();
    if (isset($r["data"])) {
      $data = json_decode($r["data"], TRUE);
      $data["cid"] = $r["cid"];
      return $data;
    }
  }
  return null;
}

function shop_cart_login_form() {
  $html  = "";
  $url = konawiki_getPageURL(
    konawiki_getPage(),
    "show", "",
    "sc_mode=login_try");
  $html .= "<h3>お客様ログイン</h3>";
  $html .= form_tag($url, "post");
  $html .= shop_cart_input("sc_email", "メール:");
  $html .= shop_cart_input("sc_pw", "パスワード:", true);
  $html .= form_input_submit("ログイン");
  $html .= "</form>";
  return $html;
}
function shop_cart_login_try() {
  $email = trim(konawiki_param("sc_email", ""));
  $pass = trim(konawiki_param("sc_pw", ""));
  $login_url = konawiki_getPageURL(
    konawiki_getPage(),
    "show", "",
    "sc_mode=login_form");
  $giveup_url = konawiki_getPageURL();
  $login_link =
    "<ul>".
    "<li><a href='$login_url'>→もう一度、ログインを試す</a></li>".
    "<li><a href='$giveup_url'>→ログインせずに情報を入力する</a></li>".
    "</ul>";
  $html_error =
    "<h3>ログインできませんでした</h3>".
    "<p>メール・パスワードをよくご確認ください。</p>".
    "<p>$login_link</p>";
  if ($email == "" || $pass == "") {
    return $html_error;
  }
  $w = shop_cart_get_customer($email);
  if ($w == null) {
    return $html_error;
  }
  // check password
  $pw_hash = shop_cart_pw_hash($pass);
  if ($w["sc_pw"] !== $pw_hash) {
    return $html_error;
  }
  // success
  $_SESSION[CART_ITEMS_SENDTO] = $w;
  $_SESSION[CART_ITEMS_LOGIN] = time();
  $url = konawiki_getPageURL(
    konawiki_getPage(),
    "show", "",
    "sc_mode=input_customer"
  );
  // message
  return
    "<h3>ログイン成功しました</h3>".
    "<p><a href='$url'>→ありがとうございます。".
    "こちらをクリックしてください。</a></p>";
}

function shop_cart_confirm() {
  // --- sendmail ---
  $master_email = konawiki_private('webmaster.email', '');
  if ($master_email == "") {
    return "<h3>システムエラー</h3>".
      "<p>konawiki.ini.phpにて、以下の設定を行ってください。</p>".
      "<p> \$private['webmaster.email'] = '***'; </p>";
  }
  //
  $customer_email = shop_cart_def("sc_email");
  $w = shop_cart_get_customer($customer_email);
  if ($customer_email == "") {
    return "<h3>お客様情報を入力してください</h3>";
  }
  $site_title = konawiki_public("title");
  $sub = konawiki_public("shop_cart.email.subject","[$site_title]");
  $subject = $sub.SHOP_CART_EMAIL_SUBJECT;
  $orders = shop_cart_table_text();
  $date = date("Y-m-d H:i:s");
  $body = <<< EOS
{$w["sc_name"]}様

この度は、Webサイト「{$site_title}」にて、
お買い物いただきまして、誠にありがとうございました。

本メールは、お客様のご注文をお受けした時点で、自動的に送信されるメールになります。
実際にショップからのご連絡は、このメール後で、送信致します。

■ご注文内容:

{$orders}

■お客様の情報

お名前: {$w["sc_name"]}
メール: {$w["sc_email"]}
電話: {$w["sc_tel"]}
郵便番号: {$w["sc_zip"]}
住所: {$w["sc_addr"]}

■送り先（もしも異なる場合）の情報

郵送先のお名前: {$w["sc_name2"]}
郵送先の電話: {$w["sc_tel2"]}
郵送先の郵便番号: {$w["sc_zip2"]}
郵送先の住所: {$w["sc_addr2"]}

■情報確定日時

{$date}

以上、ご確認のほど、よろしくお願いします。

※こちらの注文に覚えがない場合は、他の方が誤って
あなたのメールアドレスで注文した可能性があります。
その場合は、大変お手数ですが、下記までお知らせください。

---
{$site_title}
mailto:{$master_email}
EOS;
  // to customer
  $head = "From:$master_email\r\n";
  mb_send_mail($customer_email, $subject, $body, $head);
  // to web master
  $head = "From:$master_email\r\nReply-To:$customer_email\r\n";
  mb_send_mail($master_email, $subject, $body, $head);
  //
  $items = json_encode($_SESSION[CART_ITEMS_KEY]);
  $db = new PDO("sqlite:".SHOP_CART_CUSTOMER_DB);
  $stmt = $db->prepare("INSERT INTO history (cid,data,ctime)values(?,?,?);");
  $stmt->execute(array(intval($w["cid"]), $items, time()));
  $_SESSION[CART_ITEMS_KEY] = array(); // 注文を空にする
  // --- output ---
  $html = "";
  $html .= "<h3>注文ありがとうございました。</h3>";
  $html .= "<p>ただいま、お客様に確認のメールを送信しました。</p>";
  $html .= "<h3>この後の流れ</h3>";
  $html .= "<p>(1) この後、担当の者がご注文内容を元に在庫確認を行います。</p>";
  $html .= "<p>(2) 注文内容に送料を含めた正確なお値段を、担当の者が、お客様にメールします。</p>";
  $html .= "<p>(3) お客様からの入金を確認したら、商品を郵送します。</p>";
  $html .= "<br>";
  $html .= "<h3>送信したメールの内容</h3><pre>".htmlspecialchars($body)."</pre>";
  return $html;
}



