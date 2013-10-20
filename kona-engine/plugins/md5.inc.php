<?php
/** konawiki plugins -- 文字列の md5 を取得する(このままだと便利ではないので暫定版)
 * - [書式] #md5(文字列)
 * - [引数]
 * -- 文字列
 * - [使用例] #md5(あいうえお)
 * - [備考] なし
 */
 
function plugin_md5_convert($params)
{
    $s = array_shift($params);
    return md5($s);
}

?>
