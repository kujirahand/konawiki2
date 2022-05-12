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

require __DIR__.'/nako_version.inc.php';

function plugin_nako3_convert($params)
{
  konawiki_setPluginDynamic(true);
  $pid = konawiki_getPluginInfo("nako3", "pid", 1);
  konawiki_setPluginInfo("nako3", "pid", $pid+1);

  // default value
  $code = "";
  $rows = 5;
  $ver = NAKO_DEFAULT_VERSION; // default version
  $size_w = 400;
  $size_h = 300;
  $use_canvas = false;
  $baseurl = "";
  $editable = false;
  $post_url = "https://n3s.nadesi.com/index.php?page=0&action=presave";
  foreach ($params as $s) {
    if ($s == "edit" || $s == "editable") {
      $editable = true;
      continue;
    }
    if (preg_match('#rows\=([0-9]+)#', $s, $m)) {
      $rows = $m[1]; continue;
    }
    if (preg_match('#ver\=([0-9\.]+)#', $s, $m)) {
      $ver = $m[1]; continue;
    }
    if (preg_match('#post\=([0-9a-zA-Z\.\_\/\%\:\&\#]+)#', $s, $m)) {
      $post_url = $m[1]; continue;
    }
    if ($s == "canvas") {
      $use_canvas = true; continue;
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
      $baseurl = "https://n3s.nadesi.com/cdn.php?v=$ver&f=";
    }
    $jslist = array(
      // nadesiko
      $baseurl."release/wnako3.js",
      $baseurl."release/plugin_csv.js",
      // $baseurl."release/plugin_datetime.js", // v3.2.31で省略可能に
      $baseurl."release/plugin_markup.js",
      $baseurl."release/plugin_kansuji.js",
      $baseurl."release/plugin_turtle.js",
      $baseurl."release/plugin_webworker.js",
      $baseurl."release/plugin_caniuse.js",
      $baseurl."release/nako_gen_async.js", // 「!非同期モード」を使うとき
      "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.2.1/chart.min.js",
    );
    foreach ($jslist as $js) {
      $include_js .= "<script defer src='$js'></script>";
    }
  }
  // JavaScriptとCSSは1回だけあれば良い
  $js_code = "";
  $style_code = "";
  if ($pid == 1) {
    $js_code = plugin_nako3_gen_js_code($baseurl, $use_canvas);
    $style_code = plugin_nako3_gen_style_code();
  }
  // CODE
  $canvas_code = "";
  if ($use_canvas) {
    $canvas_code =
      "<canvas id='nako3_canvas_$pid' ".
      "width='$size_w' height='$size_h'></canvas>";
  }
  $j_use_canvas = ($use_canvas) ? 1 : 0;
  $readonly = ($editable) ? "" : "readonly='1' style='background-color:#f0f0f0;'";
  $can_save = ($editable) ? 'true' : 'false';
	$html = trim(htmlspecialchars($code));
  return <<< EOS
<!-- nako3 plugin -->
{$include_js}{$style_code}
<div class="nako3">

<div id="nako3_editor_main_{$pid}" class="nako3row">
<form id="nako3codeform_{$pid}" action="{$post_url}" method="POST">
<textarea rows="$rows" id="nako3_code_$pid"
          class="nako3txt" name="body" {$readonly}>{$html}</textarea>
<input type="hidden" name="version" value="{$ver}" />
</form>
</div><!-- end of #nako3_editor_main_{$pid} -->
<div id="nako3_editor_controlls_{$pid}" class="nako3row nako3ctrl" style="padding-bottom:4px;">
  <button onclick="nako3_run($pid, $j_use_canvas)">▶ 実行 </button>
  <button onclick="nako3_clear($pid, $j_use_canvas)">クリア</button>
  <span id="post_span_{$pid}" class="post_span">
    <button id="save_button_{$pid}" onclick="nako3_save_storage({$pid})">保存</button>
    <button style="display:none" id="load_button_{$pid}" onclick="nako3_load_click({$pid})">開く</button>
    &nbsp;
    <button style="display:none" id="post_button_{$pid}" onclick="nako3_post({$pid})">公開</button>
  </span>
  <span>
    <span class='nako3cur' id="cur_pos_{$pid}">1行目</span>
    <span class='nako3ver'>&nbsp;&nbsp;&nbsp;v{$ver}</span>
  </span>
</div><!-- end of #nako3_editor_controlls_{$pid} -->
<!-- LOAD AREA -->
<div id="nako3start_files_{$pid}" class="nako3files" style="display:none"></div>

<!-- ERROR -->
<div class="nako3row nako3error" id="nako3_error_{$pid}" style="display:none"></div>

<!-- RESULT -->
<div id="nako3result_div_$pid" class="nako3row" style="display:none;">
  <textarea class="nako3row nako3info" readonly
            id="nako3_info_$pid" rows="5" style="display:none"></textarea>
  <div id="nako3_info_html_$pid" class="nako3info_html" style="display:none"></div>
</div><!-- end of #nako3_error_{$pid} -->

<!-- USER FORM - FREE DOM AREA -->
<div id="nako3_div_{$pid}" class="nako3_div"></div>
<!-- CANVAS -->
{$canvas_code}

{$js_code}
</div><!-- end of #nako3 -->

<!-- dynamic js code -->
<script>nako3_init_edit_area({$pid},{$can_save},{$j_use_canvas})</script>

<!-- /nako3 plugin -->
EOS;
}

