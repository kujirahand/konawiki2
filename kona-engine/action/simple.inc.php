<?php
/* vim:set expandtab ts=4 sts=4 sw=4: */

/**
 * ページの表示アクション
 */
include_once(KONAWIKI_DIR_LIB.'/konawiki_parser.inc.php');

function action_simple_()
{
    // check dynamic plugins
    global $konawiki_show_as_dynamic_page;
    // show template
    $page = konawiki_getPage();
    $konawiki_show_as_dynamic_page = FALSE; // 基本的にプラグインがあれば dynamic となる
    $log = konawiki_getLog($page);
    if ($log == FALSE) {
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
    $log_id = intval($log['id']);
    $log['page'] = $page;
    
    // check show plugin
    _konawiki_show_plugins($log);
    // show template
    $html = $log['body'] = konawiki_parser_convert($log['body']);
    // check PRIVATE ?
    if (isset($log['private']) && $log['private']) {
        $log['body'] = '<div clss="contents">'.
                       '<div class="error">'.
                        konawiki_lang('Private Page.').
                        '</div></div>';
        $log['tag'] = '';
    }
    // create body_all
    $log['body_all'] = <<< __EOS__
<!-- body -->
{$log['body']}
<!-- end of body -->
__EOS__;

    include_template('simple.tpl.php', $log);
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

