<?php
/**
 * konawki parser (UTF-8)
 * 日本語のマークアップも認識します
 */
include_once("lib_kona.inc.php");

/**
 * convert text to html
 */
global $konawiki_parser_depth;
if (empty($konawiki_parser_depth)) {
    $konawiki_parser_depth = 0;
}

function konawiki_parser_convert($text, $flag_isContents = TRUE)
{
    global $konawiki_parser_depth;
    // parse & render
    $konawiki_parser_depth++;
    $tokens = konawiki_parser_parse($text);
    $html   = konawiki_parser_render($tokens, $flag_isContents);
    $konawiki_parser_depth--;
    return $html;
}
/** get raw text */
function konawiki_getRawText()
{
    return konawiki_public('raw_text');
}
/** get raw tokens */
function konawiki_getRawTokens()
{
    return konawiki_public('raw_tokens');
}

/**
 * 構文を解析して配列に入れる
 */
function konawiki_parser_parse($text)
{
    // convert CRLF to LF
    $text = preg_replace('#(\r\n|\r)#',"\n", $text);
    konawiki_addPublic('EOL', "\n");
    konawiki_addPublic('raw_text', $text);
    $eol = konawiki_public("EOL");
    // get mark config
    $ul_mark1 = konawiki_private('ul_mark1'); // "・"
    $ul_mark2 = konawiki_private('ul_mark2'); // "≫" (ver102以前 は全角スペースだったが仕様変更)
    
    $h1_mark1 = konawiki_private('h1_mark1'); // "■"
    $h1_mark2 = konawiki_private('h1_mark2'); //
    $h2_mark1 = konawiki_private('h2_mark1'); // "●"
    $h2_mark2 = konawiki_private('h2_mark2'); //
    $h3_mark1 = konawiki_private('h3_mark1'); // "▲"
    $h3_mark2 = konawiki_private('h3_mark2'); //
    $h4_mark1 = konawiki_private('h4_mark1'); // "▼" (TEST)
    $h4_mark2 = konawiki_private('h4_mark2'); //
    
    // main loop
    $tokens = array();
    while ( $text != "") {
        $c = mb_substr($text, 0, 1);
        // TITLE
        if ($c == "*") {
            $level = konawiki_parser_count_level($text, $c);
            konawiki_parser_skipSpace($text);
            $tokens[] = array("cmd"=>"*", "text"=>konawiki_parser_token($text, $eol), "level"=>$level);
            konawiki_parser_skipEOL($text);
        }
        else if ($c == $h1_mark1 || $c == $h1_mark2) { // title1
            konawiki_parser_getchar($text); // skip flag
            konawiki_parser_skipSpace($text);
            $str = konawiki_parser_token($text, $eol);
            $tokens[] = array("cmd"=>"*", "text"=>$str, "level"=>1);
            konawiki_parser_skipEOL($text);
        }
        else if ($c == $h2_mark1 || $c == $h2_mark2) { // title2
            konawiki_parser_getchar($text); // skip flag
            konawiki_parser_skipSpace($text);
            $str = konawiki_parser_token($text, $eol);
            $tokens[] = array("cmd"=>"*", "text"=>$str, "level"=>2);
            konawiki_parser_skipEOL($text);
        }
        else if ($c == $h3_mark1 || $c == $h3_mark2) { // title4
            konawiki_parser_getchar($text); // skip flag
            konawiki_parser_skipSpace($text);
            $str = konawiki_parser_token($text, $eol);
            $tokens[] = array("cmd"=>"*", "text"=>$str, "level"=>3);
            konawiki_parser_skipEOL($text);
        }
        else if ($c == $h4_mark1 || $c == $h4_mark2) { // title4
            konawiki_parser_getchar($text); // skip flag
            konawiki_parser_skipSpace($text);
            $str = konawiki_parser_token($text, $eol);
            $tokens[] = array("cmd"=>"*", "text"=>$str, "level"=>4);
            konawiki_parser_skipEOL($text);
        }
        // LIST <ul>
        else if ($c == '-' || $c == $ul_mark1 || $c == $ul_mark2) {
            $level = konawiki_parser_count_level2($text, array("-","・","　"));
            if ($level >= 4) {
                konawiki_parser_skipEOL($text);
                $tokens[] = array("cmd"=>"hr", "text"=>"", "level"=>$level);
            } else {
                konawiki_parser_skipSpace($text);
                $tokens[] = array("cmd"=>"-", "text"=>konawiki_parser_token($text, $eol), "level"=>$level);
            }
        }
        // LIST <ol>
        else if ($c == "+" || $c == "＋") {
            $level = konawiki_parser_count_level($text, $c);
            konawiki_parser_skipSpace($text);
            $tokens[] = array("cmd"=>"+", "text"=>konawiki_parser_token($text, $eol), "level"=>$level);
        }
        // TABLE
        else if ($c == "|") {
            konawiki_parser_getchar($text);
            $line = konawiki_parser_token($text, $eol);
            $tokens[] = array("cmd"=>"|", "text"=>$line);
        }
        // SOURCE LINE
        else if ($c == " " || $c == "\t") { // src (source) line
            konawiki_parser_getchar($text);
            $tokens[] = array("cmd"=>"src", "text"=>konawiki_parser_token($text, $eol));
        }
        // resmark or conflict mark
        else if ($c == ">" || $c == "＞") {
            if (substr($text, 0, 3) == ">>>") {
                $text = substr($text, 3);
                $flag = substr($text, 0, 3);
                $text = substr($text, 3);
                $tokens[] = array("cmd"=>"conflict", "text"=>konawiki_parser_token($text, $eol), "flag"=>$flag);
            } else {
                konawiki_parser_skipSpace($text);
                konawiki_parser_getchar($text); // skip ">"
                $line = konawiki_parser_token($text, $eol);
                $tokens[] = array("cmd"=>"resmark", "text"=>$line, "flag"=>">");
            }
        }
        // skip CR LF
        else if ($c == "\r" || $c == "\n") {
            konawiki_parser_skipEOL($text);
        }
        // PLUG-INS
        else if ($c == "#" || $c == "♪") { // plugins
            konawiki_parser_getStr($text, strlen($c)); // skip '#'
            $tokens[] = konawiki_parser_plugins($text, $c);
        }
        // SOURCE BLOCK
        else if ($c == "{" && substr($text, 0, 3) == "{{{") {
            konawiki_parser_getStr($text, 3); // skip {{{
            $tokens[] = konawiki_parser_sourceBlock($text);
        }
        else { // plain block
            $plain = "";
            while ($text != "") {
                $line = konawiki_parser_token($text, $eol);
                $eos  = substr($line, strlen($line) - 1, 1);
                $plain .= $line;
                if ($eos == "~") { $plain .= $eol; }
                // eol ?
                if (substr($text, 0, strlen($eol)) === $eol) break;
                // command ?
                $c = substr($text, 0, 1);
                if (strpos("*-+# \t\{",$c) === FALSE) {
                    continue;
                } else {
                    break;
                }
            }
            $tokens[] = array("cmd"=>"plain","text"=>$plain);
            konawiki_parser_skipEOL($text);
        }
    }
    return $tokens;
}

