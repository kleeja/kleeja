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

//we are in cache now ..
define('IN_CACHE', true);

//
//In the future here will be a real cache class
//this codes, it's just a sample and usefull for
//some time ..
//
class KleejaCache
{
    public function get(string $name): mixed
    {
        if (defined('DEV_STAGE')) {
            return false;
        }

        $name = preg_replace('![^a-z0-9_]!', '_', $name);

        if (file_exists(PATH . 'cache/' . $name . '.php')) {
            include PATH . 'cache/' . $name . '.php';

            return empty($data) ? false : $data;
        } else {
            return false;
        }
    }

    public function exists(string $name): bool
    {
        $name = preg_replace('![^a-z0-9_]!', '_', $name);

        return file_exists(PATH . 'cache/' . $name . '.php');
    }

    public function save(string $name, mixed $data, int $time = 86400): void
    {
        $name = preg_replace('![^a-z0-9_]!i', '_', $name);
        $data_for_save = '<?' . 'php' . "\n";
        $data_for_save .= '//Cache file, generated for Kleeja at ' . gmdate('d-m-Y h:i A') . "\n\n";
        $data_for_save .= '//No direct opening' . "\n";
        $data_for_save .= '(!defined("IN_COMMON") ? exit("hacking attemp!") : null);' . "\n\n";
        $data_for_save .= '//return false after x time' . "\n";
        $data_for_save .= 'if(time() > ' . (time() + $time) . ') return false;' . "\n\n";
        $data_for_save .= '$data = ' . var_export($data, true) . ";\n\n//end of cache";

        if ($fd = @fopen(PATH . 'cache/' . $name . '.php', 'w')) {
            @flock($fd, LOCK_EX); // exlusive look
            @fwrite($fd, $data_for_save);
            @flock($fd, LOCK_UN);
            @fclose($fd);
        }
    }

    public function clean(string|array $name): void
    {
        if (is_array($name)) {
            foreach ($name as $n) {
                $this->clean($n);
            }

            return;
        }

        $name = preg_replace('![^a-z0-9_]!i', '_', $name);
        kleeja_unlink(PATH . 'cache/' . $name . '.php');
    }
}

$cache = new KleejaCache();

//
//get config data from config table  ...
//
if (!($config = $cache->get('data_config'))) {
    $config = [];
    $query = [
        'SELECT' => 'c.name, c.value',
        'FROM' => "{$dbprefix}config c",
        'WHERE' => 'c.dynamic = 0',
    ];

    extract(runHook('qr_select_config_cache', get_defined_vars()));

    $result = $SQL->build($query);

    while ($row = $SQL->fetch_array($result)) {
        $config[$row['name']] = $row['value'];
    }

    $SQL->freeresult($result);

    $cache->save('data_config', $config);
}

//
//stats to cache
//
if (!($stats = $cache->get('data_stats'))) {
    $query = [
        'SELECT' =>
            's.files, s.imgs, s.sizes, s.users, s.last_f_del, s.last_google' .
            ', s.last_bing, s.google_num, s.bing_num, s.lastuser',
        'FROM' => "{$dbprefix}stats s",
    ];

    extract(runHook('qr_select_stats_cache', get_defined_vars()));

    $result = $SQL->build($query);

    while ($row = $SQL->fetch_array($result)) {
        $stats = [
            'stat_files' => $row['files'],
            'stat_imgs' => $row['imgs'],
            'stat_sizes' => $row['sizes'],
            'stat_users' => $row['users'],
            'stat_last_f_del' => $row['last_f_del'],
            'stat_last_google' => $row['last_google'],
            'stat_last_bing' => $row['last_bing'],
            'stat_google_num' => $row['google_num'],
            'stat_bing_num' => $row['bing_num'],
            'stat_last_user' => $row['lastuser'],
        ];

        extract(runHook('while_fetch_stats_in_cache', get_defined_vars()));
    }

    $SQL->freeresult($result);

    //save the stats for hour and then refresh them
    $cache->save('data_stats', $stats, 3600);

    //also, save the data for the charts later
    $query = [
        'SELECT' => 'f.filter_uid',
        'FROM' => "{$dbprefix}filters f",
        'WHERE' => "f.filter_type='stats_for_acp' AND f.filter_uid = :day",
        'BIND' => ['day' => date('d-n-Y')],
    ];

    $result = $SQL->build($query);

    //if already there is stats for this day, just update it, if not insert a new one
    if ($SQL->num_rows($result)) {
        $f_query = [
            'UPDATE' => "{$dbprefix}filters",
            'SET' => 'filter_value = :value',
            'WHERE' => "filter_type='stats_for_acp' AND filter_uid = :day",
            'BIND' => [
                'value' => implode(':', [$stats['stat_files'], $stats['stat_imgs'], $stats['stat_sizes']]),
                'day' => date('d-n-Y'),
            ],
        ];
    } else {
        $f_query = [
            'INSERT' => 'filter_uid, filter_type ,filter_value ,filter_time',
            'INTO' => "{$dbprefix}filters",
            'VALUES' => ":day, 'stats_for_acp', :value, :time",
            'BIND' => [
                'day' => date('d-n-Y'),
                'value' => implode(':', [$stats['stat_files'], $stats['stat_imgs'], $stats['stat_sizes']]),
                'time' => time(),
            ],
        ];
    }

    $SQL->build($f_query);
}

