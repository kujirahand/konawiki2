<?php
/** konawiki plugins -- なでしこのマニュアル用プラグイン
 * - [書式] (設定ファイルに記述)
 * - [引数]なし
 * - [使用例] なし
 * - [備考] page/show 専用のプラグイン
 * - [公開設定] 非公開
 */
//------------------------------------------------------------------------------
/* option
$konawiki['private']['show.plugins']['nadesiko'] = array(
        'enabled'   => FALSE,
        'file'      => 'show.nadesiko.inc.php',
        'entry'     => 'show_nadesiko',
        'db.dns'    => 'sqlite://data/command.db',
    );
*/
//------------------------------------------------------------------------------
global $plug_nadesiko;

function show_nadesiko($plugin, $log)
{
    // open db
    global $plug_nadesiko;
    global $konawiki_show_log;
    $plug_nadesiko = $plugin;
    //
    $page = konawiki_getPage();
    $page_ary = explode("/", $page);
    $res = "";
    $foot = "";
    if (count($page_ary) >= 2) {
        $mode = $page_ary[0];
        $p    = $page_ary[1];
        $p    = str_replace('／','/',$p);
        if ($mode == "分類") {
            if (count($page_ary) == 3) { // 小分類
                $p2 = $page_ary[2];
                $p2 = str_replace('／','/',$p2);
                $foot = show_nadesiko_showH2($p, $p2);
            }
            else { // 大分類
                $foot = show_nadesiko_showH1($p);
            }
        }
    }
    else if ($page == "分類") {
        $foot = show_nadesiko_showH0();
    } else {
        $res = show_nadesiko_showCommand($page);
        if (trim($res) !== "") {
            $page = konawiki_getPage();
            $page_ = rawurlencode($page);
            $page2  = "なでしこ $page";
            $page2_ = rawurldecode($page2);
            $url     = "http://nadesiko.g.hatena.ne.jp/keywordlist?word={$page_}";
            $google  = "http://www.google.co.jp/search?hl=ja&lr=lang_ja&q={$page2_}+site%3Apc.nikkeibp.co.jp";
            $google2 = "http://www.google.co.jp/search?hl=ja&lr=lang_ja&q={$page2_}";
            $foot = <<< EOS__
----
-[[ググってみる:$google2]]

#googleadsense(nako-yoko)

EOS__;
        }
    }
    $log['body'] = $res . $log['body'] . "\n". $foot;
    $konawiki_show_log = $log;
    //
    // 1回目だけ実行した
    // include("plugins/init_show.nadesiko.inc.php"); init_nadesiko();
}

function toUTF8($s)
{
    return $s;
    //return mb_convert_encoding($s, 'UTF8', 'SJIS');
}
function toSJIS($s)
{
    return $s;
    // return mb_convert_encoding($s, 'SJIS', 'UTF8');
}

function show_nadesiko_showH0()
{
    $db = show_nadesiko_getDB();
    $sql = "SELECT h1 FROM command group by h1";
    $r = $db->query($sql)->fetchAll();
    if (!$r) {
        return "ありません\n";
    }
    $res = "";
    $head_h1 = $old_head_h1 = $head_h2 = $old_head_h2 = "";
    foreach ($r as $row) {
        foreach ($row as $key => $val) { // to utf8
            $row[$key] = toUTF8($val);
        }
        $h1   = isset($row["h1"]) ? $row["h1"] : "";
        $h2   = isset($row["h2"]) ? $row["h2"] : "";
        $h1 = str_replace('/','／',$h1);
        $h2 = str_replace('/','／',$h2);
        $name = isset($row["name"]) ? $row["name"]: "";
        $head_h1 = "- &link(分類/{$h1});\n";
        if ($head_h1 != $old_head_h1) {
            $res .= $head_h1;
            $old_head_h1 = $head_h1;
        }
    }
    return $res;
}

