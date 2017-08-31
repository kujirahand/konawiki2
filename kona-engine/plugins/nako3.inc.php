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
 * --- post=url 保存先CGI(デフォルトは、nako3storage)
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
  $ver = "0.1.5"; // default version
  $major_vers = ['0.0.6', '0.1.0', '0.1.5']; // メジャーバージョンのみ許容
  $size_w = 300;
  $size_h = 300;
  $use_canvas = false;
  $baseurl = "";
  $editable = false;
  $post_url = "https://nadesi.com/v3/storage/index.php?0&presave";
  foreach ($params as $s) {
    if ($s == "edit" || $s == "editable") {
      $editable = true;
      continue;
    }
    if (preg_match('#rows\=([0-9]+)#', $s, $m)) {
      $rows = $m[1];
      continue;
    }
    if (preg_match('#ver\=([0-9\.\_]+)#', $s, $m)) {
      $tmp = $m[1];
      if (in_array($tmp)) {
        $ver = $tmp;
      }
      continue;
    }
    if (preg_match('#baseurl\=([0-9a-zA-Z\.\_\/\%\:\&\#]+)#', $s, $m)) {
      $baseurl = $m[1];
      continue;
    }
    if (preg_match('#post\=([0-9a-zA-Z\.\_\/\%\:\&\#]+)#', $s, $m)) {
      $post_url = $m[1];
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
      $pc = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
      $baseurl = "{$pc}nadesi.com/v3/$ver";
    }
    $jslist = array(
      $baseurl."/release/wnako3.js?v=$ver",
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
    $canvas_code =
      "<canvas id='nako3_canvas_$pid' ".
      "width='$size_w' height='$size_h'></canvas>";
  }
  $readonly = ($editable) ? "" : "readonly='1' style='background-color:#f0f0f0;'";
  $can_save = ($editable) ? 'true' : 'false';
	$html = trim(htmlspecialchars($code));
  return <<< EOS
<!-- nako3 plugin -->
{$include_js}
<style>
.nako3 { border: 1px solid #a0a0ff; padding:4px; margin:2px; }
.nako3row { margin:0; padding: 0; }
.nako3txt {
  margin:0; padding: 4px; font-size:1em; line-height:1.2em;
  width: 98%;
}
.nako3row > button { font-size:1em; padding:8px; }
.nako3info {
  background-color: #f0f0ff; padding:8px;
  font-size:1em; border:1px solid #a0a0ff; margin:4px; 
  width:95%; }
.nako3error {
  background-color: #fff0f0; padding:8px; color: #904040;
  font-size:1em; border:1px solid #a0a0ff; margin:4px; }
</style>
<div class="nako3">
<div class="nako3row">
<form id="nako3codeform_{$pid}" action="{$post_url}" method="POST">
<textarea rows="$rows" id="nako3_code_$pid" class="nako3txt" name="body" {$readonly}>
{$html}
</textarea>
<input type="hidden" name="version" value="{$ver}" />
</form>
</div>
<div class="nako3row">
  <button onclick="nako3_run($pid)">実　行</button>
  <button onclick="nako3_clear($pid)">クリア</button>
  <button id="post_button_{$pid}" onclick="nako3_post_{$pid}()">保存</button>
</div>
<div class="nako3row nako3error" id="nako3_error_$pid" style="display:none"></div>
<textarea class="nako3row nako3info" id="nako3_info_$pid" rows="5" style="display:none"></textarea>
{$canvas_code}
<div id="nako3_div_{$pid}"></div>
{$js_code}
<script>
// for post
post_button_{$pid}.style.visibility = {$can_save} ? "visible" : "hidden"
function nako3_post_{$pid}() {
  const post_button = document.getElementById('post_button_{$pid}')
  document.getElementById('nako3codeform_{$pid}').submit();
}
</script>
</div>
EOS;
}

function plugin_nako3_gen_js_code($baseurl) {
  $s_use_canvas = ($use_canvas) ? "true" : "false";
  return <<< EOS
<script>
var nako3_info_id = 0
var baseurl = "{$baseurl}"
var use_canvas = $s_use_canvas
var nako3_get_info = function () {
  return document.getElementById("nako3_info_" + nako3_info_id)
}
var nako3_get_error = function () {
  return document.getElementById("nako3_error_" + nako3_info_id)
}
var nako3_get_canvas = function () {
  return document.getElementById("nako3_canvas_" + nako3_info_id)
}
var nako3_print = function (s) {
  var info = nako3_get_info();
  if (!info) {
    console.log(s)
    return
  }
  s = "" + s; // 文字列に変換
  if (s.substr(0, 5) == "[err]") {
    s = s.substr(5)
    var err = nako3_get_error()
    err.innerHTML = s
    err.style.display = 'block'
  } else {
    info.innerHTML += to_html(s) + "\\n"
    info.style.display = 'block'
  }
}
var nako3_clear = function (s) {
  var info = nako3_get_info()
  if (!info) return
  info.innerHTML = ''
  info.style.display = 'none'
  var err = nako3_get_error()
  err.innerHTML = ''
  err.style.display = 'none'
  var canvas = nako3_get_canvas()
  if (!canvas) return
  var ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, canvas.width, canvas.height)
}
navigator.nako3.setFunc("表示", nako3_print)
navigator.nako3.setFunc("表示ログクリア", nako3_clear)
function to_html(s) {
  s = '' + s
  return s.replace(/\&/g, '&amp;')
          .replace(/\</g, '&lt;')
          .replace(/\>/g, '&gt;')
          .replace(/\\n/g, '<br>')
}
function nako3_run(id) {
  var code_e = document.getElementById("nako3_code_"+id);
  if (!code_e) return;
  var code = code_e.value;
  code =
    "カメ描画先=「nako3_canvas_" + id + "」;" +
    "カメ全消去;" +
    "カメ画像URL=「" + baseurl + "/demo/turtle.png」;"+code;
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
