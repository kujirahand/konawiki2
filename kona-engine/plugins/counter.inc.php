<?php
#vim:set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
/** konawiki plugins -- アクセスカウンター
 * - [書式] #counter
 * - [引数] なし
 * - [使用例] #counter
 * - [備考]
 * -- MenuBar などに埋め込んでおくと、すべてのページのアクセスをカウントできる。
 * -- popular プラグインと組み合わせることで、人気ランキングを表示できる。
 */
 
function plugin_counter_convert($params)
{
    global $konawiki;
    // This access counter use Ajax
    konawiki_setPluginDynamic(false);
    if (isset($params[0]) && $params[0] == "js") {
      // for Ajax
      plugin_counter_getCount();
      exit;
    }
    // show HTML/JavaScript Code
    $page = $konawiki['public']['page_raw'];
    $url = konawiki_getPageURL($page, "plugin", FALSE, 
      "name=counter&amp;p=js"); 
    $url = str_replace("&amp;", "&", $url);
    $s = <<< EOS
<ul class="counter">
  <li class="counter_disp">*</li>
</ul>
<script type="text/javascript">
$(function () {
  if (!window.kona2) { window.kona2 = {}; }
  if (!window.kona2.counter_go) {
    window.kona2.counter_go = 1;
    $.get("$url", function(t) {
      $(".counter_disp").html('✔' + t);
    });
  }
});
</script>
EOS;
    return $s;
}

// count up & return count
function plugin_counter_getCount()
{
    header('Content-Type: text/html');
    $log_id = konawiki_getPageId();
    if (!$log_id) {
        echo "(*)"; exit;
    }
    // Total : count up
    $now = time();
    $total = 0;
    db_exec('begin', [], 'sub');
    $sql = 
      "SELECT * FROM mcounter_total WHERE ".
      " log_id=? LIMIT 1";
    $r = db_get1($sql, [$log_id], 'sub');
    if (!isset($r["total"])) {
        // first time
        $ins_sql = 
            "INSERT INTO mcounter_total ".
            "  ( log_id, total, mtime) VALUES ".
            "  (      ?,     ?,     ?)";
        db_exec($ins_sql, [$log_id, 1, $now], 'sub');
        $total = 1;
    } else {
        // count up
        $up_sql =
            "UPDATE mcounter_total SET ".
            "  total=total+1, mtime=? ".
            "  WHERE log_id=?";
        db_exec($up_sql, [$now, $log_id], 'sub');
        $total = $r["total"] + 1;
    }
    // daily : count up
    $value = 0;
    $stime = strtotime(date("Y-m-d", $now));
    $where = "stime=$stime";
    $sql = 
        "SELECT * FROM mcounter_day WHERE ".
        "  log_id=? AND $where LIMIT 1";
    $r = db_get1($sql, [$log_id], 'sub');
    if (!isset($r["value"])) {
        $ins_sql =
            "INSERT INTO mcounter_day ".
            "  ( log_id, stime, value, mtime) VALUES".
            "  (      ?,     ?,     1,     ?)";
        db_exec($ins_sql, [$log_id, $stime, $stime], 'sub');
        $value = 1;
    } else {
        $up_sql =
            "UPDATE mcounter_day SET ".
            " value=value+1, mtime=? ".
            " WHERE log_id=? AND $where";
        db_exec($up_sql, [$stime, $log_id], 'sub');
        $value = $r["value"] + 1;
    }
    db_exec('commit', [], 'sub');   
    // show result
    $today = konawiki_lang('Today');
    echo "$total <em class='counter_memo'>($today:$value)</em>";
}

