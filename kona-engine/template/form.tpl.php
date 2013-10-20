<?php
//------------------------------------------------------------------------------
// message template
//------------------------------------------------------------------------------
// header
include(getSkinPath("parts_header.tpl.php"));
?>
<div id="wikicontent"><div class="contentpad">
  <div id="wikimessage"><div class="bodypad">
    <div class="message">
<?php echo  $body ?>
    </div>
  </div></div>
</div></div>
<p class="clear"></p>

<?php
// footer
include(getSkinPath("parts_footer.tpl.php"));



