<?php
/**
 *
 * @package Kleeja
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

//not for directly open
if (!defined('IN_KLEEJA')) {
    exit();
}

//we are in the common file
define('IN_COMMON', true);

//filename of config.php
define('KLEEJA_CONFIG_FILE', 'config.php');

//plugins folder
define('KLEEJA_PLUGINS_FOLDER', 'plugins');

if (@extension_loaded('apc')) {
    define('APC_CACHE', true);
}

//path
if (!defined('PATH')) {
    define('PATH', str_replace(DIRECTORY_SEPARATOR . 'includes', '', __DIR__) . '/');
}

//no config
if (!file_exists(PATH . KLEEJA_CONFIG_FILE)) {
    header('Location: ./install/index.php');

    exit();
}

//there is a config
require_once PATH . KLEEJA_CONFIG_FILE;

//admin files path
define('ADM_FILES_PATH', PATH . 'includes/adm');

//Report all errors, except notices
error_reporting(defined('DEV_STAGE') ? E_ALL : E_ALL ^ E_NOTICE);
if (defined('DEV_STAGE')) {
    ini_set('display_errors', 1);
    include_once PATH . 'includes/dev_tools.php';
}

//the error handler, it shows the Kleeja error page
require_once PATH . 'includes/functions_error.php';

set_error_handler('kleeja_show_error');

require_once PATH . 'includes/version.php';

//the error handler is called directly, E_USER_ERROR is deprecated for trigger_error() since PHP 8.4
if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
    kleeja_show_error(
        E_USER_ERROR,
        'You are using an old PHP version (' .
            PHP_VERSION .
            '), to run Kleeja you should use PHP ' .
            MIN_PHP_VERSION .
            ' or above.',
        __FILE__,
        __LINE__,
    );
} elseif (!class_exists('PDO') || !array_intersect(['mysql', 'sqlite'], PDO::getAvailableDrivers())) {
    kleeja_show_error(
        E_USER_ERROR,
        'In order to use Kleeja, "pdo_mysql" or "pdo_sqlite" extension has to be installed on your server.',
        __FILE__,
        __LINE__,
    );
}

//time of start and end and whatever
function get_microtime(): float
{
    [$usec, $sec] = explode(' ', microtime());

    return (float) $usec + (float) $sec;
}

//is bot ?
function is_bot(array $bots = ['googlebot', 'bing', 'msnbot']): bool
{
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        return preg_match(
            '/(' . implode('|', $bots) . ')/i',
            $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : @getenv('HTTP_USER_AGENT'),
        )
            ? true
            : false;
    }

    return false;
}

$starttm = get_microtime();

if (!is_bot() && PHP_SESSION_ACTIVE !== session_status() && !headers_sent()) {
    if (function_exists('ini_set')) {
        ini_set('session.use_cookies', 1);
        ini_set('session.lazy_write', 1);
        ini_set('session.cache_expire', 0);
        ini_set('session.cache_limiter', '');
        ini_set('session.use_only_cookies', 1);
    }

    if (!session_start()) {
        kleeja_show_error(
            E_USER_ERROR,
            'There is a problem with PHP session. We can not start it.',
            __FILE__,
            __LINE__,
        );
    }
}

//no enough data
if ((empty($dbname) || empty($dbuser)) && $dbtype !== 'sqlite') {
    header('Location: ./install/index.php');

    exit();
}

// solutions for hosts running under suexec, add define('HAS_SUEXEC', true) to config.php.
define('K_FILE_CHMOD', defined('HAS_SUEXEC') ? 0644 & ~umask() : 0644);
define('K_DIR_CHMOD', defined('HAS_SUEXEC') ? 0755 & ~umask() : 0755);

require_once PATH . 'includes/functions_alternative.php';

require_once PATH . 'includes/pdo.php';

require_once PATH . 'includes/style.php';
require_once PATH . 'includes/usr.php';
require_once PATH . 'includes/pager.php';
require_once PATH . 'includes/functions.php';
require_once PATH . 'includes/functions_display.php';
require_once PATH . 'includes/plugins.php';
require_once PATH . 'includes/FetchFile.php';
require_once PATH . 'includes/cookie.php';

if (defined('IN_ADMIN')) {
    require_once PATH . 'includes/functions_adm.php';
}

//fix integration problems
if (empty($script_encoding)) {
    $script_encoding = 'utf-8';
}

//start classes ..
$SQL = new KleejaDatabase($dbserver, $dbuser, $dbpass, $dbname, $dbprefix, $dbtype ?? 'mysql');
//no need after now
unset($dbpass);

$tpl = new kleeja_style();
$usrcp = new usrcp();

//then get caches
require_once PATH . 'includes/cache.php';

//getting dynamic configs
$query = [
    'SELECT' => 'c.name, c.value',
    'FROM' => "{$dbprefix}config c",
    'WHERE' => 'c.dynamic = 1',
];

$result = $SQL->build($query);

while ($row = $SQL->fetch_array($result)) {
    $config[$row['name']] = $row['value'];
}

$SQL->freeresult($result);

//check user or guest
$usrcp->kleeja_check_user();

//+ configs of the current group
$config = array_merge($config, (array) $d_groups[$usrcp->group_id()]['configs']);

//Kleeja was installed on http:// and HTTPS was enabled later, so move the site link to https://
//only $_SERVER['HTTPS'] is trusted, it comes from the web server, X-Forwarded-Proto can be sent by any client
if (
    !empty($_SERVER['HTTPS']) &&
    strtolower($_SERVER['HTTPS']) !== 'off' &&
    stripos($config['siteurl'], 'http://') === 0
) {
    $site_link = parse_url($config['siteurl']);
    $site_host = strtolower(($site_link['host'] ?? '') . (isset($site_link['port']) ? ':' . $site_link['port'] : ''));

    //the same host and port only, another domain or port of the server may not have a certificate
    if ($site_host !== '' && $site_host === strtolower($_SERVER['HTTP_HOST'] ?? '')) {
        $config['siteurl'] = 'https://' . substr($config['siteurl'], 7);
        update_config('siteurl', $config['siteurl'], false);
    }

    unset($site_link, $site_host);
}

//admin path
define('ADMIN_PATH', rtrim($config['siteurl'], '/') . '/admin/index.php');

//no tpl caching in dev stage
if (defined('DEV_STAGE') || defined('STOP_TPL_CACHE')) {
    $tpl->caching = false;
}

if (isset($config['foldername'])) {
    $config['foldername'] = str_replace(
        ['{year}', '{month}', '{week}', '{day}', '{username}'],
        [
            date('Y'),
            date('m'),
            date('W'),
            date('d'),
            $usrcp->name() ? preg_replace('/[^a-z0-9\._-]/', '', strtolower($usrcp->name())) : 'guest',
        ],
        $config['foldername'],
    );
}

extract(runHook('boot_common', get_defined_vars()));

/**
 * Set default time zone
 * There is no time difference between Coordinated Universal Time (UTC) and Greenwich Mean Time (GMT).
 * Kleeja supports the changing of time zone through the admin panel, see functions_display.php/kleeja_date()
 */
