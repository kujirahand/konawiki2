<?php

function action_new_() {
    $link = array(
        array(
          'url'   => konawiki_getPageURL('MenuBar', 'edit'),
          'label' => konawiki_lang('Edit MenuBar'),
        ),
        array(
          'url'   => konawiki_getPageURL('SideBar', 'edit'),
          'label' => konawiki_lang('Edit SideBar'),
        ),
        array(
          'url'   => konawiki_getPageURL('NaviBar', 'edit'),
          'label' => konawiki_lang('Edit NaviBar'),
        ),
        array(
          'url'   => konawiki_getPageURL('GlobBar', 'edit'),
          'label' => konawiki_lang('Edit GlobBar'),
        )
    );
    include_template("new.html", [
        'link' => $link
    ]);
}

