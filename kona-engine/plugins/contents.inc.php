<?php
/** konawiki plugins -- 見出し一覧を表示
 * - [書式] #contents
 * - [引数]
 * - [使用例] #contents
 */

function plugin_contents_convert($params)
{
    $html = "<div class='contentsTitle'><ul>";
    $tokens = konawiki_getRawTokens();
    // extract title tokens
    $result = array();
    foreach ($tokens as $value) {
        $cmd = $value["cmd"];
        if ($cmd == "*") {
            $result[] = array("text"=>$value["text"], "level"=>$value["level"]);
        }
    }
    $tokens = $result;
    // create contents
    $h_level = 1;
    $konawiki_headers = array();
    $flag_close = true;
    for($index = 0; $index < count($tokens); $index++) {
        $value = $tokens[$index];
        $text = $value["text"];
        // title header
        $level = $value["level"];
        // calc title hash
        $konawiki_headers[$level] = $text;
        $all_text = "/";
        for ($j = 1; $j <= $level; $j++) {
            $all_text .= $konawiki_headers[$level]."/";
        }
        $hash   = sprintf("%x",crc32($all_text));
        $uri = htmlspecialchars(konawiki_getPageURL())."#h{$hash}";
        // open
        while ($h_level < $level) {
            $html .= "<ul>";
            $h_level++;
        }
        // close
        while ($h_level > $level) {
            $html .= "</ul>";
            $h_level--;
        }
        $a_begin = "<a href='$uri'>";
        $a_end   = "</a>";
        $title = $a_begin.konawiki_parser_tohtml($text).$a_end;
        //
        $html .= "<li>".$title."</li>\n";
    }
    while ($h_level >= 0) {
        $html .= "</ul>";
        $h_level--;
    }
    $html .= "</ul></div>";
    return $html;
}


?>