/**
 * 解析済の配列データを HTML に変換する
 */
function konawiki_parser_render($tokens, $flag_isContents = TRUE)
{
    konawiki_addPublic('raw_tokens', $tokens);
    $eol = konawiki_public("EOL");
    $html = "";
    if ($flag_isContents) {
        $html = '<div class="contents">'."\n";
    }
	
    $index = 0;
    while($index < count($tokens)) {
        $value = $tokens[$index++];
        $cmd  = $value["cmd"];
        $text = $value["text"];
        if ($cmd == "*") { // title header
            $html .= konawiki_parser_render_hx($value);
        }
        else if ($cmd == "-" || $cmd == "+") {
            $html .= konawiki_parser_render_li($tokens, $index, $cmd);
        }
        else if ($cmd == "|") {
            $html .= "<table class='grid'>".$eol;
            $index--; // back to this line
            while ($index < count($tokens)) {
                $value = $tokens[$index];
                $cmd  = $value["cmd"];
                $text = rtrim($value["text"]);
                if ($cmd != "|") break;
                if (substr($text, strlen($text) - 1, 1) == "|") {
                    $text = substr($text, 0, strlen($text) - 1);
                }
                $html .= "<tr>";
                $cells = explode("|", $text);
                foreach ($cells as $i => $cell) {
                    $html .= "<td>".konawiki_parser_tohtml($cell). "</td>";
                }
                $html .= "</tr>".$eol;
                $index++;
            }
            $html .= "</table>".$eol;
        }
        else if ($cmd == "src") {
            $html .= konawiki_private("source_tag_begin") . konawiki_parser_tosource($text). $eol;
            while ($index < count($tokens)) {
                $value = $tokens[$index];
                if ($value["cmd"] == "src") {
                    $html .= konawiki_parser_tosource($value["text"]) . $eol;
                    $index++;
                    continue;
                } else {
                  break;
                }
            }
            $html .= konawiki_private("source_tag_end").$eol;
        }
        else if ($cmd == "block") {
            $html .= konawiki_parser_tosource_block($text);
        }
        else if ($cmd == "hr") {
            $html .= konawiki_private("source_tag_hr").$eol;
        }
        else if ($cmd == "plugin") {
            $html .= konawiki_parser_render_plugin($value);
        }
        else if ($cmd == "conflict") {
            $text = htmlspecialchars($text);
            if (trim($text) == "") { $text = "&nbsp;"; }
            if ($value["flag"] == "[+]") {
                $img = konawiki_resourceurl()."/img/add.png";
                $html .= "<div class='conflictadd'><img src='$img'>$text</div>".$eol;
            }
            else { //if ($value["flag"] == "[-]") {
                $img = konawiki_resourceurl()."/img/sub.png";
                $html .= "<div class='conflictsub'><img src='$img'>$text</div>".$eol;
            }
            
        }
        else if ($cmd == "resmark") {
            $s = konawiki_parser_tohtml($text)."<br/>\n";
            while ($index < count($tokens)) {
                $value = $tokens[$index];
                if ($value["cmd"] == "resmark") {
                    $s .= konawiki_parser_tohtml($value["text"]) . "<br/>" . $eol;
                    $index++;
                    continue;
                } else {
                  break;
                }
            }
            $html .= "<div class='resmark'>".$s."</div>{$eol}";
        }
        else {
            $html .= "<p>".konawiki_parser_tohtml($text)."</p>{$eol}";
        }
    }
    if ($flag_isContents) {
        $html .= "</div>\n";
    }
    return $html;
}

