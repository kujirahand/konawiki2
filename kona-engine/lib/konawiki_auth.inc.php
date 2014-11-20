<?php
/*
 * KonaWikiの認証に関する関数群
 */

function konawiki_checkLoginTime() {
    konawiki_start_session();

    // ログイン時間は有効期限内か？
    if (!isset($_SESSION["login.time"])) {
        $_SESSION["login.time"] = 0;
    }

    $limit = konawiki_private('login.time.limit');
    $t = time() - $_SESSION["login.time"];
    if ($t > $limit) {
        return false;
    }
    return true;
}

function konawiki_isLogin_read() {
    konawiki_start_session();
    if (!konawiki_checkLoginTime()) return false;
    $perm = isset($_SESSION["login.user.perm"]) 
        ? ($_SESSION["login.user.perm"]) 
        : array("read"=>false, "write"=>false);
    return ($perm["read"] === true);
}

function konawiki_isLogin_write() {
    konawiki_start_session();
    if (!konawiki_checkLoginTime()) return false;
    $perm = isset($_SESSION["login.user.perm"]) 
      ? ($_SESSION["login.user.perm"]) 
      : array("read"=>false, "write"=>false);
    return ($perm["write"] === true);
}

/**
 * auth to write (認証を使う場合)
 */
function konawiki_auth()
{
    // auth.write.enabled?
    if (konawiki_private('auth.write.enabled') == FALSE) {
        return TRUE;
    }
    // check password
    $result = false;
    $authtype = konawiki_private("auth.type");
    if ($authtype === "basic") {
        $result = _konawiki_auth_basic();
    } else {
        $result = _konawiki_auth_form();
    }
    if ($result == false) return false;
    // check permission
    $perm = $_SESSION["login.user.perm"];
    if (!$perm["write"]) return false;
    //
    return true;
}

function _konawiki_auth_check_password($user, $pass)
{
    global $konawiki_auth_user;

    $konawiki_auth_user = '';
    $result = false;

    // check users
    $users = konawiki_private('auth.users');
    if (isset($users[$user])) {
        $a_pass = $users[$user];
        if (!preg_match('#\{(.+)\}(.*)$#', $a_pass, $m)) {
            // raw password
            $result = ($pass == $a_pass);
        }
        else {
            $fmt  = $m[1];
            $hash = $m[2];
            switch ($fmt) {
            case "md5":
            case "x-php-md5":
                $result = (strtolower(md5($pass)) == strtolower($hash));
                break;
            case "sha1":
                $result = (strtolower(sha1($pass)) == strtolower($hash));
                break;
            }
        }
    }
    return $result;
}

/**
 * BASIC認証のための関数(konawiki_authから呼ばれる)
 */
function _konawiki_auth_basic()
{
    konawiki_error("Sorry, now we do not support BASIC-AUTH.");
    exit;
}

/**
 * 名前付きでセッションを開始する
 * @return unknown_type
 */
function konawiki_start_session()
{
    static $started = false;
    if ($started) return;
    session_name(konawiki_private('session.name'));
    @session_start();
    $started = true;
}

/**
 * セッションを用いたログイン認証のための関数(konawiki_authから呼ばれる)
 */
function _konawiki_auth_form()
{
    global $konawiki_auth_user;
    $konawiki_auth_user = '';
    $err = array();
    konawiki_start_session();

    // ログイン時間は有効期限内か？
    if (!isset($_SESSION["login.time"])) {
        $_SESSION["login.time"] = 0;
    }
    $login = intval($_SESSION["login.time"]);
    if ($login > 0) {
        $konawiki_auth_user 
          = isset($_SESSION["login.user"]) 
          ? $_SESSION["login.user"] 
          : "unknown";
        $_SESSION["login.time"] = time(); // 更新
        session_regenerate_id();
        return true;
    }
    konawiki_logout();
    // ログインチェック
    $user = trim(konawiki_param("user", false));
    $pw   = trim(konawiki_param("pw",   false));
    if ($user !== false && $pw !== false) {
        if (_konawiki_auth_check_password($user, $pw)) {
            // Check Permission
            $perm_list = konawiki_private('auth.users.perm');
            $perm = $_SESSION['login.user.perm'] = 
              isset($perm_list[$user]) 
              ? $perm_list[$user] 
              : array("read"=>true, "write"=>true);
            // ログイン処理
            $_SESSION["login.time"] = time();
            $_SESSION["login.user"] = $user;
            $konawiki_auth_user = $user;
            return true;
        }
    }
    // ログイン失敗～フォームを表示
    if ($user !== false) {
        $err[] = konawiki_lang('Invalid username or password.');
    }
    $msg = "<span class='error'>".
        implode('<br>', $err).
        '</span>';
    konawiki_show_loginForm($msg);
    exit;
}

function konawiki_show_loginForm($msg = "") {
    // ログインフォームを表示

    $msg_login_title = konawiki_lang('Please login.');
    $msg_username = konawiki_lang('Username');
    $msg_password = konawiki_lang('Password');
    $msg_login    = konawiki_lang('Login.button', 'Login');

    $page = konawiki_getPage();
    $action = konawiki_getPageURL($page, "login");
    $log['body'] = <<< EOS__
<h4>{$msg_login_title}</h4>
{$msg}
<form action="$action" method="post">
<table>
    <tr><td>$msg_username</td><td><input type="text"     name="user"/></td></tr>
    <tr><td>$msg_password</td><td><input type="password" name="pw"  /></td></tr>
    <tr><td colspan="2" align="right">
      <input type="submit" value="{$msg_login}" />
    </td></tr>
</table>
</form>
EOS__;
    include_template("form.tpl.php", $log);
}

function konawiki_logout()
{
    global $konawiki_auth_user;
    $konawiki_auth_user = '';

    // $authtype = konawiki_private("auth.type", "form");
    // Delete Session
    konawiki_start_session();
    unset($_SESSION["login.time"]);
    unset($_SESSION["login.user"]);
    unset($_SESSION["login.user.perm"]);
}

/**
 * auth to read
 */
function konawiki_auth_read()
{
    // auth enabled?
    if (!konawiki_private('auth.read.enabled')) {
            return TRUE;
    }

    // check auth type
    $result = false;
    $authtype = konawiki_private("auth.type");
    if ($authtype == "basic") {
            $result = _konawiki_auth_basic();
    }
    else {
            $result = _konawiki_auth_form();
    }
    if (!$result) return false;
    $perm = $_SESSION["login.user.perm"];
    if (!$perm["read"]) return false;
    return true;
}

#vim:set ts=4 sts=4 sw=4 tw=0



