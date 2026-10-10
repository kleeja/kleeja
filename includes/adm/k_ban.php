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

//for style ..
$stylee = 'admin_ban';
$H_FORM_KEYS_GET = kleeja_add_form_key_get('adm_ban_get');
$H_FORM_KEYS = kleeja_add_form_key('adm_ban');

$action = basename(ADMIN_PATH) . '?cp=' . basename(__FILE__, '.php');
$delete_item =
    basename(ADMIN_PATH) . '?cp=' . basename(__FILE__, '.php') . '&amp;' . $H_FORM_KEYS_GET . '&amp;case=del&amp;k=';
$new_item_action = basename(ADMIN_PATH) . '?cp=' . basename(__FILE__, '.php') . '&amp;case=new';

//
// Check form key
//

$case = g('case', default: 'view');
$update_ban_content = false;

$query = [
    'SELECT' => 'ban',
    'FROM' => "{$dbprefix}stats",
];

$result = $SQL->build($query);

$current_ban_data = $SQL->fetch_array($result);
$SQL->freeresult($result);

//an empty ban list must not become one empty entry
$banned_items = array_values(array_filter(explode('|', (string) $current_ban_data['ban'])));

$show_message = false;

if ($case === 'del' && ig('k')) {
    if (!kleeja_check_form_key_get('adm_ban_get')) {
        http_response_code(401);
        kleeja_admin_err($lang['INVALID_GET_KEY'], $action);
    }

    $to_delete = g('k');

    $banned_items = array_filter($banned_items, function (string $item) use ($to_delete, $lang, &$show_message): bool {
        if (md5($item) === $to_delete) {
            $show_message = sprintf($lang['ITEM_DELETED'], kleeja_html_display($item));

            return false;
        }

        return true;
    });

    $update_ban_content = $show_message;
}

if ($case === 'new') {
    if (!kleeja_check_form_key('adm_ban')) {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], title: $lang['ERROR'], redirect: $action, rs: 1);
    }

    $to_add = p('k', 'str', '');

    if (!empty($to_add)) {
        //only the new item is encoded, the others are saved encoded already
        $banned_items[] = kleeja_html_encode($to_add);
        $show_message = $lang['BAN_UPDATED'];
        $update_ban_content = true;
    }
}

if ($update_ban_content) {
    $banned_items = array_values(array_filter($banned_items));
    //update
    $update_query = [
        'UPDATE' => "{$dbprefix}stats",
        'SET' => 'ban = :ban',
        //the items are kept as they are saved, encoding them all again would add a layer at every change
        'BIND' => ['ban' => implode('|', $banned_items)],
    ];

    $SQL->build($update_query);

    if ($SQL->affected()) {
        delete_cache('data_ban');
    }
}

array_walk($banned_items, function (string &$value, int $key): void {
    //del_key is of the saved text, the content is encoded once to be printed
    $value = ['content' => kleeja_html_display($value), 'del_key' => md5($value), 'id' => $key + 1];
});
