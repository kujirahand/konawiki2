<?php
/** konawiki plugins -- CSV整形プラグイン
 * - [書式] {{{#tsv([noheader]) データ }}}
 * - [引数]
 * -- noheader .. 一行目をヘッダとしない
 * -- データ .. タブ区切りのTSVデータを指定する
 * - [使用例]
{{{
_{{{#tsv
商品名	金額
石鹸	400
シャンプー	600
_}}}
}}}

{{{#tsv
商品名	金額
石鹸	400
シャンプー	600
}}}
 * - [備考] ソースコードブロックのプラグインとして利用する
 * - [公開設定] 公開
 */

function plugin_tsv_convert($params)
{
    if (!$params) return "";
    
    $noheader = FALSE;
    $csv = "";
    foreach ($params as $s) {
        if ($s == "noheader") {
            $noheader = TRUE;
            continue;
        }
        $csv = $s;
        break;
    }
    
    $html = "<table>\n";
    $lines = explode("\n", trim($csv));
    // header
    if ($noheader == FALSE) {
        $line = array_shift($lines);
        $cols = explode("\t", $line);
        $html .= "<tr>";
        foreach ($cols as $col) {
            $col = htmlspecialchars($col, ENT_QUOTES);
            $html .= "<th>{$col}</th>";
        }
        $html .= "</tr>\n";
    }
    // csv body
    foreach ($lines as $line) {
        $cols = explode("\t", $line);
        $html .= "<tr>";
        foreach ($cols as $col) {
            $col = htmlspecialchars(trim($col), ENT_QUOTES);
            $html .= "<td>$col</td>";
        }
        $html .= "</tr>\n";
    }
    $html .= "</table>\n";
    return $html;
}
