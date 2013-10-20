<?php
/**
 * get resource file
 */
// use mime
include_once(KONAWIKI_DIR_LIB."/mime.inc.php");
//
function action_file_()
{
    // get skin resource dir
    $page = konawiki_getPage();
    $skin = konawiki_public("skin");
    $path = KONAWIKI_DIR_SKIN."/{$skin}/resource/{$page}";
    if (!file_exists($path)) {
        // check default
        $skin = "default";
        $path = KONAWIKI_DIR_SKIN."/{$skin}/resource/{$page}";
        if (!file_exists($path)) {
            if (!file_exists($path)) {
                $path = KONAWIKI_DIR_RESOURCE."/".$page;
                if (!file_exists($path)) {
                    echo "File not found:".$path;
                    exit;
                }
            }
        }
    }
    // content-type
    $ctype = mime_content_type_e( $page );
    header("Content-type: $ctype");
    header("Content-Disposition: inline; filename=\"$page\"");
    echo file_get_contents($path);
    exit;
}
?>
