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
    $users_dsn = konawiki_private('users_db.dsn');

    // setup database
    $sql_dir = konawiki_private('dir.engine').'/sql';
    database_set($db_dsn, $sql_dir.'/konawiki_sql_main.sql', 'main');
    database_set($sub_dsn, $sql_dir.'/konawiki_sql_subdb.sql', 'sub');
    database_set($backup_dsn, $sql_dir.'/konawiki_sql_backup.sql', 'backup');
    database_set($users_dsn, $sql_dir.'/konawiki_sql_users.sql', 'users');
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

/**
 * attach テーブルに comment カラム (ファイル毎のコメント, Issue #21) があるか
 * 確認し、なければ追加する。
 * 既存のDBにはマイグレーション機構がないため、必要な時点で遅延追加する。
 * 新規DBでは konawiki_sql_main.sql の定義により最初から存在する。
 * @return boolean comment カラムが利用可能なら TRUE
 */
function konawiki_attach_has_comment_column()
{
    $cols = db_get("PRAGMA table_info(attach)");
    if ($cols) {
        foreach ($cols as $col) {
            if (isset($col['name']) && $col['name'] === 'comment') {
                return TRUE;
            }
        }
    }
    return FALSE;
}

/**
 * comment カラムの利用可否を確認し、結果をプロセス内でキャッシュする。
 * @param boolean $force TRUE のときはキャッシュを使わず再確認する
 *                       (テーブル差し替えを行うテストなどから使う)
 * @return boolean comment カラムが利用可能なら TRUE
 */
function konawiki_attach_ensure_comment_column($force = FALSE)
{
    static $available = null;
    if ($available !== null && !$force) {
        return $available;
    }
    $available = FALSE;
    if (!db_table_exists('attach')) {
        return FALSE;
    }
    if (konawiki_attach_has_comment_column()) {
        $available = TRUE;
        return TRUE;
    }
    try {
        db_exec("ALTER TABLE attach ADD COLUMN comment TEXT DEFAULT ''");
        $available = TRUE;
    } catch (Exception $e) {
        // 同時実行で既に追加された場合は再確認した結果を返す。
        // 読み取り専用のDB等で追加できない場合は FALSE を返し、
        // 呼び出し元の表示処理はコメントなしで継続できるようにする。
        $available = konawiki_attach_has_comment_column();
    }
    return $available;
}

/**
 * 添付ファイルのコメントを保存する (Issue #21)
 * @param integer $id attach テーブルの id
 * @param string $comment コメント (空文字列でコメントを消去)
 * @return boolean 保存できたら TRUE
 */
function konawiki_attach_set_comment($id, $comment)
{
    if (!konawiki_attach_ensure_comment_column()) {
        return FALSE;
    }
    $id = intval($id);
    $sql = "UPDATE attach SET comment=? WHERE id=?";
    db_exec($sql, [(string)$comment, $id]);
    return TRUE;
}

/**
 * 添付ファイルのコメントを取り出す (Issue #21)
 * カラム追加前の行や未指定の場合は空文字列を返す。
 * @param array $row attach テーブルの行
 * @return string コメント
 */
function konawiki_attach_row_comment($row)
{
    if (isset($row['comment']) && $row['comment'] !== null) {
        return (string)$row['comment'];
    }
    return '';
}

/**
 * 添付ファイル名の変更内容を検証する (Issue #22)
 * 実ファイルは「{id}{拡張子}」で保存されるため、拡張子の変更は禁止する。
 * @param string $oldname 現在のファイル名
 * @param string $newname 新しいファイル名
 * @return string 問題なければ空文字列、問題があればエラーメッセージ
 */
function konawiki_attach_validate_rename($oldname, $newname)
{
    $oldname = (string)$oldname;
    $newname = trim((string)$newname);
    if ($newname === '') {
        return "ファイル名が空です。";
    }
    if (strlen($newname) > 255) {
        return "ファイル名が長すぎます。";
    }
    // パス区切り・制御文字・引用符による事故を防ぐ
    // (引用符や改行はダウンロード時の Content-Disposition ヘッダーを壊すため)
    if (preg_match('/[\x00-\x1F\x7F"\']/', $newname) ||
        strpos($newname, '/') !== FALSE || strpos($newname, '\\') !== FALSE ||
        $newname === '.' || $newname === '..') {
        return "ファイル名に使えない文字が含まれています。";
    }
    // 拡張子は大文字小文字も含めて厳密に一致させる
    $old_ext = '';
    $new_ext = '';
    if (preg_match('/(\.\w+)$/', $oldname, $m)) { $old_ext = $m[1]; }
    if (preg_match('/(\.\w+)$/', $newname, $m)) { $new_ext = $m[1]; }
    if ($old_ext !== $new_ext) {
        return "拡張子は変更できません。";
    }
    return '';
}

