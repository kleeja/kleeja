<?php
/**
 *
 * @package adm
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

// not for directly open
if (!defined('IN_ADMIN')) {
    exit();
}

set_time_limit(0);

$current_version = KLEEJA_VERSION;
$saved_version = unserialize($config['new_version'], ['allowed_classes' => false]);
$new_version = empty($saved_version['version_number']) ? KLEEJA_VERSION : $saved_version['version_number'];
$backup_archive_path = PATH . 'cache/backup.zip';
$GET_FORM_KEY = kleeja_add_form_key_get('UPDATER_FORM_KEY');

define('KLEEJA_VERSION_CHECK_LINK', 'https://api.github.com/repos/kleeja/kleeja/releases/latest');
define('KLEEJA_LATEST_PACKAGE_LINK', 'https://api.github.com/repos/kleeja/kleeja/zipball/');
//release tags are like 3.2.7, the version goes into file paths, so nothing else is accepted
define('KLEEJA_RELEASE_TAG_PATTERN', '/^\d+\.\d+\.\d+(-[0-9a-z.]+)?$/i');

$stylee = 'admin_check_update';
$current_smt = preg_replace('/[^a-z0-9_]/i', '', g('smt', default: 'general'));

if (in_array($current_smt, ['update1', 'update2', 'update3'])) {
    //only founders can do the upgrade process ...
    if (intval($userinfo['founder']) !== 1) {
        http_response_code(401);
        kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS']);
    }

    if (!kleeja_check_form_key_get('UPDATER_FORM_KEY')) {
        http_response_code(401);

        kleeja_admin_err($lang['INVALID_GET_KEY']);
    }

    if (!is_string($new_version) || !preg_match(KLEEJA_RELEASE_TAG_PATTERN, $new_version)) {
        http_response_code(400);
        kleeja_admin_err($lang['ERROR_CHECK_VER']);
    }

    $package_file = PATH . "cache/kleeja-{$new_version}.zip";
    $package_folder = PATH . "cache/kleeja-{$new_version}";
}

//check latest version
if ($current_smt == 'check') {
    //get data from kleeja github repo
    if (!($version_data = $cache->get('kleeja_repo_version'))) {
        $version_data = [];

        $github_data = FetchFile::make(KLEEJA_VERSION_CHECK_LINK)
            ->setTimeOut(15)
            ->get();

        $latest_release = empty($github_data) ? null : json_decode($github_data, true);

        //an error answer (like the rate limit one) is a valid json too, but it has no tag
        if (
            is_array($latest_release) &&
            is_string($latest_release['tag_name'] ?? null) &&
            preg_match(KLEEJA_RELEASE_TAG_PATTERN, trim($latest_release['tag_name']))
        ) {
            $version_data = [
                'version' => trim($latest_release['tag_name']),
                'info' => trim(htmlspecialchars((string) ($latest_release['body'] ?? ''))),
                'date' => trim(htmlspecialchars((string) ($latest_release['published_at'] ?? ''))),
            ];
            $cache->save('kleeja_repo_version', $version_data, 3600 * 2);
        }
    }

    $error = 0;

    if (empty($version_data['version'])) {
        $text = $lang['ERROR_CHECK_VER'];
        $error = 1;
    } elseif (version_compare(strtolower($current_version), strtolower($version_data['version']), '<')) {
        $text =
            sprintf($lang['UPDATE_NOW_S'], $current_version, strtolower($version_data['version'])) .
            '::--x--::' .
            $version_data['info'] .
            '::--x--::' .
            $version_data['date'];
        $error = 2;
    } elseif (version_compare(strtolower($current_version), strtolower($version_data['version']), '=')) {
        $text = $lang['U_LAST_VER_KLJ'];
    } else {
        $text = $lang['U_USE_PRE_RE'];
    }

    //a failed check keeps the last known version, but last_check is saved anyway,
    //or start.php would send the admin here on every visit while GitHub can't be reached
    $data = [
        'version_number' => $version_data['version'] ?? ($saved_version['version_number'] ?? null),
        'last_check' => time(),
    ];

    update_config('new_version', serialize($data), escape: false);

    $adminAjaxContent = $error . ':::' . $text;
}
// home of update page
elseif ($current_smt == 'general') {
    $showMessage = ig('show_msg');

    //start.php sends the admin here when the last check is old, and the check is saved in this config,
    //so it must be there, or the admin is sent here again and again
    if ($showMessage) {
        add_config('new_version', '');
    }
}
//1. download latest kleeja version
elseif ($current_smt == 'update1') {
    if (!class_exists('ZipArchive')) {
        $adminAjaxContent = '930:::' . $lang['NO_ZIP_ARCHIVE'];
    } elseif (!version_compare(strtolower($current_version), strtolower($new_version), '<')) {
        $adminAjaxContent = '940:::' . $lang['U_LAST_VER_KLJ'];
    } else {
        // download the latest package to cache folder
        $downloaded = FetchFile::make(KLEEJA_LATEST_PACKAGE_LINK . $new_version)
            ->setDestinationPath($package_file)
            ->isBinaryFile(true)
            ->get();

        $zip = new ZipArchive();

        if ($downloaded === true && $zip->open($package_file, ZipArchive::CHECKCONS) === true) {
            $zip->close();

            $adminAjaxContent = '1:::';
            file_put_contents(PATH . 'cache/step1.done', time());
        } else {
            if (file_exists($package_file)) {
                kleeja_unlink($package_file);
            }

            $adminAjaxContent = '2:::' . $lang['UPDATE_ERR_FETCH_PACKAGE'];
        }
    }
}
//2. extract new kleeja package
elseif ($current_smt == 'update2') {
    if (!file_exists(PATH . 'cache/step1.done')) {
        http_response_code(401);
        kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS']);
    }

    kleeja_unlink(PATH . 'cache/step1.done');

    // let's extract the zip to cache
    $zip = new ZipArchive();
    $extracted = false;

    if ($zip->open($package_file) === true) {
        // the name of folder after extracting it, github puts all the files in one folder
        $ex_folder = explode('/', (string) $zip->getNameIndex(0))[0];

        //it is a path to delete below, so it can't be empty, "." or ".."
        if (preg_match('/^[a-z0-9_-][a-z0-9_.-]*$/i', $ex_folder)) {
            //left by an earlier try, rename() can't replace a folder that has files
            foreach ([PATH . "cache/{$ex_folder}", $package_folder] as $old_folder) {
                if (is_dir($old_folder)) {
                    kleeja_unlink($old_folder);
                }
            }

            $extracted = $zip->extractTo(PATH . 'cache/');
        }

        $zip->close();

        $extracted = $extracted && rename(PATH . "cache/{$ex_folder}", $package_folder);
    }

    if (!$extracted) {
        $adminAjaxContent = '2:::' . $lang['UPDATE_ERR_FETCH_PACKAGE'];
    } else {
        // let's check if there any update files in install folder
        $update_file = "{$package_folder}/install/includes/update_schema.php";
        $db_update_file = PATH . 'cache/update_schema.php';

        //left by an earlier try, it may be for another version
        if (file_exists($db_update_file)) {
            kleeja_unlink($db_update_file);
        }

        if (file_exists($update_file)) {
            // move the update file from install folder to cache folder to include it later and delete install folder
            // becuse if install folder is exists , it can make some problems if dev mode is not active
            if (rename($update_file, $db_update_file) === false) {
                copy($update_file, $db_update_file);
            }
        }

        // skip some folders
        foreach (['cache', 'plugins', 'uploads', 'install'] as $folder_name) {
            if (file_exists("{$package_folder}/{$folder_name}")) {
                kleeja_unlink("{$package_folder}/{$folder_name}");
            }
        }

        // the styles that come with kleeja are updated with it, any other style is updated from the store
        if (is_dir("{$package_folder}/styles")) {
            foreach (
                array_diff(scandir("{$package_folder}/styles"), ['.', '..', 'bootstrap', 'default'])
                as $style_name
            ) {
                kleeja_unlink("{$package_folder}/styles/{$style_name}");
            }
        }

        file_put_contents(PATH . 'cache/step2.done', time());

        $adminAjaxContent = '1:::';
    }
}
//3. update, or rollback on failure
elseif ($current_smt == 'update3') {
    if (!file_exists(PATH . 'cache/step2.done')) {
        http_response_code(401);
        kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS']);
    }

    kleeja_unlink(PATH . 'cache/step2.done');

    //
    // 1) find what is changed, and make sure all of it can be written before any file is touched,
    // so a file with wrong permissions can't leave kleeja half updated
    //
    $changed_files = $new_files = $new_folders = $not_writable = [];
    $checked_folders = $removed_paths = $removed_files = $removed_folders = [];

    //the old files in these folders are not deleted, they have the site data, or things that are not part of kleeja
    $kept_folders = ['cache', 'plugins', 'uploads', 'install', 'styles', 'images'];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($package_folder, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );

    foreach ($files as $file) {
        // the path inside kleeja folder, like includes/functions.php
        $relative_path = substr($file->getPathname(), strlen($package_folder) + 1);
        $target = PATH . $relative_path;

        //a parent folder is listed before its files, so it is there already or it is a new folder too
        $parent_not_writable = is_dir(dirname($target)) && !is_writable(dirname($target));

        if ($file->isDir()) {
            if (!is_dir($target)) {
                $new_folders[] = $target;

                if ($parent_not_writable) {
                    $not_writable[] = dirname($relative_path) . '/';
                }
            } elseif ($relative_path !== 'lang' && !in_array(strtok($relative_path, '/\\'), $kept_folders)) {
                $checked_folders[] = $relative_path;
            }

            continue;
        }

        // same, no need to replace
        if (file_exists($target) && md5_file($target) === md5_file($file->getPathname())) {
            continue;
        }

        if (!file_exists($target)) {
            $new_files[] = $target;

            if ($parent_not_writable) {
                $not_writable[] = dirname($relative_path) . '/';
            }
        } elseif (!is_writable($target) && (!@chmod($target, K_FILE_CHMOD) || !is_writable($target))) {
            $not_writable[] = $relative_path;
        }

        $changed_files[$relative_path] = $file->getPathname();
    }

    //
    // the files and folders of the old version that are not in the new one, like an admin style that was replaced.
    // the root folder is not checked, config.php, .htaccess, the sqlite database and the uploads folder (it can be
    // renamed) are there, and neither is lang/, the languages that don't come with kleeja are added by the site owner
    //
    foreach ($checked_folders as $relative_folder) {
        foreach (array_diff(scandir(PATH . $relative_folder) ?: [], ['.', '..']) as $name) {
            $relative_path = "{$relative_folder}/{$name}";

            //a link can point to anything outside kleeja
            if (file_exists("{$package_folder}/{$relative_path}") || is_link(PATH . $relative_path)) {
                continue;
            }

            $removed_paths[] = $relative_path;

            if (!is_dir(PATH . $relative_path)) {
                $removed_files[] = $relative_path;

                continue;
            }

            $removed_folders[] = $relative_path;

            $old_files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(PATH . $relative_path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
            );

            try {
                foreach ($old_files as $old_file) {
                    if ($old_file->isDir()) {
                        $removed_folders[] = substr($old_file->getPathname(), strlen(PATH));
                    } else {
                        $removed_files[] = substr($old_file->getPathname(), strlen(PATH));
                    }
                }
            } catch (UnexpectedValueException $e) {
                //a folder inside it can't be read, so it can't be deleted
                $not_writable[] = $relative_path . '/';
            }
        }
    }

    //a file or a folder is deleted from the folder it is in
    foreach (array_merge($removed_files, $removed_folders) as $relative_path) {
        if (!is_writable(dirname(PATH . $relative_path))) {
            $not_writable[] = dirname($relative_path) . '/';
        }
    }

    //
    // 2) back up the files that will be replaced, the archive is written to the disk on close(),
    // so it is closed before any file is touched
    //
    $backup_done = false;
    $backed_up_files = 0;

    if (!sizeof($not_writable)) {
        //the backup of the last update
        if (file_exists($backup_archive_path)) {
            kleeja_unlink($backup_archive_path);
        }

        $backup = new ZipArchive();

        if ($backup->open($backup_archive_path, ZipArchive::CREATE) === true) {
            $backup_done = true;

            foreach (array_merge(array_keys($changed_files), $removed_files) as $relative_path) {
                if (file_exists(PATH . $relative_path)) {
                    $backup_done = $backup->addFile(PATH . $relative_path, $relative_path) && $backup_done;
                    $backed_up_files++;
                }
            }

            $backup_done = $backup->close() && $backup_done;
        }
    }

    //
    // 3) replace the files
    //
    if (sizeof($not_writable)) {
        $adminAjaxContent =
            '1001:::' . sprintf($lang['UPDATE_FILES_NOT_WRITABLE'], implode(', ', array_unique($not_writable)));
    } elseif (!$backup_done) {
        $adminAjaxContent = '1003:::' . $lang['UPDATE_BACKUP_CREATE_FAILED'];
    } else {
        $update_failed = false;
        $failed_files = $created_folders = [];

        //maintenance mode on, and back to what it was after the update
        $siteclose_before = (string) $config['siteclose'];
        update_config('siteclose', '1');

        foreach ($new_folders as $folder) {
            if (!is_dir($folder) && !mkdir($folder, K_DIR_CHMOD, true)) {
                $update_failed = true;
                $failed_files[] = $folder;

                break;
            }

            $created_folders[] = $folder;
        }

        if (!$update_failed) {
            foreach ($changed_files as $relative_path => $source_path) {
                if (!copy($source_path, PATH . $relative_path)) {
                    $update_failed = true;
                    $failed_files[] = $relative_path;

                    break;
                }
            }
        }

        if (!$update_failed) {
            foreach ($removed_paths as $relative_path) {
                if (!kleeja_unlink(PATH . $relative_path)) {
                    $update_failed = true;
                    $failed_files[] = $relative_path;

                    break;
                }
            }
        }

        if ($update_failed) {
            //rollback to backup
            if ($backed_up_files) {
                $zip = new ZipArchive();

                if ($zip->open($backup_archive_path) === true) {
                    //one by one, extractTo() stops at the first file it can't write, like the one that failed
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $zip->extractTo(PATH, $zip->getNameIndex($i));
                    }

                    $zip->close();
                }
            }

            //the files that were not there before the update
            foreach ($new_files as $new_file) {
                if (file_exists($new_file)) {
                    kleeja_unlink($new_file);
                }
            }

            foreach (array_reverse($created_folders) as $folder) {
                if (is_dir($folder)) {
                    kleeja_unlink($folder);
                }
            }

            $adminAjaxContent =
                '1002:::' .
                $lang['UPDATE_PROCESS_FAILED'] .
                (defined('DEV_STAGE') ? ' [failed files: ' . implode(', ', $failed_files) . ']' : '');
        } else {
            $db_errors = [];

            // we will include what we want to do in this file , and kleeja will done
            if (file_exists($db_update_file = PATH . 'cache/update_schema.php')) {
                require_once $db_update_file;

                $db_errors = kleeja_run_db_updates($update_schema, (int) ($config['db_version'] ?? 0));
            }

            // after a success update, delete files and folders in cache
            kleeja_unlink($package_folder);
            kleeja_unlink($package_file);
            delete_cache('', all: true);

            $adminAjaxContent = sizeof($db_errors)
                ? '1004:::' . sprintf($lang['UPDATE_DB_FAILED'], $new_version, implode(', ', $db_errors))
                : '1:::' . sprintf($lang['UPDATE_PROCESS_DONE'], $new_version);
        }

        //the old code can stay in OPcache, and run with the new files
        if (function_exists('opcache_reset') && !ini_get('opcache.restrict_api')) {
            opcache_reset();
        }

        update_config('siteclose', $siteclose_before);
    }
}
