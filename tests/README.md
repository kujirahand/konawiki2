# konawiki2 tests

KonaWiki2 の機能テストです。Webサーバや実データを使わず、PHP CLI だけで実行できます。

## 実行方法

```sh
# サブモジュール (kona-engine/fw_simple) が必要です
git submodule update --init

./tests/test.sh
# または
php tests/test_main.php go_test
```

全テストが合格すると終了コード `0`、失敗があると `1` を返します。
`php tests/auth.test.php` のようにテストファイルを単体で実行することもできます
(その場合も結果が表示され、失敗時は終了コード `1` になります)。

なお、テストは警告や Deprecated を隠さず表示します。新規ページ作成時の
Deprecated 警告 (Issue #35 で追跡中) は、修正されるまで実行時に表示されますが、
テスト自体は合格します。

DB を使うテストは、テストプロセス毎に作る一時ディレクトリ (サンドボックス) 内に
SQLite データベースを作成して検証するので、リポジトリの `data/` は汚れません。

## テストの書き方

`tests/` に `hoge.test.php` というファイルを作成し、下記のように記述します。
`test_main.php` が `*.test.php` を自動的に拾って実行します。

```php
<?php
require_once __DIR__ . '/test_common.inc.php';

test_eq(__LINE__, 1 + 1, 2, "test");
test_assert(__LINE__, true, "assert example");
test_contains(__LINE__, "hello world", "world", "contains example");
```

利用できるヘルパー (`test_common.inc.php`):

- `test_eq(__LINE__, $actual, $expected, $desc)` — 厳密比較 (`===`)
- `test_ne(__LINE__, $actual, $expected, $desc)`
- `test_assert(__LINE__, $bool, $desc)`
- `test_contains(__LINE__, $haystack, $needle, $desc)`
- `test_not_contains(__LINE__, $haystack, $needle, $desc)`
- `test_init_db()` — サンドボックス内にテスト用 DB を初期化する (冪等)
- `test_write_page($page, $body, $tag = FALSE)` — テスト用ページを書く

`test_common.inc.php` は `index.php` と同じ初期値で `$public` / `$private` を用意し、
本体ライブラリ (パーサ・認証・DB など) を読み込みます。
新しいテストで本体の関数を使う場合は、必要に応じて読み込みを追加してください。

注意: 全テストファイルは1プロセスで順に実行され、テスト用 DB はファイル間で
共有されます。ページを使うテストでは、他のテストファイルと重ならない
ページ名を使ってください。また、グローバル変数 (`$public` / `$private` /
`$_GET` / `$_SERVER` など) を変更したテストは、後続のファイルに影響しないよう
元の状態に戻してください。

## テストファイル一覧

| ファイル | 対象 |
|---|---|
| `auth.test.php` | 認証 (`_konawiki_auth_check_password`, `konawiki_checkPassword`) |
| `db.test.php` | DB初期化、ページ書き込み/読み出し、タグ、認証ハッシュ |
| `diff.test.php` | 編集競合チェック (`konawiki_diff_checkConflict`) |
| `fw_simple.test.php` | fw_simple (パラメータ取得、URL生成、テンプレートエンジン) |
| `lib_kona.test.php` | 基本ライブラリ (パラメータ、URL生成、URI解析、日時整形、オプション) |
| `mime_html.test.php` | MIME判定、フォーム部品、UserAgent判定 |
| `parser.test.php` | Wiki記法パーサ (トークン化、HTML変換、Wikiリンク、URLチェック) |
| `counter.test.php` | counter プラグインの月別集計 |
| `security31.test.php` | セキュリティ監査 (#31) の回帰テスト |
| `plugins.test.php` | プラグイン解決と個別プラグイン (color/small/rem/md5/clear) |

旧来のブラウザ実行テスト (`kona-engine/test/`, `index.php?all&test`) は廃止し、`tests/*.test.php` に統合しました。