function konawiki_parser_render_hx(&$value)
{
    global $konawiki_headers, $eol;
    if (empty($konawiki_headers)) $konawiki_headers = array();
    
    $level_from = konawiki_private('header_level_from');
    $level = $value["level"];
    $i = $level + ($level_from - 1);
    $text  = $value["text"];
    // calc title hash
    $konawiki_headers[$level] = $text;
    $all_text = "/";
    for ($j = 1; $j <= $level; $j++) {
        $all_text .= $konawiki_headers[$level]."/";
    }
    $hash   = sprintf("%x",crc32($all_text));
    $uri = htmlspecialchars(konawiki_getPageURL())."#h{$hash}";
    $anchor = "<a id='h{$hash}' name='h{$hash}' href='$uri' class='anchor_super'>&nbsp;*</a>";
    $noanchor = konawiki_param("noanchor", FALSE) || konawiki_public('noanchor', FALSE);
    if ($noanchor) $anchor = "";
    return "<h$i>".konawiki_parser_tohtml($text)."{$anchor}</h$i>{$eol}";
}

function konawiki_parser_render_li(&$tokens, &$index, &$cmd)
{
    $html = "";
    if ($cmd == "-") {
        $li_begin = "<ul>";
        $li_end   = "</ul>";
    }
    else {
        $li_begin = "<ol>";
        $li_end   = "</ol>";
    }
    $level = 0;
    $index--; // back to this command
    $num = count($tokens);
    while ($index < $num) {
        $value = $tokens[$index];
        if ($value["cmd"] != $cmd) break;
        $_html = "";
        $sa = $value["level"] - $level;
        if ($sa > 0) {
            for ($i = 0; $i < $sa; $i++) {
                $html .= "\n{$li_begin}\n<li>";
            }
        } else if ($sa < 0){
            $sa = $sa * -1;
            for ($i = 0; $i < $sa; $i++) {
                $html .= "</li>\n{$li_end}\n";
            }
            if ($value["level"] != 0) {
                $_html = "<li>";
                $html .= "</li>\n";
            }
        } else {
            $_html = "<li>";
            $html .= "</li>\n";
        }
        $text = konawiki_parser_tohtml($value["text"]);
        $html .= $_html . $text;
        $level = $value["level"];
        $index++;
    }
    for ($i = 0; $i < $level; $i++) {
        $html .= "</li>\n{$li_end}";
    }
    return $html;
}


