<?php
/** konawiki plugins -- タグのついたページを列挙するプラグイン
 * - [書式] #tag(pattern,[onlyname|onlylist|perpage=n|withtag])
 * - [引数]
 * -- pattern    ページ名の一部を指定する、ワイルドカードを指定可能。
 * -- onlyname   ページの内容紹介を表示しない
 * -- onlylist   階層表示しない
 * -- perpage=n  nに１ページに表示する最大件数を指定する
 * -- withtag    表示ページ名にタグをつけて表示する
 * - [使用例] #tag(KonaWiki) .. KonaWiki のついたタグを列挙する
 * - [使用例] #tag(KonaWiki*,withtag,perpage=100) .. KonaWikiから始まるページを他のタグもつけて1ページに100件分列挙する
 * - [備考]なし
 */

function plugin_tag_convert($params)
{
    konawiki_setPluginDynamic(true);
	  // get arguments
    $page = konawiki_getPage();
    $pattern  = FALSE;
    $onlyname = FALSE;
    $onlylist = FALSE;
    $withtag  = FALSE;
    $PER_PAGE = 20;
    $PER_PAGE_MAX = 100;
    foreach ($params as $n) {
        $n = trim($n);
        $v = 0;
        if (mb_strpos($n, "=") !== FALSE) {
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
            default:
                if ($pattern === FALSE) {
                    $pattern = $n;
                } else {
                    // unknown option
                }
                break;
        }
    }
    // check default
    if ($pattern === FALSE || $pattern == "") {
    	return "[USAGE] #tag(TAGNAME)";
    }
    if ($PER_PAGE > $PER_PAGE_MAX) $PER_PAGE = $PER_PAGE_MAX; // check max
    // check pager
    $limit = $PER_PAGE + 1;
    $pagername = "pager".bin2hex($pattern);
    $p = konawiki_param($pagername, 0);
    if ($p < 0) $p = 0;
    $offset = $p * $PER_PAGE;
    $res = "";
    // enum pattern
    $query = 
      "SELECT log_id FROM tags WHERE tag = ? ".
      "ORDER BY log_id DESC LIMIT {$limit} OFFSET {$offset}";
    $r = db_get($query,[$pattern]);
    if (!$r) {
        return "なし";
    }
    $log_ids = array();
    foreach ($r as $row) {
    	$log_ids[] = $row['log_id'];
    }
    // query logs
    $log_id_str = implode(',', $log_ids);
    if ($log_id_str != '') {
	    $query = "SELECT * FROM logs WHERE id in ({$log_id_str});";
    	$r = db_get($query);
    } else {
    	$r = FALSE;
    }
    if (!$r) {
    	return "[タグ抽出のエラー]";
    }
    // trim body data
    foreach ($r as $idx => $value) {
        $id   = $value["id"];
        $body = $value["body"];
        $tag_str = "";
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
    $count_r = count($r);
    
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
        $res .= plugin_tag_convert__dir($r);
    }
    
    return $res."\n".$pager;
}


function plugin_tag_convert__dir__r(&$p, &$desc)
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
                $res .= plugin_tag_convert__dir__r($elm, $desc);
            }
            $res .= "</ul>";
        }
    }
    return $res;
}

function plugin_tag_convert__dir(&$r)
{
    $res = "";
    // 階層付ハッシュに置換
    $tree = array();
    $desc = array();
    foreach ($r as $line) {
        extract($line);
        $dirs = explode('/', $name);
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
        $res .= plugin_tag_convert__dir__r($v, $desc);
    }
    $res .= "</ul>\n";
    return $res;
}


?>
