<?php
/**
 * fw_simple フレームワークの機能テスト
 * - fw_etc: パラメータ取得、NGワード
 * - fw_rooting: app_url の生成
 * - fw_template_engine(_plugins): フィルタ、テンプレート描画
 * (DBラッパー fw_database は db.test.php で検証する)
 */
require_once __DIR__ . '/test_common.inc.php';

// ---------------------------------------------------------------------
// fw_etc: get_param / post_param / ss_param
// ---------------------------------------------------------------------
$_GET = ['g' => 'gv']; $_POST = ['p' => 'pv'];
if (session_status() !== PHP_SESSION_ACTIVE) { $_SESSION = ['s' => 'sv']; }
test_eq(__LINE__, get_param('g'), 'gv', 'get_param: hit');
test_eq(__LINE__, get_param('missing'), '', 'get_param: default empty');
test_eq(__LINE__, get_param('missing', 'd'), 'd', 'get_param: custom default');
test_eq(__LINE__, post_param('p'), 'pv', 'post_param: hit');
test_eq(__LINE__, post_param('missing'), '', 'post_param: default');
test_eq(__LINE__, ss_param('s'), 'sv', 'ss_param: hit');
test_eq(__LINE__, ss_param('missing', 'd'), 'd', 'ss_param: default');
$_GET = []; $_POST = [];

// NGワード ( $ngword_list に含まれる語があるか )
global $ngword_list;
$saved_ngword = isset($ngword_list) ? $ngword_list : null;
$ngword_list = ['spam', 'xxx'];
test_eq(__LINE__, has_ngword('this is spam mail'), TRUE, 'ngword: detected');
test_assert(__LINE__, !has_ngword('clean text'), 'ngword: not detected');
$ngword_list = null;
test_assert(__LINE__, !has_ngword('spam'), 'ngword: no list means no detection');
$ngword_list = $saved_ngword;

// ---------------------------------------------------------------------
// fw_rooting: app_url / app_url_full
// ---------------------------------------------------------------------
$_SERVER['SCRIPT_NAME'] = '/index.php';
test_eq(__LINE__, app_url('show'), '/index.php?a=show', 'app_url: action only');
test_eq(__LINE__, app_url('show', 'detail'), '/index.php?a=show&d=detail', 'app_url: with detail');
test_eq(__LINE__, app_url('show', '', ['q' => 'a b', 'n' => '1']),
    '/index.php?a=show&q=a+b&n=1', 'app_url: params encoded');
$_SERVER['REQUEST_SCHEME'] = 'http'; $_SERVER['HTTP_HOST'] = 'example.com';
test_eq(__LINE__, app_url_full('show'), 'http://example.com/index.php?a=show', 'app_url_full');
ob_start(); echo_url('show', 'd1'); $echoed = ob_get_clean();
test_eq(__LINE__, $echoed, '/index.php?a=show&d=d1', 'echo_url');
// 後続のテストファイルに影響しないよう戻す
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
unset($_SESSION['s']);

// ---------------------------------------------------------------------
// fw_template_engine_plugins: フィルタ関数
// ---------------------------------------------------------------------
require_once dirname(__DIR__) . '/kona-engine/fw_simple/fw_template_engine.lib.php';

test_eq(__LINE__, t_echo('<a>'), '&lt;a&gt;', 'filter echo: escaped');
test_eq(__LINE__, t_raw('<a>'), '<a>', 'filter raw: as-is');
test_eq(__LINE__, t_safe('<a>'), '<a>', 'filter safe: as-is');
test_eq(__LINE__, t_multiline("a\nb"), 'a<br>b', 'filter multiline');
test_eq(__LINE__, t_multiline('<b>'), '&lt;b&gt;', 'filter multiline: escaped');
test_eq(__LINE__, t_check_mudai(''), '(無題)', 'filter mudai: empty');
test_eq(__LINE__, t_check_nanasi(''), '名無し', 'filter nanasi: empty');
test_eq(__LINE__, t_trim40(''), '(なし)', 'filter trim40: empty');
test_contains(__LINE__, t_trim40(str_repeat('a', 100)), ' ... ', 'filter trim40: truncated');
test_eq(__LINE__, trim_url('short'), 'short', 'trim_url: short unchanged');
test_eq(__LINE__, t_date2(0), '-', 'filter date2: zero');
test_eq(__LINE__, t_date2(mktime(0, 0, 0, 1, 2, 2024)), '2024-01-02', 'filter date2');
test_eq(__LINE__, t_datetime(0), '-', 'filter datetime: zero');
test_eq(__LINE__, t_datetime(mktime(3, 4, 0, 1, 2, 2024)), '2024/01/02 03:04', 'filter datetime');
test_eq(__LINE__, t_date(mktime(0, 0, 0, 1, 2, 2024)), '2024年01月02日', 'filter date');
test_eq(__LINE__, t_number_format(12345.6), '12,346', 'filter number_format');
test_eq(__LINE__, t_number_format(0), '0', 'filter number_format: zero');
test_eq(__LINE__, t_boolstr(TRUE), 'true', 'filter boolstr: true');
test_eq(__LINE__, t_boolstr(FALSE), 'false', 'filter boolstr: false');
test_eq(__LINE__, t_star(3), '⭐⭐⭐', 'filter star: 3');
test_eq(__LINE__, t_star(0), '', 'filter star: 0');
$html = echo_options('b', ['A', 'B'], ['a', 'b']);
test_contains(__LINE__, $html, '<option value="b" selected>B</option>', 'echo_options: selected');
test_contains(__LINE__, $html, '<option value="a">A</option>', 'echo_options: not selected');

