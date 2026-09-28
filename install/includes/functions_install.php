<?php
/**
 *
 * @package install
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

// get version info and min requirement values
require_once PATH . 'includes/version.php';

//set mysql to show no errors
define('SQL_NO_ERRORS', true);
define('EVAL_IS_ON', is_eval_is_on());

// Detect choosing another lang while installing
if (ig('change_lang') && ip('lang')) {
    header('Location: ' . $_SERVER['PHP_SELF'] . '?step=' . p('step_is') . '&lang=' . p('lang'));

    exit();
}

// Including current language
$lang = require PATH . 'lang/' . getlang() . '/common.php';
$lang = array_merge($lang, require PATH . 'lang/' . getlang() . '/install.php');

// Exceptions for development
if (file_exists(PATH . '.git') && !defined('DEV_STAGE')) {
    define('DEV_STAGE', true);
}

/**
 * Return current language of installing wizard
 * @param  bool   $link
 * @return string
 */
function getlang(bool $link = false): string
{
    $ln = 'en';

    if (ig('lang')) {
        $lang = preg_replace('/[^a-z0-9]/i', '', g('lang', default: 'en'));
        $ln = file_exists(PATH . 'lang/' . $lang . '/install.php') ? $lang : 'en';
    }

    return $link ? 'lang=' . $ln : $ln;
}

/**
 * Languages that have a translation of the installing wizard
 * @return string[]
 */
function inst_languages(): array
{
    $languages = [];

    foreach (scandir(PATH . 'lang') as $folder) {
        //folders only, a path inside a file like lang/index.html/install.php is an error when open_basedir is on
        if (
            $folder[0] !== '.' &&
            is_dir(PATH . 'lang/' . $folder) &&
            file_exists(PATH . 'lang/' . $folder . '/install.php')
        ) {
            $languages[] = $folder;
        }
    }

    return $languages;
}

/**
 * Name of a language written in the language itself
 * @param  string $code
 * @return string
 */
function inst_lang_name(string $code): string
{
    $names = ['en' => 'English', 'ar' => 'العربية'];

    if (isset($names[$code])) {
        return $names[$code];
    }

    if (class_exists('Locale')) {
        $name = Locale::getDisplayLanguage($code, $code);

        if ($name !== '' && $name !== $code) {
            return $name;
        }
    }

    return strtoupper($code);
}

/**
 * Steps of the installing wizard, shown as a progress bar at the top
 * @return array<string, string>
 */
function inst_steps(): array
{
    global $lang;

    return [
        'language' => $lang['INST_STEP_LANGUAGE'],
        'welcome' => $lang['INST_STEP_WELCOME'],
        'license' => $lang['INST_STEP_LICENSE'],
        'requirements' => $lang['INST_STEP_REQUIREMENTS'],
        'database' => $lang['INST_STEP_DATABASE'],
        'site' => $lang['INST_STEP_SITE'],
        'finish' => $lang['INST_STEP_FINISH'],
    ];
}

/**
 * Current step of the installing wizard, or empty if we are not installing (updating for example)
 * @return string
 */
function inst_current_step(): string
{
    $steps = [
        'index.php' => [
            'language' => 'language',
            'what_is_kleeja' => 'welcome',
            'official' => 'welcome',
            'choose' => 'welcome',
        ],
        'install.php' => [
            'license' => 'license',
            'f' => 'requirements',
            'c' => 'database',
            'check' => 'database',
            'data' => 'site',
            'end' => 'finish',
        ],
    ];

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');

    if (!isset($steps[$script])) {
        return '';
    }

    return $steps[$script][g('step')] ?? reset($steps[$script]);
}

/**
 * A solid icon from Font Awesome (includes/static_shared_files/fontawesome)
 * @param  string $name  the icon name without the fa- prefix
 * @param  string $class
 * @return string
 */
function inst_icon(string $name, string $class = ''): string
{
    return '<i class="kj-icon fa-solid fa-' . $name . ($class !== '' ? ' ' . $class : '') . '" aria-hidden="true"></i>';
}

/**
 * Show an error message inside the wizard, then stop
 * @param string $message
 */
function inst_error(string $message): void
{
    $GLOBALS['error_message'] = $message;

    echo gettpl('error.html');
    echo gettpl('footer.html');

    exit();
}

/**
 * Parsing installing templates
 * @param  string $tplname
 * @return string
 */
