#!/usr/bin/env python3
"""Issue #55 の HTTP 回帰テスト。python3 tests/security55_http.py で実行。

検証内容:
  1. #commentallpage が非公開ページのコメントを表示しない
  2. #blognavi の前後ページリンクに非公開ページ名が現れない
  3. go アクションが権限のないユーザーに非公開ページ名を返さない

PHP (mbstring / SQLite) が必要。
設定・DB・キャッシュは全て一時ディレクトリ内で作成する。
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


class NoRedirect(urllib.request.HTTPRedirectHandler):
    """リダイレクトを追わず、Location ヘッダーを読めるようにする"""
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class Security55HTTP(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        repo = pathlib.Path(__file__).resolve().parents[1]
        cls.tmp = tempfile.TemporaryDirectory(prefix="konawiki2-security55-")
        cls.addClassCleanup(cls.tmp.cleanup)
        cls.root = pathlib.Path(cls.tmp.name)
        shutil.copy(repo / "index.php", cls.root)
        shutil.copy(repo / "go.php", cls.root)
        shutil.copytree(repo / "kona-engine", cls.root / "kona-engine",
                        ignore=shutil.ignore_patterns("*.zip", "__pycache__"))
        shutil.copytree(repo / "skin", cls.root / "skin")
        for name in ("data", "cache", "attach"):
            (cls.root / name).mkdir()
        config = (repo / "data/template-konawiki.ini.php").read_text() + """
$private['debug'] = TRUE;
$private['admin.key'] = 'security55-admin-key';
$private['authusers'] = ['review' => 'review-pass'];
$private['users_perm'] = ['review' => ['read' => TRUE, 'write' => TRUE]];
"""
        (cls.root / "data/konawiki.ini.php").write_text(config)
        with socket.socket() as sock:
            sock.bind(("127.0.0.1", 0))
            port = sock.getsockname()[1]
        cls.base = "http://127.0.0.1:%d/index.php" % port
        cls.go_url = "http://127.0.0.1:%d/go.php" % port
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
        return client.open(cls.base, urllib.parse.urlencode(fields).encode(),
                           timeout=5).read().decode()

    def setUp(self):
        self.anon = self.client()
        self.admin = self.client()
        token = self.token(self.get(self.admin, "FrontPage&login"))
        self.post(self.admin, dict(page="FrontPage", action="login",
                                  user="review", pw="review-pass", edit_token=token))
        self.assertIn("review", self.get(self.admin, "FrontPage"))

    def token(self, html):
        match = re.search(r'name="edit_token" value="([^"]*)"', html)
        self.assertIsNotNone(match)
        return match[1]

    def db(self, kind=""):
        return sqlite3.connect(self.root / ("data/konawiki%s.db" % kind))

    def page(self, name, body, private=0):
        with self.db() as db:
            return db.execute(
                "INSERT INTO logs(name,body,private,ctime,mtime) VALUES(?,?,?,1,1)",
                (name, body, private)).lastrowid

    def comment(self, log_id, body, mtime):
        db = self.db("_sub")
        try:
            with db:
                db.execute(
                    "INSERT INTO sublogs(log_id,plug_name,plug_key,body,ctime,mtime)"
                    " VALUES(?,'comment','1',?,?,?)",
                    (log_id, body, mtime, mtime))
        finally:
            db.close()

    def go_location(self, client, query):
        """go.php を開き、リダイレクト先 (Location) と本文を返す"""
        opener = urllib.request.build_opener(
            NoRedirect, *[h for h in client.handlers
                          if isinstance(h, urllib.request.HTTPCookieProcessor)])
        try:
            res = opener.open(self.go_url + "?" + query, timeout=5)
            return res.headers.get("Location", ""), res.read().decode()
        except urllib.error.HTTPError as e:
            return e.headers.get("Location", ""), e.read().decode()

    def test_01_commentallpage_hides_private(self):
        secret = self.page("Secret55", "secret", private=1)
        public = self.page("Public55", "public")
        self.page("AllComments55", "#commentallpage")
        # sub DB のテーブルを作るため、先に一覧ページを1回表示する
        self.get(self.anon, "AllComments55")
        self.comment(secret, "SECRET-COMMENT-55", 100)
        self.comment(public, "PUBLIC-COMMENT-55", 101)
        html = self.get(self.anon, "AllComments55")
        self.assertNotIn("SECRET-COMMENT-55", html)
        self.assertNotIn("Secret55", html)
        self.assertIn("PUBLIC-COMMENT-55", html)
        self.assertIn("Public55", html)

    def test_02_blognavi_hides_private(self):
        self.page("Blog55/A", "a")
        self.page("Blog55/B", "#blognavi(Blog55*)")
        self.page("Blog55/Secret", "secret", private=1)
        html = self.get(self.anon, urllib.parse.quote("Blog55/B"))
        self.assertNotIn("Blog55%2FSecret", html)
        self.assertNotIn("Blog55/Secret", html)
        self.assertTrue("Blog55%2FA" in html or "Blog55/A" in html)

    def test_03_go_hides_private(self):
        secret = self.page("GoSecret55", "secret", private=1)
        public = self.page("GoPublic55", "public")
        loc, body = self.go_location(self.anon, str(secret))
        self.assertNotIn("GoSecret55", loc)
        self.assertNotIn("GoSecret55", body)
        self.assertIn("index.php", loc)
        loc, _ = self.go_location(self.anon, str(public))
        self.assertIn("GoPublic55", loc)
        loc, _ = self.go_location(self.anon, "999999")
        self.assertNotIn("GoPublic55", loc)
        self.assertNotIn("GoSecret55", loc)
        loc, _ = self.go_location(self.admin, str(secret))
        self.assertIn("GoSecret55", loc)


if __name__ == "__main__":
    unittest.main()
