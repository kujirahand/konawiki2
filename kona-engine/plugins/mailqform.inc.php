<?php
/** konawiki plugins -- メールでアンケートを送信する 
 * - [書式] #mailqform(to_email,subject,options...)
 * - [引数]
 * - to_email .. 送信先アドレス
 * - subject .. メールの件名
 * - options .. アンケート内容
 * -- item ... 質問事項
 * -- item=s1|s2|s3 ... 選択肢付き質問事項
 * -- @q=a ... いたずら送信防止用の質問(q)と答え(a)
 * - [使用例]
{{{
#mailqform(web@kujirahand.com,test,名前,性別=男|女,感想,@「3+5」の答えを半角で入力=8
}}}
#mailqform(web@kujirahand.com,test,名前,性別=男|女,感想,@「3+5」の答えを半角で入力=8
 * - [備考] なし
 */

function plugin_mailqform_convert($params)
{
  konawiki_setPluginDynamic(false);	
	if (count($params) == 0) { return "[usage - #mailqform(to_email,opt...)]"; }
  $page = konawiki_getPage();
  $to_email = array_shift($params);
  $subject  = array_shift($params);
  $baseurl = konawiki_public("baseurl");
  $pluginname = "mailqform";
  $pid = konawiki_getPluginInfo($pluginname, "pid", 0);

  // get items
  $url = konawiki_getPageURL();
  $form = "<form action='$url' method='post'>".
          "<table>";
  $itazura_a = "";
  $no = 1;
  $items = array();
  while ($params) {
    $item = array_shift($params);
    $q = null;
    // いたずら検証用
    if (substr($item,0,1) == "@") {
      $item = substr($item, 1);
      $a = explode("=", $item, 2);
      $item_ = htmlspecialchars($a[0]);
      $itazura_a = $a[1];
      $form .= "<tr><th>*</th>";
      $form .= "<td>{$item_}<br>";
      $form .= "<input type='text' name='mfii'></td></tr>";
      continue;
    }
    // 選択肢があるか？
    if (preg_match("#(.+)\=(.+)#", $item, $m)) {
      $item = $m[1];
      $q = explode("|",$m[2]);
    }
    // html
    $items[] = $item;
    $item_ = htmlspecialchars($item);
    $form .= "<tr><th>{$item_}</th>";
    $def = konawiki_param("mfi{$no}", "");
    $def_ = htmlspecialchars($def, ENT_QUOTES);
    if ($q) {
      $form .= "<td><select name='mfi{$no}' rows='1'>";
      foreach ($q as $v) {
        $v_ = htmlspecialchars($v, ENT_QUOTES);
        $c = ($v_ == $def_) ? "selected" : "";
        $form .= "<option value='$v_' {$c}>$v_</option>";
      }
      $form .= "</select></td>";
    } else {
      $form .= "<td><input type='text' name='mfi{$no}' size='50' ".
               " value='{$def_}'></td>";
    }
    $form .= "</tr>\n";
    $no++;
  }
  $form .= "<tr><th></th><td><input type='submit'></td></tr>";
  $form .= 
    "</table>".
    "<input type='hidden' name='pid' value='$pid'>".
    "<input type='hidden' name='mailqform_mode' value='go'>".
    "</form>";

  // already sent?
  if (konawiki_param("mailqform_mode", "") != "go") return $form;
  // pid check
  if (konawiki_param("pid", -1) != $pid) return $form;
  // いたずらチェック
  if ($itazura_a != "") {
    if ($itazura_a != konawiki_param("mfii","")) {
      $err = konawiki_lang("Please input anti-spam question.");
      $form = "<div class='error'>$err</div>".$form;
      return $form;
    }
  }
  // send
  $text = "";
  foreach ($items as $i => $v) {
    $no = $i + 1;
    $text .= $v . "=" . konawiki_param("mfi{$no}", "") . "\n";
  }
  // mail
  $header  = "From: $to_email\n";
  $header .= "Reply-To: $to_email";
  mb_send_mail($to_email, $subject, $text, $header);
  $msg = konawiki_lang("Thank you for your message!");
  return "<div>$msg</div>";
}

#vim:set expandtab tabstop=2 softtabstop=2 shiftwidth=2:
