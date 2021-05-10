<?php
/**
 * Show Login screen
 * @see lib/konawiki_auth.inc.php
 */
function action_login_()
{
    global $public;
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
    if (!konawiki_auth()) {
      $err = konawiki_lang('Failed to login.');
      konawiki_show_loginForm($err);
      exit;
    }

    $baseurl = konawiki_public("baseurl");
    $edit_token = konawiki_getEditToken();
    $url_edit = konawiki_getPageURL($page, "edit", "", "edit_token=$edit_token");
    $url_look = konawiki_getPageURL($page);

    // ログイン権限を調べる
    $msg_edit = konawiki_lang('Edit');
    $msg_view = konawiki_lang('View');
    if (konawiki_isLogin_write ()) {
        $msg = konawiki_lang("Success to login!");
        $body =
            "<p>{$msg}</p>".
             "<p><a href='$url_edit'>$msg_edit</a></p>".
             "<p><a href='$url_look'>$msg_view</a></p>";
    }
    else if (konawiki_isLogin_read()) {
        $msg = konawiki_lang("Success to login! Thank you.");
        $body =
            "<p>{$msg}</p>".
             "<p><a href='$url_look'>$msg_view</a></p>";
    } else {
        $body = konawiki_lang('Failed to login.');
    }

    // has backlink?
    $backlink = konawiki_auth_getBackLink();
    if ($backlink) {
      header("location: $backlink");
      echo "<a href='$backlink'>$backlink</a>";
      exit;
    }
    // 表示
    konawiki_showMessage($body);
}





