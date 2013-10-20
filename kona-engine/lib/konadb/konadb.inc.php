<?php
/**
 * konadb --- Database Library
 */

// check library path
if (!defined("KONADB_DIR")) {
    define("KONADB_DIR", dirname(__FILE__));
}
// include library
include_once(KONADB_DIR."/strunit.inc.php");

/**
 * konawiki - dsn
 * + format
 * |- $driver://$username:$password@hostname/$database?options[=value]
 * + example
 * |- sqlite://data/konawiki.db
 * |- mysql://root:password@localhost/database?option
 */
function konadb_create_dsn($dsn)
{
    $driver     = '';
    $user       = '';
    $password   = '';
    $host       = '';
    $database   = '';
    $opt        = '';
    
    // driver
    $driver = strunit_token($dsn, '://');
    
    if (preg_match('#^(.*)\:(.*)\@(.*)\/(.*)\?*(.*)$#',$dsn, $m)) {
        $user       = $m[1];
        $password   = $m[2];
        $host       = $m[3];
        $database   = $m[4];
        $opt        = $m[5];
    }
    else
    if (preg_match('#^(.+)\?(.*)$#',$dsn, $m)) {
        $database   = $m[1];
        $opt        = $m[2];
    }
    else {
        $database = $dsn;
    }
    // check urlencode
    $database = urldecode($database);
    //
    $db = konadb_create($driver, $database, $host, $user, $password, $opt);
    return $db;
}

function konadb_create($driver, $database, $host = "", $user = "",  $password = "", $opt = "")
{
    $fname = KONADB_DIR."/konadb_{$driver}.inc.php";
    $cname = "konadb_{$driver}";
    include_once($fname);
    $db           = new $cname;
    $db->driver   = $driver;
    $db->user     = $user;
    $db->password = $password;
    $db->host     = $host;
    $db->database = $database;
    $db->opt      = $opt;
    return $db;
}

class konadb
{
    var $driver     = '';
    var $database   = '';
    var $host       = '';
    var $user       = '';
    var $password   = '';
    var $opt        = '';
    var $handle     = null;
    var $error      = '';
    var $debug      = FALSE;
    var $sql_logs   = '';
    
    function getConfigArray()
    {
        return array(
            'driver'    => $this->driver,
            'database'  => $this->database,
            'host'      => $this->host,
            'user'      => $this->user,
            'password'  => $this->password,
            'opt'       => $this->opt,
            'debug'     => $this->debug,
        );
    }
    
    function toString()
    {
        $r = $this->getConfigArray();
        $s = "";
        foreach ($r as $key => $val) {
            $s .= "$key=>$val\n";
        }
        return $s;
    }
    
    function not_support()
    {
        echo "Database method not support.\n";
        print_r(debug_backtrace());
        exit(-1);
    }
    
    function open()
    {
        not_support();
    }
    
    function close()
    {
        not_support();
    }
    /**
     * @arg sql     sql
     * @return  TRUE or FALSE
     */
    function exec($sql)
    {
        not_support();
    }
    /**
     * @arg sql     sql
     * @return  return array
     */
    function array_query($sql)
    {
        not_support();
    }
    /**
     * @return  last id
     */
    function getLastId()
    {
        not_support();
    }
    /**
     * @arg     sql sql
     * @return  escaped sql
     */
    function escape($sql)
    {
        not_support();
    }
    function quote($sql)
    {
      return "'" . $escape($sql) . "'";
    }
    function begin()
    {
        $this->exec("begin");
    }
    function commit()
    {
        $this->exec("commit");
    }
    function rollback()
    {
        $this->exec("rollback");
    }
    function sql_log($sql)
    {
        if ($this->debug) {
            $this->sql_logs .= $sql."\n---\n";
        }
    }
    function execSplitSQL($sql)
    {
        $sqls = explode("/* --- */", $sql);
        foreach ($sqls as $s) {
          $r = $this->exec($s);
          if (!$r) return $r;
        }
        return true;
    }
}


