<?php
/** konawiki plugins -- CSV整形プラグイン
 * - [書式] {{{#csv([noheader]) データ }}}
 * - [引数]
 * -- noheader .. 一行目をヘッダとしない
 * -- データ .. カンマ区切りのCSVデータを指定する
 * - [使用例]
{{{
_{{{#csv
商品名,金額
石鹸,400
シャンプー,600
_}}}
}}}

{{{#csv
商品名,金額
石鹸,400
シャンプー,600
}}}
 * - [備考] ソースコードブロックのプラグインとして利用する
 * - [公開設定] 公開
 */

function plugin_csv_convert($params)
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
        $cols = explode(",", $line);
        $html .= "<tr>";
        foreach ($cols as $col) {
            $col = trim($col);
            $col = konawiki_parser_convert($col, FALSE);
            $html .= "<th>{$col}</th>";
        }
        $html .= "</tr>\n";
    }
    // csv body
    foreach ($lines as $line) {
        $cols = explode(",", $line);
        $html .= "<tr>";
        foreach ($cols as $col) {
            $col = trim($col);
            $col = konawiki_parser_convert($col, FALSE);
            $html .= "<td>$col</td>";
        }
        $html .= "</tr>\n";
    }
    $html .= "</table>\n";
    return $html;
}
