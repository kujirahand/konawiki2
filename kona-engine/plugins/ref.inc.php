<?php
/** konawiki plugins -- 添付ファイルや画像をリンクする
 * - [書式] #ref(filename,options..) または #ref(pagename\filename, options..)
 * - [引数]
 * - filename .. ファイル名
 * - options .. 
 * -- (width)x(height) .. 画像の大きさを指定する
 * -- *(caption) || ＊(caption) .. 画像にキャプションを表示する
 * -- @link .. リンク先を指定する
 * -- left || right .. 画像の回り込みを行う
 * - [使用例]
{{{
#ref(xxx.png,300x300,*猫の画像,@http://nadesi.com)
}}}
#ref(http://kujirahand.com/konawiki/attach/1.jpg,300x300,*猫の写真,@http://kujirahand.com/konawiki)
 * - [備考] なし
 */

function plugin_ref_convert($params)
{
  konawiki_setPluginDynamic(false);	
	if (count($params) == 0) { return "[usage - #ref(attachname)]"; }
  $page    = konawiki_getPage();
  $page_id = konawiki_getPageId();
  $fname = $params[0];
  if (preg_match("#^(.+)\\\\(.+)$#", $fname, $m)) { //has page
    $page  = $m[1];
    $fname = $m[2];
    $page_id = konawiki_getPageId($page);
  }
  array_shift($params);
  // is URL
  if (preg_match('#^http.?\:\/\/#',$fname)) {
    $file_url = $fname;
  }
  // is Attach file
  else {
    // attachファイルのパスを得る
    $db = konawiki_getDB();
    $fname_ = $db->escape($fname);
    $sql = "SELECT * FROM attach WHERE log_id=$page_id AND name='$fname_' LIMIT 1";
    $res = $db->array_query($sql);
    if (!isset($res[0]['id'])) {
      $fname_ = htmlspecialchars($fname);
      return "<div class='error'>[#ref:file not found:$fname_]</div>"; 
    }
    $id   = $res[0]['id'];
    $mime = $res[0]['ext'];
    $name = $res[0]['name'];
    // get real ext
    $ext = "";
    if (preg_match('/(\.\w+)$/',$name, $m)) {
      $ext = $m[1];
    }
    $file_url = konawiki_private('uri.attach')."/{$id}{$ext}";
  }
  $link_url = $file_url;
  // image file ?
  $caption = "";
  if (preg_match("#(\.jpg|\.jpeg|\.png|\.gif|\.ico)$#i", $fname)) {
    // check parameter
    $attr = array('alt'=>htmlspecialchars($fname, ENT_QUOTES));
    $style = "";
    foreach ($params as $p) {
      $p = trim($p);
      if (preg_match("#(\d+)x(\d+)#",$p,$m)) {
        $attr['width']  = intval($m[1]);
        $attr['height'] = intval($m[2]);
        continue;
      }
      $c = mb_substr($p, 0, 1);
      if ($c == "*" || $c == "＊") {
        $caption = htmlspecialchars(mb_substr($p, 1), ENT_QUOTES);
        $attr['alt'] = $caption;
      }
      else if ($c == "@" || $c == "＠") {
        $link_url = mb_substr($p, 1);
        $link_url = htmlspecialchars($link_url, ENT_QUOTES);
        if (!preg_match("#^(http|https)#", $link_url)) {
          $link_url = konawiki_getPageURL($link_url);
        }
      }
      else if ($p == "left") {
        $style .="float:left; margin:8px;";
      }
      else if ($p == "right") {
        $style .= "float:right; margin:8px";
      }
    }
    // make tag
    $attr_s = "";
    foreach ($attr as $key => $val) {
      $attr_s .= " $key='$val'";
    }
    if ($style != "") {
      $style = " style='$style'";
    }
    $img = "<img src='{$file_url}' {$attr_s}/>";
    $img = "<a href='{$link_url}'>{$img}</a>";
    if ($caption != "") {
      $caption = "<br/>".$caption;
      $html = "<div class='imagecaption'{$style}>{$img}{$caption}</div>";
    } else {
      $html = "<span{$style}>$img</span>";
    }
    // for <meta property="og:image">
    $ogimage = konawiki_public("og:image", null);
    if ($ogimage == null) konawiki_addPublic("og:image", $file_url);
    return $html."\n";
  }
  else {
    // is normal file
    $fname_enc = htmlspecialchars($fname);
    return "<a href='{$file_url}'>{$fname_enc}</a>";
  }
}

#vim:set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
