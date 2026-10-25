#!/usr/bin/env python3
"""PR #33 の HTTP 回帰テスト。python3 tests/security31_http.py で実行。

PHP (mbstring / SQLite) と初期化済み kona-engine/fw_simple が必要。
設定・DB・添付・キャッシュは全て一時ディレクトリ内で作成する。
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


class Security31HTTP(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        repo = pathlib.Path(__file__).resolve().parents[1]
        cls.tmp = tempfile.TemporaryDirectory(prefix="konawiki2-security31-")
        cls.addClassCleanup(cls.tmp.cleanup)
        cls.root = pathlib.Path(cls.tmp.name)
        shutil.copy(repo / "index.php", cls.root)
        shutil.copytree(repo / "kona-engine", cls.root / "kona-engine",
                        ignore=shutil.ignore_patterns("*.zip", "__pycache__"))
        shutil.copytree(repo / "skin", cls.root / "skin")
        for name in ("data", "cache", "attach"):
            (cls.root / name).mkdir()
        cls.config = (repo / "data/template-konawiki.ini.php").read_text() + """
$private['debug'] = TRUE;
$private['cache.mode'] = 'cache';
$public['wikilink.desc.visible'] = TRUE;
$private['authusers'] = ['review' => 'review-pass'];
$private['users_perm'] = ['review' => ['read' => TRUE, 'write' => TRUE]];
"""
        cls.configure()
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
        return client.open(cls.base, urllib.parse.urlencode(fields).encode(),
                           timeout=5).read().decode()

    def setUp(self):
        self.configure()
        self.anon = self.client()
        self.admin = self.client()
        token = self.token(self.get(self.admin, "FrontPage&login"))
        self.post(self.admin, dict(page="FrontPage", action="login",
                                  user="review", pw="review-pass", edit_token=token))

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

    def upload(self, client, page, filename, original="original.png", token=None):
        if token is None:
            token = self.token(self.get(client, page + "&attach"))
        boundary = "konawiki2-review-boundary"
        fields = dict(page=page, action="attach", stat="write", filename=filename)
        if token is not False:
            fields["edit_token"] = token
        parts = []
        for key, value in fields.items():
            parts.append(('--%s\r\nContent-Disposition: form-data; name="%s"'
                          '\r\n\r\n%s\r\n' % (boundary, key, value)).encode())
        parts.append(('--%s\r\nContent-Disposition: form-data; name="userfile"; '
                      'filename="%s"\r\nContent-Type: image/png\r\n\r\n'
                      % (boundary, original)).encode() + b"review-file\r\n")
        parts.append(("--%s--\r\n" % boundary).encode())
        request = urllib.request.Request(self.base, data=b"".join(parts), headers={
            "Content-Type": "multipart/form-data; boundary=" + boundary})
        return client.open(request, timeout=5).read().decode()

    def attach_legacy(self, log_id, name, content=b"review-file"):
        """拡張子が食い違う添付行を DB 直接作成で再現する (Issue #48)。

        Issue #48 以降、アップロードでは元ファイルと拡張子の異なる
        (拡張子なしを含む) 名前を作れなくなったため、過去に作られた
        行はこの方法で用意する。実ファイルは元ファイルの拡張子で
        保存される形式 ({id}.png) を再現する。
        """
        with self.db() as db:
            aid = db.execute(
                "INSERT INTO attach(log_id,name,ext,ctime,mtime)"
                " VALUES(?,?,?,1,1)", (log_id, name, "image/png")).lastrowid
            db.execute("INSERT INTO attach_counters(id) VALUES(?)", (aid,))
        (self.root / ("attach/%d.png" % aid)).write_bytes(content)
        return aid

    def test_private_description_cache(self):
        self.page("CacheSecret", "SECRET-CACHE-33", 1)
        pid = self.page("CachePublic", "[[[CacheSecret]]]")
        with self.db() as db:
            secret_id = db.execute("SELECT id FROM logs WHERE name='CacheSecret'").fetchone()[0]
            db.execute("INSERT INTO tags(log_id,tag) VALUES(?,?)", (secret_id, "SECRET-TAG-33"))
        self.assertIn("SECRET-CACHE-33", self.get(self.admin, "CachePublic"))
        public = self.get(self.anon, "CachePublic")
        self.assertNotIn("SECRET-CACHE-33", public)
        self.assertNotIn("SECRET-TAG-33", public)
        self.assertIn("SECRET-CACHE-33", self.get(self.admin, "CachePublic"))
        with self.db("_backup") as db:
            self.assertEqual(db.execute("SELECT count(*) FROM cache_logs WHERE log_id=?",
                                        (pid,)).fetchone()[0], 0)
            # 修正前に生成された、本文抜粋を含む共有キャッシュも拒否する。
            db.execute("INSERT INTO cache_logs(log_id,html,ctime) VALUES(?,?,1)",
                       (pid, "SECRET-CACHE-33 SECRET-TAG-33"))
        public = self.get(self.anon, "CachePublic")
        self.assertNotIn("SECRET-CACHE-33", public)
        self.assertNotIn("SECRET-TAG-33", public)
        # 通常の静的ページは引き続きキャッシュされる。
        static_id = self.page("CacheStatic", "STATIC-33")
        self.assertIn("STATIC-33", self.get(self.anon, "CacheStatic"))
        with self.db("_backup") as db:
            self.assertEqual(db.execute("SELECT count(*) FROM cache_logs WHERE log_id=?",
                                        (static_id,)).fetchone()[0], 1)
        with self.db() as db:
            db.execute("UPDATE logs SET body='UNCACHED-33' WHERE id=?", (static_id,))
        self.assertIn("STATIC-33", self.get(self.anon, "CacheStatic"))

    def test_attachment_delete_custom_name(self):
        pid = self.page("AttachCustom", "attachment test")
        # 拡張子の食い違う名前は Issue #48 以降アップロードでは作れないため、
        # 過去に作られた行として DB 直接作成に切り替えた
        for filename, original in (("customname", "one.png"),
                                   ("custom.txt", "two.png"),
                                   ("regular.png", "three.png")):
            if filename == "regular.png":
                self.assertIn("添付成功", self.upload(self.admin, "AttachCustom", filename, original))
                with self.db() as db:
                    aid = db.execute("SELECT id FROM attach WHERE name=?", (filename,)).fetchone()[0]
            else:
                aid = self.attach_legacy(pid, filename)
            real = self.root / ("attach/%d.png" % aid)
            legacy = self.root / ("attach/%d" % aid)
            legacy.write_bytes(b"legacy")
            neighbor = self.root / ("attach/%d.png" % (aid * 10))
            neighbor.write_bytes(b"neighbor")
            self.assertTrue(real.exists())
            token = self.token(self.get(self.admin, "AttachCustom&attach"))
            self.get(self.admin, "AttachCustom&attach&delete&id=%d&edit_token=%s" % (aid, token))
            self.assertFalse(real.exists())
            self.assertFalse(legacy.exists())
            self.assertTrue(neighbor.exists())
            with self.db() as db:
                self.assertEqual(db.execute("SELECT count(*) FROM attach WHERE id=?", (aid,)).fetchone()[0], 0)
            neighbor.unlink()

    def test_page_delete_removes_attachment_variants(self):
        body = "page deletion test"
        pid = self.page("DeletePage", body)
        aid = self.attach_legacy(pid, "displayname")
        legacy = self.root / ("attach/%d" % aid)
        legacy.write_bytes(b"legacy")
        self.get(self.admin, "DeletePage&edit&delete&hash=" + hashlib.md5(body.encode()).hexdigest())
        self.assertFalse((self.root / ("attach/%d.png" % aid)).exists())
        self.assertFalse(legacy.exists())

    def test_anonymous_upload_csrf(self):
        self.configure("\n$private['auth.write.enabled'] = FALSE;\n")
        self.page("AnonymousUpload", "anonymous test")
        token = self.token(self.get(self.anon, "AnonymousUpload&attach"))
        self.assertRegex(token, r"^[0-9a-f]{64}$")
        for invalid in (False, "wrong-token"):
            self.assertIn("ページを戻って画面を更新してください",
                          self.upload(self.anon, "AnonymousUpload", "blocked.png", token=invalid))
        other = self.client()
        self.get(other, "FrontPage")
        self.assertIn("ページを戻って画面を更新してください",
                      self.upload(other, "AnonymousUpload", "blocked.png", token=token))
        self.assertIn("添付成功", self.upload(self.anon, "AnonymousUpload", "allowed.png", token=token))
        with self.db() as db:
            self.assertEqual(db.execute("SELECT count(*) FROM attach WHERE name='blocked.png'").fetchone()[0], 0)

    def test_upload_requires_write_permission(self):
        self.page("ProtectedUpload", "protected test")
        token = self.token(self.get(self.anon, "ProtectedUpload&attach"))
        response = self.upload(self.anon, "ProtectedUpload", "unauthorized.png", token=token)
        self.assertIn('name="pw"', response)
        with self.db() as db:
            self.assertEqual(db.execute("SELECT count(*) FROM attach WHERE name='unauthorized.png'").fetchone()[0], 0)


if __name__ == "__main__":
    unittest.main(verbosity=2)
