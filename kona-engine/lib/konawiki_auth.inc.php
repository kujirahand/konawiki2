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
    $result = _konawiki_auth_form();
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
    $users = konawiki_private('authusers');
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
    konawiki_start_session();
    $err = '';

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
            $perm_list = konawiki_private('users_perm');
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
        $err = konawiki_lang('Invalid username or password.');
    }
    konawiki_show_loginForm($err);
    exit;
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
    $result = _konawiki_auth_form();
    if (!$result) return false;
    $perm = $_SESSION["login.user.perm"];
    if (!$perm["read"]) return false;
    return true;
}

function konawiki_show_loginForm($msg = '') {
    // ログインフォームを表示
    $msg_login    = konawiki_lang('Login.button', 'Login');

    include_template('login.html', [
      'page' => konawiki_getPage(),
      'action' => konawiki_getPageURL(FALSE, "login"),
      'edit_token' => konawiki_getEditToken(TRUE),
      'msg' => $msg,
    ]);
}

#vim:set ts=4 sts=4 sw=4 tw=0



