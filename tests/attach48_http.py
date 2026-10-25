#!/usr/bin/env python3
"""Issue #48 の HTTP 回帰テスト。python3 tests/attach48_http.py で実行。

添付アップロード時のファイル名が、名前変更 (Issue #22) と同じ検証を受けることを確認する。

- 保存名の正常系・既定名 (ファイル名欄が空)・日本語名・連番の維持
- 拡張子の変更 (大文字小文字違い・削除を含む) の拒否
- パス区切り・引用符・長すぎる名前・`.` / `..` の拒否
- 拒否時に DB 行も実ファイルも残らないこと

制御文字の拒否は検証関数自体を対象とする tests/attach_rename.test.php で
カバー済みのため、HTTP では送らない。

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

OK = "添付成功"
ERR_EXT = "拡張子は変更できません"
ERR_CHAR = "ファイル名に使えない文字が含まれています"
ERR_LONG = "ファイル名が長すぎます。"


class Attach48HTTP(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        repo = pathlib.Path(__file__).resolve().parents[1]
        cls.tmp = tempfile.TemporaryDirectory(prefix="konawiki2-attach48-")
        cls.addClassCleanup(cls.tmp.cleanup)
        cls.root = pathlib.Path(cls.tmp.name)
        shutil.copy(repo / "index.php", cls.root)
        shutil.copytree(repo / "kona-engine", cls.root / "kona-engine",
                        ignore=shutil.ignore_patterns("*.zip", "__pycache__"))
        shutil.copytree(repo / "skin", cls.root / "skin")
        for name in ("data", "cache", "attach"):
            (cls.root / name).mkdir()
        # テンプレート既定の資格情報は無効のため、テスト用ユーザーを明示する
        config = (repo / "data/template-konawiki.ini.php").read_text() + """
$private['debug'] = TRUE;
$private['admin.key'] = 'attach48-admin-key';
$private['authusers'] = ['review' => 'review-pass'];
$private['users_perm'] = ['review' => ['read' => TRUE, 'write' => TRUE]];
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
            page="FrontPage", action="login", user="review",
            pw="review-pass", edit_token=self.token(login_page)))
        top = self.get(self.admin, "FrontPage")
        self.assertIn("review", top)  # ログインできていること

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

    def attach_files(self):
        return sorted(p.name for p in (self.root / "attach").iterdir())

    def upload(self, client, page, orig_name, content_bytes,
               comment=None, save_name=""):
        # アップロードにも edit_token が必須なので、添付フォームから取得する
        form = self.get(client, urllib.parse.quote(page) + "&attach")
        fields = [
            ("page", page), ("action", "attach"), ("stat", "write"),
            ("edit_token", self.token(form)), ("filename", save_name),
        ]
        if comment is not None:
            fields.append(("comment", comment))
        boundary = "----attach48" + uuid.uuid4().hex
        body = b""
        for key, value in fields:
            body += ("--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n"
                     % (boundary, key)).encode() + value.encode() + b"\r\n"
        ctype = "text/csv" if orig_name.endswith(".csv") else "text/plain"
        body += ("--%s\r\nContent-Disposition: form-data; name=\"userfile\"; "
                 "filename=\"%s\"\r\nContent-Type: %s\r\n\r\n"
                 % (boundary, orig_name, ctype)).encode()
        body += content_bytes + b"\r\n--%s--\r\n" % boundary.encode()
        req = urllib.request.Request(
            self.base, body,
            {"Content-Type": "multipart/form-data; boundary=" + boundary})
        try:
            return client.open(req, timeout=5).read().decode()
        except urllib.error.HTTPError as e:
            return e.read().decode()

    # ---- tests ----
    def test_01_valid_custom_name(self):
        self.page("Attach48Main", "#attachfiles\n")
        content = b"custom content"
        html = self.upload(self.admin, "Attach48Main", "memo.txt", content,
                           save_name="custom.txt")
        self.assertIn(OK, html)
        row = self.attach_row("custom.txt")
        self.assertIsNotNone(row)
        real = self.root / "attach" / ("%d.txt" % row[0])
        self.assertTrue(real.exists())
        self.assertEqual(real.read_bytes(), content)

    def test_02_default_name(self):
        self.page("Attach48Default", "#attachfiles\n")
        html = self.upload(self.admin, "Attach48Default", "plain.txt", b"x",
                           save_name="")
        self.assertIn(OK, html)
        self.assertIsNotNone(self.attach_row("plain.txt"))

    def test_03_extension_change_rejected(self):
        self.page("Attach48Ext", "#attachfiles\n")
        before = self.attach_files()
        for save_name in ("a.png", "a.TXT", "a"):
            with self.subTest(save_name=save_name):
                html = self.upload(self.admin, "Attach48Ext", "a.txt", b"x",
                                   save_name=save_name)
                self.assertNotIn(OK, html)
                self.assertIn(ERR_EXT, html)
                self.assertIsNone(self.attach_row(save_name))
        # 拒否時に実ファイルが残らないこと
        self.assertEqual(self.attach_files(), before)

    def test_04_bad_names_rejected(self):
        self.page("Attach48Bad", "#attachfiles\n")
        before = self.attach_files()
        names = [
            "../evil.txt", "sub/evil.txt", "sub\\evil.txt", 'bad".txt',
            "bad'.txt", ".", "..", "a" * 252 + ".txt",
        ]
        for save_name in names:
            with self.subTest(save_name=save_name):
                html = self.upload(self.admin, "Attach48Bad", "evil.txt", b"x",
                                   save_name=save_name)
                self.assertNotIn(OK, html)
                self.assertTrue(
                    any(m in html for m in (ERR_CHAR, ERR_LONG, ERR_EXT)),
                    "検証エラーの文言がない")
                self.assertIsNone(self.attach_row(save_name))
        self.assertEqual(self.attach_files(), before)

    def test_05_numbering_still_works(self):
        self.page("Attach48Dup", "#attachfiles\n")
        html = self.upload(self.admin, "Attach48Dup", "dup.txt", b"1",
                           save_name="")
        self.assertIn(OK, html)
        html = self.upload(self.admin, "Attach48Dup", "other.txt", b"2",
                           save_name="dup.txt")
        self.assertIn(OK, html)
        self.assertIsNotNone(self.attach_row("dup_2.txt"))

    def test_06_japanese_name_ok(self):
        self.page("Attach48Jp", "#attachfiles\n")
        html = self.upload(self.admin, "Attach48Jp", "orig.txt", b"jp",
                           save_name="日本語メモ.txt")
        self.assertIn(OK, html)
        self.assertIsNotNone(self.attach_row("日本語メモ.txt"))


if __name__ == "__main__":
    unittest.main()
