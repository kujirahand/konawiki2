#!/usr/bin/env python3
"""Issue #35 の HTTP 回帰テスト。python3 tests/newpage35_http.py で実行。

新規ページ作成時に PHP 8.1+ の Deprecated 警告
(Automatic conversion of false to array is deprecated) が出て、
display_errors=On の環境で保存フローが壊れる問題を検証する。

- api__write (自動保存): レスポンスが "ok" で始まり、新しい hash が返ること
- フォーム保存 (stat=update): 警告でヘッダ送信が失敗せず、
  保存後のページへリダイレクトされること
- 既存ページの更新では従来どおり保存できること

PHP サーバーは display_errors=1 / error_reporting=E_ALL で起動する
(警告がレスポンスに混入する条件を再現するため)。
設定・DB・キャッシュは全て一時ディレクトリ内で作成する。
"""
import hashlib
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
import urllib.parse
import urllib.request


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class NewPage35HTTP(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        repo = pathlib.Path(__file__).resolve().parents[1]
        cls.tmp = tempfile.TemporaryDirectory(prefix="konawiki2-newpage35-")
        cls.addClassCleanup(cls.tmp.cleanup)
        cls.root = pathlib.Path(cls.tmp.name)
        shutil.copy(repo / "index.php", cls.root)
        shutil.copytree(repo / "kona-engine", cls.root / "kona-engine",
                        ignore=shutil.ignore_patterns("*.zip", "__pycache__"))
        shutil.copytree(repo / "skin", cls.root / "skin")
        for name in ("data", "cache", "attach"):
            (cls.root / name).mkdir()
        config = (repo / "data/template-konawiki.ini.php").read_text() + """
$private['debug'] = TRUE;
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
            ["php", "-d", "display_errors=1", "-d", "error_reporting=E_ALL",
             "-S", "127.0.0.1:%d" % port, "-t", str(cls.root)],
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
    def client(no_redirect=False):
        handlers = [urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())]
        if no_redirect:
            handlers.append(NoRedirect())
        return urllib.request.build_opener(*handlers)

    @classmethod
    def get(cls, client, query):
        return client.open(cls.base + "?" + query, timeout=5).read().decode()

    @classmethod
    def post(cls, client, fields):
        data = urllib.parse.urlencode(fields).encode()
        return client.open(cls.base, data, timeout=5).read().decode()

    @classmethod
    def post_no_redirect(cls, client, fields):
        data = urllib.parse.urlencode(fields).encode()
        try:
            resp = client.open(cls.base, data, timeout=5)
            return resp.status, resp.headers, resp.read().decode()
        except urllib.error.HTTPError as e:
            return e.code, e.headers, e.read().decode()

    def setUp(self):
        self.admin = self.client()
        login_page = self.get(self.admin, "FrontPage&login")
        self.post(self.admin, dict(
            page="FrontPage", action="login", user="review",
            pw="review-pass", edit_token=self.hidden(login_page, "edit_token")))
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

    def editor(self, client, page):
        """編集画面を開いて (login_auth_hash, hash, id) を返す。

        編集画面は edit_token なしではリンク画面になるため、
        リンクをたどって実際のエディタを開く。
        """
        html = self.get(client, "%s&edit" % urllib.parse.quote(page))
        if 'id="body_txt"' not in html:
            m = re.search(r'href="([^"]*edit_token=[0-9a-f]+[^"]*)"', html)
            self.assertIsNotNone(m, "編集リンクが見つからない: %s" % page)
            url = m.group(1).replace("&amp;", "&")
            if url.startswith("?"):
                url = self.base + url
            elif not url.startswith("http"):
                url = self.base + "?" + url
            html = client.open(url, timeout=5).read().decode()
        self.assertIn('id="body_txt"', html, "エディタが開けない: %s" % page)
        self.assertNotIn("Deprecated", html)
        return (self.hidden(html, "login_auth_hash"),
                self.hidden(html, "hash") or "",
                self.hidden(html, "id") or "")

    def db(self, kind=""):
        return sqlite3.connect(self.root / ("data/konawiki%s.db" % kind))

    def page_body(self, name):
        with self.db() as db:
            row = db.execute(
                "SELECT body FROM logs WHERE name=?", (name,)).fetchone()
        return row[0] if row else None

    def assert_no_deprecated(self, text):
        self.assertNotIn("Deprecated", text)
        self.assertNotIn("Automatic conversion of false to array", text)
        self.assertNotIn("Cannot modify header information", text)

    # ---- tests ----
    def test_api_write_creates_new_page_without_deprecated(self):
        page = "NewPage35Api"
        self.assertIsNone(self.page_body(page))
        auth_hash, form_hash, page_id = self.editor(self.admin, page)
        body = "api__write で作る新規ページ"
        res = self.post(self.admin, dict(
            page=page, action="edit", stat="api__write", id=page_id,
            body=body, tag="", hash=form_hash, login_auth_hash=auth_hash))
        self.assert_no_deprecated(res)
        lines = res.split("\n")
        self.assertEqual("ok", lines[0],
                         "レスポンスが ok で始まらない: %r" % res[:200])
        self.assertEqual(hashlib.md5(body.encode()).hexdigest(), lines[1])
        self.assertEqual(body, self.page_body(page))

    def test_form_update_creates_new_page_and_redirects(self):
        page = "NewPage35Form"
        self.assertIsNone(self.page_body(page))
        body = "フォーム保存で作る新規ページ"
        # status/Location を直接確認するため、リダイレクトを抑止した
        # クライアントでログインし直して編集画面を開く
        client = self.client(no_redirect=True)
        login_page = self.get(client, "FrontPage&login")
        self.post(client, dict(
            page="FrontPage", action="login", user="review",
            pw="review-pass", edit_token=self.hidden(login_page, "edit_token")))
        auth_hash, form_hash, page_id = self.editor(client, page)
        status, headers, res = self.post_no_redirect(client, dict(
            page=page, action="edit", stat="update", id=page_id,
            body=body, tag="", hash=form_hash, login_auth_hash=auth_hash))
        self.assert_no_deprecated(res)
        self.assertIn(status, (301, 302, 303, 307, 308),
                      "保存後にリダイレクトされない: status=%s body=%r"
                      % (status, res[:200]))
        location = headers.get("Location") or ""
        self.assertIn(page, location)
        self.assertEqual(body, self.page_body(page))
        # リダイレクト先で本文が見えること
        shown = self.get(self.admin, page)
        self.assert_no_deprecated(shown)
        self.assertIn(body, shown)

    def test_api_write_updates_existing_page(self):
        page = "NewPage35Existing"
        auth_hash, form_hash, page_id = self.editor(self.admin, page)
        body1 = "1回目の本文"
        res = self.post(self.admin, dict(
            page=page, action="edit", stat="api__write", id=page_id,
            body=body1, tag="", hash=form_hash, login_auth_hash=auth_hash))
        self.assertEqual("ok", res.split("\n")[0])
        # 既存ページの更新 (hash は保存後のものを使う)
        body2 = "2回目の本文"
        res = self.post(self.admin, dict(
            page=page, action="edit", stat="api__write", id=page_id,
            body=body2, tag="",
            hash=hashlib.md5(body1.encode()).hexdigest(),
            login_auth_hash=auth_hash))
        self.assert_no_deprecated(res)
        self.assertEqual("ok", res.split("\n")[0])
        self.assertEqual(body2, self.page_body(page))


if __name__ == "__main__":
    unittest.main(verbosity=2)
