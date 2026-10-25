<?php
/**
 * 認証まわりの機能テスト
 * - _konawiki_auth_check_password(): $private['authusers'] との照合
 * - konawiki_checkPassword(): 保存形式 ({md5}/{sha1}/平文) の照合
 */
require_once __DIR__ . '/test_common.inc.php';

global $private;
$saved_users = isset($private['authusers']) ? $private['authusers'] : null;

// ---------------------------------------------------------------------
// _konawiki_auth_check_password
// ---------------------------------------------------------------------
$private['authusers'] = [
    'taro'       => '{md5}' . md5('s3cret'),
    'hanako'     => '{sha1}' . sha1('pw2'),
    'xphp'       => '{x-php-md5}' . md5('pw3'),
    'plain_user' => 'plainpass',
];

test_eq(__LINE__, _konawiki_auth_check_password('taro', 's3cret'), TRUE,  'auth: md5 correct');
test_eq(__LINE__, _konawiki_auth_check_password('taro', 'wrong'),  FALSE, 'auth: md5 wrong');
test_eq(__LINE__, _konawiki_auth_check_password('taro', ''),       FALSE, 'auth: md5 empty');
test_eq(__LINE__, _konawiki_auth_check_password('hanako', 'pw2'),  TRUE,  'auth: sha1 correct');
test_eq(__LINE__, _konawiki_auth_check_password('hanako', 'pw3'),  FALSE, 'auth: sha1 wrong');
test_eq(__LINE__, _konawiki_auth_check_password('xphp', 'pw3'),    TRUE,  'auth: x-php-md5 correct');
test_eq(__LINE__, _konawiki_auth_check_password('plain_user', 'plainpass'), TRUE,  'auth: raw correct');
test_eq(__LINE__, _konawiki_auth_check_password('plain_user', 'plainpas'),  FALSE, 'auth: raw wrong (prefix)');
test_eq(__LINE__, _konawiki_auth_check_password('plain_user', '0'),         FALSE, 'auth: raw wrong (0)');
test_eq(__LINE__, _konawiki_auth_check_password('nobody', 's3cret'), FALSE, 'auth: unknown user');

// 保存側ハッシュの大文字小文字は区別しない (md5)
$private['authusers']['upper'] = '{md5}' . strtoupper(md5('abc'));
test_eq(__LINE__, _konawiki_auth_check_password('upper', 'abc'), TRUE, 'auth: md5 upper-case hash');

if ($saved_users === null) {
    unset($private['authusers']);
} else {
    $private['authusers'] = $saved_users;
}

// ---------------------------------------------------------------------
// konawiki_checkPassword (ページ凍結などに使う保存値の照合)
// 第1引数の文字列を、第2引数の保存形式に応じて照合する現行仕様を確認する
// ---------------------------------------------------------------------
test_eq(__LINE__, konawiki_checkPassword('abc', '{md5}' . md5('abc')),   TRUE,  'checkPassword: md5');
test_eq(__LINE__, konawiki_checkPassword('abc', '{md5}' . md5('xyz')),   FALSE, 'checkPassword: md5 mismatch');
test_eq(__LINE__, konawiki_checkPassword('abc', '{sha1}' . sha1('abc')), TRUE,  'checkPassword: sha1');
test_eq(__LINE__, konawiki_checkPassword('abc', '{sha1}' . sha1('xyz')), FALSE, 'checkPassword: sha1 mismatch');
test_eq(__LINE__, konawiki_checkPassword('abc', 'abc'), TRUE,  'checkPassword: plain');
test_eq(__LINE__, konawiki_checkPassword('abc', 'abd'), FALSE, 'checkPassword: plain mismatch');
test_eq(__LINE__, konawiki_checkPassword('abc', ''),    FALSE, 'checkPassword: empty stored value');
test_eq(__LINE__, konawiki_checkPassword('abc', '{x-php-md5}' . md5('abc')),   TRUE,  'checkPassword: x-php-md5');
test_eq(__LINE__, konawiki_checkPassword('abc', '{x-php-md5}' . md5('xyz')),   FALSE, 'checkPassword: x-php-md5 mismatch');
test_eq(__LINE__, konawiki_checkPassword('abc', '{x-php-sha1}' . sha1('abc')), TRUE,  'checkPassword: x-php-sha1');
test_eq(__LINE__, konawiki_checkPassword('abc', '{x-php-sha1}' . sha1('xyz')), FALSE, 'checkPassword: x-php-sha1 mismatch');
test_eq(__LINE__, konawiki_checkPassword('abc', '{md5}' . strtoupper(md5('abc'))),   TRUE, 'checkPassword: md5 uppercase stored');
test_eq(__LINE__, konawiki_checkPassword('abc', '{sha1}' . strtoupper(sha1('abc'))), TRUE, 'checkPassword: sha1 uppercase stored');
test_eq(__LINE__, konawiki_checkPassword('Abc', 'abc'), FALSE, 'checkPassword: plain is case sensitive');
test_eq(__LINE__, konawiki_checkPassword('abc', null), FALSE, 'checkPassword: null stored value');
