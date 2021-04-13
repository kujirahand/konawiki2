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
    // check filename
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $page)) {
        return __404('Invalid File name.');
    }
    // default
    if ($skin == 'default') {
        $path = KONAWIKI_DIR_DEF_RES."/{$page}";
        return __out($path, $page);
    }
    // Skin
    $path = KONAWIKI_DIR_SKIN."/{$skin}/resource/{$page}";
    if (!file_exists($path)) {
        // check default
        $skin = "default";
        $path = KONAWIKI_DIR_SKIN."/{$skin}/resource/{$page}";
        if (!file_exists($path)) {
            if (!file_exists($path)) {
                $path = KONAWIKI_DIR_DEF_RES."/".$page;
                if (!file_exists($path)) {
                    echo "File not found:".$path;
                    exit;
                }
            }
        }
    }
    return __out($path, $page);
}

function __404($msg = '') {
    header('HTTP/1.0 404 File not found');
    header('Content-type: text/plain');
    echo '404 File not found. '.$msg."\n";
}

function __out($path, $page) {
    // check exists
    if (!file_exists($path)) {
        return __404();
    }
    // content-type
    $ctype = mime_content_type_e( $page );
    header("Content-type: $ctype");
    header('Content-Length: ' . filesize($path));
    header("Content-Disposition: inline; filename=\"$page\"");
    readfile($path);
}




