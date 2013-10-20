<?php
function action_nadesiko_jump()
{
    action_nadesiko_();
}

function action_nadesiko_()
{
    global $konawiki;
    global $plug_nadesiko;
    $plug_nadesiko = $konawiki['private']['show.plugins']['nadesiko'];
    include_once("plugins/show.nadesiko.inc.php");
    $id = intval(konawiki_param("id"));
    if ($id > 0) {
        $db = show_nadesiko_getDB();
        $r = $db->array_query("SELECT id,name FROM command WHERE id=$id LIMIT 1");
        if (isset($r[0]['id'])) {
            $name = toUTF8($r[0]['name']);
        } else {
            $name = FALSE;
        }
        if ($name == FALSE) {
            konawiki_error("$id はありません。");
            exit;
        }
    } else {
        konawiki_error("$id はありません。");
        exit;
    }
    $url = konawiki_getPageURL($name);
    konawiki_jump($url);
}
?>
