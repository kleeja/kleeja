<?php
/**
 *
 * @package install
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

// Report all errors, except notices
@error_reporting(E_ALL ^ E_NOTICE);

/**
 * include important files
 */
define('IN_COMMON', true);
define('STOP_PLUGINS', true);
define('PATH', __DIR__ . '/../');
define('CLI', PHP_SAPI === 'cli');

//it installs without asking anything, and prints the admin password, so it is for the command line only
if (!CLI) {
    http_response_code(403);

    exit('This file works from the command line only: php install/quick.php --password=... --link=...');
}

include_once PATH . 'includes/plugins.php';
include_once PATH . 'includes/functions_error.php';
include_once PATH . 'includes/functions_display.php';
include_once PATH . 'includes/functions_alternative.php';
include_once PATH . 'includes/functions.php';

include_once PATH . 'includes/pdo.php';

include_once 'includes/functions_install.php';

//cli options
$cli_options = getopt('', ['password::', 'link::']);

if (file_exists(PATH . 'config.php')) {
    include_once PATH . 'config.php';
} else {
    do_config_export('localhost', 'root', '', 'kleeja', 'klj_');

    exit(
        '`config.php` was missing! so we created one for you, kindly edit the file with database information.' . PHP_EOL
    );
}

$SQL = inst_db(create: true);

if (!$SQL->is_connected()) {
    exit('Can not connect to database, please make sure the data in `config.php` is correct!' . PHP_EOL);
}

if (inst_is_installed()) {
    exit('Kleeja is installed already, there is nothing to do!' . PHP_EOL);
}

if ($SQL->driver === 'mysql') {
    if (!empty($SQL->version()) && version_compare($SQL->version(), MIN_MYSQL_VERSION, '<')) {
        exit('The required MySQL version is `' . MIN_MYSQL_VERSION . '` and yours is `' . $SQL->version() . '`!');
    }
}

foreach (['cache', 'uploads', 'uploads/thumbs'] as $folder) {
    if (!is_writable(PATH . $folder)) {
        @chmod(PATH . $folder, 0755);

        if (!is_writable(PATH . $folder)) {
            exit('The folder `' . $folder . '` has to be writable!');
        }
    }
}

//install
include_once PATH . 'includes/usr.php';
include_once PATH . 'includes/functions_alternative.php';

$usrcp = new usrcp();
$password = !empty($cli_options['password']) ? (string) $cli_options['password'] : (string) mt_rand();
$user_salt = substr(base64_encode(pack('H*', sha1(mt_rand()))), 0, 7);
//the login page hashes the password HTML encoded, as p() gives it
$user_pass = $usrcp->kleeja_hash_password(kleeja_html_encode(trim($password)) . $user_salt);
$user_name = $clean_name = 'admin';
$user_mail = $config_sitemail = 'admin@example.com';
$config_urls_type = 'id';
$config_sitename = 'Yet Another Kleeja';
$config_siteurl = rtrim(kleeja_html_encode($cli_options['link'] ?? 'http://localhost/'), '/') . '/';
$config_time_zone = 'Asia/Damascus';

// Queries
include 'includes/install_sqls.php';
include 'includes/default_values.php';

//SQLite has no database charset
if ($SQL->driver === 'mysql') {
    $SQL->query($install_sqls['ALTER_DATABASE_UTF']);
}

$err = 0;
$errors = '';

foreach ($install_sqls as $name => $sql_content) {
    if ($name === 'DROP_TABLES' || $name === 'ALTER_DATABASE_UTF') {
        continue;
    }

    if (!$SQL->query($sql_content, $install_params[$name] ?? [])) {
        $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
        echo $lang['INST_SQL_ERR'] . ' : ' . $name . '[basic]' . PHP_EOL;
        $err++;
    }
}

if ($err === 0) {
    //add configs
    foreach ($config_values as $cn) {
        if (empty($cn[6])) {
            $cn[6] = 0;
        }

        $sql = "INSERT INTO `{$dbprefix}config` (`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`) VALUES (?, ?, ?, ?, ?, ?, ?);";

        if (!$SQL->query($sql, array_slice($cn, 0, 7))) {
            $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
            echo $lang['INST_SQL_ERR'] . ' : [configs_values] ' . $cn[0] . PHP_EOL;
            $err++;
        }
    }

    //add groups configs
    foreach ($config_values as $cn) {
        if ($cn[4] !== 'groups') {
            continue;
        }

        $sql = "INSERT INTO `{$dbprefix}groups_data` (`group_id`, `name`, `value`) VALUES (1, :name, :value), (2, :name, :value), (3, :name, :value);";

        if (!$SQL->query($sql, ['name' => $cn[0], 'value' => $cn[1]])) {
            $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
            echo $lang['INST_SQL_ERR'] . ' : [groups_configs_values] ' . $cn[0] . PHP_EOL;
            $err++;
        }
    }

    //add exts
    foreach ($ext_values as $gid => $exts) {
        $itxt = '';
        $params = [];

        foreach ($exts as $t => $v) {
            $itxt .= ($itxt === '' ? '' : ',') . '(?, ?, ?)';
            array_push($params, $t, $gid, $v);
        }

        $sql = "INSERT INTO `{$dbprefix}groups_exts` (`ext`, `group_id`, `size`) VALUES " . $itxt . ';';

        if (!$SQL->query($sql, $params)) {
            $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
            echo $lang['INST_SQL_ERR'] . ' : [ext_values] ' . $gid . PHP_EOL;
            $err++;
        }
    }

    //add acls
    foreach ($acls_values as $cn => $ct) {
        $it = 1;
        $itxt = '';
        $params = [];

        foreach ($ct as $ctk) {
            $itxt .= ($itxt === '' ? '' : ',') . '(?, ?, ?)';
            array_push($params, $cn, $it, $ctk);
            $it++;
        }

        $sql = "INSERT INTO `{$dbprefix}groups_acl` (`acl_name`, `group_id`, `acl_can`) VALUES " . $itxt . ';';

        if (!$SQL->query($sql, $params)) {
            $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
            echo $lang['INST_SQL_ERR'] . ' : [acl_values] ' . $cn . PHP_EOL;
            $err++;
        }
        $it++;
    }
}

if ($err > 0) {
    echo PHP_EOL . 'We encountered a problem during installation, see the error log:' . PHP_EOL;
    echo $errors;
} else {
    echo 'Kleeja has been installed successfully, enjoy ...' . PHP_EOL;
    echo 'Username: admin' . PHP_EOL;
    echo 'Password: ' . $password . PHP_EOL;
}
