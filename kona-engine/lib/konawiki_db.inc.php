<?php
/**
 * データベースを簡単に使うためのライブラリなど
 * [備考]
 * main/sub/backup とデータベースを分けているのは、バックアップのしやすさを優先したため
 */

/**
 * データベースクラスを取得する
 * @return konadb
 */
function konawiki_getDB()
{
    global $konawiki;
    $db = isset($konawiki['private']['db.handle']) 
      ? $konawiki['private']['db.handle'] : false;
    if ($db == FALSE) {
        $dsn = $konawiki['private']['db.dsn'];
        $db  = konadb_create_dsn($dsn);
        if (!$db) {
            echo 'DATABASE OPEN ERROR!';
            echo '('.konawiki_lang("Please check dir permission.").')'; 
            exit;
        }
        $konawiki['private']['db.handle'] =& $db;
        $db->debug = $konawiki['private']['debug'];
        if (!$db->open()) {
            echo 'DATABASE OPEN ERROR!';
            echo '('.konawiki_lang("Could not open DB.").')'; 
            exit;
        }
    }
    return $db;
}

/**
 * サブデータベースのクラスを取得する
 * @return konadb
 */
function konawiki_getSubDB()
{
    global $konawiki;
    $db = $konawiki['private']['subdb.handle'];
    if ($db == FALSE) {
        $dsn = $konawiki['private']['subdb.dsn'];
        $db  = konadb_create_dsn($dsn);
        if (!$db) {
            echo 'database open error(SUB DB)';
            exit;
        }
        $konawiki['private']['subdb.handle'] =& $db;
        $db->debug = $konawiki['private']['debug'];
        if (!$db->open()) {
            echo 'DATABASE OPEN ERROR!(SUB DB)';
            exit;
        }
    }
    return $db;
}

/**
 * バックアップ用データベースクラスを取得する
 * @return konadb
 */
function konawiki_getBackupDB()
{
    global $konawiki;
    $db = $konawiki['private']['backupdb.handle'];
    if ($db == FALSE) {
        $dsn = $konawiki['private']['backupdb.dsn'];
        $db  = konadb_create_dsn($dsn);
        if (!$db) {
            echo 'database open error';
            exit;
        }
        $konawiki['private']['backupdb.handle'] =& $db;
        $db->debug = $konawiki['private']['debug'];
        if (!$db->open()) {
            echo 'DATABASE OPEN ERROR!(BUCKUP DB)';
            exit;
        }
    }
    return $db;
}

/**
 * データベースの設定ファイル  "key_value" から値を取り出す
 * @param $key
 * @param $default
 * @return integer
 */
function konawiki_db_getConfig($key, $default = false)
{
	// get db
	$db = konawiki_getDB();
	if (!$db) { return $default; }
	// select key
	$_key = $db->escape($key);
	$sql = "SELECT * FROM key_values WHERE name = '{$_key}' LIMIT 1";
	$rows = @$db->array_query($sql);
	if (!$rows) return $default;
	$v = isset($rows[0]['value']) ? $rows[0]['value'] : $default;
	return $v;
}

function konawiki_setAuthHash($log_id, $hash)
{
  $log_id = intval($log_id);
  $db = konawiki_getDB();
  if (!$db) { return false; }
  $sql = "SELECT * FROM log_auth_hash WHERE log_id=$log_id";
  $rows = $db->array_query($sql);
  if (!$rows) {
      $sql =
      "INSERT INTO log_auth_hash".
      " (log_id,  hash) VALUES".
      " ($log_id, '$hash')";
  } else {
      $sql = 
      "UPDATE log_auth_hash".
      " SET hash='$hash'".
      " WHERE log_id=$log_id";
  }
  @$db->begin();
  @$db->exec($sql);
  @$db->commit();
}
function konawiki_getAuthHash($log_id) {
  $log_id = intval($log_id);
  $db = konawiki_getDB();
  if (!$db) { return ''; }
  $sql = "SELECT * FROM log_auth_hash WHERE log_id=$log_id";
  $rows = $db->array_query($sql);
  if (!$rows) { return ''; }
  return $rows[0]['hash'];
}

