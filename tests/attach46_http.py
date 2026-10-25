#!/usr/bin/env python3
"""Issue #46 の HTTP 回帰テスト。python3 tests/attach46_http.py で実行。

attach コメント機能を、認証を有効にした実経路 (index.php?page&attach&stat) で検証する。

- stat=comment の受け付け (ログイン済み + 正しいトークン) と拒否
  (誤トークン / 未ログイン / 他ページ所属の添付)
- アップロード時のコメント保存
- `=` を含むファイル名 (data と data=2023.csv) の衝突シナリオ
- 表示リクエストでは comment カラムの移行 (ALTER) が走らず、
  書き込み (アップロード) で移行されること

PHP (mbstring / SQLite) が必要。設定・DB・キャッシュは全て
一時ディレクトリ内で作成する。
"""
import http.cookiejar
import pathlib
import re
import shutil
import socket
import sqlite3
import subprocess
import tempfile
import time
import unittest
import urllib.error
import urllib.parse
import urllib.request
import uuid


class Attach46HTTP(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        repo = pathlib.Path(__file__).resolve().parents[1]
        cls.tmp = tempfile.TemporaryDirectory(prefix="konawiki2-attach46-")
        cls.addClassCleanup(cls.tmp.cleanup)
        cls.root = pathlib.Path(cls.tmp.name)
        shutil.copy(repo / "index.php", cls.root)
        shutil.copytree(repo / "kona-engine", cls.root / "kona-engine",
                        ignore=shutil.ignore_patterns("*.zip", "__pycache__"))
        shutil.copytree(repo / "skin", cls.root / "skin")
        for name in ("data", "cache", "attach"):
            (cls.root / name).mkdir()
        # テンプレート既定の username / password、認証有効 (auth.write.enabled=TRUE) を使う
        config = (repo / "data/template-konawiki.ini.php").read_text() + """
$private['debug'] = TRUE;
"""
        (cls.root / "data/konawiki.ini.php").write_text(config)
        with socket.socket() as sock:
            sock.bind(("127.0.0.1", 0))
            port = sock.getsockname()[1]
        cls.base = "http://127.0.0.1:%d/index.php" % port
        log = tempfile.TemporaryFile()
        cls.addClassCleanup(log.close)
        cls.server = subprocess.Popen(
            ["php", "-S", "127.0.0.1:%d" % port, "-t", str(cls.root)],
            stdout=log, stderr=log)
        cls.addClassCleanup(cls.stop_server)
        for _ in range(100):
            try:
                cls.get(cls.client(), "FrontPage")
                break
            except (OSError, urllib.error.URLError):
                time.sleep(0.05)
        else:
            raise RuntimeError("PHP server did not start")

    @classmethod
    def stop_server(cls):
        cls.server.terminate()
        cls.server.wait(timeout=5)

    @staticmethod
    def client():
        return urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    @classmethod
    def get(cls, client, query):
        return client.open(cls.base + "?" + query, timeout=5).read().decode()

    @classmethod
    def post(cls, client, fields):
        if isinstance(fields, dict):
            fields = list(fields.items())
        data = urllib.parse.urlencode(fields).encode()
        return client.open(cls.base, data, timeout=5).read().decode()

    def setUp(self):
        self.anon = self.client()
        self.admin = self.client()
        login_page = self.get(self.admin, "FrontPage&login")
        self.post(self.admin, dict(
            page="FrontPage", action="login", user="username",
            pw="password", edit_token=self.token(login_page)))
        top = self.get(self.admin, "FrontPage")
        self.assertIn("username", top)  # ログインできていること

    # ---- helpers ----
    @staticmethod
    def hidden(html, name):
        m = re.search(
            r"name=['\"]%s['\"][^>]*value=['\"]([^'\"]*)['\"]" % re.escape(name),
            html)
        if m is None:
            m = re.search(
                r"value=['\"]([^'\"]*)['\"][^>]*name=['\"]%s['\"]"
                % re.escape(name), html)
        return m.group(1) if m else None

    def token(self, html):
        token = self.hidden(html, "edit_token")
        self.assertIsNotNone(token, "edit_token がフォームにない")
        self.assertRegex(token, r"^[0-9a-f]{64}$")
        return token

    def db(self):
        return sqlite3.connect(self.root / "data/konawiki.db")

    def page(self, name, body):
        with self.db() as db:
            return db.execute(
                "INSERT INTO logs(name,body,private,ctime,mtime)"
                " VALUES(?,?,0,1,1)", (name, body)).lastrowid

    def attach_row(self, name):
        with self.db() as db:
            return db.execute(
                "SELECT id, log_id, name, ext FROM attach WHERE name=?",
                (name,)).fetchone()

    def attach_comment(self, attach_id):
        with self.db() as db:
            row = db.execute(
                "SELECT comment FROM attach WHERE id=?", (attach_id,)).fetchone()
        return row[0] if row else None

    def attach_columns(self):
        with self.db() as db:
            return [r[1] for r in db.execute("PRAGMA table_info(attach)")]

    def upload(self, client, page, orig_name, content_bytes,
               comment=None, save_name=""):
        """multipart/form-data を手組みしてアップロードし、応答本文を返す"""
        boundary = "----konawiki" + uuid.uuid4().hex
        parts = []

        def field(name, value):
            parts.append(
                ("--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n"
                 % (boundary, name, value)).encode())

        field("page", page)
        field("action", "attach")
        field("stat", "write")
        field("filename", save_name)
        if comment is not None:
            field("comment", comment)
        ctype = "text/csv" if orig_name.endswith(".csv") else "text/plain"
        parts.append(
            ("--%s\r\nContent-Disposition: form-data; name=\"userfile\";"
             " filename=\"%s\"\r\nContent-Type: %s\r\n\r\n"
             % (boundary, orig_name, ctype)).encode())
        parts.append(content_bytes + b"\r\n")
        parts.append(("--%s--\r\n" % boundary).encode())
        req = urllib.request.Request(
            self.base, b"".join(parts),
            {"Content-Type": "multipart/form-data; boundary=" + boundary})
        try:
            return client.open(req, timeout=5).read().decode()
        except urllib.error.HTTPError as e:
            return e.read().decode()

    def ensure_memo(self):
        """test_01 の memo.txt が無ければアップロードして行を返す"""
        row = self.attach_row("memo.txt")
        if row is None:
            if self.page_id("Attach46Main") is None:
                self.page("Attach46Main", "#attachfiles\n")
            res = self.upload(self.admin, "Attach46Main", "memo.txt",
                              "メモの内容\n".encode(),
                              comment="アップロード時コメント")
            self.assertIn("添付成功", res)
            row = self.attach_row("memo.txt")
        return row

    def page_id(self, name):
        with self.db() as db:
            row = db.execute(
                "SELECT id FROM logs WHERE name=?", (name,)).fetchone()
        return row[0] if row else None

    # ---- tests (番号 prefix で実行順を固定する) ----
    def test_01_upload_with_comment(self):
        self.page("Attach46Main", "#attachfiles\n")
        res = self.upload(self.admin, "Attach46Main", "memo.txt",
                          "メモの内容\n".encode(),
                          comment="アップロード時コメント")
        self.assertIn("添付成功", res)
        row = self.attach_row("memo.txt")
        self.assertIsNotNone(row)
        self.assertEqual("アップロード時コメント", self.attach_comment(row[0]))

    def test_02_routed_comment_update_and_rejections(self):
        row = self.ensure_memo()
        attach_id = row[0]
        # 正常系: 添付フォームを開いて新しいトークンを取り直してから POST
        form = self.get(self.admin, "Attach46Main&attach")
        res = self.post(self.admin, dict(
            page="Attach46Main", action="attach", stat="comment",
            id=str(attach_id), comment="経路経由で更新",
            edit_token=self.token(form)))
        self.assertIn("コメントを更新しました", res)
        self.assertEqual("経路経由で更新", self.attach_comment(attach_id))

        # 誤トークンでは変わらない
        self.get(self.admin, "Attach46Main&attach")
        res = self.post(self.admin, dict(
            page="Attach46Main", action="attach", stat="comment",
            id=str(attach_id), comment="誤トークン更新",
            edit_token="0" * 64))
        self.assertNotIn("コメントを更新しました", res)
        self.assertEqual("経路経由で更新", self.attach_comment(attach_id))

        # 未ログインでは変わらない (この版では未ログイン時の edit_token は
        # ダミー値 'PleaseLogin' で、トークン照合も認証も通らない)
        anon_form = self.get(self.anon, "Attach46Main&attach")
        res = self.post(self.anon, dict(
            page="Attach46Main", action="attach", stat="comment",
            id=str(attach_id), comment="未ログイン更新",
            edit_token=self.hidden(anon_form, "edit_token") or ""))
        self.assertNotIn("コメントを更新しました", res)
        self.assertEqual("経路経由で更新", self.attach_comment(attach_id))

        # 他ページの添付としては更新できない (所属チェック)。
        # edit_token はセッション単位で、添付の無いページのフォームには
        # トークン自体が無いため、Attach46Main のフォームで取得した
        # 正規トークンを使い、page だけを Attach46Other にして送る
        self.page("Attach46Other", "#attachfiles\n")
        main_form = self.get(self.admin, "Attach46Main&attach")
        res = self.post(self.admin, dict(
            page="Attach46Other", action="attach", stat="comment",
            id=str(attach_id), comment="他ページ経由の更新",
            edit_token=self.token(main_form)))
        self.assertNotIn("コメントを更新しました", res)
        self.assertIn("このページの添付ファイルではありません", res)
        self.assertEqual("経路経由で更新", self.attach_comment(attach_id))

    def test_03_equals_filename_collision(self):
        log_id = self.page("Attach46Collision",
                           "#attachfiles(data=2023.csv=集計結果)\n")
        # 拡張子なしの名前 "data" は Issue #48 以降アップロードでは作れないため、
        # 過去に作られた行として DB 直接作成で再現する
        # (実ファイルは元ファイル a.txt の拡張子で {id}.txt として置かれる形式)
        with self.db() as db:
            data_id = db.execute(
                "INSERT INTO attach(log_id,name,ext,comment,ctime,mtime)"
                " VALUES(?,?,?,?,1,1)",
                (log_id, "data", "text/plain", "data用コメント")).lastrowid
            db.execute("INSERT INTO attach_counters(id) VALUES(?)", (data_id,))
        (self.root / "attach" / ("%d.txt" % data_id)).write_bytes(b"plain text\n")
        res = self.upload(self.admin, "Attach46Collision", "b.csv",
                          b"a,b\n1,2\n", comment="CSV用コメント",
                          save_name="data=2023.csv")
        self.assertIn("添付成功", res)
        html = self.get(self.admin, "Attach46Collision")
        self.assertIn("集計結果", html)
        self.assertIn("data用コメント", html)
        # 引数コメントが DB コメントより優先されるので、CSV のDBコメントは出ない
        self.assertNotIn("CSV用コメント", html)
        # `data` 側へ誤って結合して適用されない
        self.assertNotIn("2023.csv=集計結果", html)

    def test_04_read_does_not_migrate_but_write_does(self):
        log_id = self.page("Attach46Legacy", "#attachfiles\n")
        # attach テーブルを旧定義 (comment なし) に差し替える。
        # 後続のテストが追加されても影響しないよう、終了時 (finally) に
        # 元のテーブルとカウンタを復元する
        with self.db() as db:
            counters = db.execute(
                "SELECT id, view, download FROM attach_counters").fetchall()
            db.execute("ALTER TABLE attach RENAME TO attach46_backup")
            db.execute("CREATE TABLE attach (id INTEGER PRIMARY KEY,"
                       " log_id INTEGER, name TEXT, ext TEXT,"
                       " ctime INTEGER, mtime INTEGER)")
            db.execute("INSERT INTO attach(log_id,name,ext,ctime,mtime)"
                       " VALUES(?,?,?,1,1)", (log_id, "legacy46.txt", "text/plain"))
            # 差し替えた旧行のカウンタが残ると、この後のアップロードで
            # attach_counters の id が衝突するため、一旦消しておく
            # (元の内容は finally で復元する)
            db.execute("DELETE FROM attach_counters")
        try:
            self.assertNotIn("comment", self.attach_columns())

            # 表示リクエストでは移行されない
            self.assertIn("legacy46.txt", self.get(self.admin, "Attach46Legacy"))
            self.assertIn("legacy46.txt",
                          self.get(self.admin, "Attach46Legacy&attach"))
            self.assertNotIn("comment", self.attach_columns())

            # 書き込み (アップロード) で移行される
            res = self.upload(self.admin, "Attach46Legacy", "legacy2.txt",
                              b"legacy2\n", comment="移行後コメント")
            self.assertIn("添付成功", res)
            self.assertIn("comment", self.attach_columns())
            row = self.attach_row("legacy2.txt")
            self.assertIsNotNone(row)
            self.assertEqual("移行後コメント", self.attach_comment(row[0]))
        finally:
            with self.db() as db:
                exists = db.execute(
                    "SELECT 1 FROM sqlite_master"
                    " WHERE type='table' AND name='attach46_backup'").fetchone()
                if exists:
                    db.execute("DROP TABLE attach")
                    db.execute("ALTER TABLE attach46_backup RENAME TO attach")
                    db.execute("DELETE FROM attach_counters")
                    db.executemany(
                        "INSERT INTO attach_counters(id,view,download)"
                        " VALUES(?,?,?)", counters)


if __name__ == "__main__":
    unittest.main()
