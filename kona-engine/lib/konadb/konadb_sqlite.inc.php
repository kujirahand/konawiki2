<?php
/**
 * konadb for sqlite
 */
class konadb_sqlite extends konadb
{
    function open()
    {
        $this->handle = @sqlite_open(
            $this->database,
            0666,
            $this->error);
        return $this->handle;
    }
    
    function close()
    {
        @sqlite_close($this->handle);
    }
    
    function exec($sql)
    {
    	$sql = $this->check_sql($sql);
        // execute sql
        if ($this->debug) {
            $this->sql_log($sql);
            $res = sqlite_exec(
                $this->handle,
                $sql);
            if ($res == FALSE) {
                echo '<pre>';
                print_r(debug_backtrace());
                echo '</pre>';
            }
        } else {
            $res = @sqlite_exec(
                $this->handle,
                $sql);
        }
        return $res;
    }
    
    function array_query($sql)
    {
        $sql = $this->check_sql($sql);
    	
        if ($this->debug) {
            $this->sql_log($sql);
            $res = sqlite_array_query(
                $this->handle,
                $sql,SQLITE_BOTH);
            if ($res === FALSE) {
                $this->sql_log("ERROR");
            }
        } else {
            $res = sqlite_array_query(
                $this->handle,
                $sql, SQLITE_BOTH);
            // debug
            /*
            if (!$res) {
                echo "<pre>";
                echo $sql;
                print_r( debug_backtrace());
            }
            */
        }
        return $res;
    }
    
    function escape($sql)
    {
        if ($sql) {
            $sql = sqlite_escape_string($sql);
        } else {
            $sql = "";
        }
        return $sql;
    }
    function getLastId()
    {
        return sqlite_last_insert_rowid($this->handle);
    }
    function sql_log($sql)
    {
        konadb::sql_log($sql);
    }
    
   /**
     * SQLを確認する
     * @param $sql
     * @return string
     */
    function check_sql($sql)
    {
    	// SQLITEでは AUTO_INCREMENT 不要
    	$sql = str_replace('AUTO_INCREMENT','',$sql);
    	// INDEX TEXT
    	if (strpos($sql, 'CREATE') && strpos($sql, 'INDEX')) {
	    	$sql = preg_replace('#\(\d+\)#', '', $sql);
    	}	
    	return $sql;
    }
}

?>