// ---------------------------------------------------------
// CSS - 1度だけ取り込まれる
// ---------------------------------------------------------
function plugin_nako3_gen_style_code() {
  // --- CSS --
  return <<< EOS
<style>
.nako3 { border: 1px solid #a0a0ff; padding:4px; margin:2px; }
.nako3row { margin:0; padding: 0; }
.nako3ctrl {
    border-bottom: 1px dotted silver;
}
.nako3txt {
  margin:0; padding: 4px; font-size:1em; line-height:1.4em;
  width: 98%;
}
.nako3row  > button, 
.post_span > button { font-size:1em; padding:8px; }
.post_span { margin-left: 8px; }
.tmp_btn {
  border-bottom: 1px solid gray;
  text-decoration: none;
  padding: 4px;
  font-size: 0.8em;
  background-color: #f3f3ff;
}
.tmp_btn > a {
  color: black;
}
.nako3info {
  background-color: #f0f0ff;
  border: 1px solid #a0a0ff;
  padding: 4px; margin: 0;
  font-size: 1em;
  width:98%;
}
.nako3error {
  background-color: #fff0f0; padding:8px; color: #904040;
  font-size:1em; border:1px solid #a0a0ff; margin:4px;
}
.nako3ver { font-size:0.6em; color:gray; }
.nako3cur {
  margin:0; padding: 2px; font-size: 0.6em;
  color:#505050; background-color: #f0f0f0;
}
.nako3info_html {
  border: 1px solid #a0a0a0;
  padding: 4px; margin: 4px;
}
.nako3_conv_html_link {
  color: navy;
  font-size: 9px; padding: 4px;
  border: 1px solid silver;
  background-color: #f0f0f0;
}
.nako3_div {
  font-size: 1em;
  line-height: 1.4em;
  padding: 18px;
}
.nako3_div button {
  margin: 4px;
  padding: 4px;
  font-size: 0.9em;
}
.nako3_div input {
  margin: 6px;
  padding: 6px;
}
.nako3_div input[type=checkbox] {
  padding: 4px;
  margin: 4px;
}
.nako3file {
  background-color: #f0f0f0;
  color: black;
  font-size: 0.9em;
  border: 1px solid gray;
  margin: 2px; padding: 8px;
}
.nako3files {
  background-color: #f0f0ff;
  font-size:0.8em; color: gray;
  border: 1px dashed gray;
  padding: 12px; margin: 8px;
  line-height: 2.8em;
}
</style>
EOS;
}
// ---------------------------------------------------------
// JavaScript - 1度だけ取り込まれる
// ---------------------------------------------------------
function plugin_nako3_gen_js_code($baseurl, $use_canvas) {
  $s_use_canvas = ($use_canvas) ? "true" : "false";
  $j_use_canvas = ($use_canvas) ? 1 : 0;
  return <<< EOS
<script type="text/javascript">
var nako3_info_id = 0
var baseurl = "{$baseurl}"
var use_canvas = $s_use_canvas
function qs(query) {
  return document.querySelector(query)
}
var nako3_get_resultbox = function () {
  return qs("#nako3result_div_" + nako3_info_id)
}
var nako3_get_info = function () {
  return qs("#nako3_info_" + nako3_info_id)
}
var nako3_get_error = function () {
  return qs("#nako3_error_" + nako3_info_id)
}
var nako3_get_canvas = function () {
  return qs("#nako3_canvas_" + nako3_info_id)
}
var nako3_get_div = function () {
  return qs("#nako3_div_" + nako3_info_id)
}
// 表示
var nako3_print = function (s, sys) {
  var info = nako3_get_info()
  if (!info) return
  var box = nako3_get_resultbox()
  box.style.display = 'block'
  s = "" + s // 文字列に変換
  if (s.substr(0, 9) == "==ERROR==") {
    // エラーだった場合
    s = s.substr(9)
    var err = nako3_get_error()
    err.innerHTML = s
    err.style.display = 'block'
    return
  } else {
    // エラー以外の場合
    if (!sys) { sys = {} }
    if (typeof(sys['__printPool']) === 'undefined') { sys.__printPool = '' }
    s = sys.__printPool + s
    sys.__printPool = ''
    sys.__v0['表示ログ'] += (s + '\\n')
    console.log("[表示] " + s)
    // 表示  
    info.innerHTML += to_html(s) + "\\n"
    info.style.display = 'block'
  }
}
//---------------------------------
var nako3_clear = function (s, use_canvas) {
  var info = nako3_get_info()
  if (!info) return
  info.innerHTML = ''
  info.style.display = 'none'
  var err = nako3_get_error()
  err.innerHTML = ''
  err.style.display = 'none'
  var div = nako3_get_div()
  if (div) div.innerHTML = ''
  if (use_canvas) {
    var canvas = nako3_get_canvas()
    if (canvas) {
      var ctx = canvas.getContext('2d')
      ctx.clearRect(0, 0, canvas.width, canvas.height)
    }
  }
  if (navigator.nako3) {
    navigator.nako3.clearPlugins()
  }
}

// 独自関数の登録
var nako3_add_func = function () {
  navigator.nako3.setFunc("表示", [['の', 'を', 'と']], nako3_print, true)
  navigator.nako3.setFunc("表示ログクリア", [], nako3_clear, true)
}
var nako3_init_timer = setInterval(function(){
  if (typeof(navigator.nako3) === 'undefined') return
  clearInterval(nako3_init_timer)
  nako3_add_func()
}, 500)

function to_html(s) {
  s = '' + s
  return s.replace(/\&/g, '&amp;')
          .replace(/\</g, '&lt;')
          .replace(/\>/g, '&gt;')
}

//------------------------------------
// なでしこのプログラムを実行する関数
//------------------------------------
async function nako3_run(id, use_canvas) {
  if (typeof(navigator.nako3) === 'undefined') {
    alert('現在ライブラリを読み込み中です。しばらくお待ちください。')
    return
  }
  var code_e = qs("#nako3_code_" + id)
  if (!code_e) return
  var code = code_e.value
  var canvas_name = "#nako3_canvas_" + id
  var div_name = "#nako3_div_" + id
  var addon =
    "「" + div_name + "」へDOM親要素設定;" +
    "「" + div_name + "」に「」をHTML設定;"
  if (use_canvas) {
    addon += 
      "「" + canvas_name + "」へ描画開始;" +
      "カメ描画先=「" + canvas_name + "」;"
  }
  addon += "\\n" // 重要(インデント構文対策)
  try {
    const nako3 = navigator.nako3
    nako3_info_id = id
    nako3_clear()
    await nako3.loadDependencies(addon + code, 'main.nako3', addon)
    nako3.run(addon + code, 'main.nako3', addon)
    console.log("DONE")
  } catch (e) {
    nako3_print("==ERROR==" + e.message + "")
    console.log(e)
  }
}

//------------------------------------
// 投稿などエディタの機能
//------------------------------------
const nako3_save_key = 'nako3start::'
const nako3_save_key_files = 'nako3start::__files__'
var nako3_save_name = 'テスト.nako3'

function nako3_init_edit_area(pid, can_save, use_canvas) {
  // テキストエリアにイベントを設定
  nako3set_textarea(
    qs('#nako3_code_' + pid), 
    qs('#cur_pos_' + pid),
    pid, use_canvas)
  // 保存ボタンの表示設定
  qs('#post_span_' +pid).style.visibility = can_save ? "visible" : "hidden";
  if (!can_save) {return}
  // 開くボタンの確認
  nako3_check_load_button(pid)
}

function nako3_post(pid) {
  const post_form = qs('#nako3codeform_' + pid)
  const ta = qs('#nako3_code_' + pid)
  if (ta.value != '') {post_form.submit()}
}

// ローカル保存
function nako3_save_storage(pid) {
  // 要素を得る
  const ta = document.getElementById('nako3_code_' + pid)
  const pb = document.getElementById('post_button_' + pid)
  // ファイル名を尋ねて保存
  var filename = prompt('保存名を入力してください', nako3_save_name)
  filename = filename.replace('::', '__')
  if (filename == '') {return}
  nako3_save_name = filename
  const savekey = nako3_save_key + nako3_save_name
  localStorage[savekey] = ta.value
  if (!localStorage[nako3_save_key_files]) {
    localStorage[nako3_save_key_files] = ''
  }
  const files = localStorage[nako3_save_key_files].split('::')
  if (files[0] == '') {files.shift()}
  const fi = files.indexOf(filename)
  if (fi >= 0) {
    files[fi] = filename
  } else {
    files.push(filename)
  }
  localStorage[nako3_save_key_files] = files.join('::')
  alert('保存しました')
  // 公開ボタンを表示
  if (pb) {pb.style.display = 'inline'}
  // 開くボタンを確認
  nako3_check_load_button(pid)
  nako3_cancel_files(pid)
}

function nako3_check_load_button(pid) {
  const btn = qs('#load_button_' + pid)
  if (!localStorage[nako3_save_key_files]) {
    btn.style.display = 'none'
    return
  }
  btn.style.display = 'inline'
}

function nako3_load_click(pid) {
  const files_div = document.getElementById('nako3start_files_' + pid)
  if (!localStorage[nako3_save_key_files]) {
    files_div.style.display = 'none'
    return
  }
  files_div.style.display = 'block'
  const files = localStorage[nako3_save_key_files].split('::')
  var html = '一覧: '
  for (var i in files) {
    if (!files[i]) {continue}
    html += '<span class="nako3file" onclick="nako3start_loadfile(' + pid + ',' + i + ')">'
    html += to_html(files[i]) + '</span>'
  }
  html += '<span class="nako3file" onclick="nako3_clear_files(' + pid + ')">'
  html += '(全消去)</span>'
  html += '<span class="nako3file" style="color:blue;" '
  html += 'onclick="nako3_cancel_files(' + pid + ')">'
  html += 'キャンセル</span>'
  files_div.innerHTML = html
}
function nako3_cancel_files(pid) {
  const files_div = qs('#nako3start_files_' + pid)
  files_div.style.display = 'none'
}
function nako3_clear_files(pid) {
  const cf = confirm('保存したプログラムを全部消去しますがよろしいですか？')
  if (!cf) {return}
  const files = localStorage[nako3_save_key_files].split('::')
  for (var i in files) {
    const fkey = nako3_save_key + files[i]
    localStorage.removeItem(fkey)
  }
  localStorage.removeItem(nako3_save_key_files)
  nako3_cancel_files(pid)
  nako3_check_load_button(pid)
}
function nako3start_loadfile(pid, no) {
  const files = localStorage[nako3_save_key_files].split('::')
  const ta = document.getElementById('nako3_code_' + pid)
  const files_div = document.getElementById('nako3start_files_' + pid)
  if (ta.value != '') {
    const cf = confirm(files[no]+'を読み込みますか？')
    if (!cf) {return}
  }
  nako3_save_name = files[no]
  const fkey = nako3_save_key + files[no]
  ta.value = localStorage[fkey]
  ta.focus()
  files_div.style.display = 'none'
}
// エディタのカーソル位置を表示するように
function nako3set_textarea(edt, lbl, pid, use_canvas) {
  const showCursor = function () {
    // カーソルをカウント
    const pos = edt.selectionEnd
    const text = edt.value
    const a = text.split("\\n")
    var row = 1, col = 0, total = 0
    for (let i = 0; i < a.length; i++) {
      const len = a[i].length
      total += len + 1
      if (pos < total) {
        col = pos - total + len + 1
        break
      }
      row++
    }
    lbl.innerHTML = '' + row + '行目' // + col + '列目'
  }
  edt.addEventListener('mouseup', (e) => {
    showCursor()
  })
  edt.addEventListener('keyup', (e) => {
    if (e.isComposing) return // 漢字の変換中なら抜ける
    // ショートカット
    if (e.key == 'F9' || e.key == 'F5') {
      e.preventDefault() // skip default event
      nako3_run(pid, use_canvas)
      return
    }
    // カーソルの移動
    if (e.key == 'ArrowUp' || e.key == 'Enter' || 
        e.key == 'ArrowDown' || e.key == 'ArrowLeft' || 
        e.key == 'ArrowRight' || e.key == 'Backspace' || 
        e.key == 'Meta') {
        showCursor()
    }
  })
}
</script>
EOS;
}
// ---------------------------------------------------------



