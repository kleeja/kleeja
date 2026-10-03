<?php
/**
 *
 * @package Kleeja
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 * Those are some dev functions that help us for debugging
 * since kleeja is a web app, so our target will just be debugging using browsers
 */

//no for directly open
if (!defined('IN_COMMON')) {
    exit();
}

// Of course, i knew the function name from Laravel
function dd(mixed $data): void
{
    echo '<pre>' . PHP_EOL;
    var_dump($data);
    echo '</pre>' . PHP_EOL;
    exit();
}
