<?php

function action_logout_()
{
    konawiki_logout();
    $log['body'] = konawiki_lang('Success to logout.');
    include_template("form.tpl.php", $log);
}
?>
