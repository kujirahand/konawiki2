<?php

class konadb_mysql extends konadb
{
    function open()
    {
        $this->handle = mysql_connect(
            $this->host,
            $this->user,
            $this->password,
            TRUE // host,user,passwordが同じでも新しい接続を確立
        );
        if (!@mysql_select_db($this->database, $this->handle)) {
            $this->handle = FALSE;
        }
        return $this->handle;
    }
    
    function close()
    {
        @mysql_close($this->handle);
    }
    
    function __exec($sql)
    {
        // execute sql
        if ($this->debug) {
            $this->sql_log($sql);
            $result = @mysql_query($sql, $this->handle);
            if ($result == FALSE) {
                echo '<pre>';
                $err = mysql_error($this->handle);
                echo "[ERROR] {$err}\n{$sql}\n";
                print_r(debug_backtrace());
                echo '</pre>';
            }
        } else {
            $result = @mysql_query($sql, $this->handle);
        }
        return ($result !== FALSE);
    }
    
    /**
     * [重要] 暫定サポート→複文の実行
     */
    function exec($sql)
    {
        $sql = trim($sql);
        $sql_a = preg_split("#\/\* \-\-\- \*\/#", $sql);
        if (count($sql_a) == 1) {
            return $this->__exec($sql);
        }
        $result = TRUE;
        
        foreach ($sql_a as $row) {
            $row = trim($row);
            if (!$row) continue;
            if (!$this->__exec($row)) {
                $result = FALSE;
                break;
            }
        }
        
        return $result;
    }
    
    function array_query($sql)
    {
        if ($this->debug) {
            $this->sql_log($sql);
            $result = @mysql_query($sql, $this->handle);
            if ($result == FALSE) {
                echo '<pre>';
                print_r(debug_backtrace());
                echo '</pre>';
            }
        } else {
            $result = @mysql_query($sql, $this->handle);
        }
        if ($result === FALSE) return FALSE;
        $r = array();
        while ($row = mysql_fetch_array($result, MYSQL_BOTH)) {
            $r[] = $row;
        }
        return $r;
    }
    
    function escape($sql)
    {
        $sql = mysql_real_escape_string($sql);
        return $sql;
    }
    function getLastId()
    {
        return mysql_insert_id($this->handle);
    }
    function sql_log($sql)
    {
        konadb::sql_log($sql);
    }
}

?>