function konawiki_parser_skipEOL(&$text)
{
    for (;;) {
        $c = substr($text, 0, 1);
        if ($c == "\r" || $c == "\n") {
            $text = substr($text, 1);
        } else {
            break;
        }
    }
}

function konawiki_parser_skipSpace(&$text)
{
    for (;;) {
        $c = substr($text, 0, 1);
        if ($c == " " || $c == "\t") {
            $text = substr($text, 1);
        } else {
            break;
        }
    }
}

function konawiki_parser_getchar(&$text)
{
    $c = mb_substr($text, 0, 1);
    $text = mb_substr($text, 1);
    return $c;
}

function konawiki_parser_getStr(&$text, $len)
{
    $result = substr($text, 0, $len);
    $text = substr($text, $len);
    return $result;
}

function konawiki_parser_ungetchar(&$text, $ch)
{
    $text = $ch . $text;
}

function konawiki_parser_count_level(&$text, $ch)
{
    $level = 0;
    for(;;){
        $c = konawiki_parser_getchar($text);
        if ($c == $ch) {
            $level++;
            continue;
        }
        konawiki_parser_ungetchar($text, $c);
        break;
    }
    return $level;
}

function konawiki_parser_count_level2(&$text, $ch_array)
{
    $level = 0;
    for(;;){
        $c = konawiki_parser_getchar($text);
        $r = array_search($c, $ch_array);
        if ($r !== FALSE) {
            $level++;
            continue;
        }
        konawiki_parser_ungetchar($text, $c);
        break;
    }
    return $level;
}

function konawiki_parser_token(&$text, $sub)
{
    $i = strpos($text, $sub);
    if ($i === FALSE) {
        $res  = $text;
        $text = "";
    } else {
        $res  = substr($text, 0, $i);
        $text = substr($text, ($i + strlen($sub)));
    }
    return $res;
}

if (!function_exists("htmlspecialchars_decode")) {
    function htmlspecialchars_decode($s) {
        $s = str_replace('&gt;', '>', $s);
        $s = str_replace('&lt;', '<', $s);
        $s = str_replace('&quot;', '"', $s);
        $s = str_replace('&#039;', "'", $s);
        $s = str_replace('&amp;', '&', $s);
        return $s;
    }
}

/**
 * inline
 */
