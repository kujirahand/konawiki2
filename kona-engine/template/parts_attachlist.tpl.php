<!-- parts_attachlist.begin -->
<div id="wikiattach">
添付ファイル:
<?php
    include_once(KONAWIKI_DIR_ACTION.'/attach.inc.php');
    $p = konawiki_getPage();
    $links = konawiki_getAttachListLink($p);
    if ($links) {
        foreach ($links as $s) {
            echo "[" . $s . "] ";
        }
    } else {
        echo "なし";
    }
?>
</div>
<!-- parts_attachlist.end -->
