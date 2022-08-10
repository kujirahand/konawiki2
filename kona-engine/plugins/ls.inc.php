<?php
/** konawiki plugins -- WIKIページを列挙するプラグイン
 * - [書式] #ls(pattern,[onlyname|onlylist|perpage=n|withtag|sort=(date|name)|[(asc|desc)]])
 * - [引数]
 * -- pattern    ページ名の一部を指定する、ワイルドカードを指定可能。
 * -- onlyname   ページの内容紹介を表示しない
 * -- onlylist   階層表示しない
 * -- perpage=n  nに１ページに表示する最大件数を指定する
 * -- sort=(date|name) 名前順、日付順どちらで表示するか
 * -- (asc|desc) 昇順降順     
 * -- withtag    表示ページ名にタグをつけて表示する
 * - [使用例] #ls(KonaWiki*) .. KonaWiki から始まるページを列挙する
 * - [使用例] #ls(KonaWiki*,withtag,perpage=100) .. KonaWikiから始まるページをタグをつけて1ページに100件分列挙する
 * - [備考]なし
 */

function plugin_ls_convert($params)
{
	konawiki_setPluginDynamic(true);
	
    // get arguments
    $page = konawiki_getPage();
    $pattern = "";
    $onlyname = FALSE;
    $onlylist = FALSE;
    $withtag  = FALSE;
    $PER_PAGE = 20;
    $PER_PAGE_MAX = 100;
    $sort_field = "name";
    $sort_type = "ASC";
    $tag_str = "";
    foreach ($params as $n) {
        $v = 0;
        if (strpos($n, "=") !== FALSE) {
            $a = explode("=",$n);
            $n = array_shift($a);
            $v = array_shift($a);
        }
        switch ($n) {
            case "onlyname":
                $onlyname = TRUE;
                break;
            case "onlylist":
                $onlylist = TRUE;
                break;
            case "perpage":
                $PER_PAGE = intval($v);
                if ($PER_PAGE <= 0) $PER_PAGE = 20;
                break;
            case "withtag":
                $withtag = TRUE;
                break;
            case "sort":
            	if ($v == "name") $sort_field = "name";
            	if ($v == "date") $sort_field = "ctime";
            	break;
            case "asc":
            	$sort_type = "ASC";
            	break;
            case "desc":
            	$sort_type = "DESC";
            	break;
            default:
	            if ($pattern == "") {
                    $pattern = $n;
                } else {
                    // unknown option
                }
                break;
        }
    }
    // check default
    if ($pattern === "") {
        // これより下層のページを列挙する
        $pattern = "$page/%";
    } else {
        $pattern = str_replace("*", "%", $pattern);
    }
    if (strpos($pattern, '%') === FALSE) {
        $pattern .= '%'; // 前方一致
    }
    if ($PER_PAGE > $PER_PAGE_MAX) $PER_PAGE = $PER_PAGE_MAX; // check max
    // check pager
    $limit = $PER_PAGE + 1;
    $pagername = "pager".bin2hex($pattern);
    $p = intval(konawiki_param($pagername, 0));
    if ($p < 0) $p = 0;
    $offset = $p * $PER_PAGE;
    $res = "";
    // enum pattern
    $query = 
      "SELECT id,name,body FROM logs WHERE name LIKE ? AND private = 0".
      "  ORDER BY {$sort_field} {$sort_type}".
      "  LIMIT ? OFFSET ?";
    $r = db_get($query, [$pattern, $limit, $offset]);
    if (!$r) {
        return "なし";
    }
    // trim body data
    $count_r = 0;
    foreach ($r as $idx => $value) {
        $id   = $value["id"];
        $body = $value["body"];
        if (!$body) { continue; }
        $count_r++;
        // body に #plugin があれば削除
        $body = preg_replace('/\#[a-zA-Z0-9_]+(\(.+?\))?/', '', $body);
        $body = preg_replace('#\s{2,}#', ' ', $body);
        if ($body != "" && $onlyname == FALSE) {
            $body_ary = explode("\n", $body, 2);
            $body = $body_ary[0];
            $body = mb_strimwidth($body, 0, 80, '..');
            $body = htmlspecialchars($body);
        } else {
            $body = "";
        }
        if ($withtag) {
            $tags = konawiki_getTag($id);
            $tag_str = "";
            if ($tags) {
                foreach ($tags as $tag) {
                    $url = konawiki_getPageURL($tag, 'search', 'tag');
                    $tag_ = htmlspecialchars($tag);
                    $tag_str .= "[<a href='$url'>{$tag_}</a>]";
                }
            }
        }
        if ($onlyname) {
            $hbody = "";
        } else {
            $hbody = "<span class='memo'>…{$tag_str}{$body}</span>";
        }
        $r[$idx]["body"] = $hbody;
    }
    
    // pager
    $pager = "";
    if ($p >= 1) {
        $pp = $p - 1;
        $url = konawiki_getPageURL($page, '', '', "$pagername=$pp");
        $pager .= "<a href='$url'>←前へ</a>\n";
    }
    if (count($r) > $PER_PAGE) {
        array_pop($r);
        $pp = $p + 1;
        $url = konawiki_getPageURL($page, '', '', "$pagername=$pp");
        $pager .= "<a href='$url'>次へ→</a>\n";
    }
    if ($pager != "") {
        $pager = "<div class='pager'>\n{$pager}</div>\n";
    }
    
    // to html
    if ($onlylist) {
        $res .= "<ul>\n";
        foreach ($r as $line) {
            extract($line);
            $link = konawiki_getPageLink($name, 'dir');
            $res .= "<li>{$link}{$body}</li>\n";
        }
        $res .= "</ul>\n";
    } else {
        $res .= plugin_ls_convert__dir($r);
    }
    
    return $res."\n".$pager;
}


function plugin_ls_convert__dir__r(&$p, &$desc)
{
    $res = "";
    if (isset($p["path"])) {
        $path     = $p["path"];
        $body = isset($desc[$path]) ? $desc[$path] : "";
        $link = konawiki_getPageLink($path, "normal", basename($path));
        $res .= "<li>{$link}{$body}</li>\n";
    }
    if (isset($p["children"])) {
        $children = $p["children"];
        if (count($children) > 0) {
            $res .= "<ul>";
            foreach ($children as $elm) {
                $res .= plugin_ls_convert__dir__r($elm, $desc);
            }
            $res .= "</ul>";
        }
    }
    return $res;
}

function plugin_ls_convert__dir(&$r)
{
    $res = "";
    // 階層付ハッシュに置換
    $tree = array();
    $desc = array();
    foreach ($r as $line) {
        extract($line);
        $dirs = mb_split('/', $name);
        $desc[$name] = $body;
        $p = &$tree;
        $tmp = array();
        foreach ($dirs as $n) {
            $tmp[] = $n;
            if (!isset($p[$n])) {
                $p[$n] = array("path"=>join('/', $tmp), "children"=>array());
            }
            $p = &$p[$n]["children"];
        }
    }
    //echo '<pre>';
    //print_r($tree);
    // 再帰的にデータを辿る
    $res .= "<ul>\n";
    foreach ($tree as $key => $v) {
        $res .= plugin_ls_convert__dir__r($v, $desc);
    }
    $res .= "</ul>\n";
    return $res;
}


?>