function __konawiki_parser_tohtml(&$text, $level)
{
    // make link
    $result = "";
    while ($text <> "") {
        // wikiname
        $c1 = mb_substr($text, 0, 1);
        $c2 = mb_substr($text, 0, 2);
        // Wiki Link
        if ($c2 == "[[") {
            // description mode ?
            if (substr($text, 0, 3) === "[[[") { // with desctiption
                $text = substr($text, 3);
                $page = konawiki_parser_token($text, "]]]");
                $result .= konawiki_parser_showPageDescription($page);
            }
            else { // simple name
                $text = substr($text, 2);
                $s = konawiki_parser_token($text, "]]");
                $result .= konawiki_parser_makeWikiLink($s);
            }
            continue;
        }
        // end of inline plugin
        if ($c2 == ");" && $level > 0) {
            $text = substr($text, 2);
            return $result;
        }
        // inline plugin
        if (($c1 == '&') &&
            preg_match('#^\&([\d\w_]+?)\(#',$text, $m)) {
            $pname  = trim($m[1]);
            $plugin = konawiki_parser_getPlugin($pname);
            $text   = substr($text, strlen($m[0]));
            if (!file_exists($plugin["file"])) {
                $result .= htmlspecialchars("&".$pname."(");
            } else {
                $pparam = __konawiki_parser_tohtml($text, $level + 1);
                $param_ary = explode(",", $pparam);
                include_once($plugin["file"]);
                $p = array("cmd"=>"plugin", "text"=>$pname, "params"=>$param_ary);
                $s = konawiki_parser_render_plugin($p);
                $result .= $s;
            }
            continue;
        }
        // strong
        if ($c2 == "''") {
            $text = substr($text, 2);
            $s = konawiki_parser_token($text, "''");
            $str = konawiki_parser_tohtml($s);
            $result .= "<strong>$str</strong>";
            continue;
        }
        // url
        if (preg_match('@^(http|https|ftp)\://[\w\d\.\#\$\%\&\(\)\-\=\_\~\^\|\,\.\/\?\+\!\[\]\@]+@', $text, $m) > 0) {
            $result .= konawiki_parser_makeUriLink($m[0]);
            $text = substr($text, strlen($m[0]));
            continue;
        }
        // ~
        if ($c2 == "~\n" || $c2 == "~\r") {
            $result .= "<br/>";
            $text = substr($text, 1);
            continue;
        }
        // escape ?
        $c = $c1;
        switch ($c) {
            case '>': $c = '&gt;'; break;
            case '<': $c = '&lt;'; break;
            case '&': $c = '&amp;'; break;
            case '"': $c = '&quot;'; break;
        }
        $result .= $c;
        $text = mb_substr($text, 1);
    }
    return $result;
}


function konawiki_parser_tohtml($text)
{
    $r = "";
    while ($text != "") {
        $r .= __konawiki_parser_tohtml($text, 0);
    }
    return $r;
}

function konawiki_parser_tosource($src)
{
    $src = htmlspecialchars($src, ENT_QUOTES);
    return $src;
}

function konawiki_parser_tosource_block($src)
{
    global $eol;
    // plugin ?
    if (substr($src,0,1) == "#") {
        $src     = substr($src, 1);
        $line    = konawiki_parser_token($src, "\n");
        $pname   = trim(konawiki_parser_token($line, "("));
        $arg_str = konawiki_parser_token($line, ")");
        if ($arg_str != "") {
            $args = explode(",", $arg_str);
        } else {
            $args = array();
        }
        array_push($args, $src);
        // call plugin function
        $path = KONAWIKI_DIR_PLUGINS."/".$pname.".inc.php";
        $func = "plugin_{$pname}_convert";
        if (file_exists($path)) {
            include_once($path);
            if (is_callable($func)) {
                $res = @call_user_func($func, $args);
                return $res;
            }
        }
    }
    // no plugin
    $src = htmlspecialchars($src, ENT_QUOTES);
    $begin = konawiki_private("source_tag_begin");
    $end   = konawiki_private("source_tag_end") . $eol;
    return $begin.$src.$end;
}

function konawiki_parser_makeUriLink($url)
{
    $disp = mb_strimwidth($url, 0, 60, "..");
    // $disp = htmlspecialchars($url);
    $link = htmlspecialchars($url);
    return "<a href='$link'>$disp</a>";
}