//make them as seperated vars
extract($stats);
unset($stats);

//
//get banned ips data from stats table  ...
//
if (!($banss = $cache->get('data_ban'))) {
    $query = [
        'SELECT' => 's.ban',
        'FROM' => "{$dbprefix}stats s",
    ];

    extract(runHook('qr_select_ban_cache', get_defined_vars()));
    $result = $SQL->build($query);

    $row = $SQL->fetch_array($result);
    $ban1 = $row['ban'];
    $SQL->freeresult($result);

    $banss = [];

    if (!empty($ban1) || $ban1 !== ' ' || $ban1 !== '  ') {
        //seperate ips ..
        $ban2 = explode('|', $ban1);

        for ($i = 0; $i < sizeof($ban2); $i++) {
            $banss[$i] = $ban2[$i];
        }
    }

    unset($ban1, $ban2, $gt);

    $cache->save('data_ban', $banss);
}

//
//get rules data from stats table  ...
//
if (!($ruless = $cache->get('data_rules'))) {
    $query = [
        'SELECT' => 's.rules',
        'FROM' => "{$dbprefix}stats s",
    ];

    extract(runHook('qr_select_rules_cache', get_defined_vars()));
    $result = $SQL->build($query);

    $row = $SQL->fetch_array($result);
    $ruless = $row['rules'];
    $SQL->freeresult($result);

    $cache->save('data_rules', $ruless);
}

//
//get ex-header-footer data from stats table  …
//
if (!($extras = $cache->get('data_extra'))) {
    $query = [
        'SELECT' => 's.ex_header, s.ex_footer',
        'FROM' => "{$dbprefix}stats s",
    ];

    extract(runHook('qr_select_extra_cache', get_defined_vars()));
    $result = $SQL->build($query);

    $row = $SQL->fetch_array($result);

    $extras = [
        'header' => $row['ex_header'],
        'footer' => $row['ex_footer'],
    ];

    $SQL->freeresult($result);

    $cache->save('data_extra', $extras);
}

//
//Get groups data
//
if (!($d_groups = $cache->get('data_groups'))) {
    $d_groups = [];

    //data
    $query = [
        'SELECT' => 'g.*',
        'FROM' => "{$dbprefix}groups g",
        'ORDER_BY' => 'g.group_id ASC',
    ];

    extract(runHook('qr_select_groups_cache', get_defined_vars()));
    $result = $SQL->build($query);

    //Initiating
    while ($row = $SQL->fetch_array($result)) {
        //the name is saved encoded twice (p() and kleeja_html_encode()), it is kept encoded once to be printed
        $row['group_name'] = kleeja_html_display($row['group_name']);
        $d_groups[$row['group_id']]['data'] = $row;
        $d_groups[$row['group_id']]['configs'] = [];
        $d_groups[$row['group_id']]['acls'] = [];
        $d_groups[$row['group_id']]['exts'] = [];
    }
    $SQL->freeresult($result);

    //configs
    $query = [
        'SELECT' => 'g.group_id, g.name, g.value',
        'FROM' => "{$dbprefix}groups_data g",
        'ORDER_BY' => 'g.group_id ASC',
    ];

    extract(runHook('qr_select_groups_data_cache', get_defined_vars()));
    $result = $SQL->build($query);

    while ($row = $SQL->fetch_array($result)) {
        $d_groups[$row['group_id']]['configs'][$row['name']] = $row['value'];
    }
    $SQL->freeresult($result);

    //acl
    $query2 = [
        'SELECT' => 'g.group_id, g.acl_name, g.acl_can',
        'FROM' => "{$dbprefix}groups_acl g",
        'ORDER_BY' => 'g.group_id ASC',
    ];

    extract(runHook('qr_select_groups_acls_cache', get_defined_vars()));
    $result2 = $SQL->build($query2);

    while ($row = $SQL->fetch_array($result2)) {
        $d_groups[$row['group_id']]['acls'][$row['acl_name']] = (int) $row['acl_can'];
    }
    $SQL->freeresult($result2);

    //exts
    $query3 = [
        'SELECT' => 'g.group_id, g.ext, g.size',
        'FROM' => "{$dbprefix}groups_exts g",
        'ORDER_BY' => 'g.group_id ASC',
    ];

    extract(runHook('qr_select_groups_exts_cache', get_defined_vars()));
    $result3 = $SQL->build($query3);

    while ($row = $SQL->fetch_array($result3)) {
        $d_groups[$row['group_id']]['exts'][$row['ext']] = (int) $row['size'];
    }
    $SQL->freeresult($result3);

    unset($query, $query2, $query3, $result, $result2, $result3);

    $cache->save('data_groups', $d_groups);
}

// ummm, does this useful here
extract(runHook('in_cache_page', get_defined_vars()));

function cache(): KleejaCache
{
    static $cache = null;

    if (is_null($cache)) {
        $cache = new KleejaCache();
    }

    return $cache;
}
