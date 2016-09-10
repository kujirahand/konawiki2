<?php
// header
include_once(getSkinPath('parts_header.tpl.php'));
?><style>
* { padding:0; margin:0 }
#wikisimple {
  margin: 0px;
  font-size: 0.8em;
  line-height: 1.3em;
  padding-top:0px;
  padding-bottom: 0px;
  padding-left:2px;
  padding-right:2px;
}
#wikisimple h1 {
  font-size: 2em;
  background-color: #fff0f0;
  color: white;
  margin-top: 12px;
  margin-bottom: 8px;
}
#wikisimple h2 {
  font-size: 1.5em;
  border-bottom: 2px solid #fff0f0;
  margin-top: 4px;
  margin-bottom: 4px;
}
#wikisimple h3 {
  font-size: 1.0em;
  border-top    : 2px solid #ffc0c0;
  border-bottom : 2px solid #ffc0c0;
  margin-top    : 8px;
  margin-bottom : 4px;
  background-color: #fff0f0;
  padding: 6px;
}
#wikisimple ul {
  padding: 8px;
  list-style-type: none;
}
div.underline {
  border-top: 2px solid #ffc0c0;
  margin-top: 8px;
}
</style>
<div id="wikisimple">
<?php echo $body_all ?>
</div>
<div class="clear"></div>
<!-- simple footer -->
</body></html>





