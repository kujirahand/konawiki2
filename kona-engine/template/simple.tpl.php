<?php
// header
include_once(getSkinPath('parts_header.tpl.php'));
?>
<style>
#wikisimple {
  margin: 8px;
}
#wikisimple h1 {
  font-size: 2em;
  background-color: blue;
  color: white;
  margin-top: 12px;
  margin-bottom: 8px;
}
#wikisimple h2 {
  font-size: 1.5em;
  border-bottom: 2px solid blue;
  margin-top: 12px;
  margin-bottom: 4px;
}
#wikisimple h3 {
  font-size: 1.3em;
  border-bottom: 2px solid blue;
  margin-top: 12px;
  margin-bottom: 4px;
}
</style>
<div class="clear"></div>
<div id="wikisimple">
<?php echo $body_all ?>
</div>
<div class="clear"></div>
      
<?php
// footer
include_once(getSkinPath('parts_footer.tpl.php'));






