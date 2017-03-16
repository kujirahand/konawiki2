<?php
/** konawiki plugins -- なでしこ3のWEBエディタを表示する 
 * - [書式]
{{{
#nako3(なでしこのプログラム);
}}}
 * - [引数]
 * -- rows=num エディタの行数
 * -- ver=xxx なでしこ3のバージョン
 * -- canvas canvasを用意する場合に指定
 * -- baseurl=url なでしこ3の基本URL
 * -- edit/editable 編集可能な状態にする
 * -- size=(width)x(height) canvasの幅と高さ
 * - [使用例] #nako3(なでしこのプログラム);
{{{
#nako3(なでしこのプログラム);
}}}
 * - [備考]
 * - [公開設定] 公開
 */

function plugin_nako3_convert($params)
{
  konawiki_setPluginDynamic(true);
  $pid = konawiki_getPluginInfo("nako3", "pid", 1);
  konawiki_setPluginInfo("nako3", "pid", $pid+1);
  
  // default value
  $code = "";
  $rows = 5;
  $ver = "0.0.3";
  $size_w = 300;
  $size_h = 300;
  $use_canvas = false;
  $baseurl = "";
  $editable = false;
  foreach ($params as $s) {
    if ($s == "edit" || $s == "editable") {
      $editable = true;
      continue;
    }
    if (preg_match('#rows\=([0-9]+)#', $s, $m)) {
      $rows = $m[1];
      continue;
    }
    if (preg_match('#ver\=([0-9a-zA-Z\.\_]+)#', $s, $m)) {
      $ver = $m[1];
      continue;
    }
    if (preg_match('#baseurl\=([0-9a-zA-Z\.\_\/\%\:\&\#]+)#', $s, $m)) {
      $baseurl = $m[1];
      continue;
    }
    if ($s == "canvas") {
      $use_canvas = true;
      continue;
    }
    if (preg_match('#size\=([0-9]+)x([0-9]+)#', $s, $m)) {
      $use_canvas = true;
      $size_w = $m[1];
      $size_h = $m[2];
      continue;
    }
    $code = $s;
    break;
  }
  // URL
  $include_js = "";
  if ($pid == 1) {
    if ($baseurl == "") {
      $baseurl = "http://files.nadesi.com/nako3/$ver";
    }
    $jslist = array(
      $baseurl."/release/wnako3.js",
      $baseurl."/release/plugin_turtle.js"
    );
    foreach ($jslist as $js) {
      $include_js .= "<script src='$js'></script>";
    }
  }
  // JS_CODE
  $js_code = "";
  if ($pid == 1) {
    $js_code = plugin_nako3_gen_js_code($baseurl);
  }
  // CODE
  $canvas_code = "";
  if ($use_canvas) {
    $canvas_code = "<canvas id='nako3_canvas_$pid'></canvas>";
  }
  $readonly = ($editable) ? "" : "readonly='1' style='background-color:#f0f0f0;'";
	$html = trim(htmlspecialchars($code));
  return <<< EOS
<!-- nako3 plugin -->
{$include_js}    
<style>
.nako3 { border: 1px solid #ffa0ff; padding:4px; margin:0px; }
.nako3row { margin:0; padding: 0; }
.nako3txt {
  margin:0; padding: 4px; font-size:1em; line-height:1.2em; 
  width: 98%;
}
.nako3row > button { font-size:1em; padding:8px; }
.nako3info { background-color: #f0f0ff; padding:8px; 
  font-size:1em; border:1px solid #a0a0ff; margin:4px; }
</style>
<div class="nako3">
<div class="nako3row">
<textarea rows="$rows" id="nako3_code_$pid" class="nako3txt" {$readonly}>
{$html}
</textarea></div>
<div class="nako3row"><button onclick="nako3_run($pid)">実　行</button>
<button onclick="nako3_clear($pid)">クリア</button></div>
<div class="nako3row nako3info" id="nako3_info_$pid"></div>
{$canvas_code}
{$js_code}
</div>
EOS;
}

function plugin_nako3_gen_js_code($baseurl) {
  return <<< EOS
<script>
var nako3_info_id = 0;
var baseurl = "{$baseurl}";
var nako3_get_info = function (id) {
  return document.getElementById("nako3_info_" + nako3_info_id);
};
var nako3_print = function (s) {
  var info = nako3_get_info(nako3_info_id);
  if (!info) {
    console.log(s); return;
  }
  s = "" + s; // 文字列に変換
  if (s.substr(0, 5) == "[err]") {
    s = s.substr(5);
    s = "<span style='color:red'>" + to_html(s) + "</span>";
    info.innerHTML = s;
  } else {
    info.innerHTML += to_html(s) + "<br>";
  }
};
var nako3_clear = function (s) {
  var info = nako3_get_info(nako3_info_id);
  if (!info) return; 
  info.innerHTML = "";
};
navigator.nako3.setFunc("表示", nako3_print);
navigator.nako3.setFunc("表示ログクリア", nako3_clear);
function to_html(s) {
  s = "" + s;
  return s.replace(/\&/g, '&amp;')
          .replace(/\</g, '&lt;')
          .replace(/\>/g, '&gt;')
          .replace(/\\n/g, '<br>');
}
function nako3_run(id) {
  var code_e = document.getElementById("nako3_code_"+id);
  if (!code_e) return;
  var code = code_e.value;
  code = 
    "カメ描画先=「nako3_canvas_" + id + "」;" + 
    "カメ画像URL=「" + baseurl + "/demo/turtle.png」;"+
    code;
  try {
    nako3_info_id = id;
    nako3_clear();
    navigator.nako3.run(code);
  } catch (e) {
    nako3_print("[err]" + e.message + "");
    console.log(e);
  }
}
</script>
EOS;
}