function konawiki_initDB()
{
    global $konawiki;
    $DATABASE_VERSION = 105;
    
    $konawiki['private']['db.handle']       = FALSE;
    $konawiki['private']['subdb.handle']    = FALSE;
    $konawiki['private']['backupdb.handle'] = FALSE;
    
    // Initialize Main Database
    $db = konawiki_getDB();
    if ($db) {
        // update ?
        $db_version = konawiki_db_getConfig('version', 0);
    	  if ($db_version < $DATABASE_VERSION) {
    		    if ($db_version == 0) {
    			    _konawiki_db_init();
    		    }
            konawiki_initDB_versionup($db_version);
            konawiki_showMessage(konawiki_lang('Success to update DB.'));
            exit;
        }
    }
    else {
        $msg = "[ERROR] MAIN DATABASE : FAILED TO OPEN!";
        echo "<p>$msg</p>";
        konawiki_error($msg);
        exit;
    }
}

function _konawiki_db_init()
{
	konawiki_initDB_createDB();
	konawiki_initDB_addHelp();
	$msg = konawiki_lang('Success to init DB.');
	konawiki_showMessage($msg);
	exit;
}

function konawiki_initDB_versionup($current_version)
{
	// ---
	// バージョンに応じて、順次バージョンアップする
	// ---
	if ($current_version < 102) {
		// key_value/tag テーブルの導入
		konawiki_initDB_version102();
	}
	if ($current_version == 102) {
		konawiki_initDB_version103();
	}
	if ($current_version == 103) {
		konawiki_initDB_version104();
  }
	if ($current_version == 104) {
    konawiki_initDB_version105();
  }
	// if ($current_version == 105) { ... }
}

function konawiki_initDB_createDB()
{
    $errmsg = konawiki_lang('Failed to init DB');
    // init main db
    $db = konawiki_getDB();
    $sql = file_get_contents(
        KONAWIKI_DIR_TEMPLATE."/konawiki_sql_main.txt");
    if (!$db->execSplitSQL($sql)) {
        $errmsg .= "<pre style='color:red'>".$db->error."</pre>";
        konawiki_error($errmsg); 
        exit;
    }
    // init sub db
    $subdb = konawiki_getSubDB();
    if ($subdb) {
        $sql = file_get_contents(
            KONAWIKI_DIR_TEMPLATE."/konawiki_sql_subdb.txt");
        if (!$subdb->execSplitSQL($sql)) {
            $errmsg .= "<pre>".$subdb->error."</pre>";
            konawiki_error($errmsg.":subdb:"); 
            exit;
        }
    }
    // init backup db
    $backupdb = konawiki_getBackupDB();
    if ($backupdb) {
        $sql = file_get_contents(
            KONAWIKI_DIR_TEMPLATE."/konawiki_sql_backup.txt");
        if (!$backupdb->execSplitSQL($sql)) {
            $errmsg .= "<pre>".$backupdb->error."</pre>";
            konawiki_error($errmsg.":backupdb:");
            exit;
        }
    }
}


function konawiki_initDB_addHelp()
{
    return;
    // Now not support
    $helps = array(
        'FirstGuide.txt'    =>  konawiki_public("FrontPage"),
        'AboutKonaWiki.txt' =>  'KonaWikiについて',
        'WikiFormat.txt'    =>  'KonaWikiについて/整形ルール',
        'plug-ins.txt'      =>  'KonaWikiについて/標準プラグイン一覧'
    );
    
    foreach ($helps as $fname => $wname) {
        $_POST['page'] = $wname;
        $body = "■{$wname}\n#help($fname)\n";
        if (!konawiki_writePage($body, $err)) {
            echo "DBへの書き込みができませんでした。";
        }
    }
    
    $_POST['page'] = konawiki_public('FrontPage');
}

