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
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";

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

    // 古いエラーを削除する
    $limit_old = time() - 60 * 60 * 24 * 30; // 30days
    db_exec("DELETE FROM login_errors WHERE ctime < ?", [$limit_old], 'users');

    // 複数回のログインを拒否する
    $limit_t = time() - 60 * 60 * 24; // 24hours
    $erros = db_get("SELECT * FROM login_errors WHERE ip=? AND ctime > ? LIMIT 6", [$ip, $limit_t], 'users');
    // エラーならばエラーを記録して終了
    if (count($erros) >= 6) {
        konawiki_error("There have been too many login attempts.", "Login Error");
        exit;
    }

    // ログイン実行 (エラーの記録はkonawiki_auth.inc.phpで行う)
    if (!konawiki_auth()) {
        konawiki_show_loginForm(konawiki_lang('Failed to login.'));
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
            "<h4>{$msg}</h4>".
            "<p>{$msg}</p>" .
            "<p><a href='$url_edit'>$msg_edit</a></p>".
            "<p><a href='$url_look'>$msg_view</a></p>";
        // ログイン情報について表示する
        $body .= "<h5>Login History:</h5><ul>\n";
        $history_a = db_get("SELECT * FROM login_history WHERE user=? ORDER BY ctime DESC LIMIT 10", [$user], 'users');
        foreach ($history_a as $row) {
            $ctime = date("Y-m-d H:i:s", $row['ctime']);
            $ip = $row['ip'];
            $body .= "<li>{$ctime} [{$ip}]</li>\n";
        }
        $body .= "</ul>\n";
        // ログインに失敗した情報について表示する
        $erros = db_get("SELECT * FROM login_errors WHERE ip=? ORDER BY ctime DESC LIMIT 101", [$ip], 'users');
        $times = count($erros);
        $body .= "<h5>Login Erros ({$times}times):</h5><ul>\n";
        $i = 0;
        foreach ($erros as $row) {
            $ctime = date("Y-m-d H:i:s", $row['ctime']);
            $ip = $row['ip'];
            $body .= "<li>{$ctime} [{$ip}]</li>\n";
            $i++;
            if ($i >= 6) { break; }
        }
        $body .= "</ul>\n";
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





