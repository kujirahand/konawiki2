<?php
/**
 * edit.tpl.php -- wiki editor
 */

//------------------------------------------------------------------------------
// initialize
//------------------------------------------------------------------------------
if (empty($id)) $id = "";
if (empty($body)) $body = "";
if (empty($tag)) $tag = "";
if (empty($mtime)) $mtime = time();
if (empty($error_message)) $error_message = "";
if (empty($conflict_body )) $conflict_body = "";
if (empty($hash)) $hash = "";
if (empty($login_auth_hash)) $login_auth_hash = "";
if (empty($private)) $private = 0;
$private_chk = ($private) ? "checked" : "";
//------------------------------------------------------------------------------
// header
//------------------------------------------------------------------------------
include(getSkinPath("parts_header.tpl.php"));
echo '<p class="clear"/>';

//------------------------------------------------------------------------------
// javascript
//------------------------------------------------------------------------------
$jquery       = konawiki_insert_jquery();
$page_edit_js = 'index.php?konawiki_page_edit.js&js';
$__js = <<< __________EOS__________
<!-- editor script -->
{$jquery}
<script type="text/javascript" src="{$page_edit_js}"></script>
<script type="text/javascript"><!--
$(function(){
    // editor init
    konawiki_page_edit_init("{$baseurl}", "{$page}", "{$id}");
    // textarea resize
    var txt = document.getElementById("body_txt");
});

// closeTitleList
function closeTitleList() {
  $("#wikieditortitle").hide();
}
//-->
</script>
<!-- end of editor script -->
__________EOS__________;
echo $__js;

//------------------------------------------------------------------------------
// form
//------------------------------------------------------------------------------
$msg_tag_desc = konawiki_lang('Comma is delimiter.');
$msg_save = konawiki_lang('Save');
$msg_save_show = konawiki_lang('Save and Show');
$msg_preview = konawiki_lang('Preview');
$msg_delete = konawiki_lang('Delete if blank.');
$msg_private = konawiki_lang('Private');

$body_html = htmlspecialchars($body);
$form = <<< _________________________________________________________END_OF_FORM
<!-- form.begin -->
<form id="main_frm" method="post" action="{$baseurl}">
<div>
<textarea id="body_txt" name="body" rows="16" cols="70" class="maintext">{$body_html}</textarea>
</div>
<div class="buttons">
  <span id="save_btns">
    <input class="pure-button" type="button"    id="save_btn"   value="$msg_save"/>
    <input class="pure-button" type="submit"    id="submit_btn" value="$msg_save_show"/>
    <input class="pure-button" type="button" onclick="konawiki_edit_preview(); return false;" 
           value="$msg_preview"/>
    <input type="checkbox"  id="editmode_chk"   name="editmode"  value="delete" />
    <label for="editmode_chk"><span class="hint">$msg_delete</span></label>
    <input type="checkbox"  id="private_chk"   name="private_chk"  value="1" $private_chk />
    <label for="private_chk"><span class="hint">$msg_private</span></label>
           </span>
  <p style="padding: 8px;">
    <span class="hint">Tag:</span>
    <input type="text"      id="tag_txt"   name="tag" size="20" value="{$tag}"/>
    <span class="hint">($msg_tag_desc)</span>
  </p>
  <input type="hidden"    name="page"    value="{$page_raw}" />
  <input type="hidden"    name="action"  value="edit" />
  <input type="hidden"    name="stat"    value="update" />
  <input type="hidden"    name="id"      value="{$id}" />
  <input type="hidden"    id="hash_param" 
         name="hash"
        value="{$hash}" />
  <input type="hidden" id="login_auth_hash" 
         name="login_auth_hash" 
        value="{$login_auth_hash}" />
</div><!--end of buttons-->

</form>
<!-- form.end -->
_________________________________________________________END_OF_FORM;

//------------------------------------------------------------------------------
// header & footer
//------------------------------------------------------------------------------
$pagename_link = konawiki_getPageLink($page_raw,'dir');
$edit_header = <<< ___EOS
{$error_message}
{$conflict_body}
___EOS;

$msg_update  = konawiki_lang('Updated time', 'Updated');
$mtime_html  = konawiki_date_html(intval($mtime),'easy');
$mtime2_html = konawiki_time($mtime);
$easyhelp    = konawiki_lang('Mini help', '');
$edit_footer = <<< ___EOS
<div id="msg"></div>
<div id="debug"></div>
<div class="date" id="uptime_div">$msg_update({$mtime_html} {$mtime2_html})</div>
<div id="count_div" class="date"></div>
$easyhelp
<!-- *****preview**** -->
<div id="wikibody" style="width:100%;">
<div id="preview"></div>
</div><!-- end of wikibody for preview -->
</p>
___EOS;
//------------------------------------------------------------------------------
// body
//------------------------------------------------------------------------------
if (empty($command)) $command = "";
$__body =   
  $edit_header.
  $form.
  $command.
  $edit_footer
  ;

//------------------------------------------------------------------------------
// editor body html
//------------------------------------------------------------------------------
?>
<div class="clear"></div>

<div id="wikieditor"><div class="editorpad">

  <div id="wikieditorbody"><div class="bodypad">
        <?php echo $__body; ?>
  </div></div><!-- end of #wikieditorbody -->

</div></div><!-- end of #wikieditor -->

<div class="clear"></div>

<?php
//------------------------------------------------------------------------------
// editor option
//------------------------------------------------------------------------------
?>


<a name="wikicommand-a">&nbsp;</a>
<div id="wikicommand" style="display:none;">
<?php
include(getSkinPath("parts_attachlist2.tpl.php"));
include(getSkinPath("parts_backuplist.tpl.php"));
include(getSkinPath("parts_replace_form.tpl.php"));
include(getSkinPath("parts_rename_form.tpl.php"));
include(getSkinPath("parts_rename_ex_form.tpl.php"));
include(getSkinPath("parts_batch.tpl.php"));
?>
<h6>Export</h6>
<ul>
  <li><a href="index.php?all&export">Export all wiki data</a></li>
  <li><a href="index.php?all&import">Import all wiki data</a></li>
</ul>
</div><!-- wikicommand -->
<div class="box" id="wikicommand-btn">
  <a class="pure-button"
  href="#wikicommand-a"
  onclick="showWikiCommand()">
  Show Command</a>
</div>
<script>
  function showWikiCommand() {
    $("#wikicommand").show();
    $("#wikicommand-btn").hide();
  }
</script>
<?php
// ---------------------------------------------------------------------
// footer
include_once(getSkinPath('parts_footer.tpl.php'));
// ---------------------------------------------------------------------