function konawiki_parser_showPageDescription($page)
{
    $page_htm = htmlspecialchars($page);
    # get page info
    $db = konawiki_getDB();
    $page_ = $db->escape($page);
    $sql = "SELECT * FROM logs WHERE name='{$page_}'";
    $res = $db->array_query($sql);
    if (!isset($res[0]['id'])) {
        // page not found
        $url = konawiki_getPageURL($page,"edit");
        $caption = "<a href='$url'>{$page_htm}</a>";
        return "<span class='none'>$caption</span>";
    }
    $log  = $res[0];
    $url  = konawiki_getPageURL($page);
    # make tag info
    $tags = konawiki_getTag($log["id"]);
    $tag_str = "";
    foreach ($tags as $tag) {
        $tag_url = konawiki_getPageURL($tag,"search","tag");
        $tag_htm = htmlspecialchars($tag);
        $tag_str .= "[<a href='{$tag_url}'>{$tag_htm}</a>]";
    }
    # make body
    $body_a = explode("\n", $log["body"]."\n\n", 2);
    $body = $body_a[0];
    $body = mb_strimwidth($body, 0, 80, "..");
    $body_htm = htmlspecialchars($body);
    # link
    $caption = "<a href='{$url}'>$page_htm</a>";
    $desc_visible = konawiki_public("wikilink.desc.visible");
    if ($desc_visible) {
        $s = "{$caption}<span class='memo'>…{$tag_str}{$body_htm}</span>";
    } else {
        if ($tag_str !== "") { $tag_str = "…".$tag_str; }
        $s = "{$caption}<span class='memo'>{$tag_str}</span>";
    }
    return $s;
}

function konawiki_parser_makeWikiLink($name)
{
    // check pattern
    // -[[wikiname]]
    // -[[name:url]]
    // -[[caption:wikiname]]
    // -[[caption:wikiname]]
    $caption  = ""; // display name
    $link     = ""; // link
    $wikiname = ""; // wikiname
    $wikilink = TRUE;
    
    // [[xxx:xxxx]]
    if (strpos($name, ":") === FALSE) { // simple ?
        // [[wikiname]]
        $caption = $wikiname = $name;
        $link = konawiki_getPageURL2($name);
    }
    else {
        // [[xxx:xxx]]
        preg_match('|^(.*?)\:(.*)$|', $name, $e);
        $caption = $e[1];
        $link    = $e[2];
        // protocol ?
        if ($caption == 'http' || $caption == 'https' || $caption == 'ftp') {
            $link = $caption = $name;
        }
        // check all url
        if (strpos($link, '://') !== FALSE) {
            // url
            $caption = konawiki_parser_disp_url($caption);
            $link = $link;
            $wikilink = FALSE;
        }
        else {
            // wiki link
            // [[caption:WikiPage]]
            $wikiname = $link;
            $link = konawiki_getPageURL2($link);
            $wikilink = TRUE;
        }
    }
    
    // wikipage exists ?
    if ($wikilink === TRUE) {
        // exists ?
        $id = konawiki_getPageId($wikiname);
        $caption = htmlspecialchars($caption, ENT_QUOTES);
        if ($id === FALSE) {
            $caption = "<span class='none'>$caption</span>";
        } else {
            // show todo tag
            $tags = konawiki_getTag($id);
            if ((array_search('todo', $tags) !== FALSE) || (array_search('TODO', $tags) !== FALSE)) {
                $caption = "<span class='todo'>$caption</span>";
            }
        }
    }
    return "<a href='$link'>$caption</a>";
}

function konawiki_parser_disp_url($url)
{
    $omit = konawiki_info("omit_longurl", TRUE);
    if ($omit) {
        $len = konawiki_info("omit_longurl_len", 80);
        $url = mb_strimwidth($url, 0, $len, "..");
        return $url;
    } else {
        return $url;
    }
}

