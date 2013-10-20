<!-- parts_backuplist.begin -->
<?php
// show backuplist
$bk     = konawiki_getBackupDB();
$log_id = konawiki_getPageId();
if ($log_id > 0) {
    $page   = konawiki_getPage();
    $sql    = "SELECT id,mtime FROM oldlogs WHERE log_id=$log_id order by mtime DESC limit 10";
    $res    = $bk->array_query($sql);
    if ($res && count($res) > 0) {
        $baseurl = konawiki_baseurl();
        $url = konawiki_getPageURL();
        $cur = $url."/edit";
        echo "<div id='backuplist'>";
        echo "<p>History:</p>";
        echo "<ul>";
        echo "<li><a href='$cur'>Now</a></li>\n";
        foreach ($res as $r) {
            $id    = $r['id'];
            $mtime = intval($r['mtime']);
            $s1    = konawiki_datetime_html($mtime,'easy');
            echo "<li><a href='{$url}/edit/log?id=$id'>$s1</a></li>\n";
        }
        echo "</ul>";
        echo "<form action='{$baseurl}' method='post'>".
             "<p><input type='checkbox' name='cmd' value='removebackup' />".
             konawiki_lang('Remove backup');
        echo " <input type='submit' value='".konawiki_lang('Execute')."' />";
        echo form_input_hidden('page',$page);
        echo form_input_hidden('action','edit');
        echo form_input_hidden('stat','removebackup');
        echo "</p></form>";
        echo "</div>";
    }
}
?>
<!-- parts_backuplist.end -->
