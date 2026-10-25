konawiki2
=========

The KonaWiki application excels in facilitating information sharing. It allows for easy markup in Japanese, making it user-friendly. KonaWiki can be utilized not only for writing but also for blogging and website construction.

情報共有に活躍するWIKIアプリ。KonaWikiを使うと、日本語で手軽にマークアップすることができます。執筆、ブログ、サイト構築にも利用できます。

## Information

- [URL] http://kujirahand.com/konawiki
- [PHP] PHP 7.x / 8.x
- [DB]  SQLite3

## Functions

- **Automatic Backup** : Offers peace of mind with its regular automatic saving feature.
- **Word Count** : Continuously counts the number of characters, both in part and in whole.
- **Plugins** : A wide range of plugins are available for use.　[→ plugins](kona-engine/plugins/)

## Install

- [(ja) 日本語でインストールの手順](https://kujirahand.com/konawiki/index.php?%E3%82%A4%E3%83%B3%E3%82%B9%E3%83%88%E3%83%BC%E3%83%AB%E3%81%AB%E3%81%A4%E3%81%84%E3%81%A6)

### Install for command line:

```sh
git clone --recursive https://github.com/kujirahand/konawiki2.git ./konawiki2
cd konawiki2
chmod 777 attach
chmod 777 data
chmod 777 cache
```

## For Developers

KonaWiki2 uses the [just](https://just.systems) task runner. Run `just --list` to see all recipes.

- `just test` — Run all tests (PHP CLI tests, if present, and the HTTP regression tests in `tests/`)
- `just test-http` — Run only the HTTP regression tests (e.g. `just test-http csrf10` to filter)
- `just test-cli` — Run only the PHP CLI tests (`tests/test_main.php`)
- `just lint` — Check PHP syntax
- `just serve` — Start a local server at http://127.0.0.1:8000
- `just setup` — Initialize the submodule and directory permissions (same as the install steps above)

タスクランナーの `just` を使用しています。テストを実行するには、`just test` を実行します。


