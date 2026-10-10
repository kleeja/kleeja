<?php
/**
 *
 * @package install
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

// not for directly open
if (!defined('IN_COMMON')) {
    exit();
}

// drop older versions update support after 2 year of release or as appropriate in order to
// make this file smaller in size.
//
// This file runs in two places: in this version (install/update.php, the updater and the database
// update of the dashboard), and in the updater of the version that is being updated, which copies the
// new files and runs this file with its own functions and database class still loaded.
// So the functions here use only what the older versions have too: no BIND (the database class of
// Kleeja 3 doesn't know it), the result of a query is given to fetch() and freeresult() (Kleeja 3 takes
// the last result when it gets false), and nothing of includes/ that is new in this version.
// A failed query of Kleeja 3 on MySQL throws an exception and stops its updater, with the site closed.
$update_schema = [];

$update_schema[9]['sql'] = [
    'files_size_big' => "ALTER TABLE `{$dbprefix}files` CHANGE `size` `size` BIGINT(20)  NOT NULL  DEFAULT '0';",
    'group_size_big' => "ALTER TABLE `{$dbprefix}groups_exts` CHANGE `size` `size` BIGINT(20)  NOT NULL  DEFAULT '0';",
    'files_index_type' => "ALTER TABLE `{$dbprefix}files` ADD INDEX `type` (`type`);",
    'id_form_img' =>
        'INSERT INTO `' .
        $dbprefix .
        'config` (`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`) VALUES (\'id_form_img\', X\'6964\', \'<select id=\"id_form_img\" name=\"id_form_img\">\r\n <option <IF NAME=\"con.id_form_img==id\">selected=\"selected\"</IF> value=\"id\">{lang.IDF_IMG}</option>\r\n <option <IF NAME=\"con.id_form_img ==filename\">selected=\"selected\"</IF> value=\"filename\">{lang.IDFF_IMG}</option>\r\n<option <IF NAME=\"con.id_form_img ==direct\">selected=\"selected\"</IF> value=\"direct\">{lang.IDFD_IMG}</option>\r\n </select>\n\', \'21\', X\'75706C6F6164\', \'0\', \'0\');',
];

//drop the columns of the old design that nothing reads anymore, one column a query, as SQLite drops only one at a time
$update_schema[10]['sql'] = [
    'stats_drop_last_file' => "ALTER TABLE `{$dbprefix}stats` DROP COLUMN `last_file`;",
    'stats_drop_today' => "ALTER TABLE `{$dbprefix}stats` DROP COLUMN `today`;",
    'stats_drop_counter_today' => "ALTER TABLE `{$dbprefix}stats` DROP COLUMN `counter_today`;",
    'stats_drop_counter_all' => "ALTER TABLE `{$dbprefix}stats` DROP COLUMN `counter_all`;",
    'stats_drop_counter_yesterday' => "ALTER TABLE `{$dbprefix}stats` DROP COLUMN `counter_yesterday`;",
    'users_drop_session_id' => "ALTER TABLE `{$dbprefix}users` DROP COLUMN `session_id`;",
    'plugins_drop_plg_icon' => "ALTER TABLE `{$dbprefix}plugins` DROP COLUMN `plg_icon`;",
    'plugins_drop_plg_uninstall' => "ALTER TABLE `{$dbprefix}plugins` DROP COLUMN `plg_uninstall`;",
    'plugins_drop_plg_instructions' => "ALTER TABLE `{$dbprefix}plugins` DROP COLUMN `plg_instructions`;",
    'plugins_drop_plg_store' => "ALTER TABLE `{$dbprefix}plugins` DROP COLUMN `plg_store`;",
    'plugins_drop_plg_files' => "ALTER TABLE `{$dbprefix}plugins` DROP COLUMN `plg_files`;",
];

$update_schema[10]['functions'] = [
    //Damask replaced the Masmak admin theme, and an update over the old files leaves its folder behind
    function () {
        if (is_dir(PATH . 'admin/Masmak')) {
            kleeja_unlink(PATH . 'admin/Masmak');
        }
    },
    //the plugins of older versions are not made for this version and its KleejaDatabase,
    //so they are disabled until the admin updates them and enables them again
    function () {
        global $SQL, $dbprefix;

        $SQL->build([
            'UPDATE' => "{$dbprefix}plugins",
            'SET' => 'plg_disabled = 1',
        ]);
    },
    //configField() gave every text field the same id before, so the labels of those settings pointed to nothing
    function () {
        global $SQL, $dbprefix;

        $old_id = 'id="kj_meta_seo_home_meta_keywords"';
        $names = [];

        if ($result = $SQL->query("SELECT name, `option` FROM `{$dbprefix}config`")) {
            while ($row = $SQL->fetch($result)) {
                //the name goes into the query below, the names of the settings are words, so the others are skipped
                if (str_contains((string) $row['option'], $old_id) && preg_match('/^[a-z0-9_]+$/i', $row['name'])) {
                    $names[] = $row['name'];
                }
            }

            $SQL->freeresult($result);
        }

        foreach ($names as $name) {
            $SQL->query(
                "UPDATE `{$dbprefix}config` SET `option` = REPLACE(`option`, '{$old_id}', 'id=\"{$name}\"')" .
                    " WHERE name = '{$name}'",
            );
        }
    },
    //the updater of Kleeja 3 doesn't update the styles folder, so the bootstrap style of this version is
    //taken from the package that the updater keeps in the cache folder while it runs this file.
    //the old files are kept, like an upload over them, the styles built on bootstrap may still load them
    function () {
        $folder = PATH . 'styles/bootstrap';

        if (!class_exists('ZipArchive') || !is_dir($folder)) {
            return;
        }

        $style_version = function (string|false $info): string {
            return $info !== false && preg_match('/^\s*version\s*=\s*([0-9][0-9a-z.\-]*)/mi', $info, $m) ? $m[1] : '0';
        };

        $installed_version = $style_version(@file_get_contents("{$folder}/info.txt"));
        $newest = null;

        //cache/kleeja-<version>.zip, github puts its files in one folder, like kleeja-kleeja-0a1b2c3/
        foreach (glob(PATH . 'cache/kleeja-*.zip') ?: [] as $archive) {
            $zip = new ZipArchive();

            if ($zip->open($archive) !== true) {
                continue;
            }

            $prefix = explode('/', (string) $zip->getNameIndex(0))[0] . '/styles/bootstrap/';
            $version = $style_version($zip->getFromName($prefix . 'info.txt'));
            $zip->close();

            if (version_compare($version, $newest['version'] ?? $installed_version, '>')) {
                $newest = ['archive' => $archive, 'prefix' => $prefix, 'version' => $version];
            }
        }

        if ($newest === null) {
            return;
        }

        $zip = new ZipArchive();

        if ($zip->open($newest['archive']) !== true) {
            return;
        }

        $files = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $relative_path = substr($name, strlen($newest['prefix']));

            if (
                !str_starts_with($name, $newest['prefix']) ||
                $relative_path === '' ||
                str_ends_with($name, '/') ||
                str_contains($relative_path, '..')
            ) {
                continue;
            }

            //the folder that has to be writable, the closest one that is there for a new folder
            $target = "{$folder}/{$relative_path}";
            $writable_path = file_exists($target) ? $target : dirname($target);

            while (!file_exists($writable_path)) {
                $writable_path = dirname($writable_path);
            }

            //one file that can't be written, and nothing is changed, half of a style is worse than the old one
            if (!is_writable($writable_path)) {
                $zip->close();

                return;
            }

            $files[$i] = $target;
        }

        foreach ($files as $i => $target) {
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), defined('K_DIR_CHMOD') ? K_DIR_CHMOD : 0755, true);
            }

            file_put_contents($target, $zip->getFromIndex($i));
        }

        $zip->close();
    },
    //og_default is the new version of the default style, a site that uses it, or a style that depends on it,
    //moves to og_default, or to bootstrap if it can't be downloaded, then the folder of the old one is deleted
    function () {
        global $SQL, $dbprefix;

        $styles_folder = PATH . 'styles';
        $og_default_folder = "{$styles_folder}/og_default";

        if (
            !($result = $SQL->query("SELECT value FROM `{$dbprefix}config` WHERE name IN ('style', 'style_depend_on')"))
        ) {
            //it isn't known whether the site uses it, so its folder stays
            return;
        }

        $uses_default = false;

        while ($row = $SQL->fetch($result)) {
            $uses_default = $uses_default || $row['value'] === 'default';
        }

        $SQL->freeresult($result);

        if ($uses_default && !is_dir($og_default_folder) && class_exists('ZipArchive')) {
            //install/update.php doesn't load it
            if (!class_exists('FetchFile')) {
                require_once PATH . 'includes/FetchFile.php';
            }

            $archive = PATH . 'cache/og_default.zip';

            $downloaded = FetchFile::make('https://github.com/kleeja/og_default/archive/refs/tags/3.0.zip')
                ->setDestinationPath($archive)
                ->isBinaryFile(true)
                ->get();

            $zip = new ZipArchive();

            //FetchFile doesn't verify the certificate of the server, so only the file of the 3.0 release is extracted
            if (
                $downloaded === true &&
                hash_file('sha256', $archive) === 'eb92b20e0a899f436a1b9a601b0ce60b7ffbd0c2d018fb99bb0074920f60ee99' &&
                $zip->open($archive) === true
            ) {
                //github puts the files in one folder, og_default-3.0/
                $zip_folder = explode('/', (string) $zip->getNameIndex(0))[0];

                if ($zip->extractTo($styles_folder) && $zip_folder !== 'og_default') {
                    rename("{$styles_folder}/{$zip_folder}", $og_default_folder);
                }

                $zip->close();
            }

            if (file_exists($archive)) {
                kleeja_unlink($archive);
            }
        }

        if ($uses_default) {
            //neither of them depends on another style
            $new_style = file_exists("{$og_default_folder}/info.txt") ? 'og_default' : 'bootstrap';

            $switched =
                $SQL->query("UPDATE `{$dbprefix}config` SET value = '{$new_style}' WHERE name = 'style'") &&
                $SQL->query("UPDATE `{$dbprefix}config` SET value = '' WHERE name = 'style_depend_on'");

            //the site would point to a style that is gone
            if (!$switched) {
                return;
            }
        }

        if (is_dir("{$styles_folder}/default")) {
            kleeja_unlink("{$styles_folder}/default");
        }
    },
];
