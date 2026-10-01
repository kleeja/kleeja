<?php
/**
 *
 * @package Kleeja
 * @copyright (c) 2007 Kleeja.net
 * @license http://www.kleeja.net/license
 *
 */

//no for directly open
if (!defined('IN_COMMON')) {
    exit();
}

// We are in the plugin system, plugins files won't work outside here
define('IN_PLUGINS_SYSTEM', true);

/**
 * Kleeja Plugins System
 * @package plugins
 */
class Plugins
{
    /**
     * The catalog of plugins and styles of Kleeja 4, in the kleeja/store-catalog repository
     */
    public const STORE_CATALOG_LINK = 'https://raw.githubusercontent.com/kleeja/store-catalog/master/kleeja-4-catalog.json';

    /**
     * A plugin name is a folder name, it can't be empty, "." or ".."
     */
    private const NAME_PATTERN = '/^[a-z0-9_-][a-z0-9_.-]*$/i';

    /**
     * List of loaded plugins
     */
    private array $plugins = [];

    /**
     * All hooks from all plugins listed in this variable
     */
    private array $all_plugins_hooks = [];
    /**
     * Names of the plugins of each hook, in the same order of $all_plugins_hooks, kept only in DEV_STAGE for debugging
     */
    private array $hooks_plugins = [];
    private array $installed_plugins = [];
    private array $installed_plugins_info = [];

    private string $plugin_path = PATH . 'plugins';

    private static ?Plugins $instance = null;

    /**
     * The $kleeja_plugin entry of each plugin whose init.php was included, it can't be included twice
     */
    private static array $definitions = [];

    /**
     * Why the last static action on a plugin failed
     */
    private static string $last_error = '';

    /**
     * Initiating the class
     */
    public function __construct()
    {
        global $SQL, $dbprefix;

        //if plugins system is turned off, then stop right now!
        if (defined('STOP_PLUGINS')) {
            return;
        }

        if (defined('KLEEJA_PLUGINS_FOLDER')) {
            $this->plugin_path = PATH . KLEEJA_PLUGINS_FOLDER;
        }

        // Get installed plugins
        $query = [
            'SELECT' => 'plg_name, plg_ver',
            'FROM' => "{$dbprefix}plugins",
            'WHERE' => 'plg_disabled = 0',
        ];

        $result = $SQL->build($query);

        while ($row = $SQL->fetch($result)) {
            $this->installed_plugins[$row['plg_name']] = $row['plg_ver'];
        }
        $SQL->freeresult($result);

        $this->load_enabled_plugins();
    }

    /**
     * Load the plugins from root/plugins folder
     * @return void
     */
    private function load_enabled_plugins(): void
    {
        $dh = opendir($this->plugin_path);

        while ($dh !== false and false !== ($folder_name = readdir($dh))) {
            if (is_dir($this->plugin_path . '/' . $folder_name) && preg_match('/[a-z0-9_.]{3,}/', $folder_name)) {
                if (!empty($this->installed_plugins[$folder_name])) {
                    if ($this->fetch_plugin($folder_name)) {
                        array_push($this->plugins, $folder_name);
                    }
                }
            }
        }

        //sort the plugins from high to low priority
        krsort($this->plugins);
    }

    /**
     * Get the plugin information and other things
     * @param  string $plugin_name
     * @return bool
     */
    private function fetch_plugin(string $plugin_name): bool
    {
        //load the plugin
        @include_once $this->plugin_path . '/' . $plugin_name . '/init.php';

        if (empty($kleeja_plugin)) {
            return false;
        }

        self::$definitions[$plugin_name] = $kleeja_plugin[$plugin_name] ?? [];

        $priority = $kleeja_plugin[$plugin_name]['information']['plugin_priority'];
        $this->installed_plugins_info[$plugin_name] = $kleeja_plugin[$plugin_name]['information'];

        //bring the real priority of plugin and replace current one
        $plugin_current_priority = array_search($plugin_name, $this->plugins);
        unset($this->plugins[$plugin_current_priority]);
        $this->plugins[$priority] = $plugin_name;

        //update plugin if current loaded version is > than installed one
        if ($this->installed_plugins[$plugin_name]) {
            if (
                version_compare(
                    $this->installed_plugins[$plugin_name],
                    $kleeja_plugin[$plugin_name]['information']['plugin_version'],
                    '<',
                )
            ) {
                if (is_callable($kleeja_plugin[$plugin_name]['update'])) {
                    global $SQL, $dbprefix;

                    //update plugin
                    $kleeja_plugin[$plugin_name]['update'](
                        $this->installed_plugins[$plugin_name],
                        $kleeja_plugin[$plugin_name]['information']['plugin_version'],
                    );

                    //update current plugin version
                    $update_query = [
                        'UPDATE' => "{$dbprefix}plugins",
                        'SET' => 'plg_ver = :version',
                        'WHERE' => 'plg_name = :name',
                        'BIND' => [
                            'version' => kleeja_html_encode(
                                $kleeja_plugin[$plugin_name]['information']['plugin_version'],
                            ),
                            'name' => kleeja_html_encode($plugin_name),
                        ],
                    ];

                    $SQL->build($update_query);
                }
            }
        }

        //add plugin hooks to global hooks, depend on its priority
        if (!empty($kleeja_plugin[$plugin_name]['functions'])) {
            foreach ($kleeja_plugin[$plugin_name]['functions'] as $hook_name => $hook_value) {
                if (empty($this->all_plugins_hooks[$hook_name][$priority])) {
                    $this->all_plugins_hooks[$hook_name][$priority] = [];
                }
                array_push($this->all_plugins_hooks[$hook_name][$priority], $hook_value);
                krsort($this->all_plugins_hooks[$hook_name]);

                if (defined('DEV_STAGE')) {
                    $this->hooks_plugins[$hook_name][$priority][] = $plugin_name;
                    krsort($this->hooks_plugins[$hook_name]);
                }
            }
        }

        return true;
    }

