<?php
/* vim:set expandtab ts=4 sts=4 sw=4: */
/**
 * konadb for pdo sqlite
 * -----------------------------
 * PDO は、PHP5 専用
 * -----------------------------
 */
class konadb_pdosqlite extends konadb
{
    function open()
    {
        $path = $this->database;
        try {
            $this->handle = new PDO("sqlite:$path", null, null);
            $this->handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception){
            echo "<pre>PDO SQLite Error: Could not open : `$path`\n";
            echo "Please change directory permission.\n";
	    echo $exception->getMessage();
            exit;
        }
        return $this->handle;
    }
    
    function close()
    {
        $this->handle = null;
    }
    
    function exec($sql)
    {
        // execute sql
        $line = $this->check_sql($sql);
        $line = $line . ";";
        try {
            $r = $this->handle->exec($line);    
            $this->sql_log("[ok]($r) $line");
            return true;
        } catch (PDOException $e) {
            $errstr = 
                join(":",$this->handle->errorInfo());
            $this->error .= "[ERROR]" . $errstr . "\n";
            $this->sql_log("[ng] $line $errstr");
            return false;
        }
    }
    
    function array_query($sql)
    {
        // execute sql
        try {
            $res = $this->handle->query($sql);
            if (!$res) return FALSE;
            $result = $res->fetchAll();
            $cnt = count($result);
            $this->sql_log("[ok]($cnt)".$sql);
            return $result;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            $this->sql_log("[ng]".$sql. " ".$msg);
            $this->error .= 
                "[ERROR]".
                join(":",$this->handle->errorInfo())."\n";
        }
        return false;
    }
    
    /**
     * SQLを確認する
     * @param $sql
     * @return string
     */
    function check_sql($sql)
    {
    	// SQLITEでは AUTO_INCREMENT 不要
        $u = strtoupper($sql);
        if (preg_match('#CREATE#', $u)) {
            $sql = str_replace('AUTO_INCREMENT','',$sql);
            // INDEX TEXT
            if (strpos($sql, 'CREATE') !== FALSE && strpos($sql, 'INDEX') !== FALSE) {
                $sql = preg_replace('#\(\d+\)#', '', $sql);
            }
        }
    	return $sql;
    }
    
    function escape($sql)
    {
        $sql = str_replace("'", "''", $sql);
        return $sql;
    }
    function quote($sql)
    {
        return $this->handle->quote($sql);
    }
    function begin()
    {
        $this->handle->beginTransaction();
    }
    function commit()
    {
        $this->handle->commit();
    }
    function rollback()
    {
        $this->handle->rollBack();
    }
    function getLastId()
    {
	    return $this->handle->lastInsertId();
	    	
    }
    function sql_log($sql)
    {
        konadb::sql_log($sql);
    }
}