/**
 * 同じページ内で重複しない添付ファイル名を返す (Issue #22)
 * 同名があれば、拡張子の前に「_2」「_3」…と連番を付けて最初に空いている名前にする。
 * @param integer $log_id ページの id
 * @param string $name 希望するファイル名
 * @param integer $exclude_id 重複判定から除外する attach の id (名前変更時の自分自身)
 * @return string 重複しないファイル名 (255バイト以内)
 */
function konawiki_attach_unique_name($log_id, $name, $exclude_id = 0)
{
    $log_id = intval($log_id);
    $exclude_id = intval($exclude_id);
    $name = (string)$name;
    $sql = "SELECT id FROM attach WHERE log_id=? AND name=? AND id<>?";
    $dup = db_get1($sql, [$log_id, $name, $exclude_id]);
    if (!isset($dup['id'])) {
        return $name;
    }
    // 拡張子の切り分けは既存の規約どおり (最後のドット以降)
    $base = $name;
    $ext = '';
    if (preg_match('/(\.\w+)$/', $name, $m)) {
        $ext = $m[1];
        $base = substr($name, 0, strlen($name) - strlen($ext));
    }
    // 連番候補が、除外対象 (自分自身) の現在の名前と同じ場合は空きとみなさない
    // (例: report_2.txt を report.txt に変更しようとして report_2.txt に戻ってしまうのを防ぐ)
    $self_name = null;
    if ($exclude_id > 0) {
        $self = db_get1("SELECT name FROM attach WHERE id=?", [$exclude_id]);
        if (isset($self['name'])) { $self_name = (string)$self['name']; }
    }
    for ($n = 2; ; $n++) {
        $suffix = "_{$n}" . $ext;
        // 255バイトを超えないよう、必要ならベース名を短くする
        $b = $base;
        if (strlen($b) + strlen($suffix) > 255) {
            $b = mb_strcut($b, 0, max(0, 255 - strlen($suffix)), 'UTF-8');
        }
        $cand = $b . $suffix;
        if ($self_name !== null && $cand === $self_name) {
            continue;
        }
        $dup = db_get1($sql, [$log_id, $cand, $exclude_id]);
        if (!isset($dup['id'])) {
            return $cand;
        }
    }
}

/**
 * 添付ファイルの名前を変更する (Issue #22)
 * mtime は変更しない (一覧の並び順を変えないため)。comment にも触れない。
 * @param integer $id attach テーブルの id
 * @param string $newname 新しいファイル名 (関数内でも検証する)
 * @param integer|null $log_id 指定すると同一ページ内の重複を更新と不可分に防ぐ
 * @return boolean 変更できたら TRUE
 */
function konawiki_attach_rename($id, $newname, $log_id = null)
{
    $id = intval($id);
    $newname = trim((string)$newname);
    // 単独で呼ばれた場合にも安全なよう、現在の名前を取得して検証する
    $row = db_get1("SELECT * FROM attach WHERE id=?", [$id]);
    if (!isset($row['id'])) {
        return FALSE;
    }
    if (konawiki_attach_validate_rename($row['name'], $newname) !== '') {
        return FALSE;
    }
    $db = database_get();
    if ($log_id !== null) {
        // 重複チェックと更新を1文で行い、同時変更でも同名が重複しないようにする
        $stmt = $db->prepare(
            "UPDATE attach SET name=? WHERE id=?".
            " AND NOT EXISTS (SELECT 1 FROM attach".
            " WHERE log_id=? AND name=? AND id<>?)");
        $stmt->execute([$newname, $id, intval($log_id), $newname, $id]);
    } else {
        $stmt = $db->prepare("UPDATE attach SET name=? WHERE id=?");
        $stmt->execute([$newname, $id]);
    }
    // 実際に更新された行がある場合だけ成功とする
    return $stmt->rowCount() > 0;
}
