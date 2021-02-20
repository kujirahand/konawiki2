<?php
/**
 * Show Login screen
 * @see lib/konawiki_auth.inc.php
 */
function action_login_()
{
    // ログイン実行するか？
    $user = konawiki_param("user", false);
    $pass = konawiki_param("pass", false);

    // ロボットには登録しない
    $public['norobot'] = TRUE;

    if (!$user) {
        konawiki_show_loginForm();
        exit;
    }

    // ログイン実行
    konawiki_auth();

    $baseurl = konawiki_public("baseurl");
    $page = konawiki_getPage();
    $edit_token = konawiki_getEditToken();
    $url_edit = konawiki_getPageURL($page, "edit", "", "edit_token=$edit_token");
    $url_look = konawiki_getPageURL($page);

    // ログイン権限を調べる
    $msg_edit = konawiki_lang('Edit');
    $msg_view = konawiki_lang('View');
    if (konawiki_isLogin_write ()) {
        $msg = konawiki_lang("Success to login!");
        $log['body'] =
            "<p>{$msg}</p>".
             "<p><a href='$url_edit'>$msg_edit</a></p>".
             "<p><a href='$url_look'>$msg_view</a></p>";
    }
    else if (konawiki_isLogin_read()) {
        $msg = konawiki_lang("Success to login! Thank you.");
        $log['body'] =
            "<p>{$msg}</p>".
             "<p><a href='$url_look'>$msg_view</a></p>";
    } else {
        $log['body'] = konawiki_lang('Failed to login.');
    }

    // 表示
    include_template("form.tpl.php", $log);
}





