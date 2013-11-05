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
    // 実装メモ:
    // - サブデータベースに保存する
    // - カウンタは、DBのカラム ctime を利用する
    // - 挿入日時は、mtime を利用する
    konawiki_setPluginDynamic(false);
    if (isset($params[0]) && $params[0] == "js") {
      plugin_counter_getCount();
      exit;
    }
    $page = konawiki_getPage();
    $url = konawiki_getPageURL($page, "plugin", FALSE, 
      "name=counter&amp;p=js"); 
    $url = str_replace("&amp;", "&", $url);
    $s = <<< EOS
<div id="counter_div" class="counter"></div>
<script type="text/javascript">
xhr_get(
  "$url",
  function(t) {
    var e = document.getElementById("counter_div");
    e.innerHTML = t;
  });
function xhr_get(url, callback) {
  var xhr = new XMLHttpRequest();
  xhr.open('GET', url, true);
  xhr.onreadystatechange = function () {
    if (xhr.readyState == 4 && xhr.status == 200) {
      callback(xhr.responseText, xhr);
    }
  };
  xhr.send();
}
</script>
EOS;
    return $s;
	
}

// count up & return count
function plugin_counter_getCount()
{
    header("Content-Type: text/plain");
    $log_id = konawiki_getPageId();
    if (!$log_id) {
        echo "(0)";
        exit;
    }
    $pname = 'counter';
    $db = konawiki_getSubDB();
    $sql = "SELECT * FROM sublogs WHERE ".
        " log_id=$log_id AND plug_name='$pname'".
        " ";
    $count = 1;
    $r = @$db->array_query($sql);
    $mtime = time();
    if (isset($r[0]["id"])) {
        $id = $r[0]["id"];
        $count = intval($r[0]["ctime"]) + 1;
        $sql = "UPDATE sublogs SET ctime=$count,mtime=$mtime WHERE".
            " id=$id";
        if (!@$db->exec($sql)) {
            echo "[counter.failed]";
        }
    }
    else {
        $sql = "INSERT INTO sublogs ".
            "(log_id, plug_name, ctime, mtime)VALUES".
            "($log_id,'$pname','$count', $mtime)";
        if (!@$db->exec($sql)) {
            echo "[counter.failed]";
        }
    }
    echo $count;
}