function gettpl(string $tplname): string
{
    global $lang;

    $tpl = preg_replace('/{{([^}]+)}}/', '<?php \\1 ?>', file_get_contents('style/' . $tplname));

    ob_start();

    if (EVAL_IS_ON) {
        eval('?> ' . $tpl . '<?php ');
    } else {
        include_once kleeja_eval($tpl);
    }

    $stpl = ob_get_contents();
    ob_end_clean();

    return $stpl;
}

function is_eval_is_on(): bool
{
    $eval_on = false;
    eval('$eval_on = true;');

    return $eval_on;
}

function kleeja_eval(string $code): string
{
    $path = PATH . 'cache/' . md5($code) . '.php';
    file_put_contents($path, $code);

    return $path;
}

/**
 * Export config, the values are the raw ones as typed, not HTML encoded
 * @param  string $srv
 * @param  string $usr
 * @param  string $pass
 * @param  string $nm
 * @param  string $prf
 * @param  string $type
 * @return bool
 */
function do_config_export(string $srv, string $usr, string $pass, string $nm, string $prf, string $type = 'mysql'): bool
{
    $type = $type === 'sqlite' ? 'sqlite' : 'mysql';
    //it is added to the queries as it is
    $prf = preg_replace('/[^a-z0-9_]/i', '', $prf);

    if ($type === 'sqlite' && strpos($nm, '.') === false) {
        $nm = $nm . '.db';
    }

    $data = '<?php' . "\n\n" . '//fill these variables with your data' . "\n";
    $data .= '//for more information about this file, visit: ' . "\n";
    $data .= '//https://github.com/kleeja/kleeja/wiki/config.php-file' . "\n\n";

    //var_export writes each value as a safe PHP string, whatever it has
    if ($type !== 'mysql') {
        $data .= '$dbtype   = ' . var_export($type, true) . "; //database type \n";
    }
    $data .= '$dbserver = ' . var_export($srv, true) . "; //database server \n";
    $data .= '$dbuser   = ' . var_export($usr, true) . "; // database user \n";
    $data .= '$dbpass   = ' . var_export($pass, true) . "; // database password \n";
    $data .= '$dbname   = ' . var_export($nm, true) . "; // database name \n";
    $data .= '$dbprefix = ' . var_export($prf, true) . "; // if you use prefix for tables , fill it \n";

    if (is_writable(PATH)) {
        if (@file_put_contents(PATH . 'config.php', $data, LOCK_EX) !== false) {
            return true;
        }
    }

    if (defined('CLI') && CLI) {
        return true;
    }

    header('Content-Type: text/x-delimtext; name="config.php"');
    header('Content-disposition: attachment; filename=config.php');
    echo $data;

    exit();
}

/**
 * Usefull to caluculte time of execution
 */
function get_microtime(): float
{
    [$usec, $sec] = explode(' ', microtime());

    return (float) $usec + (float) $sec;
}

/**
 * Get config value from database directly, if not return false.
 * @param  string       $name
 * @return string|false
 */
function inst_get_config(string $name): string|false
{
    global $SQL, $dbprefix, $dbname;

    if (empty($SQL)) {
        if (!isset($dbname)) {
            return false;
        }

        $SQL = inst_db();
    }

    if (!$SQL->is_connected()) {
        return false;
    }

    $result = $SQL->query("SELECT value FROM `{$dbprefix}config` WHERE `name` = :name", ['name' => $name]);

    if (!$SQL->num_rows($result)) {
        return false;
    } else {
        $current_ver = $SQL->fetch_array($result);

        return $current_ver['value'] ?? false;
    }
}

/**
 * Connect to the database of config.php
 * @param  bool           $create create the SQLite database file if it is not there, only when installing
 * @return KleejaDatabase
 */
function inst_db(bool $create = false): KleejaDatabase
{
    global $dbserver, $dbuser, $dbpass, $dbname, $dbprefix, $dbtype;

    if ($create && ($dbtype ?? 'mysql') === 'sqlite' && !file_exists(PATH . $dbname)) {
        @touch(PATH . $dbname);
    }

    return new KleejaDatabase(
        $dbserver ?? '',
        $dbuser ?? '',
        $dbpass ?? '',
        $dbname ?? '',
        $dbprefix ?? '',
        $dbtype ?? 'mysql',
    );
}

/**
 * Is Kleeja installed already, config.php leads to a database that has Kleeja settings
 * @return bool
 */