function konawiki_parser_plugins(&$text, $flag)
{
    mb_regex_encoding("UTF-8");
    $word = "";
    if (mb_ereg('^[\w\d\_\-]+', $text, $m) == 0) {
        return array("cmd"=>"", "text"=>"#");
    }
    //
    $word = $m[0];
    $res  = array("cmd"=>"plugin", "text"=>$word, "params"=>array());
    $text = substr($text, strlen($word)); // skip $word
    konawiki_parser_skipSpace($text);
    $c = mb_substr($text, 0, 1);
    if ($c == "(") { // has params
        konawiki_parser_getStr($text, 1); // skip '('
        if ($flag === "&") {
            if (strpos($text, ");") >= 0) {
                $param_str = konawiki_parser_token($text, ');');
                $res["params"] = explode("\,", $param_str);
            }
        }
        else { // $flag == "#"
            if (strpos($text, ")") >= 0) {
                $param_str = konawiki_parser_token($text, ')');
                $res["params"] = explode(",", $param_str);
                if (substr($text,0,1) == ";") { // (xx); の形式なら";"を削る
                    $text = substr($text, 1);
                }
            }
        }
    }
    else if ($c == "{" && substr($text,0,3) === "{{{") {
        //todo
    }
    else if ($c == "～") {
    	$eol = konawiki_public("EOL");
    	$line = konawiki_parser_token($text, $eol);
    	$line = mb_substr($line, 1);
    	$res["params"] = explode("～", $line);
    }
    
    // check plugins
    $plugin = konawiki_parser_getPlugin($word);
    $f = $plugin["file"];
    if (file_exists($f)) {
        include_once($f);
        if (is_callable($plugin["init"])) {
            call_user_func($plugin["init"]);
        }
    } else {
        $res["cmd"] = "";
        $eword = urlencode($word);
        if ($word != $eword) {
        	$word .= "($eword)";
        }
        $res["text"] = "[No Plugin:{$word}]";
    }
    return $res;
}

function konawiki_parser_render_plugin($value)
{
    global $konawiki_show_as_dynamic_page;
    global $plugin_params;

    $text   = "";
    $pname  = $value["text"];
    $params = $value["params"];
    // check security
    $disable = konawiki_private("plugins.disable");
    if (isset($disable[$pname]) && $disable[$pname] == true) {
        return "<!-- disable #{$pname} -->"; // disable
    }
    $plugin = konawiki_parser_getPlugin($pname);
    // check dynamic
    $plugin_params["flag_dynamic"] = true;
    // count plugins id
    $pid = konawiki_getPluginInfo($pname,"pid", 0);
    konawiki_setPluginInfo($pname, "pid", ($pid + 1));
    // action
    if (konawiki_param("plugin") == $pname) {
        $action = $plugin["action"];
        if (is_callable($action)) {
            if (!call_user_func($action, $params)) {
                $err = isset($plugin_params["error"]) ? $plugin_params["error"] : "";
                if ($err !== "") $err = "($err)";
                $msg = konawiki_lang('Failed to execute plugin [%s].');
                $text .= "<p class='error'>";
                $text .= sprintf($msg, '#'.$pname);
                $text .= $err . "</p>";
            }
        }
    }
    // convert
    $convert = $plugin["convert"];
    if (is_callable($convert)) {
        $text .= call_user_func($convert, $params);
    }
    // check dynamic
    if ($plugin_params["flag_dynamic"]) {
    	$konawiki_show_as_dynamic_page = true;
    }
    
    return $text;
}

/**
 * ソースコードのブロックを抽出する
 * @param $text
 * @return array
 */
function konawiki_parser_sourceBlock(&$text)
{
    $nest = 1;
    $src = "";
    $eol = konawiki_public("EOL");
    while ($text != "") {
        $c = mb_substr($text, 0, 1);
        // {{{
        if ($c == '{' && substr($text, 0, 3) === '{{{') {
                $src .= '{{{';
                $text = substr($text, 3);
                $nest++;
                continue;
        }
        // }}}
        if ($c == '}' && substr($text, 0, 3) === '}}}') {
                $text = substr($text, 3);
                $nest--;
                if ($nest <= 0) { break; }
                continue;
        }
        // other
        $line = konawiki_parser_token($text, $eol);
        $src .= $line.$eol;
    }
    return array("cmd"=>"block", "text"=>$src);
}



