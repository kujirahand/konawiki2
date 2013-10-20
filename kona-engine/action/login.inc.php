<?php
/**
 * ログイン画面の表示
 * 実際の認証は、konawiki_lib.inc.php の konawiki_auth() で行われる。
 */
function action_login_()
{
    // ログイン実行するか？
    $user = konawiki_param("user", false);
    $pass = konawiki_param("pass", false);

    if (!$user) {
        konawiki_show_loginForm();
        exit;
    }

    // ログイン実行
    konawiki_auth();

    $baseurl = konawiki_public("baseurl");
    $page = konawiki_getPage();
    $url_edit = konawiki_getPageURL($page, "edit");
    $url_look = konawiki_getPageURL($page);

    // ログイン権限を調べる
    $msg_edit = konawiki_lang('Edit');
    $msg_view = konawiki_lang('View');
    if (konawiki_isLogin_write ()) {
        $msg = konawiki_public('login.message', "Success to login!");
        $log['body'] =
            "<p>{$msg}</p>".
             "<p><a href='$url_edit'>$msg_edit</a></p>".
             "<p><a href='$url_look'>$msg_view</a></p>";
    }
    else if (konawiki_isLogin_read()) {
        $msg = konawiki_public('login.message.readonly', "Success to login! Thank you.");
        $log['body'] =
            "<p>{$msg}</p>".
             "<p><a href='$url_look'>$msg_view</a></p>";
    } else {
        $log['body'] = konawiki_lang('Failed to login.');
    }

    // 表示
    include_template("form.tpl.php", $log);
}





