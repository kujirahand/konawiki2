<?php
#vim:set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
// プラグインを実行するだけのアクション
function action_plugin_()
{
    $page   = konawiki_getPage();
    $pname  = konawiki_param("name","");
    $p      = konawiki_param("p","");
    if ($pname == "") { echo "error"; exit; }
    // check disable 
    $disable = konawiki_private("plugins.disable");
    if (isset($disable[$pname]) && $disable[$pname]) {
        echo "error"; exit;
    }
    // get pluin info 
    $pi = konawiki_parser_getPlugin($pname);
    $file    = $pi['file'];
    $init    = $pi['init'];
    $action  = $pi['action'];
    $convert = $pi['convert'];  
    if (!file_exists($file)) {
        echo 'error'; exit;
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


