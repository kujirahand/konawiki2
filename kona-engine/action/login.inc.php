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
    $page = konawiki_getPage();

    // ロボットには登録しない
    $public['norobot'] = TRUE;

    if (!$user) {
        konawiki_show_loginForm();
        exit;
    }

    // check edit_token for clickjacking
    $checkResult = konawiki_checkEditToken();
    if (!$checkResult) {
        $label = konawiki_lang('Login');
        $login_link = konawiki_getPageURL2($page, "login");
        konawiki_showMessage(
            "<div><h3>{$label}:</h3><p>".
            "<a class=\"pure-button pure-button-primary\" href=\"$login_link\">{$label}</a>".
            "</p></div>");
        exit;
    }

    // ログイン実行
    konawiki_auth();

    $baseurl = konawiki_public("baseurl");
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