    /**
     * get an installed plugin information
     * @param  string $plugin_name
     * @return array
     */
    public function installed_plugin_info(string $plugin_name): array
    {
        if (!empty($this->installed_plugins_info[$plugin_name])) {
            return $this->installed_plugins_info[$plugin_name];
        }

        return [];
    }

    /**
     * Bring all codes of this hook
     * This function scattered all over kleeja files
     * @param  string $hook_name
     * @param  array  $args
     * @return array
     */
    public function run(string $hook_name, array $args = []): array
    {
        $return_value = $to_be_returned = [];

        if (!empty($this->all_plugins_hooks[$hook_name])) {
            foreach ($this->all_plugins_hooks[$hook_name] as $_ => $functions) {
                foreach ($functions as $function) {
                    if (is_callable($function)) {
                        $return_value = $function($args);

                        if (is_array($return_value)) {
                            $args = array_merge($args, $return_value);
                            $to_be_returned = array_merge($to_be_returned, $return_value);
                        }
                    }
                }
            }
        }

        return sizeof($to_be_returned) ? $to_be_returned : [];
    }

    /**
     * return current instance Plugins class
     *
     * @return Plugins
     */
    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * return debug info about plugins system
     * @return array
     */
    public function getDebugInfo(): array
    {
        if (!defined('DEV_STAGE')) {
            return [];
        }

        return [
            'all_plugins_hooks' => $this->all_plugins_hooks,
            'hooks_plugins' => $this->hooks_plugins,
            'installed_plugins' => $this->installed_plugins,
        ];
    }

    /**
     * Get a plugin from the store, install it and enable it, in one call.
     * The steps that were done before are skipped, so a plugin in the plugins folder is not downloaded again.
     * Like the other static actions, it doesn't check the permissions of the current user,
     * and the plugin hooks run from the next request.
     * @param  string $plugin_name
     * @return bool   false if it fails, and lastError() says why
     */
    public static function installFromStore(string $plugin_name): bool
    {
        if (!self::is_local($plugin_name) && !self::download($plugin_name)) {
            return false;
        }

        $row = self::installed_row($plugin_name);

        if (!$row) {
            return self::install($plugin_name);
        }

        return $row['plg_disabled'] == 0 || self::enable($plugin_name);
    }

