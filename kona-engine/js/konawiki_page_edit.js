/**
 * konawiki editor javascript
 * エディタ編集のためのJavaScript
 */

/** config
 ******************************************************************************/
var kona_ = {
    flag_change: false,
    flag_autosave: true,
    flag_savelock: false,
    auto_save_time: 1000 * 60 /*sec*/,
    baseurl: null,
    this_page: null,
    this_id: null,
    timeout: 1000 * 30 /*sec*/,
    dummy: 0
};

/** debug
 ******************************************************************************/
function r(e) {
    var s = "<pre>debug:";
    for (var i in e) {
        if (typeof(i) != "function") {
            s += i + "=" + e[i] + "\n";
        } else {
            s += i + "=Function\n";
        }
    }
    s += "</pre>";
    $('#debug').html(s);
}

/** init
 ******************************************************************************/
function konawiki_page_edit_init(baseurl, this_page, this_id)
{
    // set config
    kona_.baseurl   = baseurl;
    kona_.this_page = this_page;
    kona_.this_id   = this_id;
    
    konawiki_edit_set_event();
    konawiki_edit_set_autosave();
    
    calcTextLength();
    
    konawiki_set_page_move_alert();

}

function konawiki_set_page_move_alert()
{
    $(window).bind("beforeunload", function(e){
        if (kona_.flag_change) {
            var msg = "保存前にページを移動しようとしています。";
            return e.originalEvent.returnValue = msg;
        }
    });
}

function konawiki_edit_set_event()
{
    //--------------------------------------------------------------------------
    // #body_txt
    $('#body_txt').keyup(function(e){
        var code = e.keyCode; // $('#msg').html(code);
        if (code == 13 || code == 8/*BS*/ || code == 46/*Delete*/) {
            kona_.flag_change = true;
            calcTextLength();
        }
    });
    //--------------------------------------------------------------------------
    // #save_btn
    $('#save_btn').click(konawiki_edit_save_btn_click);
    $('#main_frm').submit(konawiki_edit_submit_btn_click);
    //--------------------------------------------------------------------------
    // #editmode_chk -- checkbox
    var chk = $('#editmode_chk');
    chk.change(function(e){
        if (chk.get()[0].checked) {
            $('#save_btn').hide();
        } else {
            $('#save_btn').show();
        }
    });
    // confict copy button
    $('#conflict_copy_edit_btn').click(function(){
        var s1 = $('#conflict_text').val();
        var s2 = $('#body_txt').val();
        $('#conflict_text').val(s2);
        $('#body_txt').val(s1);
    });
}

function konawiki_edit_set_autosave()
{
    if (!kona_.flag_autosave) return;
    // autosave function
    var save_timer_id;
    var ontime = function() {
        clearInterval(save_timer_id);
        if (kona_.flag_change) {
            $('#save_btn').click();
        }
        save_timer_id = setInterval(ontime, kona_.auto_save_time);
    };
    save_timer_id = setInterval(ontime, kona_.auto_save_time);
}

function konawiki_edit_save_btn_click()
{
    if (kona_.flag_savelock) return;
    $("#save_btns").hide();
    kona_.flag_autosave = false;
    // show message
    $('#uptime_div')
        .html('保存しています')
        .css('background-color','#ffaaaa');
    // post
    konawiki_edit_post();
}

function konawiki_edit_submit_btn_click()
{
    if (kona_.flag_savelock) return;
    $("#submit_btn").hide();
    kona_.flag_change = false;
    kona_.flag_autosave = false;
    // show message
    $('#uptime_div')
        .html('保存しています')
        .css('background-color','#ffaaaa');
    return true;
}

function konawiki_edit_post_lock(msg)
{
    kona_.flag_savelock = true;
    
}

function konawiki_edit_post_unlock(msg, result)
{
    // show message
    var color = '#ff9090';
    if (result) color = '#fff0f0';
    $('#uptime_div')
        .fadeOut("fast")
        .html(msg)
        .css('background-color', color)
        .fadeIn("slow");
    if (result) {
        kona_.flag_autosave = true;
        kona_.flag_change = false;
        kona_.flag_savelock = false;
    } else {
        kona_.flag_autosave = false;
        kona_.flag_change = false;
        kona_.flag_savelock = false;
    }
    $("#save_btns").show();
}

function konawiki_edit_post()
{
    if (kona_.flag_savelock == true) return;
    konawiki_edit_post_lock('保存しています');
    
    $.ajaxSetup({timeout: kona_.timeout});
    
    // define once
    if (!kona_.konawiki_edit_post_def) {
        kona_.konawiki_edit_post_def = true;
        $("#uptime_div").ajaxError(function(event, request, options, err){
            kona_.flag_autosave = false;
            konawiki_edit_post_unlock("保存に失敗しました。手動でコミットしてください。", false);
            return;
        });
    }
    $.post(
        // url
        kona_.baseurl,
        // param
        {
            encode_hint:"美しい牛乳",
            page:kona_.this_page,
            action:"edit",
            stat:"api__write",
            id:kona_.this_id,
            body:$("#body_txt").val(),
            tag:$('#tag_txt').val(),
            hash:$("#hash_param").val(),
            login_auth_hash:$("#login_auth_hash").val()
        },
        // result
        function(msg){ // save result
            var res_ary = msg.split("\n");
            var result = res_ary.shift();
            var msg = res_ary.join("\n");
            if (result != "ok") {
                konawiki_edit_post_unlock("保存に失敗しました。" +
                    "手動でコミットしてください。" + msg, false);
                return;
            }
            var hash = res_ary.shift();
            $("#hash_param").val(hash);
            var t = new Date();
            var tm = t.getHours() + 
                ":" + t.getMinutes() + 
                ":" + t.getSeconds();
            konawiki_edit_post_unlock(
                "更新時(" + tm + "):" + hash, true);
            console.log('autosave:'+tm);
        }
    );
    // 自動でプレビューしたい場合は以下のコメントを外す
    // konawiki_edit_preview();
}

function konawiki_edit_preview()
{
  $.ajaxSetup({timeout: kona_.timeout});

  if ($("#body_txt").val() != "")
  {
    $.post("index.php",
      {"action":"edit", "stat":"preview", "body":$("#body_txt").val()},
      function(data, status) {
        $("#preview").html("<b>プレビュー:</b><hr/>" + data);
    },
      "html"
    );
  } else
  {
    $("#preview").html('');
  }
}


// caret
function moveCaretPos(textarea,ln, ln2){
  if (ln2 == undefined) ln2 = ln;
  textarea.focus();
  if(jQuery.browser.msie){
    var e=textarea.createTextRange();
    e.collapse(true);
    e.moveStart("character", ln);
    e.collapse(false);
    e.select();
  } else {
    if (textarea.setSelectionRange) {
      var s = textarea.value.substr(0, ln);
      textarea.scrollTop = (s.split("\n").length - 1) * 16;
      textarea.setSelectionRange(ln, ln2);
    }
    else {
      $('#msg').html('移動できません');
    }
  }
}

// calc text length
function calcTextLength ()
{
    var body = $('#body_txt').val();
    body = body.replace(/(\r|\n)/g,"");
    var len = body.length;
    var m = body.match(/&count\(.+?\);/g);
    var part = "";
    var ss;
    if (m) {
        var pa = [];
        for (var i = 0; i < m.length; i++) {
            ss = (m[i].length - 9);
            pa.push("(" + ss + "ch)");
        }
        part = " &count" + pa.join("") + ";";
    }
    $('#count_div').html("All:" + len + "ch" + part + " ---&gt; &amp;count(***);");
}

