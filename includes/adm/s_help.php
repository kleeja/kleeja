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

//the guides have their own language file, lang/{language}/help.php
get_lang('help');

//a translation that misses some words shows them in English
if ($config['language'] !== 'en') {
    $lang += (array) include PATH . 'lang/en/help.php';
}

//for style ..
$stylee = 'admin_help';

//the guides are shown in groups, in this order
$help_groups = [
    'start' => $lang['HELP_GROUP_START'],
    'pages' => $lang['HELP_GROUP_PAGES'],
    'plugins' => $lang['HELP_GROUP_PLUGINS'],
];

//
// the guides of Kleeja, their words are in help.php, under HELP_{GUIDE}_ keys, see adm_help_lang_section()
// the guide of a control panel page takes its title from the menu, and 'page' is the page it explains,
// so the help button of that page opens it
//
$help_core_guides = [
    'overview' => ['group' => 'start', 'icon' => 'compass', 'sections' => ['features', 'tips']],
    'first_steps' => ['group' => 'start', 'icon' => 'rocket', 'sections' => ['steps', 'tips']],
    'routine' => [
        'group' => 'start',
        'icon' => 'calendar-check',
        'sections' => ['features:DAILY', 'features:WEEKLY', 'features:MONTHLY'],
    ],
    'security' => ['group' => 'start', 'icon' => 'shield-halved', 'sections' => ['tips', 'warnings']],
    'troubleshooting' => ['group' => 'start', 'icon' => 'life-ring', 'sections' => ['faq']],

    'dashboard' => [
        'page' => 'start',
        'title' => 'R_CPINDEX',
        'icon' => 'gauge-high',
        'sections' => ['features', 'steps', 'tips', 'warnings'],
    ],
    'settings' => [
        'page' => 'a_configs',
        'link' => './?cp=options',
        'title' => 'R_CONFIGS',
        'icon' => $ext_icons['configs'],
        'sections' => ['features', 'steps', 'tips', 'warnings'],
    ],
    'files' => [
        'page' => 'c_files',
        'title' => 'R_FILES',
        'icon' => $ext_icons['files'],
        'sections' => ['features', 'steps', 'tips', 'warnings'],
    ],
    'images' => [
        'page' => 'd_img_ctrl',
        'title' => 'R_IMG_CTRL',
        'icon' => $ext_icons['img_ctrl'],
        'sections' => ['features', 'tips'],
    ],
    'messages' => [
        'page' => 'e_calls',
        'title' => 'R_CALLS',
        'icon' => $ext_icons['calls'],
        'sections' => ['features', 'tips', 'warnings'],
    ],
    'reports' => [
        'page' => 'f_reports',
        'title' => 'R_REPORTS',
        'icon' => $ext_icons['reports'],
        'sections' => ['features', 'steps', 'tips'],
    ],
    'users' => [
        'page' => 'g_users',
        'title' => 'R_USERS',
        'icon' => $ext_icons['users'],
        'sections' => ['features', 'steps', 'tips', 'warnings'],
    ],
    'search' => [
        'page' => 'h_search',
        'title' => 'R_SEARCH',
        'icon' => $ext_icons['search'],
        'sections' => ['features', 'tips'],
    ],
    'plugins' => [
        'page' => 'j_plugins',
        'title' => 'R_PLUGINS',
        'icon' => $ext_icons['plugins'],
        'sections' => ['features', 'steps', 'tips', 'warnings'],
    ],
    'ban' => [
        'page' => 'k_ban',
        'title' => 'R_BAN',
        'icon' => $ext_icons['ban'],
        'sections' => ['features', 'tips', 'warnings'],
    ],
    'terms' => [
        'page' => 'l_rules',
        'title' => 'R_RULES',
        'icon' => $ext_icons['rules'],
        'sections' => ['features', 'tips'],
    ],
    'styles' => [
        'page' => 'm_styles',
        'title' => 'R_STYLES',
        'icon' => $ext_icons['styles'],
        'sections' => ['features', 'tips', 'warnings'],
    ],
    'extra' => [
        'page' => 'n_extra',
        'title' => 'R_EXTRA',
        'icon' => $ext_icons['extra'],
        'sections' => ['features', 'tips', 'warnings'],
    ],
    'updates' => [
        'page' => 'p_check_update',
        'title' => 'R_CHECK_UPDATE',
        'icon' => $ext_icons['check_update'],
        'sections' => ['features', 'steps', 'tips', 'warnings'],
    ],
    'maintenance' => [
        'page' => 'r_repair',
        'title' => 'R_REPAIR',
        'icon' => $ext_icons['repair'],
        'sections' => ['features', 'steps', 'tips'],
    ],
    //it signs out once opened, so no link to it
    'session' => [
        'page' => 'b_lgoutcp',
        'link' => '',
        'title' => 'R_LGOUTCP',
        'icon' => 'right-from-bracket',
        'sections' => ['features', 'tips'],
    ],
];

$help_guides = [];

foreach ($help_core_guides as $help_id => $help_guide) {
    $help_key = 'HELP_' . strtoupper($help_id);

    $help_guides[$help_id] = [
        'group' => $help_guide['group'] ?? 'pages',
        'icon' => $help_guide['icon'],
        'title' => $lang[$help_guide['title'] ?? $help_key . '_TITLE'],
        'intro' => $lang[$help_key . '_INTRO'] ?? '',
        'page' => $help_guide['page'] ?? '',
        'link' => $help_guide['link'] ?? (isset($help_guide['page']) ? './?cp=' . $help_guide['page'] : ''),
        'sections' => array_map(fn($section) => adm_help_lang_section($help_key, $section), $help_guide['sections']),
    ];
}

