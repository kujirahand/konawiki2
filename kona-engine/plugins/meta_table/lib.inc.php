<?php
// const
define('KONA_META_LIMIT', 100);

function plugin_meta_table_checkLogin() {
  // session
  konawiki_start_session();
  
  // ログインが必須
  if (!konawiki_isLogin()) {
    $backlink = konawiki_getPageURL(FALSE);
    konawiki_auth_setBackLink($backlink);
    $link = konawiki_getPageURL(FALSE, 'login');
    $msg = konawiki_lang('Please login.');
    return "<a class='pure-button' href='$link'>$msg</a>";
  }
  
  return FALSE;
}

function plugin_meta_table_check_json($json, &$params) {
  // decode
  $params = json_decode($json, TRUE);
  if (!$json) { return "([meta_table error] json data broken])"; }
  // check keys  
  $name = isset($params['name']) ? $params['name'] : '';
  if (!$name) {
    return '([meta_table error] no name)';
  }
  $fields = isset($params['fields']) ? $params['fields'] : '';
  if (!$fields) {
    return '([meta_table error] no fields)';
  }
  return FALSE;
}

function plugin_meta_table_menu($json) {
  $list = konawiki_getPageURL(FALSE, 'show', '', 'm=liset');
  $add = konawiki_getPageURL(FALSE, 'show', '', 'm=add');
  return meta_table_template('menu.inc.html', [
    'name' => $json['name'],
    'link_list' => $list,
    'link_add' => $add]);
}