date_default_timezone_set('GMT');

//remove PHP version header
header_remove('X-Powered-By');

//kleeja session id
define('KJ_SESSION', preg_replace('/[^-,a-zA-Z0-9]/', '', session_id()));

//site url must end with /
$config['siteurl'] = rtrim($config['siteurl'], '/') . '/';

//check lang
if (!$config['language'] || empty($config['language'])) {
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) > 2) {
        $config['language'] = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

        if (!file_exists(PATH . 'lang/' . $config['language'] . '/common.php')) {
            $config['language'] = 'en';
        }
    }
}

//check style
if (is_null($config['style']) || empty($config['style'])) {
    $config['style'] = 'bootstrap';
}

//check h_kay, important for kleeja
if (empty($config['h_key'])) {
    $h_k = sha1(microtime() . rand(0, 100));

    if (!update_config('h_key', $h_k)) {
        add_config('h_key', $h_k);
    }
}

//current Kleeja admin style
define('ACP_STYLE_NAME', 'Damask');

//path variables for Kleeja
$STYLE_PATH =
    $config['siteurl'] .
    'styles/' .
    (trim($config['style_depend_on']) == '' ? $config['style'] : $config['style_depend_on']) .
    '/';
$THIS_STYLE_PATH = $config['siteurl'] . 'styles/' . $config['style'] . '/';
$THIS_STYLE_PATH_ABS = PATH . 'styles/' . $config['style'] . '/';
$STYLE_PATH_ADMIN = $config['siteurl'] . 'admin/' . ACP_STYLE_NAME . '/';
$STYLE_PATH_ADMIN_ABS = PATH . 'admin/' . ACP_STYLE_NAME . '/';
$DEFAULT_PATH_ADMIN_ABS = PATH . 'admin/' . ACP_STYLE_NAME . '/';
$DEFAULT_PATH_ADMIN = $config['siteurl'] . 'admin/' . ACP_STYLE_NAME . '/';

