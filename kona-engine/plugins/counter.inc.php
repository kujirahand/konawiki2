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
    if (isset($params[0]) && $params[0] == "history") {
      // for Ajax history fetch
      plugin_counter_getHistory();
      exit;
    }
    // show HTML/JavaScript Code
    $page = $konawiki['public']['page_raw'];
    $url = konawiki_getPageURL($page, "plugin", FALSE, 
      "name=counter&amp;p=js"); 
    $url = str_replace("&amp;", "&", $url);

    $history_url = konawiki_getPageURL($page, "plugin", FALSE,
      "name=counter&amp;p=history");
    $history_url = str_replace("&amp;", "&", $history_url);

    $is_login = konawiki_isLogin() ? 'true' : 'false';
    $login_class = konawiki_isLogin() ? ' counter_clickable' : '';
    $page_html = htmlspecialchars($page, ENT_QUOTES);
    $history_url_html = htmlspecialchars($history_url, ENT_QUOTES);

    $s = <<< EOS
<ul class="counter">
  <li class="counter_disp{$login_class}" data-page="{$page_html}" data-url="{$history_url_html}">*</li>
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

  if ($is_login) {
    // イベントハンドラが重複登録されるのを防ぐ
    if (!window.kona2.counter_event_set) {
      window.kona2.counter_event_set = 1;
      $(document).on("click", ".counter_clickable", function() {
        var historyUrl = $(this).attr("data-url");
        var pageName = $(this).attr("data-page");
        showCounterHistory(historyUrl, pageName);
      });
    }
  }

  function showCounterHistory(url, page) {
    $("#counter-history-modal").remove();

    $.getJSON(url, function(res) {
      if (res.status !== "ok") {
        alert(res.message);
        return;
      }

      var modalHtml = 
        '<div id="counter-history-modal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:9999;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.25s ease-in-out;">' +
        '  <div style="background:#ffffff;padding:24px;border-radius:16px;width:90%;max-width:420px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);position:relative;transform:scale(0.95);transition:transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);font-family:system-ui, -apple-system, sans-serif;">' +
        '    <button id="counter-history-close" style="position:absolute;top:18px;right:18px;background:none;border:none;font-size:24px;cursor:pointer;color:#94a3b8;transition:color 0.2s;line-height:1;">&times;</button>' +
        '    <h3 style="margin-top:0;margin-bottom:20px;font-size:1.15rem;font-weight:700;color:#1e293b;border-bottom:1px solid #f1f5f9;padding-bottom:12px;display:flex;align-items:center;gap:8px;">' +
        '      <svg style="width:20px;height:20px;color:#3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"></path></svg>' +
        '      <span>アクセス統計: <span style="color:#3b82f6;">' + page + '</span></span>' +
        '    </h3>' +
        '    <div style="max-height:280px;overflow-y:auto;padding-right:4px;">' +
        '      <table style="width:100%;border-collapse:collapse;font-size:0.95rem;">' +
        '        <thead>' +
        '          <tr style="border-bottom:2px solid #e2e8f0;text-align:left;color:#64748b;font-weight:600;">' +
        '            <th style="padding:10px 8px;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.05em;">年月</th>' +
        '            <th style="padding:10px 8px;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.05em;text-align:right;">アクセス数</th>' +
        '          </tr>' +
        '        </thead>' +
        '        <tbody style="color:#334155;">';

      if (res.history.length === 0) {
        modalHtml += '<tr><td colspan="2" style="padding:24px;text-align:center;color:#94a3b8;font-size:0.9rem;">アクセスログはありません。</td></tr>';
      } else {
        $.each(res.history, function(idx, item) {
          modalHtml += 
            '<tr class="counter-row" style="border-bottom:1px solid #f1f5f9;">' +
            '  <td style="padding:10px 8px;color:#475569;">' + item.month + '</td>' +
            '  <td style="padding:10px 8px;text-align:right;font-weight:600;color:#0f172a;">' + item.count.toLocaleString() + '</td>' +
            '</tr>';
        });
      }

      modalHtml += 
        '        </tbody>' +
        '      </table>' +
        '    </div>' +
        '  </div>' +
        '</div>';

      var \$modal = \$(modalHtml).appendTo("body");

      // 表示アニメーションを起動
      setTimeout(function() {
        \$modal.css("opacity", "1");
        \$modal.find("div").css("transform", "scale(1)");
      }, 50);

      // 閉じるイベント
      \$modal.find("#counter-history-close, #counter-history-modal").on("click", function(e) {
        if (e.target === this || e.currentTarget.id === "counter-history-close") {
          \$modal.css("opacity", "0");
          \$modal.find("div").css("transform", "scale(0.95)");
          setTimeout(function() {
            \$modal.remove();
          }, 250);
        }
      });
    });
  }
});
</script>
<style type="text/css">
.counter_clickable {
  cursor: pointer;
  text-decoration: underline dotted;
  transition: color 0.2s;
}
.counter_clickable:hover {
  color: #3b82f6;
}
#counter-history-close:hover {
  color: #1e293b !important;
}
.counter-row:hover {
  background-color: #f8fafc;
}
</style>
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

// 月ごとの統計履歴を取得
function plugin_counter_getHistory()
{
    header('Content-Type: application/json; charset=utf-8');
    if (!konawiki_isLogin()) {
        echo json_encode([
            "status" => "error",
            "message" => konawiki_lang("Login required.")
        ]);
        exit;
    }
    $log_id = konawiki_getPageId();
    if (!$log_id) {
        echo json_encode([
            "status" => "error",
            "message" => "Page not found."
        ]);
        exit;
    }

    $history = plugin_counter_getHistoryData($log_id);

    echo json_encode([
        "status" => "ok",
        "page" => konawiki_getPage(),
        "history" => $history
    ]);
}

// 月ごとの統計履歴のデータを取得するヘルパー
function plugin_counter_getHistoryData($log_id)
{
    // DBから日ごとのアクセスログを取得
    $rows = db_get("SELECT stime, value FROM mcounter_day WHERE log_id=? ORDER BY stime DESC", [$log_id], 'sub');
    $monthly = [];
    foreach ($rows as $row) {
        $month = date("Y-m", $row['stime']);
        if (!isset($monthly[$month])) {
            $monthly[$month] = 0;
        }
        $monthly[$month] += $row['value'];
    }

    $history = [];
    foreach ($monthly as $month => $count) {
        $history[] = [
            "month" => $month,
            "count" => $count
        ];
    }
    return $history;
}