    /**
     * Download a plugin from the store to the plugins folder, without installing it.
     * If the plugin is there, its files are updated, and they are kept if the download fails.
     * @param  string $plugin_name
     * @return bool   false if it fails, and lastError() says why
     */
    public static function download(string $plugin_name): bool
    {
        if (!preg_match(self::NAME_PATTERN, $plugin_name)) {
            return self::fail('PACKAGE_REMOTE_FILE_MISSING', $plugin_name);
        }

        if (!class_exists('ZipArchive')) {
            return self::fail('NO_ZIP_ARCHIVE');
        }

        if (!($store_plugin = self::store_plugin($plugin_name))) {
            return false;
        }

        $compatible =
            version_compare(strtolower($store_plugin['kleeja_version']['min']), KLEEJA_VERSION, '<=') &&
            version_compare(strtolower($store_plugin['kleeja_version']['max']), KLEEJA_VERSION, '>=');

        if (!$compatible && !(defined('IGNORE_STORE_COMPATIBILITY') && IGNORE_STORE_COMPATIBILITY)) {
            return self::fail('PACKAGE_N_CMPT_KLJ');
        }

        $plugins_folder = PATH . KLEEJA_PLUGINS_FOLDER;
        $folder = "{$plugins_folder}/{$plugin_name}";
        $backup = "{$folder}_backup";
        $archive = PATH . "cache/{$plugin_name}.zip";

        $downloaded = FetchFile::make($store_plugin['file']['url'])
            ->setDestinationPath($archive)
            ->isBinaryFile(true)
            ->get();

        $zip = new ZipArchive();

        if ($downloaded !== true || $zip->open($archive) !== true) {
            if (file_exists($archive)) {
                kleeja_unlink($archive);
            }

            return self::fail('STORE_SERVER_ERROR');
        }

        //github puts the files in one folder, like kj-ftp-1.1.1/
        $zip_folder = explode('/', (string) $zip->getNameIndex(0))[0];

        //left by an earlier update, rename() can't replace a folder that has files
        if (is_dir($backup)) {
            kleeja_unlink($backup);
        }

        //move the current files out of the way, to bring them back if the new ones fail
        $had_folder = is_dir($folder);
        $moved = !$had_folder || rename($folder, $backup);

        $extracted = $moved && preg_match(self::NAME_PATTERN, $zip_folder) && $zip->extractTo($plugins_folder);

        $zip->close();
        kleeja_unlink($archive);

        if ($extracted && $zip_folder !== $plugin_name) {
            $extracted = rename("{$plugins_folder}/{$zip_folder}", $folder);
        }

        if (!$extracted || !file_exists("{$folder}/init.php")) {
            if ($moved) {
                if (is_dir($folder)) {
                    kleeja_unlink($folder);
                }

                if ($had_folder) {
                    rename($backup, $folder);
                }
            }

            return self::fail('EXTRACT_ZIP_FAILED', KLEEJA_PLUGINS_FOLDER);
        }

        if ($had_folder) {
            kleeja_unlink($backup);
        }

        return true;
    }

    /**
     * Install a plugin that is in the plugins folder, it is enabled after that
     * @param  string $plugin_name
     * @return bool   false if it fails, and lastError() says why
     */
    public static function install(string $plugin_name): bool
    {
        global $SQL, $dbprefix;

        if (!self::is_local($plugin_name)) {
            return self::fail('PLUGIN_NOT_FOUND', $plugin_name);
        }

        if (self::installed_row($plugin_name)) {
            return self::fail('PLUGIN_EXISTS_BEFORE');
        }

        $plugin = self::definition($plugin_name);
        $plugin_info = $plugin['information'] ?? [];

        if (empty($plugin_info['plugin_version'])) {
            return self::fail('PLUGIN_NOT_FOUND', $plugin_name);
        }

        //the versions of Kleeja that the plugin works on, an empty one or 0 is no limit
        if (
            (!empty($plugin_info['plugin_kleeja_version_min']) &&
                version_compare(KLEEJA_VERSION, $plugin_info['plugin_kleeja_version_min'], '<')) ||
            (!empty($plugin_info['plugin_kleeja_version_max']) &&
                version_compare(KLEEJA_VERSION, $plugin_info['plugin_kleeja_version_max'], '>'))
        ) {
            return self::fail('PACKAGE_N_CMPT_KLJ');
        }

        $description = $plugin_info['plugin_description'] ?? '';

        if (is_array($description)) {
            $description = !empty($description['en']) ? $description['en'] : (string) reset($description);
        }

        //don't show mysql errors of the install callback
        if (!defined('SQL_NO_ERRORS')) {
            define('SQL_NO_ERRORS', true);
        }

        $insert_query = [
            'INSERT' =>
                '`plg_name` ,`plg_ver`, `plg_author`, `plg_dsc`, `plg_icon`, `plg_uninstall`, `plg_instructions`, `plg_store`, `plg_files`',
            'INTO' => "{$dbprefix}plugins",
            'VALUES' => ":name, :version, :author, :description, '', '', '', '', ''",
            'BIND' => [
                'name' => kleeja_html_encode($plugin_name),
                'version' => kleeja_html_encode($plugin_info['plugin_version']),
                'author' => kleeja_html_encode($plugin_info['plugin_developer'] ?? ''),
                'description' => kleeja_html_encode($description),
            ],
        ];

        $SQL->build($insert_query);

        if (is_callable($plugin['install'] ?? null)) {
            $plugin['install']($SQL->insert_id());
        }

        delete_cache('', all: true);

        return true;
    }

