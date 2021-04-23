<?php
/**
 * データベースを簡単に使うためのライブラリなど
 * [備考]
 * main/sub/backup とデータベースを分けているのは、バックアップのしやすさを優先したため
 */


function konawiki_initDB()
{
    global $private;
    // get info
    $db_dsn = konawiki_private('db.dsn');
    $sub_dsn = konawiki_private('subdb.dsn');
    $backup_dsn = konawiki_private('backupdb.dsn');

    // setup database
    $sql_dir = konawiki_private('dir.engine').'/sql';
    database_set($db_dsn, $sql_dir.'/konawiki_sql_main.txt', 'main');
    database_set($sub_dsn, $sql_dir.'/konawiki_sql_subdb.txt', 'sub');
    database_set($backup_dsn, $sql_dir.'/konawiki_sql_backup.txt', 'backup');
}

/**
 * データベースクラスを取得する
 */
function konawiki_getDB()
{
    return database_get('main');
}

/**
 * サブデータベースのクラスを取得する
 */
function konawiki_getSubDB()
{
    return database_get('sub');
}

/**
 * バックアップ用データベースクラスを取得する
 */
function konawiki_getBackupDB()
{
    return database_get('backup');
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
    $row = db_get1('SELECT * FROM key_values WHERE name = ? LIMIT 1', [$key]);
    if (!$row) return $default;
    $v = isset($row['value']) ? $row['value'] : $default;
    return $v;
}

function konawiki_setAuthHash($log_id, $hash)
{
    $log_id = intval($log_id);
    $sql = "SELECT * FROM log_auth_hash WHERE log_id=? LIMIT 1";
    $row = db_get1($sql, [$log_id]);
    if (!$row) {
        $sql =
        "INSERT INTO log_auth_hash".
        " (log_id,  hash) VALUES".
        " (?, ?)";
        db_exec($sql, [$log_id, $hash]);
    } else {
        $sql = 
        "UPDATE log_auth_hash".
        " SET hash=?".
        " WHERE log_id=?";
        db_exec($sql, [$hash, $log_id]);
    }
}
function konawiki_getAuthHash($log_id) {
    $log_id = intval($log_id);
    $db = konawiki_getDB();
    if (!$db) { return ''; }
    $sql = "SELECT * FROM log_auth_hash WHERE log_id=? LIMIT 1";
    $row = db_get1($sql, [$log_id]);
    if (!$row) { return ''; }
    return $row['hash'];
}

function _konawiki_db_init()
{
    konawiki_initDB_createDB();
    konawiki_initDB_addHelp();
    $msg = konawiki_lang('Success to init DB.');
    konawiki_showMessage($msg);
    exit;
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
