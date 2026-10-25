#!/usr/bin/env python3
"""Issue #10 の HTTP 回帰テスト。python3 tests/csrf10_http.py で実行。

comment / article / bodycomment / bodyvote / vote プラグインの
書き込みに CSRF トークン (edit_token) が必須であることを検証する。

- フォームにはセッション固有の edit_token が埋め込まれる (未ログインでも)
- トークンなし・誤トークン・他セッションのトークンでは書き込まれない
- 正しいトークンでは従来どおり書き込める
- ログイン中の編集 (comment/article の編集モード, vote のCSV編集) も同様
- edit_token に配列を渡してもエラーにならず拒否される

PHP (mbstring / SQLite) が必要。設定・DB・キャッシュは全て
一時ディレクトリ内で作成する。
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

CSRF_ERROR = "意図せぬ操作が行われました"


class Csrf10HTTP(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        repo = pathlib.Path(__file__).resolve().parents[1]
        cls.tmp = tempfile.TemporaryDirectory(prefix="konawiki2-csrf10-")
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

    def token(self, html):
        token = self.hidden(html, "edit_token")
        self.assertIsNotNone(token, "edit_token がフォームにない")
        self.assertRegex(token, r"^[0-9a-f]{64}$")
        return token

    def db(self, kind=""):
        return sqlite3.connect(self.root / ("data/konawiki%s.db" % kind))

    def page(self, name, body):
        with self.db() as db:
            return db.execute(
                "INSERT INTO logs(name,body,private,ctime,mtime)"
                " VALUES(?,?,0,1,1)", (name, body)).lastrowid

    def page_body(self, name):
        with self.db() as db:
            row = db.execute(
                "SELECT body FROM logs WHERE name=?", (name,)).fetchone()
        return row[0] if row else None

    def sublog_body(self, plug_name, page_name):
        with self.db() as main, self.db("_sub") as sub:
            row = main.execute(
                "SELECT id FROM logs WHERE name=?", (page_name,)).fetchone()
            if row is None:
                return None
            rows = sub.execute(
                "SELECT body FROM sublogs WHERE log_id=? AND plug_name=?",
                (row[0], plug_name)).fetchall()
        return rows[0][0] if rows else None

    def assert_blocked(self, html, before, after):
        self.assertIn(CSRF_ERROR, html)
        self.assertEqual(before, after)

    # ---- comment ----
    def test_comment_post_requires_token(self):
        self.page("CsrfComment", "#comment")
        html = self.get(self.anon, "CsrfComment")
        token = self.token(html)
        pid = self.hidden(html, "pid")
        base = dict(page="CsrfComment", plugin="comment", pid=pid,
                    name="", comment="", r_name="tester",
                    r_comment="CSRFコメント")
        # トークンなし
        res = self.post(self.anon, base)
        self.assert_blocked(res, None, self.sublog_body("comment", "CsrfComment"))
        # 誤トークン
        res = self.post(self.anon, dict(base, edit_token="wrong-token"))
        self.assert_blocked(res, None, self.sublog_body("comment", "CsrfComment"))
        # 他セッションのトークン (相手のセッションでは無効)
        other = self.client()
        self.get(other, "CsrfComment")  # 自分のセッション/トークンを確立
        res = self.post(other, dict(base, edit_token=token))
        self.assert_blocked(res, None, self.sublog_body("comment", "CsrfComment"))
        # 配列トークンでも落ちない
        res = self.post(self.anon, list(base.items()) + [("edit_token[]", "x")])
        self.assertNotIn("TypeError", res)
        self.assertIsNone(self.sublog_body("comment", "CsrfComment"))
        # 正しいトークンでは投稿できる
        self.post(self.anon, dict(base, edit_token=token))
        self.assertIn("CSRFコメント", self.sublog_body("comment", "CsrfComment"))

    def test_comment_edit_requires_token(self):
        self.page("CsrfCommentEdit", "#comment")
        html = self.get(self.anon, "CsrfCommentEdit")
        self.post(self.anon, dict(
            page="CsrfCommentEdit", plugin="comment",
            pid=self.hidden(html, "pid"), name="", comment="",
            r_name="tester", r_comment="元のコメント",
            edit_token=self.token(html)))
        before = self.sublog_body("comment", "CsrfCommentEdit")
        self.assertIn("元のコメント", before)
        # ログイン中の編集フォームにもトークンがある
        edit_html = self.get(self.admin, "CsrfCommentEdit&comment-mode=edit")
        token = self.token(edit_html)
        pid = self.hidden(edit_html, "pid")
        base = dict(page="CsrfCommentEdit", plugin="comment", pid=pid,
                    name="", comment="", body="書き換えたコメント",
                    **{"comment-mode": "edit"})
        res = self.post(self.admin, base)
        self.assert_blocked(res, before,
                            self.sublog_body("comment", "CsrfCommentEdit"))
        self.post(self.admin, dict(base, edit_token=token))
        self.assertEqual("書き換えたコメント",
                         self.sublog_body("comment", "CsrfCommentEdit"))

    # ---- article ----
    def test_article_post_and_edit_require_token(self):
        self.page("CsrfArticle", "#article")
        html = self.get(self.anon, "CsrfArticle")
        token = self.token(html)
        pid = self.hidden(html, "pid")
        base = dict(page="CsrfArticle", plugin="article", pid=pid,
                    name="", comment="", r_body1="", r_body3="",
                    r_name="tester", r_comment="CSRF件名",
                    r_body5="CSRF本文", conf="さくら")
        res = self.post(self.anon, base)
        self.assert_blocked(res, None, self.sublog_body("article", "CsrfArticle"))
        res = self.post(self.anon, dict(base, edit_token="wrong-token"))
        self.assert_blocked(res, None, self.sublog_body("article", "CsrfArticle"))
        self.post(self.anon, dict(base, edit_token=token))
        before = self.sublog_body("article", "CsrfArticle")
        self.assertIn("CSRF件名", before)
        # ログイン中の編集
        edit_html = self.get(self.admin, "CsrfArticle&article-mode=edit")
        token = self.token(edit_html)
        pid = self.hidden(edit_html, "pid")
        base = dict(page="CsrfArticle", plugin="article", pid=pid,
                    name="", comment="", body="編集済みBBS",
                    conf="さくら", **{"article-mode": "edit"})
        res = self.post(self.admin, base)
        self.assert_blocked(res, before,
                            self.sublog_body("article", "CsrfArticle"))
        self.post(self.admin, dict(base, edit_token=token))
        self.assertEqual("編集済みBBS",
                         self.sublog_body("article", "CsrfArticle"))

    def test_token_is_stable_across_renders(self):
        # 描画のたびにトークンが変わると、先に開いたタブのフォームや
        # 同一ページの複数フォームが使えなくなるため、再利用されること
        self.page("CsrfTokenStable", "#comment")
        t1 = self.token(self.get(self.anon, "CsrfTokenStable"))
        t2 = self.token(self.get(self.anon, "CsrfTokenStable"))
        self.assertEqual(t1, t2)

    # ---- bodycomment ----
    def test_bodycomment_requires_token(self):
        self.page("CsrfBodyComment", "本文です\n#bodycomment")
        html = self.get(self.anon, "CsrfBodyComment")
        token = self.token(html)
        pid = self.hidden(html, "pid")
        base = dict(page="CsrfBodyComment", plugin="bodycomment", pid=pid,
                    name="", comment="", r_name="tester",
                    r_comment="本文コメント")
        before = self.page_body("CsrfBodyComment")
        res = self.post(self.anon, base)
        self.assert_blocked(res, before, self.page_body("CsrfBodyComment"))
        res = self.post(self.anon, dict(base, edit_token="wrong-token"))
        self.assert_blocked(res, before, self.page_body("CsrfBodyComment"))
        self.post(self.anon, dict(base, edit_token=token))
        self.assertIn("本文コメント", self.page_body("CsrfBodyComment"))

    # ---- vote ----
    def test_vote_and_csv_edit_require_token(self):
        self.page("CsrfVote", "#vote(好きな色,赤,青)")
        html = self.get(self.anon, "CsrfVote")
        token = self.token(html)
        base = dict(page="CsrfVote", plugin="vote", vote_id="好きな色",
                    **{"vote-mode": "write", "vote-param": "0"})
        res = self.post(self.anon, base)
        self.assert_blocked(res, None, self.sublog_body("vote", "CsrfVote"))
        res = self.post(self.anon, dict(base, edit_token="wrong-token"))
        self.assert_blocked(res, None, self.sublog_body("vote", "CsrfVote"))
        self.post(self.anon, dict(base, edit_token=token))
        self.assertIn("赤,1", self.sublog_body("vote", "CsrfVote"))
        # ログイン中のCSV編集
        edit_html = self.get(self.admin, "CsrfVote&vote-mode=edit")
        token = self.token(edit_html)
        base = dict(page="CsrfVote", plugin="vote", vote_id="好きな色",
                    csv="赤,99\n青,5", **{"vote-mode": "write-csv"})
        before = self.sublog_body("vote", "CsrfVote")
        res = self.post(self.admin, base)
        self.assert_blocked(res, before, self.sublog_body("vote", "CsrfVote"))
        self.post(self.admin, dict(base, edit_token=token))
        self.assertIn("赤,99", self.sublog_body("vote", "CsrfVote"))

    # ---- bodyvote ----
    def test_bodyvote_requires_token(self):
        self.page("CsrfBodyVote", "#bodyvote(青[0],白[0])")
        html = self.get(self.anon, "CsrfBodyVote")
        token = self.token(html)
        pid = self.hidden(html, "pid")
        button = "vote" + hashlib.md5("青".encode()).hexdigest()
        base = dict(page="CsrfBodyVote", plugin="bodyvote", pid=pid,
                    **{button: "投票"})
        before = self.page_body("CsrfBodyVote")
        res = self.post(self.anon, base)
        self.assert_blocked(res, before, self.page_body("CsrfBodyVote"))
        res = self.post(self.anon, dict(base, edit_token="wrong-token"))
        self.assert_blocked(res, before, self.page_body("CsrfBodyVote"))
        self.post(self.anon, dict(base, edit_token=token))
        self.assertIn("青[1]", self.page_body("CsrfBodyVote"))


if __name__ == "__main__":
    unittest.main(verbosity=2)
