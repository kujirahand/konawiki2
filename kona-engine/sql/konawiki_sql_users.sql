/**
 * konawiki users sql
 */

CREATE TABLE users (
    user_id     INTEGER PRIMARY KEY,
    user        TEXT UNIQUE,
    password    TEXT,
    salt        TEXT NOT NULL,
    name        TEXT,
    profile     TEXT,
    last_login  INTEGER DEFAULT 0,
    ctime       INTEGER DEFAULT 0,
    mtime       INTEGER DEFAULT 0
);

/* --- */

CREATE TABLE login_errors (
    ip      TEXT NOT NULL,
    ctime   INTEGER
);

/* --- */

CREATE TABLE login_history (
    history_id  INTEGER PRIMARY KEY,
    user        TEXT,
    ip          TEXT,
    ctime       INTEGER
);