//get languge of common
get_lang('common');
get_olang($config['language']);

//run ban system
get_ban();

if (isset($_GET['go']) && $_GET['go'] == 'login') {
    define('IN_LOGIN', true);
}

//install.php exists
if (
    file_exists(PATH . 'install') &&
    !defined('IN_ADMIN') &&
    !defined('IN_LOGIN') &&
    !defined('DEV_STAGE') &&
    !(defined('IN_GO') && in_array(g('go'), ['queue'])) &&
    !(defined('IN_UCP') && in_array(g('go'), ['captcha', 'login']))
) {
    //Different message for admins! delete install folder
    kleeja_info(
        user_can('enter_acp') ? $lang['DELETE_INSTALL_FOLDER'] : $lang['WE_UPDATING_KLEEJA_NOW'],
        $lang['SITE_CLOSED'],
    );
}

//is site close
$login_page = '';

if (
    $config['siteclose'] == '1' &&
    !user_can('enter_acp') &&
    !defined('IN_LOGIN') &&
    !defined('IN_ADMIN') &&
    !(defined('IN_GO') && in_array(g('go'), ['queue'])) &&
    !(defined('IN_UCP') && in_array(g('go'), ['captcha', 'login', 'register', 'logout']))
) {
    //if download, images ?
    if (
        (defined('IN_DOWNLOAD') && (ig('img') || ig('thmb') || ig('thmbf') || ig('imgf'))) ||
        g('go', 'str', '') == 'queue'
    ) {
        @$SQL->close();
        $fullname = 'images/site_closed.jpg';
        $filesize = filesize($fullname);
        header("Content-length: $filesize");
        header('Content-type: image/jpg');
        readfile($fullname);

        exit();
    }

    // Send a 503 HTTP response code to prevent search bots from indexing the maintenace message
    http_response_code(503);
    kleeja_info($config['closemsg'], $lang['SITE_CLOSED']);
}

//exceed total size
if ($stat_sizes >= $config['total_size'] * 1048576 && !defined('IN_LOGIN') && !defined('IN_ADMIN')) {
    // convert megabytes to bytes
    // Send a 503 HTTP response code to prevent search bots from indexing the maintenace message
    http_response_code(503);
    kleeja_info($lang['SIZES_EXCCEDED'], $lang['STOP_FOR_SIZE']);
}

//detect bots and save stats
kleeja_detecting_bots();

//check for page number
if (empty($perpage) || intval($perpage) == 0) {
    $perpage = 14;
}

//captcha file
$captcha_file_path = $config['siteurl'] . 'ucp.php?go=captcha';

if (defined('STOP_CAPTCHA')) {
    $config['enable_captcha'] = 0;
}

extract(runHook('end_common', get_defined_vars()));

register_shutdown_function(function (): void {
    session_write_close();

    $err = error_get_last();

    if (is_array($err) && !empty($err['type']) && in_array($err['type'], [E_ERROR, E_PARSE])) {
        kleeja_log('[FATAL] ' . basename($err['file']) . ':' . $err['line'] . ' ' . $err['message']);
    }
});
