# task runner for just (https://just.systems)
# レシピの一覧は `just` または `just --list` で表示できます。

php := "/usr/bin/env php"
python := "/usr/bin/env python3"

# レシピの一覧を表示する
default:
    @just --list

# 全テストを実行する (PHP CLIテスト + HTTP回帰テスト)
test:
    #!/usr/bin/env bash
    set -euo pipefail
    if [ -f tests/test_main.php ]; then
        echo "== PHP CLI tests: php tests/test_main.php go_test =="
        {{php}} tests/test_main.php go_test
    else
        echo "== PHP CLI tests: tests/test_main.php がないためスキップします =="
    fi
    echo "== HTTP regression tests: python3 tests/*_http.py =="
    for f in tests/*_http.py; do
        [ -e "$f" ] || continue
        echo "-- $f"
        {{python}} "$f"
    done

# PHP CLIテストだけを実行する (tests/test_main.php が必要)
test-cli:
    #!/usr/bin/env bash
    set -euo pipefail
    if [ ! -f tests/test_main.php ]; then
        echo "tests/test_main.php が見つかりません。" >&2
        exit 1
    fi
    {{php}} tests/test_main.php go_test

# HTTP回帰テストだけを実行する (例: just test-http csrf10 で対象を絞れる)
test-http $filter="":
    #!/usr/bin/env bash
    # 引数 filter は export 引数として環境変数で受け取る (レシピ本文への
    # テキスト展開だと、引用符等を含む引数で bash の構文が壊れるため)
    set -euo pipefail
    ran=0
    for f in tests/*_http.py; do
        [ -e "$f" ] || continue
        if [ -n "$filter" ] && [[ "$f" != *"$filter"* ]]; then
            continue
        fi
        echo "-- $f"
        {{python}} "$f"
        ran=1
    done
    if [ "$ran" -eq 0 ]; then
        echo "対象のHTTPテストが見つかりません: $filter" >&2
        exit 1
    fi

# PHPファイルの構文チェックを行う (サブモジュール kona-engine/fw_simple を除く)
lint:
    #!/usr/bin/env bash
    set -uo pipefail
    fail=0
    count=0
    while IFS= read -r f; do
        count=$((count + 1))
        if ! out=$({{php}} -l "$f" 2>&1); then
            echo "$out"
            fail=1
        fi
    done < <(find . -path ./kona-engine/fw_simple -prune -o -type f \( -name '*.php' -o -name '*.inc.php' \) -print | sort)
    echo "lint: ${count} files checked"
    exit "$fail"

# 初期セットアップを行う (サブモジュールの取得 + 書き込み権限の設定)
setup:
    git submodule update --init
    chmod 777 attach data cache

# 開発用サーバーを起動する (例: just serve / just serve 8080)
serve port="8000":
    {{php}} -S 127.0.0.1:{{port}} -t .
