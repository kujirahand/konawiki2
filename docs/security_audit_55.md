# セキュリティ監査記録 (Issue #55)

## 概要

定期セキュリティチェック (#31) の最終再確認を最新 master (d1059fb) で実施し、非公開ページの列挙・表示経路を中心に再点検した。その結果、漏洩 3 件を Issue #55 として切り出して修正した。

再確認では、既存の修正（認証、検索・RSS・`#navi`・`#recentcomment` 等の private 除外、CSRF、アップロード、テンプレート対策）が維持されていることを確認した。また、全体テスト（CLI 508 件・HTTP 35 件）は修正前に合格していた。

## 修正した問題 (3 件)

1. `#commentallpage` (`kona-engine/plugins/commentallpage.inc.php`)
   - 漏洩内容: sublogs のコメント全件表示で対象ページの private を確認しておらず、非公開ページの名前とコメント本文が漏れた。
   - 修正: `#recentcomment` と同じく `konawiki_getPageInfoById()` で確認し、private ページはスキップする。
2. `#blognavi` (`kona-engine/plugins/blognavi.inc.php`)
   - 漏洩内容: ページ列挙 SQL に private の除外条件がなく、前後ページへのリンクに非公開ページ名が現れた。
   - 修正: `#navi` と同じ `COALESCE(private,0)=0` を SQL に追加（パターン指定時も条件を維持）。
3. `go` アクション (`kona-engine/action/go.inc.php`)
   - 漏洩内容: 連番 ID からのリダイレクトで private を確認せず、ID を列挙すると非公開ページ名が分かった（名前のオラクル）。
   - 修正: private かつ書き込み権限（`konawiki_isLogin_write()`）がない場合は、存在しない ID と同じ `./index.php` へリダイレクトする。

## 検証

- HTTP 回帰テスト `tests/security55_http.py` を追加した（`python3 tests/security55_http.py` で実行）。