// ---------------------------------------------------------------------
// fw_template_engine: 変数名変換・条件変換・テンプレート描画
// ---------------------------------------------------------------------
test_eq(__LINE__, template_var_name('simple'), 'simple', 'template_var_name: plain');
test_eq(__LINE__, template_var_name('a.b.c'), "a['b']['c']", 'template_var_name: dotted');
test_eq(__LINE__, check_eq_flag(' eq '), '==', 'check_eq_flag: eq');
test_eq(__LINE__, check_eq_flag(' ne '), '!=', 'check_eq_flag: ne');
test_eq(__LINE__, check_eq_flag(' gt '), '>', 'check_eq_flag: gt');
test_eq(__LINE__, check_eq_flag(' lt '), '<', 'check_eq_flag: lt');
test_eq(__LINE__, check_eq_flag(' gteq '), '>=', 'check_eq_flag: gteq');
test_eq(__LINE__, check_eq_flag(' lteq '), '<=', 'check_eq_flag: lteq');

// 実際にテンプレートファイルを描画する (サンドボックス内)
global $DIR_TEMPLATE, $DIR_TEMPLATE_CACHE, $FW_TEMPLATE_PARAMS, $KONA2_TEST_SANDBOX;
$tpl_dir = $KONA2_TEST_SANDBOX . '/tpl';
$cache_dir = $KONA2_TEST_SANDBOX . '/tplcache';
if (!is_dir($tpl_dir)) mkdir($tpl_dir, 0777, TRUE);
if (!is_dir($cache_dir)) mkdir($cache_dir, 0777, TRUE);
file_put_contents($tpl_dir . '/hello.html',
    'Hello {{$name}} {{if $flag}}YES{{else}}NO{{endif}}' .
    ' {{for $items as $it}}[{{$it}}]{{endfor}}' .
    ' {{$user.name}} {{$name | raw}}');
$saved_tpl = isset($DIR_TEMPLATE) ? $DIR_TEMPLATE : null;
$saved_cache = isset($DIR_TEMPLATE_CACHE) ? $DIR_TEMPLATE_CACHE : null;
$DIR_TEMPLATE = $tpl_dir;
$DIR_TEMPLATE_CACHE = $cache_dir;
$FW_TEMPLATE_PARAMS = [];

ob_start();
template_render('hello.html', [
    'name' => '<b>W</b>', 'flag' => TRUE,
    'items' => ['a', 'b'], 'user' => ['name' => 'taro'],
]);
$out = ob_get_clean();
test_contains(__LINE__, $out, 'Hello &lt;b&gt;W&lt;/b&gt;', 'template: var escaped');
test_contains(__LINE__, $out, 'YES', 'template: if true');
test_contains(__LINE__, $out, '[a][b]', 'template: for loop');
test_contains(__LINE__, $out, 'taro', 'template: dotted var');
test_contains(__LINE__, $out, '<b>W</b>', 'template: raw filter not escaped');

// flag=FALSE 側 (else)
ob_start();
template_render('hello.html', [
    'name' => 'x', 'flag' => FALSE,
    'items' => [], 'user' => ['name' => 'jiro'],
]);
$out = ob_get_clean();
test_contains(__LINE__, $out, 'NO', 'template: if false goes to else');

// 存在しないテンプレートは例外になる
$threw = FALSE;
try {
    ob_start();
    template_render('no_such_template.html', []);
    ob_end_clean();
} catch (Exception $e) {
    ob_end_clean();
    $threw = TRUE;
}
test_assert(__LINE__, $threw, 'template: missing file throws');

$DIR_TEMPLATE = $saved_tpl;
$DIR_TEMPLATE_CACHE = $saved_cache;
