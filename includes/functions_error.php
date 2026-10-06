<?php
/**
 *
 * @package Kleeja
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

//no for directly open
if (!defined('IN_COMMON')) {
    exit();
}

/**
 * Error handler for Kleeja; also used by the installer.
 *
 * Only non-fatal types ever reach this function (E_WARNING, E_NOTICE,
 * E_RECOVERABLE_ERROR, E_DEPRECATED, E_USER_*). Fatal types
 * (E_ERROR, E_PARSE, E_CORE_*, E_COMPILE_*) bypass the handler in the engine.
 *
 * @param int    $error_number
 * @param string $error_string
 * @param string $error_file
 * @param int    $error_line
 *
 * @return bool true = error handled, PHP's internal handler is skipped
 */
function kleeja_show_error(
    int $error_number,
    string $error_string = '',
    string $error_file = '',
    int $error_line = 0,
): bool {
    // Respect @ (error suppression operator) and current error_reporting setting
    if (!(error_reporting() & $error_number)) {
        return false;
    }

    switch ($error_number) {
        case E_NOTICE:
        case E_WARNING:
        case E_USER_WARNING:
        case E_USER_NOTICE:
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            if (function_exists('kleeja_log')) {
                $error_name = [
                    E_WARNING => 'Warning',
                    E_NOTICE => 'Notice',
                    E_USER_WARNING => 'U_Warning',
                    E_USER_NOTICE => 'U_Notice',
                    E_DEPRECATED => 'Deprecated',
                    E_USER_DEPRECATED => 'U_Deprecated',
                ][$error_number];
                kleeja_log('[' . $error_name . '] ' . basename($error_file) . ':' . $error_line . ' ' . $error_string);
            }

            return true;

        // Only E_USER_ERROR and E_RECOVERABLE_ERROR reach this default case.
        default:
            if (!headers_sent()) {
                header('HTTP/1.1 503 Service Temporarily Unavailable');
                header('Content-Type: text/html; charset=UTF-8');
            }

            $error_name =
                [
                    E_USER_ERROR => 'E_USER_ERROR',
                    E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
                ][$error_number] ?? 'E_UNKNOWN';

            $escape = fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $error_template = @file_get_contents(__DIR__ . '/error.html');

            if ($error_template === false) {
                echo '<strong>Kleeja error: [ ' .
                    $error_number .
                    ':' .
                    $escape(basename($error_file)) .
                    ':' .
                    $error_line .
                    ' ]</strong><br />' .
                    nl2br($escape($error_string));
            } else {
                echo strtr($error_template, [
                    '{ROOT}' => $escape(kleeja_web_root()),
                    '{TITLE}' => 'Kleeja Error',
                    '{BADGE}' => 'HTTP 503 · Service Temporarily Unavailable',
                    '{TYPE}' => 'error',
                    '{MESSAGE}' => nl2br($escape($error_string)),
                    '{ERROR_NAME}' => $error_name,
                    '{ERROR_NUMBER}' => (string) $error_number,
                    '{ERROR_FILE}' => $escape(basename($error_file)),
                    '{ERROR_LINE}' => (string) $error_line,
                ]);
            }

            global $SQL;

            if (isset($SQL)) {
                @$SQL->close();
            }

            exit();
    }
}

/**
 * The web path of the Kleeja folder, like /kleeja/, for the files that error.html loads.
 * It does not use the config, the error page can show up before it is loaded, and it
 * works for the scripts in sub folders like admin/index.php and for the serve.php urls
 * @return string
 */
function kleeja_web_root(): string
{
    $root = str_replace('\\', '/', dirname(__DIR__));
    $script = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
    $path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));

    //go up a folder for every folder between Kleeja and the script, admin/index.php has one
    if (str_starts_with($script, $root . '/')) {
        $depth = substr_count(substr($script, strlen($root) + 1), '/');

        for ($i = 0; $i < $depth; $i++) {
            $path = str_replace('\\', '/', dirname($path));
        }
    }

    return rtrim($path, '/') . '/';
}
