<?php
/* vim:set expandtab ts=4 sts=4 sw=4: */

/**
 * ページの表示アクション
 */
include_once(KONAWIKI_DIR_LIB.'/konawiki_parser.inc.php');

function action_taglist_()
{
    // check dynamic plugins
    global $konawiki_show_as_dynamic_page;
    // show template
    $page = konawiki_getPage();
    $log_exists = TRUE;
    $konawiki_show_as_dynamic_page = FALSE; // 基本的にプラグインがあれば dynamic となる

    // make dummy body
    $body = "*** Page list\n".
            "#tag($page)\n";
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
    // set header & footer & edit_link
    $log['body_header'] = konawiki_getArray($log, 'body_header', '');
    $log['body_footer'] = konawiki_getArray($log, 'body_footer', '');
    $log['edit_menu'] = konawiki_getEditMenu($log);
    $log['ctime_html'] = konawiki_date_html(intval($log['ctime']), 'normal');
    $log['mtime_html'] = konawiki_date_html(intval($log['mtime']), 'normal');
    $log['rawtag'] = htmlspecialchars($log['tag']);
    $log['tag'] = '';
    $log['flag_dynamic'] = FALSE;
    $log_id = intval($log['id']);
    $log['page'] = $page;
    
    // show template
    $html = $log['body'] = konawiki_parser_convert($log['body']);
    // create body_all
    $wikibody_header = isset($log['wikibody_header']) ? $log['wikibody_header'] : '';
    $wikibody_footer = isset($log['wikibody_footer']) ? $log['wikibody_footer'] : '';
    $log['body_all'] = <<< __EOS__
{$wikibody_header}
<!-- body -->
{$log['body']}
<!-- end of body -->
{$wikibody_footer}
__EOS__;

    include_template('show.html', $log);
}

