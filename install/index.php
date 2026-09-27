<?php
/**
 *
 * @package install
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

// Report all errors, except notices
error_reporting(E_ALL ^ E_NOTICE);

/**
 * include important files
 */
define('IN_COMMON', true);

//path to this file from Kleeja root folder
define('PATH', '../');

include_once PATH . 'includes/version.php';
include_once PATH . 'includes/functions_error.php';

//before anything check PHP version compatibility, the error handler is called directly like in common.php
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
}

// if PDO or its drivers are not installed
if (!class_exists('PDO') || !array_intersect(['mysql', 'sqlite'], PDO::getAvailableDrivers())) {
    kleeja_show_error(
        E_USER_ERROR,
        'In order to use Kleeja, "pdo_mysql" or "pdo_sqlite" extension has to be installed on your server.',
        __FILE__,
        __LINE__,
    );
}

if (file_exists(PATH . 'config.php')) {
    include_once PATH . 'config.php';
}

include_once PATH . 'includes/functions.php';

include_once PATH . 'includes/pdo.php';

include_once 'includes/functions_install.php';

// old links to choose a language
if (g('step') === 'language' && ig('ln')) {
    header('Location: ./?step=what_is_kleeja&lang=' . g('ln', default: 'en'));

    exit();
}

/**
 * print header
 */
echo gettpl('header.html');

/**
 * Navigation ..
 */
switch (g('step', 'str')) {
    default:
    case 'language':
        echo gettpl('lang.html');

        break;

    case 'what_is_kleeja':
        echo gettpl('what_is_kleeja.html');

        break;

    case 'official':
        echo gettpl('official.html');

        break;

    case 'choose':
        $php_ver = true;

        //check version of PHP
        if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
            $php_ver = false;
        }

        //config.php is included at the top of this file
        $install_or_no = !inst_is_installed();

        echo gettpl('choose.html');

        break;
}

/**
 * print footer
 */
echo gettpl('footer.html');