//
// plugins add their own guides here, or more sections to the guides of Kleeja,
// the guide of plugin developers below shows how
//
extract(runHook('admin_help_guides', get_defined_vars()));

//
// the installed plugins, the ones that have no guide yet are listed with their description and settings
//
$query = [
    'SELECT' => 'plg_name',
    'FROM' => "{$dbprefix}plugins",
    'WHERE' => 'plg_disabled = 0',
    'ORDER BY' => 'plg_id ASC',
];

$result = $SQL->build($query);

while ($row = $SQL->fetch($result)) {
    $help_plugin = $row['plg_name'];
    $help_plugin_info = Plugins::getInstance()->installed_plugin_info($help_plugin);

    //installed, but its files are gone
    if (empty($help_plugin_info)) {
        continue;
    }

    $help_plugin_icon = file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $help_plugin . '/icon.png')
        ? PATH . KLEEJA_PLUGINS_FOLDER . '/' . $help_plugin . '/icon.png'
        : '';

    if (isset($help_guides[$help_plugin])) {
        $help_guides[$help_plugin] += [
            'badge' => $lang['HELP_PLUGIN_BADGE'],
            'image' => empty($help_guides[$help_plugin]['icon']) ? $help_plugin_icon : '',
        ];

        continue;
    }

    $help_settings_page = $help_plugin_info['settings_page'] ?? '';

    $help_guides[$help_plugin] = [
        'group' => 'plugins',
        'title' => $help_plugin_info['plugin_title'] ?? $help_plugin,
        'intro' => $help_plugin_info['plugin_description'] ?? '',
        'image' => $help_plugin_icon,
        'badge' => $lang['HELP_PLUGIN_BADGE'],
        'link' =>
            $help_settings_page !== '' && !preg_match('/^https?:\/\//', $help_settings_page)
                ? './?' . kleeja_html_encode($help_settings_page)
                : '',
        'link_title' => $lang['HELP_PLUGIN_SETTINGS'],
        'sections' => [['type' => 'text', 'text' => $lang['HELP_PLUGIN_NO_GUIDE']]],
    ];
}

$SQL->freeresult($result);

//the last guide, for plugin developers, with an example of the hook above
$help_dev_example = <<<'PHP'
$kleeja_plugin['my_plugin']['functions'] = [
    'admin_help_guides' => function ($args) {
        $help_guides = $args['help_guides'];

        // the key is the plugin name
        $help_guides['my_plugin'] = [
            'title' => ['en' => 'My plugin', 'ar' => 'إضافتي'],
            'intro' => ['en' => 'What the plugin does, in a sentence or two.', 'ar' => '...'],
            'icon' => 'puzzle-piece',
            // the help button of ?cp=my_plugin opens this guide
            'page' => 'my_plugin',
            'link' => './?cp=my_plugin',
            'sections' => [
                ['type' => 'features', 'items' => [['en' => 'Send files by email.', 'ar' => '...']]],
                ['type' => 'steps', 'items' => ['en' => ['Open the settings.', 'Save.'], 'ar' => ['...', '...']]],
                ['type' => 'faq', 'items' => [['q' => 'A question?', 'a' => 'The answer.']]],
            ],
        ];

        // a section in a guide of Kleeja
        $help_guides['settings']['sections'][] = ['type' => 'tips', 'items' => ['...']];

        return compact('help_guides');
    },
];
PHP;

$help_guides['plugin_dev'] = [
    'group' => 'plugins',
    'icon' => 'code',
    'title' => $lang['HELP_PLUGIN_DEV_TITLE'],
    'intro' => $lang['HELP_PLUGIN_DEV_INTRO'],
    'sections' => [
        adm_help_lang_section('HELP_PLUGIN_DEV', 'features'),
        ['type' => 'code', 'code' => $help_dev_example],
    ],
];

//
// ready for the template: grouped, in order, with the sections of each guide
//
$help_order = array_flip(array_keys($help_groups));
$help_rows = [];

foreach ($help_guides as $help_id => $help_guide) {
    if (is_array($help_guide)) {
        $help_rows[] = adm_help_prepare_guide((string) $help_id, $help_guide, $help_groups);
    }
}

//the guides of a group keep their order
usort($help_rows, fn($a, $b) => $help_order[$a['group']] <=> $help_order[$b['group']]);

//the help button of a page opens its guide
$help_from = g('page');
$help_focus = '';

foreach ($help_rows as $help_n => $help_row) {
    $help_rows[$help_n]['group_title'] = $help_groups[$help_row['group']];
    $help_rows[$help_n]['group_first'] = $help_n === 0 || $help_rows[$help_n - 1]['group'] !== $help_row['group'];
    $help_rows[$help_n]['group_last'] =
        !isset($help_rows[$help_n + 1]) || $help_rows[$help_n + 1]['group'] !== $help_row['group'];

    if ($help_focus === '' && $help_from !== '' && $help_row['page'] === $help_from) {
        $help_focus = $help_row['id'];
    }

    //tips and warnings sit beside the rest on wide screens
    $help_rows[$help_n]['body'] = $help_rows[$help_n]['aside'] = '';

    foreach ($help_row['sections'] as $help_section) {
        $help_section_items = $help_section['items'];
        $help_rows[$help_n][$help_section['aside'] ? 'aside' : 'body'] .= $tpl->display('admin_help_section');
    }

    if ($help_rows[$help_n]['body'] === '') {
        $help_rows[$help_n]['body'] = $help_rows[$help_n]['aside'];
        $help_rows[$help_n]['aside'] = '';
    }
}

unset($help_n, $help_row, $help_section, $help_section_items);
