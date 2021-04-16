<?php
/* vim:set expandtab ts=4 sts=4 sw=4: */

/**
 * ページの表示アクション
 */
include_once(KONAWIKI_DIR_LIB.'/konawiki_parser.inc.php');

function action_show_()
{
    // check dynamic plugins
    global $konawiki_show_as_dynamic_page;
    // show template
    $page = konawiki_getPage();
    $log_exists = TRUE;
    $konawiki_show_as_dynamic_page = FALSE; // 基本的にプラグインがあれば dynamic となる
    $log = konawiki_getLog($page);
    if ($log == FALSE || !isset($log['body'])) {
        $body = "*** Page List\n".
            "#ls\n";
        $log = array(
            'id'            => 0,
            'body'          => $body,
            'tag'           => '',
            'body_header'   => '',
            'body_footer'   => '',
            'ctime'         => time(),
            'mtime'         => time(),
        );
        $log_exists = FALSE;
        header('HTTP/1.0 404 Not Found');
    }
    // set header & footer & edit_link
    $log['body_header'] = konawiki_getArray($log, 'body_header', '');
    $log['body_footer'] = konawiki_getArray($log, 'body_footer', '');
    $log['edit_menu'] = konawiki_getEditMenu($log);
    $log['ctime_html'] = konawiki_date_html(intval($log['ctime']), 'normal');
    $log['mtime_html'] = konawiki_date_html(intval($log['mtime']), 'normal');
    $log['rawtag'] = htmlspecialchars($log['tag']);
    $log['tag'] = _konawiki_show_tag($log['tag'], $log['id']);
    $log['flag_dynamic'] = FALSE;
    $log_id = intval($log['id']);
    $log['page'] = $page;
    
    // Cache function
    $has_cache = FALSE;
    $cachemode = konawiki_private('cache.mode');
    if ($cachemode == 'cache') {
      // clear cache ?
      $cache = konawiki_param("cache", false);
      if ($cache == "clear") {
          konawiki_clearCacheDB($log_id);
      }
      else if ($cache == "clearall") {
          konawiki_clearCacheDB_All();
	    }
	    // check CACHE
	    if ($log_exists) {
	    	// CHECK CACHE
        $sql = "SELECT * FROM cache_logs WHERE log_id=? LIMIT 1";
        $r = db_get1($sql, [$log_id], 'backup');
	    	if ($r) {
	    		$log['body'] = $r['html'];
	    		$has_cache = TRUE;
	    	}
	    }
    }
    if (!$has_cache) {
	    // check show plugin
	    _konawiki_show_plugins($log);
	    // show template
	    $html = $log['body'] = konawiki_parser_convert($log['body']);
	    // make cache
	    if (($log_exists) && ($konawiki_show_as_dynamic_page == FALSE)) {
        $sql = "SELECT log_id FROM cache_logs WHERE log_id=? LIMIT 1";
        $r = db_get1($sql, [$log_id], 'backup');
	    	if (isset($r['log_id'])) {
		    	$r = db_exec("DELETE FROM cache_logs WHERE log_id=?", [$log_id], 'backup');
	    	}
	    	$sql = "INSERT INTO cache_logs (log_id,html,ctime)VALUES(?,?,?)";
        db_exec($sql, [$log_id, $html, time()], 'backup');
	    }
    }
    $cache_checker = "";
    if ($cachemode == 'cache' && konawiki_isLogin_write() && $has_cache) {
		$has_cache_str	= $has_cache ? "CACHED" : "RAW"; 
		$is_dynamic_str	= $konawiki_show_as_dynamic_page ? "DYNAMIC" : "STATIC"; 
		$link_clear_cache = konawiki_getPageURL(false, "show", "", "cache=clear");
		$link_clear_all_cache = konawiki_getPageURL(false, "show", "", "cache=clearall");
		$cache_checker =
			"<div style='padding:6px; background-color:#ffffe0; font-size:0.8em; margin-top: 12px;'>".
			"Cache status : $has_cache_str/$is_dynamic_str - ".
			"[<a href='$link_clear_cache'>clear</a>]".
			"[<a href='$link_clear_all_cache'>clear all</a>]".
			"</div>";
   	}
    // check PRIVATE ?
    if (isset($log['private']) && $log['private']) {
        $log['body'] = '<div clss="contents">'.
                       '<div class="error">'.
                        konawiki_lang('Private Page.').
                        '</div></div>';
        $log['tag'] = '';
    }
    // create body_all
    $wikibody_header = isset($log['wikibody_header']) ? $log['wikibody_header'] : '';
    $wikibody_footer = isset($log['wikibody_footer']) ? $log['wikibody_footer'] : '';
    $log['body_all'] = <<< __EOS__
{$wikibody_header}
{$log['body_header']}
<!-- body -->
{$log['body']}
<!-- end of body -->
{$cache_checker}
{$log['tag']}
{$log['body_footer']}
{$wikibody_footer}
__EOS__;

    include_template('show.tpl.php', $log);
}

function _konawiki_show_plugins(&$log)
{
    global $konawiki_show_log;
    $modified = FALSE;
    // show insert plug-ins
    $show_plugins = konawiki_private('show.plugins');
    if ($show_plugins) {
      foreach ($show_plugins as $name => $plugin) {
          if (!$plugin['enabled']) continue;
          $modified = TRUE;
          $file  = $plugin['file'];
          $entry = $plugin['entry'];
          include_once(KONAWIKI_DIR_PLUGINS.'/'.$file);
          if (is_callable($entry)) {
            call_user_func($entry,$plugin,$log);
          }
      }
      if ($modified) {
          $log = $konawiki_show_log;
      }
    }
}

function _konawiki_show_tag($tag, $id)
{
    $taglimit = 10;
    
    $tag_visible = konawiki_public('tag.link.pages.visible', true);
    $ret = "<!-- tags -->";
    // related page
    if ($tag_visible && ($tag != "")) {
        $tags = explode(",", $tag);
        $or = array();
        $tag_html = "";
        $params = [];
        foreach ($tags as $word) {
            $or[] = "tag=?";
            $params[] = $word;
            $word_html = htmlspecialchars($word);
            $uw = urlencode($word);
            $tag_html .= " <a href='index.php?{$uw}&amp;taglist'> {$word_html} </a>";
        }
        $or_str = join(" OR ", $or);
        $sql = "SELECT log_id FROM tags WHERE {$or_str} ORDER BY log_id DESC LIMIT ?";
        $params[] = $taglimit;
        $r = db_get($sql, $params);
        if ($r) {
            $pages = array();
            foreach ($r as $row) {
                $log_id = $row['log_id'];
                if ($log_id == $id) continue;
                $page = konawiki_getPageNameFromId($log_id);
                $pages[$page] = '<li>'.konawiki_getPageLink($page).'</li>';
            }
            if ($pages) {
                $page_str = join("\n", $pages);
                $ret .= <<< EOS__
<div class="tagpagelist">
<div class="title"> Tag : {$tag_html}</div>
<ul>
{$page_str}
</ul>
</div>
EOS__;
            }
        }
    }
    // taglist
    if (konawiki_public('tag.link.visible')) {
        if ($ret == "") { $ret = "<br/>"; }
        $ret .= konawiki_makeTagLink($tag);
    }
    
    return $ret;
}



