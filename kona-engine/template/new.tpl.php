<?php
include konawiki_template('parts_header.tpl.php');
?>
<div id="wikimessage"><div class="bodypad">
<div class="message">
<h4>新規</h4>

<form action="<?php echo $baseurl?>">
<p>
<input type="text" name="page" value="<?php echo $page?>" size="40" id="title_txt"/>
<input type="hidden" name="action" value="edit" />
<input type="submit" value="編集" />
</p>
</form>

<div class="contents">
<ul>
  <li><a href="#" id="date_btn">タイトルに今日の日付を記入</a></li>
  <li><a href="#" id="page_btn">タイトルを現在のページに</a></li>
  <li><a href="#" id="menubar_btn">MenuBarの編集</a></li>
  <li><a href="#" id="sidebar_btn">SideBarの編集</a></li>
  <li><a href="#" id="navibar_btn">NaviBarの編集</a></li>
</ul>
</div>

</div><!-- message -->
</div></div>
<script type="text/javascript"><!--
    function $(id) {
      return document.querySelector(id);
    }
    function zero(value, keta) {
        var temp = "000000000";
        var str  = temp + "" + value;
        return str.substr(str.length - keta, keta);
    }
    $("#date_btn").onclick = function (e) {
        e.preventDefault();
        var d = new Date();
        var s = d.getFullYear() + "/" + 
              zero(1+d.getMonth(),2) + "/" + 
              zero(d.getDate(),2);
        $("#title_txt").value = s;
        return false;
    };
    $("#page_btn").onclick = function () {
        var s = "<?php echo $page?>";
        $("#title_txt").value = s;
        return false;
    };
    $("#menubar_btn").onclick = function () {
        $("#title_txt").value = "MenuBar";
        return false;
    };
    $("#sidebar_btn").onclick = function(){
        $("#title_txt").value = "SideBar";
        return false;
    };
    $("#navibar_btn").onclick = function(){
        $("#title_txt").value = "NaviBar";
        return false;
    };
//-->
</script>

<p class="clear"></p>
<?php
include konawiki_template('parts_footer.tpl.php');
?>