// ver0 -> 102
function konawiki_initDB_version102()
{
    $db = konawiki_getDB();
    // テーブルの削除
    $db->begin();
    if (!$db->exec("DROP TABLE key_values")) {
        $db->rollback();
        konawiki_error("データベースのバージョンアップ失敗");
        exit;
    }
    // 新規テーブルの作成
    $sql = <<< EOS
CREATE TABLE key_values
(
    name    TEXT,
    value   INTEGER DEFAULT 0
);
EOS;
    if (!$db->exec($sql)) {
        $db->rollback();
        konawiki_error("データベースのバージョンアップ失敗");
        exit;
    }
    if (!$db->exec("INSERT INTO key_values (name, value) VALUES ('version', 102)")) {
        $db->rollback();
        konawiki_error("データベースのバージョンアップ失敗");
        exit;
    }
    $db->commit();
    
    // init backup db
    $backupdb = konawiki_getBackupDB();
    if ($backupdb) {
        $sql = file_get_contents(
            KONAWIKI_DIR_TEMPLATE."/konawiki_sql_backup.txt");
        // sqlite では、AUTO_INCREMENT をつけるとエラーになる
        if ($db->driver == "sqlite" || $db->driver == "pdosqlite") {
            $sql = str_replace("AUTO_INCREMENT", "", $sql);
        }
        if (!$backupdb->exec($sql)) {
            konawiki_error($errmsg.":backupdb"); exit;
        }
    }
}

// ver102 -> 103
function konawiki_initDB_version103()
{
    $db = konawiki_getDB();
    $db->begin();
    // index
    $r = @$db->exec('CREATE UNIQUE INDEX logs_name_index ON logs (name(256));');
    if (!$r) {
    	konawiki_error('アップデートに失敗しました。インデックス(logs_name_index)が追加できません。');
    	$db->rollback();
    	exit;
    }
    $r = @$db->exec('CREATE UNIQUE INDEX tags_tag_index ON tags (log_id, tag(256));');
    if (!$r) {
    	konawiki_error('アップデートに失敗しました。インデックス(tags_tag_index)が追加できません。');
    	$db->rollback();
    	exit;
    }
    // update
    $r = @$db->exec('UPDATE key_values SET value=103 WHERE name="version";');
    if (!$r) {
    	konawiki_error('アップデートに失敗しました。');
    	$db->rollback();
    	exit;
    }
    // create cache db
    $back_db = konawiki_getBackupDB();
    $back_db->begin();
    $sql = <<< END_OF_SQL
CREATE TABLE cache_logs
(
	log_id	INTEGER PRIMARY KEY,
	html	TEXT,
	ctime	INTEGER,
	flag	TEXT
);
END_OF_SQL;
    $r = @$back_db->exec($sql);
    if (!$r) {
    	konawiki_error('アップデートに失敗しました。');
    	$db->rollback();
    	exit;
    }
    
    // OK
    $back_db->commit();
    $db->commit();
}

function konawiki_initDB_version104()
{
    $sql = <<< EOS
CREATE TABLE log_auth_hash (
  log_id  INTEGER,
  hash    TEXT
);
EOS;
    $db = konawiki_getDB();
    $db->begin();
    // create table
    $r = @$db->exec($sql);
    if (!$r) {
    	konawiki_error('アップデート1に失敗しました。');
    	$db->rollback();
    	exit;
    }
    // update version
    $r = @$db->exec('UPDATE key_values SET value=104 WHERE name="version";');
    if (!$r) {
    	konawiki_error('アップデート2に失敗しました。');
    	$db->rollback();
    	exit;
    }
    $db->commit();
}

function konawiki_initDB_version105()
{
    $sql = <<< EOS
/* --- */
ALTER TABLE logs ADD COLUMN private INTEGER DEFAULT 0;
ALTER TABLE logs ADD COLUMN freeze  INTEGER DEFAULT 0;
/* --- */
EOS;
    $sql2 = <<< EOS
/* --- */
ALTER TABLE oldlogs ADD COLUMN private INTEGER DEFAULT 0;
ALTER TABLE oldlogs ADD COLUMN freeze  INTEGER DEFAULT 0;
/* --- */
EOS;

    $db = konawiki_getDB();
    $db->begin();
    // create table
    $r = @$db->exec($sql);
    if (!$r) {
    	$db->rollback();
    	konawiki_error('アップデート1に失敗しました。'.
        $db->error);
    	exit;
    }
    $db2 = konawiki_getBackupDB();
    $db2->begin();
    $r = @$db2->exec($sql2);
    if (!$r) {
    	$db2->rollback();
      konawiki_error('アップデート2に失敗しました。'.
        $db2->error);
      exit;
    }
    // update version
    $r = @$db->exec('UPDATE key_values SET value=105 WHERE name="version";');
    if (!$r) {
    	konawiki_error('アップデート3に失敗しました。');
    	$db->rollback();
    	exit;
    }
    $db->commit();
    $db2->commit();
}