function show_nadesiko_showH1($h1)
{
    $db = show_nadesiko_getDB();
    $sql = "SELECT name,h1,h2 FROM command WHERE h1=? order by h2";
    $p = $db->prepare($sql);
    $p->execute([$h1]);
    $r = $p->fetchAll();
    if (!$r) {
        return "ありません\n";
    }
    $res = "";
    $head = $head2 = "";
    foreach ($r as $row) {
        foreach ($row as $key => $val) { // to utf8
            $row[$key] = toUTF8($val);
        }
        $h1  = $row["h1"];
        $h1 = str_replace('/','／',$h1);
        $h2  = $row["h2"];
        $h2 = str_replace('/','／',$h2);
        $name = $row["name"];
        $head = "- &link(分類/$h1/$h2);\n";
        if ($head2 != $head) {
            $res .= $head;
            $head2 = $head;
        }
        $res .= "--[[$name]]\n";
    }
    return $res;
}

function show_nadesiko_showH2($h1,$h2)
{
    $db = show_nadesiko_getDB();
    $sql = "SELECT name,h1,h2 FROM command WHERE h1=? AND h2=? order by h1";
    $p = $db->prepare($sql);
    $p->execute([$h1, $h2]);
    $r = $p->fetchAll();
    if (!$r) {
        return "ありません\n";
    }
    $res = "";
    $head_h1 = $old_head_h1 = $head_h2 = $old_head_h2 = "";
    foreach ($r as $row) {
        foreach ($row as $key => $val) { // to utf8
            $row[$key] = toUTF8($val);
        }
        $h1   = $row["h1"];
        $h2   = $row["h2"];
        $h1 = str_replace('/','／',$h1);
        $h2 = str_replace('/','／',$h2);
        $name = $row["name"];
        $head_h1 = "- &link(分類/{$h1});\n";
        if ($head_h1 != $old_head_h1) {
            $res .= $head_h1;
            $old_head_h1 = $head_h1;
        }
        $head_h2 = "-- &link(分類/{$h1}/{$h2});\n";
        if ($head_h2 != $old_head_h2) {
            $res .= $head_h2;
            $old_head_h2 = $head_h2;
        }
        $res .= "---[[$name]]\n";
    }
    return $res;
}

function show_nadesiko_showCommand($name)
{
    $db = show_nadesiko_getDB();
    $sql = "SELECT * FROM command WHERE name=?";
    $p = $db->prepare($sql);
    $p->execute([$name]);
    $r = $p->fetchAll();
    $res = '';
    if ($r) {
        foreach ($r as $row) {
            foreach ($row as $key => $val) {
                $val = toUTF8($val);
                $val = str_replace('|','｜', $val);
                $row[$key] = $val;
            }
            extract($row);
            if ($name == $kana) {
                $kana = "";
            } else {
                $kana = "($kana)";
            }
            if ($ctype=="変数") {
                $argdesc = "初期値";
            } else {
                $argdesc = "引数";
            }
            $h1 = str_replace('/','／',$h1);
            $h2 = str_replace('/','／',$h2);
            $res .= <<< EOS__
{{{#rem
#googleadsense(nako-yoko)
}}}

* $name $kana
|分類|&link(分類/$h1/$h2);|
|種類|$ctype|
|$argdesc|$args|
|説明|$desc|
|識別|id:$id|

EOS__;
        }
    }
    return $res;
}

function show_nadesiko_getDB()
{
    global $plug_nadesiko;
    $db = isset($plug_nadesiko['db.handle'])  ? $plug_nadesiko['db.handle'] : null ;
    if (!$db) {
        $dns = $plug_nadesiko['db.dns'];
        $db = new PDO($dns);
        // var_dump($db);
        if (!$db) {
            echo 'COMMAND DATABASE OPEN ERROR!';
            exit;
        }
        $plug_nadesiko['db.handle'] = $db;
    }
    return $db;
}
