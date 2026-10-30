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
define('PATH', '../');

if (file_exists(PATH . 'config.php')) {
    include_once PATH . 'config.php';
}

include_once PATH . 'includes/plugins.php';
include_once PATH . 'includes/functions.php';
include_once PATH . 'includes/functions_alternative.php';

include_once PATH . 'includes/pdo.php';
include_once PATH . 'includes/usr.php';

include_once 'includes/functions_install.php';

//
// nothing to update if Kleeja is not installed, this connects $SQL too
//
if (!inst_is_installed()) {
    header('Location: ./index.php?' . getlang(1));

    exit();
}

include_once 'includes/update_schema.php';

$current_db_version = (int) inst_get_config('db_version');

$available_db_updates = array_filter(array_keys($update_schema), function (int $v) use ($current_db_version): bool {
    return $v > $current_db_version;
});

sort($available_db_updates);

$update_msgs_arr = $update_errors = [];
$login_failed = false;

/**
 * print header
 */
echo gettpl('header.html');

if (!sizeof($available_db_updates)) {
    $update_msgs_arr[] = $lang['INST_UPDATE_CUR_VER_IS_UP'];

    delete_cache('', all: true);
    echo gettpl('update_end.html');
} elseif (!ip('update_now') || !inst_admin_login(p('username'), p('password'))) {
    //changing the database is for admins only, and only when they ask for it
    $login_failed = ip('update_now');

    echo gettpl('update.html');
} else {
    //old versions have no db_version
    if (inst_get_config('db_version') === false) {
        $SQL->query("INSERT INTO `{$dbprefix}config` (`name`, `value`) VALUES ('db_version', '')");
    }

    $update_errors = kleeja_run_db_updates($update_schema, $current_db_version);

    delete_cache('', all: true);
    echo gettpl('update_end.html');
}

/**
 * print footer
 */
echo gettpl('footer.html');
