<?php
/** konawiki plugins -- メタテーブルプラグイン 
 * - [書式] {{{#meta_table(データ) }}}
 * - [引数]
 * - [使用例]
 * - [備考] メタアイテム一覧ページを作成するプラグイン
 * - [公開設定] 公開
 {{{
  // 管理ページ
  {{{#meta_table
  {
    "name": "物件",
    "fields": ["タイプ", "価格", "交通", "所在地", "間取り"]
  }
  }}}
  // 個別ページ
  #meta_table_show
  // 一覧ページ
  #meta_table_list(物件,タイプ=xxx)
}}}
 */

require_once __DIR__.'/meta_table/index.inc.php';