    /**
     * Enable an installed plugin
     * @param  string $plugin_name
     * @return bool   false if it isn't installed
     */
    public static function enable(string $plugin_name): bool
    {
        return self::set_disabled($plugin_name, false);
    }

    /**
     * Disable an installed plugin, its data stays in the database
     * @param  string $plugin_name
     * @return bool   false if it isn't installed
     */
    public static function disable(string $plugin_name): bool
    {
        return self::set_disabled($plugin_name, true);
    }

    /**
     * Why the last static action failed, after it returned false
     * @return string
     */
    public static function lastError(): string
    {
        return self::$last_error;
    }

    /**
     * @param  string $plugin_name
     * @param  bool   $disabled
     * @return bool
     */
    private static function set_disabled(string $plugin_name, bool $disabled): bool
    {
        global $SQL, $dbprefix;

        if (!self::installed_row($plugin_name)) {
            return self::fail('PLUGIN_NOT_INSTALLED', $plugin_name);
        }

        $update_query = [
            'UPDATE' => "{$dbprefix}plugins",
            'SET' => 'plg_disabled = :disabled',
            'WHERE' => 'plg_name = :name',
            'BIND' => [
                'disabled' => $disabled ? 1 : 0,
                'name' => kleeja_html_encode($plugin_name),
            ],
        ];

        $SQL->build($update_query);

        delete_cache('', all: true);

        return true;
    }

    /**
     * Is the plugin in the plugins folder
     * @param  string $plugin_name
     * @return bool
     */
    private static function is_local(string $plugin_name): bool
    {
        return preg_match(self::NAME_PATTERN, $plugin_name) &&
            file_exists(PATH . KLEEJA_PLUGINS_FOLDER . "/{$plugin_name}/init.php");
    }

    /**
     * The row of the plugin in the plugins table
     * @param  string     $plugin_name
     * @return array|null null if it isn't installed
     */
    private static function installed_row(string $plugin_name): ?array
    {
        global $SQL, $dbprefix;

        $query = [
            'SELECT' => 'plg_id, plg_disabled',
            'FROM' => "{$dbprefix}plugins",
            'WHERE' => 'plg_name = :name',
            'BIND' => ['name' => kleeja_html_encode($plugin_name)],
        ];

        $result = $SQL->build($query);
        $row = $SQL->fetch($result);
        $SQL->freeresult($result);

        return $row ?: null;
    }

    /**
     * The $kleeja_plugin entry of the plugin, from its init.php
     * @param  string $plugin_name
     * @return array
     */
    private static function definition(string $plugin_name): array
    {
        //the enabled plugins were included when the instance was made
        self::getInstance();

        if (!isset(self::$definitions[$plugin_name])) {
            $kleeja_plugin = [];

            @include_once PATH . KLEEJA_PLUGINS_FOLDER . "/{$plugin_name}/init.php";

            self::$definitions[$plugin_name] = $kleeja_plugin[$plugin_name] ?? [];
        }

        return self::$definitions[$plugin_name];
    }

    /**
     * The plugin in the store catalog, its 'file' is the version that this site gets
     * @param  string      $plugin_name
     * @return array|false false if the catalog can't be read or the plugin isn't in it
     */
    private static function store_plugin(string $plugin_name): array|false
    {
        if (!($catalog = cache()->get('store_catalog'))) {
            $catalog = json_decode((string) FetchFile::make(self::STORE_CATALOG_LINK)->get(), true);

            if (!is_array($catalog)) {
                return self::fail('STORE_SERVER_ERROR');
            }

            cache()->save('store_catalog', $catalog);
        }

        foreach ($catalog as $item) {
            if (($item['type'] ?? '') != 'plugin' || ($item['name'] ?? '') != $plugin_name) {
                continue;
            }

            //the preview versions are for the developers
            $item['file'] = isset($item['preview']) && defined('DEV_STAGE') ? $item['preview'] : $item['file'] ?? [];

            if (!empty($item['file']['url'])) {
                return $item;
            }
        }

        return self::fail('PACKAGE_REMOTE_FILE_MISSING', $plugin_name);
    }

    /**
     * Keep the reason of a failure for lastError()
     * @param  string $lang_key the messages are in acp.php, the key itself is kept if it isn't loaded
     * @param  string ...$args
     * @return false
     */
    private static function fail(string $lang_key, string ...$args): bool
    {
        global $lang;

        self::$last_error = vsprintf($lang[$lang_key] ?? $lang_key, $args);

        return false;
    }
}

function runHook(string $hookName, array $definedVariables): array
{
    return Plugins::getInstance()->run($hookName, $definedVariables);
}
