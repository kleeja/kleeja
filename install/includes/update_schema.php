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

// $update_schema[10]['functions'] = [
//     function () {
//     },
//     function () {
//     },
// ];
