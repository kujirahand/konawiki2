# セキュリティ監査フォローアップ記録 (Issue #32)

Issue #31 の監査 (docs/security_audit_31.md) で「仕様判断が必要」として
切り出した残件への対応記録。判断と互換性への影響を項目ごとに記す。

## 1. テンプレート設定ファイルの既定資格情報

- `data/template-konawiki.ini.php` の管理キー `konawiki` と
  既定ユーザー `username` / `password` を有効な初期値として
  配布するのをやめた。管理キーは空、既定ユーザーはコメント例のみ。
- 初回起動時にテンプレートが実設定へコピーされる経路は従来どおりだが、
  管理キーとログインユーザーがどちらも未設定の場合、`index.php` が
  セットアップ案内を表示して終了する (書き込み認証が無効な場合を除く)。
  導入手順への影響: 新規導入時は設定ファイルで管理キーとユーザーの
  設定が必須になる (従来も設定ファイルの編集自体は必要だった)。
- 既存の設定ファイルには影響しない。ただし、既定値のまま運用されて
  いる場合は次項のガードにより管理操作・ログインが拒否されるため、
  管理キーとパスワードの変更が必要になる。

## 2. 既定値のままの資格情報へのガード

- `konawiki_checkAdminKey()` を追加し、管理キーの照合を一元化した
  (edit の rename / replace / batch 系、freeze、import が利用)。
  - 空の管理キーは、入力が空でも一致とみなさない。
  - 既定値だった `konawiki` (平文) は公開情報のため未設定として扱う。
- ログイン照合でも、既定ペア `username` / `password` (平文) の
  ままではログインを拒否する。保存値が空の場合も一致とみなさない。

## 3. パスワード保存形式の password_hash 拡張

- ユーザーパスワード (ログイン照合 `_konawiki_auth_check_password`) と
  管理キー照合 (`konawiki_checkPassword`) の両方で、以下の形式を
  受け付けるようにした。
  - `{password_hash}<password_hash() の出力>` (推奨)
  - 接頭辞なしの `password_hash()` 出力そのもの (`$2y$...` 等)
  - 従来形式 (平文 / `{md5}` / `{sha1}` / `{x-php-md5}`) は互換のため継続
- ログイン照合側に欠けていた `{x-php-sha1}` もあわせて対応した。
- ハッシュの生成例: `php -r "echo password_hash('pw', PASSWORD_DEFAULT), PHP_EOL;"`

## 4. meta_table の新規作成 (m2=new) が GET でページを作成する問題

- 追加フォームを POST 化し、edit_token の hidden フィールドを追加。
- 新規作成は POST + edit_token (POST のみ受理) が必須になった。
  GET リンクやトークンなしの POST ではページを作成せず、
  追加フォームをエラー付きで再表示する。
- 既存ページの編集 (m2=edit) の GET 表示、更新・削除 (従来から
  POST + トークン) には影響しない。
- あわせて作成直後の `$log` 組み立てで未定義変数 `$pagenam` を
  使っていたタイポを `$pagename` に修正した。

## 5. bodyvote / bodycomment が freeze 中のページにも書き込む問題

- 両プラグインは本文を書き換えるため、通常の編集が凍結中に
  拒否されるのと挙動をそろえ、action 側で
  `konawiki_isPageFrozen()` を確認し、凍結中は書き込まない。
- #vote / #comment は本文ではなく sublogs へ書くため対象外とした
  (凍結は本文の編集を止める仕様であり、従来の位置付けを変えない)。

## 6. plugin アクションの設計

- convert の一律制限は行わない判断とした。表示専用プラグインの
  直接実行には正当な利用があり、書き込み系は各プラグイン側で
  ログイン・CSRFトークン・凍結チェックを行う前提を明文化する。
- ただし、状態変更を伴うディスパッチャ型プラグイン
  (`meta_table`: convert 内で list/add/edit/update/delete を振り分ける)
  は直接実行の対象から外した。設定 `plugins.action_denied` で
  対象を追加できる。
- プラグイン名は英数字とアンダースコアのみ許可する検証も追加した。

## 7. fw_simple テンプレートエンジンの extract()

- 修正は php_fw_simple リポジトリ側で行った (PR:
  https://github.com/kujirahand/php_fw_simple/pull/2)。
  `template_render()` の全 `extract()` を `EXTR_SKIP` にし、
  引数のキーで内部変数 (`$file_cache` 等) を上書きできないようにした。
- konawiki2 側はサブモジュールの参照をその修正コミットへ更新した。
- konawiki2 本体側の現在の呼び出しではリクエスト由来のキーは
  渡らないため潜在的な問題であり、実害の確認された問題ではない。

## 8. Host ヘッダによる baseurl 組み立て

- `konawiki_http_host()` を追加し、URL 組み立てに使うホスト名を
  検証する (ホスト名[:ポート] / [IPv6][:ポート] の形式のみ許可)。
  不正な場合は `SERVER_NAME`、それも不正なら `localhost` に落とす。
  適用箇所: `konawiki_options.inc.php` の baseurl / uri 系と
  `index.php` の `$scrUri`。
- 正当なホスト名 (バーチャルホストやポート付き) は従来どおり
  使える。ホスト名を固定したい場合は、従来どおり設定ファイルで
  `$public['baseurl']` を明示すればそちらが優先される。
- プロキシ配下で `X-Forwarded-Host` を信頼する変更は行っていない
  (信頼するかどうかは配置側の判断のため)。

## 9. 低リスクの残件 (PR #33 レビュー時の任意対応分)

- 旧キャッシュ行の掃除: 形式の合わない (接頭辞のない) キャッシュ行を
  show の読み出し時に削除するようにした。動的ページでは再生成時の
  削除に到達しないため、従来は未使用のまま残存していた。
- 添付ディレクトリの glob メタ文字: `konawiki_removeAttachFiles()` を
  `glob()` からディレクトリ走査に変え、パスに `[ ] * ?` 等が
  含まれても数値IDに一致する実ファイルを削除できるようにした。

## 検証

- HTTP 回帰テスト `tests/security32_http.py` を追加
  (password_hash / x-php-sha1 ログイン、既定資格情報の拒否、
  管理キーの各形式、meta_table の作成ガード、凍結中の
  bodyvote / bodycomment、plugin アクションの拒否、Host ヘッダ、
  旧キャッシュ行の掃除、glob メタ文字を含む添付ディレクトリ)。
- 既存の HTTP 回帰テスト (csrf10 / newpage35 / security31) も全件合格。