function inst_is_installed(): bool
{
    global $dbname, $dbuser, $dbtype;

    //SQLite has no user
    if (empty($dbname) || (empty($dbuser) && ($dbtype ?? 'mysql') !== 'sqlite')) {
        return false;
    }

    return !empty(inst_get_config('language'));
}

/**
 * A posted value as it is typed, not HTML encoded like p(), for what is not shown in pages, like config.php
 * @param  string $name
 * @param  bool   $trim
 * @return string
 */
function inst_post(string $name, bool $trim = true): string
{
    $value = isset($_POST[$name]) && is_string($_POST[$name]) ? $_POST[$name] : '';

    return $trim ? trim($value) : $value;
}

/**
 * Link of Kleeja, guessed from the link of the installer
 * @return string
 */
function inst_site_url(): string
{
    $https =
        (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
        (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443 ||
        strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $path = preg_replace('#/install$#', '', rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/'));

    return ($https ? 'https' : 'http') . '://' . $host . $path . '/';
}

/**
 * Check the username and password of an admin, the one who can enter the control panel
 * @param  string $name     as p() returns it, like the login page
 * @param  string $password as p() returns it, like the login page
 * @return bool
 */
function inst_admin_login(string $name, string $password): bool
{
    global $SQL, $dbprefix;

    if ($name === '' || $password === '') {
        return false;
    }

    $usrcp = new usrcp();

    $result = $SQL->query("SELECT * FROM `{$dbprefix}users` WHERE `clean_name` = :clean_name LIMIT 1", [
        'clean_name' => $usrcp->cleanusername(trim($name)),
    ]);

    $user = $SQL->fetch_array($result);

    if (
        empty($user['password']) ||
        !$usrcp->kleeja_hash_password(trim($password) . $user['password_salt'], $user['password'])
    ) {
        return false;
    }

    if (!empty($user['founder'])) {
        return true;
    }

    $result = $SQL->query(
        "SELECT `acl_can` FROM `{$dbprefix}groups_acl` WHERE `acl_name` = 'enter_acp' AND `group_id` = :group_id",
        ['group_id' => (int) $user['group_id']],
    );

    return !empty($SQL->fetch_array($result)['acl_can']);
}

/**
 * Is a failed query of an update about something that is there already, which means it was done before
 * @param  array $error [code, message] of $SQL->get_error()
 * @return bool
 */
function inst_update_done_before(array $error): bool
{
    //MySQL: 1060 duplicate column, 1061 duplicate key, 1062 duplicate entry
    return in_array((int) ($error[0] ?? 0), [1060, 1061, 1062], true) ||
        preg_match('/duplicate|already exists|UNIQUE constraint failed/i', (string) ($error[1] ?? '')) === 1;
}

/**
 * trying to detect cookies settings
 * @return array
 */
function get_cookies_settings(): array
{
    $server_port = !empty($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : (int) @getenv('SERVER_PORT');
    $server_name = $server_name = !empty($_SERVER['HTTP_HOST'])
        ? strtolower($_SERVER['HTTP_HOST'])
        : (!empty($_SERVER['SERVER_NAME'])
            ? $_SERVER['SERVER_NAME']
            : @getenv('SERVER_NAME'));

    // HTTP HOST can carry a port number...
    if (strpos($server_name, ':') !== false) {
        $server_name = substr($server_name, 0, strpos($server_name, ':'));
    }

    $cookie_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? true : false;
    $cookie_name = 'klj_' . strtolower(substr(str_replace('0', 'z', base_convert(md5(mt_rand()), 16, 35)), 0, 5));

    $name = !empty($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');

    if (!$name) {
        $name = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : @getenv('REQUEST_URI');
    }

    $script_path = trim(dirname(str_replace(['\\', '//'], '/', $name)));

    if ($script_path !== '/') {
        if (substr($script_path, -1) === '/') {
            $script_path = substr($script_path, 0, -1);
        }

        $script_path = str_replace(['../', './'], '', $script_path);

        if ($script_path[0] !== '/') {
            $script_path = '/' . $script_path;
        }
    }

    $cookie_domain = $server_name;

    if (strpos($cookie_domain, 'www.') === 0) {
        $cookie_domain = str_replace('www.', '.', $cookie_domain);
    }

    return [
        'server_name' => $server_name,
        'cookie_secure' => $cookie_secure,
        'cookie_name' => $cookie_name,
        'cookie_domain' => $cookie_domain,
        'cookie_path' => str_replace('/install', '', $script_path),
    ];
}
