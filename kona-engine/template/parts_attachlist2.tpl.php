<!-- parts_attachlist2.begin -->
<div id="wikiattach">
<?php
    include_once(KONAWIKI_DIR_ACTION.'/attach.inc.php');
    $p = konawiki_getPage();
    $page_ = konawiki_getPageURL();
    $baseurl = konawiki_public("baseurl");
    echo "<a href='{$page_}/attach'>".konawiki_lang('Attachment files').":</a><br/>\n";
    $links = konawiki_getAttachList($p);
    $msg_etu = konawiki_lang('Show file', 'Show');
    if ($links) {
        $res = array();
        foreach ($links as $s) {
            $w = '#ref('.$s['name'].')';
            $name_ = urlencode($s['name']);
            $u = "{$page_}/attach?file=".$name_;
            $x = "<a href='$u'>($msg_etu)</a>";
            $z = "<input type='text' size='18' value='$w'".
                "onclick='this.select()'/>{$x}";
            $res[] = $z;
        }
        echo join($res, " - ");
    } else {
        echo konawiki_lang('None');
    }
?>
</div>
<!-- parts_attachlist2.end -->
