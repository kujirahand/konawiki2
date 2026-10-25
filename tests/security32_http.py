#!/usr/bin/env python3
"""Issue #32 の HTTP 回帰テスト。python3 tests/security32_http.py で実行。

PHP (mbstring / SQLite) と初期化済み kona-engine/fw_simple が必要。
設定・DB・添付・キャッシュは全て一時ディレクトリ内で作成する。
"""
import hashlib
import http.client
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


def php_hash(password):
    out = subprocess.run(
        ["php", "-r",
         "echo password_hash($argv[1], PASSWORD_DEFAULT);", password],
        capture_output=True, text=True, check=True)
    return out.stdout.strip()


def start_server(root):
    with socket.socket() as sock:
        sock.bind(("127.0.0.1", 0))
        port = sock.getsockname()[1]
    log = tempfile.TemporaryFile()
    server = subprocess.Popen(
        ["php", "-S", "127.0.0.1:%d" % port, "-t", str(root)],
        stdout=log, stderr=log)
    base = "http://127.0.0.1:%d/index.php" % port
    opener = urllib.request.build_opener(
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    for _ in range(100):
        try:
            opener.open(base + "?FrontPage", timeout=5).read()
            break
        except (OSError, urllib.error.URLError):
            time.sleep(0.05)
    else:
        server.terminate()
        raise RuntimeError("PHP server did not start")
    return server, base, port


def build_root(repo):
    tmp = tempfile.TemporaryDirectory(prefix="konawiki2-security32-")
    root = pathlib.Path(tmp.name)
    shutil.copy(repo / "index.php", root)
    shutil.copy(repo / "go.php", root)
    shutil.copytree(repo / "kona-engine", root / "kona-engine",
                    ignore=shutil.ignore_patterns("*.zip", "__pycache__"))
    shutil.copytree(repo / "skin", root / "skin")
    for name in ("data", "cache", "attach"):
        (root / name).mkdir()
    shutil.copy(repo / "data/template-konawiki.ini.php",
                root / "data/template-konawiki.ini.php")
    return tmp, root


class Security32HTTP(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        repo = pathlib.Path(__file__).resolve().parents[1]
        cls.repo = repo
        cls.tmp, cls.root = build_root(repo)
        cls.addClassCleanup(cls.tmp.cleanup)
        # 添付ディレクトリには glob メタ文字を含む名前を使う (#32)
        cls.attach_dir = cls.root / "at[t]ach"
        cls.attach_dir.mkdir()
        cls.admin_hash = php_hash("admin-secret-32")
        cls.user_hash = php_hash("hash-pass")
        sha1 = hashlib.sha1("sha1-pass".encode()).hexdigest()
        cls.config = (repo / "data/template-konawiki.ini.php").read_text()
        cls.config += """
$private['admin.key'] = '{password_hash}%s';
$private['authusers'] = [
  'review'   => 'review-pass',
  'hashuser' => '{password_hash}%s',
  'sha1user' => '{x-php-sha1}%s',
];
$private['users_perm'] = [
  'review'   => ['read' => TRUE, 'write' => TRUE],
  'hashuser' => ['read' => TRUE, 'write' => TRUE],
  'sha1user' => ['read' => TRUE, 'write' => TRUE],
];
$private['dir.attach'] = dirname(__DIR__) . '/at[t]ach';
""" % (cls.admin_hash, cls.user_hash, sha1)
        cls.configure()
        cls.server, cls.base, cls.port = start_server(cls.root)
        cls.addClassCleanup(cls.stop_server)

    @classmethod
    def stop_server(cls):
        cls.server.terminate()
        cls.server.wait(timeout=5)

    @classmethod
    def configure(cls, extra=""):
        (cls.root / "data/konawiki.ini.php").write_text(cls.config + extra)

    @staticmethod
    def client():
        return urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    @classmethod
    def get(cls, client, query):
        return client.open(cls.base + "?" + query, timeout=5).read().decode()

    @classmethod
    def post(cls, client, fields):
        data = urllib.parse.urlencode(fields).encode()
        return client.open(cls.base, data, timeout=5).read().decode()

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
        return token

    def login(self, client, user, pw):
        login_page = self.get(client, "FrontPage&login")
        self.post(client, dict(page="FrontPage", action="login", user=user,
                               pw=pw, edit_token=self.token(login_page)))
        return self.get(client, "FrontPage")

    def setUp(self):
        self.configure()
        self.anon = self.client()
        self.admin = self.client()
        top = self.login(self.admin, "review", "review-pass")
        self.assertIn("review", top)

    # ---- DB helpers ----
    def db(self, kind=""):
        return sqlite3.connect(self.root / ("data/konawiki%s.db" % kind))

    def page(self, name, body, private=0, freeze=0):
        with self.db() as db:
            return db.execute(
                "INSERT INTO logs(name,body,private,freeze,ctime,mtime)"
                " VALUES(?,?,?,?,1,1)",
                (name, body, private, freeze)).lastrowid

    def page_body(self, name):
        with self.db() as db:
            row = db.execute("SELECT body FROM logs WHERE name=?",
                             (name,)).fetchone()
        return row[0] if row else None

    def page_freeze(self, name):
        with self.db() as db:
            row = db.execute("SELECT freeze FROM logs WHERE name=?",
                             (name,)).fetchone()
        return row[0] if row else None

    # ---- 認証: password_hash / x-php-sha1 ----
    def test_password_hash_login(self):
        client = self.client()
        top = self.login(client, "hashuser", "hash-pass")
        self.assertIn("hashuser", top)
        bad = self.client()
        top = self.login(bad, "hashuser", "wrong-pass")
        self.assertNotIn("hashuser", top)

    def test_xphp_sha1_login(self):
        client = self.client()
        top = self.login(client, "sha1user", "sha1-pass")
        self.assertIn("sha1user", top)

    def test_default_pair_rejected(self):
        # 旧テンプレートの既定ペア username/password では入れない
        self.configure(
            "\n$private['authusers'] = ['username' => 'password'];"
            "\n$private['users_perm'] = ['username' =>"
            " ['read' => TRUE, 'write' => TRUE]];\n")
        client = self.client()
        top = self.login(client, "username", "password")
        self.assertNotIn("username", top)

    # ---- 管理キー ----
    def test_admin_key_password_hash(self):
        self.page("FreezeHash", "freeze target")
        # 間違ったキーでは凍結できない
        self.post(self.admin, dict(page="FreezeHash", action="freeze",
                                   **{"pass": "wrong-key"}))
        self.assertEqual(0, self.page_freeze("FreezeHash"))
        # {password_hash} 形式のキーで凍結できる
        self.post(self.admin, dict(page="FreezeHash", action="freeze",
                                   **{"pass": "admin-secret-32"}))
        self.assertEqual(1, self.page_freeze("FreezeHash"))

    def test_admin_key_default_and_empty_rejected(self):
        self.page("FreezeDefault", "freeze target")
        for extra, attempt in (
                ("\n$private['admin.key'] = 'konawiki';\n", "konawiki"),
                ("\n$private['admin.key'] = '';\n", "anything")):
            self.configure(extra)
            client = self.client()
            self.login(client, "review", "review-pass")
            self.post(client, dict(page="FreezeDefault", action="freeze",
                                   **{"pass": attempt}))
            self.assertEqual(0, self.page_freeze("FreezeDefault"),
                             "admin.key=%r で凍結できてしまった" % extra)

    # ---- meta_table: 新規作成は POST + トークン必須 ----
    def test_meta_table_create_requires_post_and_token(self):
        body = ('{{{#meta_table\n'
                '{"name": "物件", "fields": ["価格"]}\n'
                '}}}')
        self.page("MetaHost", body)
        # GET では作成されない
        self.get(self.admin, "MetaHost&m=edit&mname=BadGet&m2=new")
        self.assertIsNone(self.page_body("物件/BadGet"))
        # トークンなし POST では作成されない
        self.post(self.admin, dict(page="MetaHost", action="show",
                                   m="edit", mname="BadPost", m2="new"))
        self.assertIsNone(self.page_body("物件/BadPost"))
        # 追加フォームのトークン付き POST では作成される
        add = self.get(self.admin, "MetaHost&m=add")
        token = self.token(add)
        self.post(self.admin, dict(page="MetaHost", action="show",
                                   m="edit", mname="GoodPost", m2="new",
                                   edit_token=token))
        self.assertIsNotNone(self.page_body("物件/GoodPost"))

    # ---- freeze 中の bodyvote / bodycomment ----
    def test_bodyvote_frozen(self):
        self.page("FrozenVote", "#bodyvote(青[0],白[0])", freeze=1)
        html = self.get(self.anon, "FrozenVote")
        token = self.token(html)
        pid = self.hidden(html, "pid")
        button = "vote" + hashlib.md5("青".encode()).hexdigest()
        self.post(self.anon, dict(page="FrozenVote", plugin="bodyvote",
                                  pid=pid, edit_token=token,
                                  **{button: "投票"}))
        self.assertIn("青[0]", self.page_body("FrozenVote"))
        # 凍結していないページでは投票できる (対照)
        self.page("OpenVote", "#bodyvote(青[0],白[0])")
        html = self.get(self.anon, "OpenVote")
        token = self.token(html)
        pid = self.hidden(html, "pid")
        self.post(self.anon, dict(page="OpenVote", plugin="bodyvote",
                                  pid=pid, edit_token=token,
                                  **{button: "投票"}))
        self.assertIn("青[1]", self.page_body("OpenVote"))

    def test_bodycomment_frozen(self):
        self.page("FrozenComment", "本文です\n#bodycomment", freeze=1)
        html = self.get(self.anon, "FrozenComment")
        token = self.token(html)
        pid = self.hidden(html, "pid")
        self.post(self.anon, dict(page="FrozenComment", plugin="bodycomment",
                                  pid=pid, edit_token=token, name="",
                                  comment="", r_name="tester",
                                  r_comment="凍結中コメント"))
        self.assertNotIn("凍結中コメント", self.page_body("FrozenComment"))
        # 凍結していないページでは書ける (対照)
        self.page("OpenComment", "本文です\n#bodycomment")
        html = self.get(self.anon, "OpenComment")
        token = self.token(html)
        pid = self.hidden(html, "pid")
        self.post(self.anon, dict(page="OpenComment", plugin="bodycomment",
                                  pid=pid, edit_token=token, name="",
                                  comment="", r_name="tester",
                                  r_comment="通常コメント"))
        self.assertIn("通常コメント", self.page_body("OpenComment"))

    # ---- plugin アクション ----
    def test_plugin_action_meta_table_denied(self):
        res = self.get(self.admin, "FrontPage&plugin&name=meta_table&p=x")
        self.assertIn("error (denied)", res)
        res = self.get(self.admin, "FrontPage&plugin&name=html&p=x")
        self.assertIn("error (disabled)", res)

    # ---- Host ヘッダ ----
    def raw_get(self, host):
        conn = http.client.HTTPConnection("127.0.0.1", self.port, timeout=5)
        conn.request("GET", "/index.php?FrontPage", headers={"Host": host})
        res = conn.getresponse()
        body = res.read().decode()
        conn.close()
        return body

    def test_host_header_validation(self):
        body = self.raw_get("evil.example.com\"><script>alert(1)</script>")
        self.assertNotIn("evil.example.com", body)
        self.assertNotIn("<script>alert(1)</script>", body)
        # 正当なホスト名は従来どおり使える
        body = self.raw_get("good.example.com")
        self.assertIn("good.example.com", body)

    # ---- 旧キャッシュ行の掃除 ----
    def test_old_cache_row_deleted(self):
        self.configure("\n$private['cache.mode'] = 'cache';\n")
        pid = self.page("DynCache", "#comment")
        with self.db("_backup") as db:
            db.execute(
                "INSERT INTO cache_logs(log_id,html,ctime) VALUES(?,?,1)",
                (pid, "OLD-FORMAT-CACHE"))
        self.get(self.anon, "DynCache")
        with self.db("_backup") as db:
            count = db.execute(
                "SELECT count(*) FROM cache_logs WHERE log_id=?",
                (pid,)).fetchone()[0]
        self.assertEqual(0, count)

    # ---- glob メタ文字を含む添付ディレクトリ ----
    def test_attach_delete_in_glob_dir(self):
        self.page("AttachGlob", "attachment test")
        boundary = "konawiki2-security32-boundary"
        token = self.token(self.get(self.admin, "AttachGlob&attach"))
        fields = dict(page="AttachGlob", action="attach", stat="write",
                      filename="globfile", edit_token=token)
        parts = []
        for key, value in fields.items():
            parts.append(('--%s\r\nContent-Disposition: form-data; name="%s"'
                          '\r\n\r\n%s\r\n' % (boundary, key, value)).encode())
        parts.append(('--%s\r\nContent-Disposition: form-data; name="userfile"; '
                      'filename="one.png"\r\nContent-Type: image/png\r\n\r\n'
                      % boundary).encode() + b"glob-file\r\n")
        parts.append(("--%s--\r\n" % boundary).encode())
        request = urllib.request.Request(
            self.base, data=b"".join(parts),
            headers={"Content-Type":
                     "multipart/form-data; boundary=" + boundary})
        res = self.admin.open(request, timeout=5).read().decode()
        self.assertIn("添付成功", res)
        with self.db() as db:
            aid = db.execute("SELECT id FROM attach WHERE name='globfile'"
                             ).fetchone()[0]
        real = self.attach_dir / ("%d.png" % aid)
        self.assertTrue(real.exists())
        token = self.token(self.get(self.admin, "AttachGlob&attach"))
        self.get(self.admin,
                 "AttachGlob&attach&delete&id=%d&edit_token=%s" % (aid, token))
        self.assertFalse(real.exists(),
                         "glob メタ文字を含むディレクトリで実ファイルが残った")


class Security32SetupHTTP(unittest.TestCase):
    """未設定 (テンプレートそのまま) ではセットアップ案内になること"""

    @classmethod
    def setUpClass(cls):
        repo = pathlib.Path(__file__).resolve().parents[1]
        cls.tmp, cls.root = build_root(repo)
        cls.addClassCleanup(cls.tmp.cleanup)
        # data/konawiki.ini.php は置かない (初回起動でコピーされる)
        cls.server, cls.base, cls.port = start_server(cls.root)
        cls.addClassCleanup(cls.stop_server)

    @classmethod
    def stop_server(cls):
        cls.server.terminate()
        cls.server.wait(timeout=5)

    def test_setup_page_on_first_run(self):
        opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        body = opener.open(self.base + "?FrontPage",
                           timeout=5).read().decode()
        self.assertIn("セットアップが必要です", body)
        self.assertIn("konawiki.ini.php", body)
        # テンプレートが実設定にコピーされている
        self.assertTrue((self.root / "data/konawiki.ini.php").exists())
        # 既定ペアでのログイン試行もセットアップ案内のまま (到達しない)
        data = urllib.parse.urlencode(
            dict(page="FrontPage", action="login", user="username",
                 pw="password", edit_token="x")).encode()
        body = opener.open(self.base, data, timeout=5).read().decode()
        self.assertIn("セットアップが必要です", body)


if __name__ == "__main__":
    unittest.main(verbosity=2)
