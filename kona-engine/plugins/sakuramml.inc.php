<?php
/** konawiki plugins -- ピコサクラMML
 * - [書式] {{{#sakuramml データ }}}
 * - [引数]
 * -- データ  .. 再生したいデータ
 * - [使用例]
{{{
_{{{#sakuramml
ドレミファソ
_}}}
}}}
 * - [公開設定] 公開
 */

function plugin_sakuramml_convert($params)
{
    $pid = konawiki_getPluginInfo("sakuramml", "pid", 1);
    konawiki_setPluginInfo("sakuramml", "pid", $pid+1);
    
    $mml  = trim(array_shift($params));
    
    $html = "";
    $args = [
        "mml" => htmlspecialchars($mml),
        "pid" => $pid,
    ];
    if ($pid == 1) { // 初回のみヘッダを表示
        $html .= getTemplateHeader($args);
    }
    $html .= getTemplate($args);
    return $html;
}

function getTemplate($args) {
    extract($args);
    return <<< EOS__
<!-- #sakuramml.parts.pid{$pid}-->
<div class="sakuramml_block" id="sakuramml_bock{$pid}">
  <div>
    <textarea id="sakuramml_txt{$pid}" cols="60" rows="8" style="width:97%;padding:8px;background-color:#fffff0;">{$mml}</textarea>
  </div>
  <div id="player{$pid}" class="sakuramml_player_buttons" style="display:none;">
    <button id="btnPlay{$pid}" style="padding:8px;">▶ ピコ再生</button>
    <button id="btnStop{$pid}" style="padding:8px;">停止</button> &nbsp;
    <span class="sakuramml_version" style="font-size:0.4em;"></span>
    <span style="padding:6px; font-size:0.7em;">
      (シンセ選択:
      <label for="pico{$pid}"><input type="radio" id="pico{$pid}" name="player_type{$pid}" value="pico" checked="1">picoaudio</label>
      <label for="jzz{$pid}"><input type="radio" id="jzz{$pid}" name="player_type{$pid}" value="jzz">jzz-synth-tiny</label>)
    </span>
  </div>
  <div>
    <div id="player_gui{$pid}"></div>
  </div>
    <div id="skr_error_msg{$pid}" style="padding:0.5em; font-size: 0.6em; color: gray; height: 200px; overflow: scroll; display:none;"></div>
</div>
<script type="module">
    window.sakuramml_setup({$pid});
</script>
<!-- end of #sakuramml.parts.pid{$pid}-->

EOS__;
}

function getTemplateHeader($args) {
    extract($args);
    return <<< __EOS__

<!-- #sakuramml (pico sakura) -->
<style>
.sakuramml_block {
    margin-top: 1em;
    margin-bottom: 1em;
    padding-top: 1em;
    padding-bottom: 1em;
    border-top: 1px solid silver;
}
</style>
<!-- pico sakura ------------------------------------------------>
  <!-- jzz player -->
  <script src="https://cdn.jsdelivr.net/npm/jzz"></script>
  <script src="https://cdn.jsdelivr.net/npm/jzz-midi-smf"></script>
  <script src="https://cdn.jsdelivr.net/npm/jzz-synth-tiny"></script>
  <script src="https://cdn.jsdelivr.net/npm/jzz-input-kbd"></script>
  <script src="https://cdn.jsdelivr.net/npm/jzz-gui-player"></script>
  <!-- picoaudio player -->
  <script src="https://unpkg.com/picoaudio/dist/browser/PicoAudio.js"></script>

<script type="module">
  // WebAssemblyを読み込む --- (*1)
  import init, {compile,get_version} from 'https://cdn.jsdelivr.net/npm/sakuramml@0.1.10/sakuramml.js';

  // Promiseの仕組みでライブラリを読み込む
  init().then(() => {
    console.log('ok')
    for (let e of document.querySelectorAll('.sakuramml_player_buttons')) {
        e.style.display = 'block';
    }
    window.sakuramml_ver = 'ver.' + get_version()
    for (let e of document.querySelectorAll('.sakuramml_version')) {
        e.innerHTML = window.sakuramml_ver;
    }
  }).catch(err => {
    console.error(err);
    document.getElementById('skr_error_msg1').innerHTML = '[LOAD_ERROR]' + tohtml(err.toString())
  });
  window.sakuramml_pid = 1;
  // これが必要
  window.sakura_log = function (s) {
    const pid = window.sakuramml_pid;
    const msg = document.getElementById('skr_error_msg' + pid);
    if (s == "") { return; }
    console.log(pid, s)
    msg.style.display = 'block';
    msg.innerHTML = tohtml(s)
  }
  function tohtml(s) {
    s = s.replace(/&/g,'&amp;')
    s = s.replace(/</g,'&lt;')
    s = s.replace(/>/g,'&gt;')
    s = s.replace(/\\n/g,'<br>\\n')
    return s
  }
  window.player_jzz = null;
  window.player_pico = null;
  window.sakuramml_setup = function (pid) {
    document.getElementById('btnPlay' + pid).onclick = () => {
      playMML(pid)
    };
    document.getElementById('btnStop' + pid).onclick = () => {
      if (window.player_jzz) { window.player_pico.stop(); }
      if (window.player_pico) { window.player_pico.stop(); }
    }
  };

  function playMML(pid) {
    const txt = document.getElementById('sakuramml_txt' + pid)
    const pico = document.getElementById('pico' + pid)
    window.sakuramml_pid = pid;
    // init player
    if (pico.checked) {
      if (!window.player_pico) {
        // load Pico
        window.player_pico = new PicoAudio();
        window.player_pico.init();
      }
    } else {
      if (!window.player_jzz) {
        // load JZZ
        document.getElementById('player_gui' + pid).style.display = 'none'
        window.player_jzz = new JZZ.gui.Player('player_gui' + pid);
        JZZ.synth.Tiny.register('Web Audio');
      }
    }
    try {
      const a = compile(txt.value)
      const smfData = new Uint8Array(a);
      if (pico.checked) {
        if (window.player_jzz) {
          window.player_jzz.stop(); //
        }
        const parsedData = player_pico.parseSMF(smfData);
        window.player_pico.setData(parsedData);
        window.player_pico.play();
      } else {
        if (window.player_jzz) {
          window.player_pico.stop(); //
        }
        window.player_jzz.load(new JZZ.MIDI.SMF(smfData));
        window.player_jzz.play();
      }
    } catch (err) {
      console.error(err);
      document.getElementById('skr_error_msg' + pid).innerHTML = '[SYSTEM_ERROR]' + tohtml(err.toString())
    }
  }
</script>
__EOS__;
}

