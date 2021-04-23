<?php

function action_logout_()
{
    konawiki_logout();
    $msg = konawiki_lang('Success to logout.');
    konawiki_showMessage($msg);
}

