<?php
#vim:set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
// プラグインを実行するだけのアクション
function action_plugin_()
{
    $page   = konawiki_getPage();
    $pname  = konawiki_param("name","");
    $p      = konawiki_param("p","");
    if ($pname == "") { echo "error (no parameters)"; exit; }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $pname)) {
        echo "error (invalid plugin name)"; exit;
    }
    // check disable
    $disable = konawiki_private("plugins.disable");
    if (isset($disable[$pname]) && $disable[$pname]) {
        echo "error (disabled)"; exit;
    }
    // 状態変更を伴うディスパッチャ型プラグインは、convert の直接実行を
    // 一律には許可しない (#32)。対象は設定 plugins.action_denied で
    // 追加できる (キー形式・値形式のどちらでも可)。
    // なお convert 一般の一律制限は行わない: 表示専用プラグインの
    // 直接実行には正当な利用があり、書き込み系は各プラグイン側で
    // ログイン・CSRFトークン・凍結チェックを行う前提とする。
    $denied = array('meta_table' => TRUE);
    $config_denied = konawiki_private('plugins.action_denied', array());
    if (is_array($config_denied)) {
        foreach ($config_denied as $k => $v) {
            if (is_int($k)) { $denied[$v] = TRUE; }
            else if ($v) { $denied[$k] = TRUE; }
        }
    }
    if (isset($denied[$pname])) {
        echo "error (denied)"; exit;
    }
    // get pluin info
    $pi = konawiki_parser_getPlugin($pname);
    $file    = $pi['file'];
    $init    = $pi['init'];
    $action  = $pi['action'];
    $convert = $pi['convert'];
    if (!file_exists($file)) {
        header("HTTP/1.1 404 Not Found");
        echo "404 not found"; exit;
    }
    include_once $file;
    // analize params
    $params = explode(",", $p);
    if (function_exists($init)) {
      $s = call_user_func($init, $params);
      echo $s;
    }
    if (function_exists($convert)) {
      $s = call_user_func($convert, $params);
      echo $s;
    }
}


