<?php
//
// kleeja language, admin help page
// English
//
// Each guide reads its words from the keys that start with HELP_{GUIDE}_
//  - HELP_{GUIDE}_TITLE and HELP_{GUIDE}_INTRO, the guides of control panel pages take their title from acp.php
//  - a list is numbered: HELP_{GUIDE}_TIP_1, HELP_{GUIDE}_TIP_2 ... it ends at the first missing number,
//    so an item can be added or removed without touching the code
//  - HELP_{GUIDE}_TIP_TITLE, if there is one, replaces the default title of that list
//  - questions and answers are HELP_{GUIDE}_FAQ_Q_1 and HELP_{GUIDE}_FAQ_A_1
//

return [
    //
    // the page
    //
    'HELP_TITLE' => 'Help',
    'HELP_SUBTITLE' =>
        'Guides for every page of the control panel, and the habits that keep a Kleeja service fast and safe.',
    'HELP_ONLINE_DOCS' => 'Online documentation',
    'HELP_REPORT_PROBLEM' => 'Report a problem',
    'HELP_SEARCH' => 'Search the guides',
    'HELP_SEARCH_PLACEHOLDER' => 'Search the guides',
    'HELP_RESULTS_COUNT' => 'Matching guides: %d',
    'HELP_NO_RESULTS' => 'No guide matches your search.',
    'HELP_NO_RESULTS_HINT' => 'Try a shorter word, or the name of a page from the menu.',
    'HELP_CLEAR_SEARCH' => 'Clear search',
    'HELP_CONTENTS' => 'Guides',
    'HELP_OPEN_PAGE' => 'Open this page',
    'HELP_GROUP_START' => 'Getting started',
    'HELP_GROUP_PAGES' => 'Control panel pages',
    'HELP_GROUP_PLUGINS' => 'Plugins',
    'HELP_SECTION_FEATURES' => 'What you can do here',
    'HELP_SECTION_STEPS' => 'Step by step',
    'HELP_SECTION_TIPS' => 'Best practices',
    'HELP_SECTION_WARNINGS' => 'Be careful',
    'HELP_SECTION_FAQ' => 'Common questions',
    'HELP_SECTION_CODE' => 'Example',
    'HELP_PLUGIN_BADGE' => 'Plugin',
    'HELP_PLUGIN_SETTINGS' => 'Plugin settings',
    'HELP_PLUGIN_NO_GUIDE' =>
        'This plugin has not added a guide to this page yet. For help with it, contact its developer.',

    //
    // getting started
    //
    'HELP_OVERVIEW_TITLE' => 'Find your way around',
    'HELP_OVERVIEW_INTRO' =>
        'The control panel has three parts: the sidebar, the top bar, and the page you are working on. Here is what each one does.',
    'HELP_OVERVIEW_FEATURE_1' =>
        'The <strong>sidebar</strong> lists every page of the control panel. The page you are on is highlighted, and its sub-pages appear under it. On a small screen, open it with the menu button in the top bar.',
    'HELP_OVERVIEW_FEATURE_2' =>
        'The <strong>bell</strong> in the top bar shows how many messages and file reports are waiting for you. A dot appears on it when there are any.',
    'HELP_OVERVIEW_FEATURE_3' =>
        'The <strong>Color mode</strong> button switches the control panel between light, dark, or the mode of your device.',
    'HELP_OVERVIEW_FEATURE_4' =>
        'The <strong>help</strong> button (the question mark) opens the guide of the page you are on.',
    'HELP_OVERVIEW_FEATURE_5' =>
        'The <strong>account menu</strong>, under your name, links back to your site, and <strong>Clear Session</strong> signs you out of the control panel.',
    'HELP_OVERVIEW_FEATURE_6' =>
        'Your service name in the top bar, and <strong>View site</strong> at the bottom of the sidebar, open your site in a new tab, so you can check your changes as you make them.',
    'HELP_OVERVIEW_TIP_1' =>
        'Every page starts with a path, such as <em>Dashboard / Settings</em>. Select any part of it to go back.',
    'HELP_OVERVIEW_TIP_2' =>
        'Actions that delete something ask you to confirm first. Read the message before you accept: most deletions cannot be undone.',
    'HELP_OVERVIEW_TIP_3' =>
        'Press <kbd>/</kbd> on this page to jump to the search box, then type a word such as <em>thumbnails</em> or <em>ban</em>.',

    'HELP_FIRST_STEPS_TITLE' => 'First steps after installing',
    'HELP_FIRST_STEPS_INTRO' =>
        'Kleeja works as soon as it is installed, but a few minutes of setup make it yours and keep it safe. Follow these steps in order.',
    'HELP_FIRST_STEPS_STEP_TITLE' => 'Set up your service',
    'HELP_FIRST_STEPS_STEP_1' =>
        'Open <strong>Settings → General settings</strong>. Check the service name, the service URL (it must end with <code>/</code>), both email addresses and the time zone.',
    'HELP_FIRST_STEPS_STEP_2' =>
        'Open <strong>Settings → Upload settings</strong>. Set <strong>Max service size</strong> to the space you can give Kleeja on your server, and choose how file links look.',
    'HELP_FIRST_STEPS_STEP_3' =>
        'Open <strong>Users & Groups</strong>. For each group, select <strong>Files\' Extensions Settings</strong> and keep only the file types you want to accept, each with a sensible size.',
    'HELP_FIRST_STEPS_STEP_4' =>
        'On the same page, select <strong>Edit data</strong> for the Guests and Users groups to set the number of upload fields, the waiting time before downloads and the image watermark.',
    'HELP_FIRST_STEPS_STEP_5' =>
        'Write the rules of your service in <strong>Terms</strong>, so visitors know what they may upload.',
    'HELP_FIRST_STEPS_STEP_6' =>
        'Open <strong>Maintenance</strong>, copy the queue link and add it as a cron job on your hosting, to run every hour or two.',
    'HELP_FIRST_STEPS_STEP_7' =>
        'Go back to the <strong>Dashboard</strong> and fix every notification it shows, such as low PHP upload limits or a writable <code>config.php</code>.',
    'HELP_FIRST_STEPS_STEP_8' =>
        'Open your site in a private browser window and upload a file as a guest, to see exactly what your visitors see.',
    'HELP_FIRST_STEPS_TIP_1' =>
        'Turn on <strong>Maintenance Mode</strong> in General settings while you set things up, and turn it off when you are ready. You can still use the site; visitors see your maintenance message.',
    'HELP_FIRST_STEPS_TIP_2' =>
        'Browse the <strong>Kleeja Store</strong> in Plugins and Styles for features and designs made for Kleeja.',

    'HELP_ROUTINE_TITLE' => 'A routine for running your service',
    'HELP_ROUTINE_INTRO' =>
        'A few minutes of regular care keep your service clean, fast and trusted. Here is a routine you can follow.',
    'HELP_ROUTINE_DAILY_TITLE' => 'Every day',
    'HELP_ROUTINE_DAILY_1' => 'Check the bell in the top bar, and answer new <strong>Messages</strong>.',
    'HELP_ROUTINE_DAILY_2' =>
        'Open <strong>Reports</strong> and deal with each one: delete the reported file if it breaks your terms, then delete the report.',
    'HELP_ROUTINE_DAILY_3' =>
        'On the Dashboard, use <strong>Last visit</strong> to look through the files and images uploaded since you last checked.',
    'HELP_ROUTINE_WEEKLY_TITLE' => 'Every week',
    'HELP_ROUTINE_WEEKLY_1' =>
        'Read the Dashboard notifications and the storage bar, to catch problems before your visitors do.',
    'HELP_ROUTINE_WEEKLY_2' =>
        'Open <strong>Check for updates</strong>. When a new version is out, update Kleeja, then your plugins and styles.',
    'HELP_ROUTINE_WEEKLY_3' =>
        'Clean up old messages and reports with <strong>Delete items older than 30 days</strong>.',
    'HELP_ROUTINE_WEEKLY_4' =>
        'Review <strong>Ban Control</strong>: add the abusers you found, and remove bans that are no longer needed.',
    'HELP_ROUTINE_MONTHLY_TITLE' => 'Every month',
    'HELP_ROUTINE_MONTHLY_1' =>
        'Back up the database and the uploads folder, and keep a copy somewhere other than your server.',
    'HELP_ROUTINE_MONTHLY_2' =>
        'Review the extensions and sizes of each group in <strong>Users & Groups</strong>, and remove the types nobody needs.',
    'HELP_ROUTINE_MONTHLY_3' => 'Delete the plugins and styles you no longer use.',
    'HELP_ROUTINE_MONTHLY_4' =>
        'If the numbers on the Dashboard look wrong, run <strong>re-sync</strong> in Maintenance.',

    'HELP_SECURITY_TITLE' => 'Keep your service safe',
    'HELP_SECURITY_INTRO' =>
        'A file upload service accepts files from strangers, so it needs more care than most sites. These habits protect your server, your visitors and your reputation.',
    'HELP_SECURITY_TIP_1' => 'Keep Kleeja, its plugins and its styles up to date. Updates often fix security problems.',
    'HELP_SECURITY_TIP_2' =>
        'Allow only the file types you need. Each group has its own list in <strong>Users & Groups → Files\' Extensions Settings</strong>.',
    'HELP_SECURITY_TIP_3' =>
        'Keep the <code>.htaccess</code> files in the uploads folder and in its <code>thumbs</code> folder. They stop uploaded files from running as code, and the Dashboard warns you when one is missing.',
    'HELP_SECURITY_TIP_4' =>
        'Once Kleeja is installed, make <code>config.php</code> read-only, with permissions 640 or 644.',
    'HELP_SECURITY_TIP_5' =>
        'Give the <strong>Access Admin control panel</strong> permission only to groups you trust, and use a strong password that you use nowhere else.',
    'HELP_SECURITY_TIP_6' => 'Enable the captcha in <strong>Interface and design settings</strong> to slow down bots.',
    'HELP_SECURITY_TIP_7' => 'Select <strong>Clear Session</strong> when you finish, especially on a shared computer.',
    'HELP_SECURITY_WARNING_1' =>
        'Never allow extensions that a server can run, such as <code>php</code>, <code>phtml</code>, <code>phar</code>, <code>cgi</code>, <code>pl</code>, <code>asp</code> or <code>jsp</code>.',
    'HELP_SECURITY_WARNING_2' =>
        'Think twice before allowing <code>html</code>, <code>htm</code>, <code>svg</code> or <code>js</code>. Browsers can run code inside them, which lets attackers target your visitors.',
    'HELP_SECURITY_WARNING_3' =>
        'Install plugins only from the Kleeja Store or from developers you trust. A plugin can change anything in Kleeja.',

    'HELP_TROUBLESHOOTING_TITLE' => 'Troubleshooting',
    'HELP_TROUBLESHOOTING_INTRO' =>
        'Answers to the problems admins run into most often. If yours is not here, search the online documentation or report it on GitHub.',
    'HELP_TROUBLESHOOTING_FAQ_Q_1' => 'Large files fail to upload. What should I check?',
    'HELP_TROUBLESHOOTING_FAQ_A_1' =>
        'Three limits must all allow the file: the size of its extension in the group\'s <strong>Files\' Extensions Settings</strong>, the free space left under <strong>Max service size</strong>, and <code>upload_max_filesize</code> and <code>post_max_size</code> in the PHP settings of your server. The Dashboard tells you when the PHP limits are too low; ask your host to raise them if you can\'t.',
    'HELP_TROUBLESHOOTING_FAQ_Q_2' => 'My site tells every visitor "We have run out of space".',
    'HELP_TROUBLESHOOTING_FAQ_A_2' =>
        'The uploaded files have reached <strong>Max service size</strong>, so Kleeja stops the site for visitors. Raise the size in Upload settings, or delete files you don\'t need. The control panel keeps working in the meantime.',
    'HELP_TROUBLESHOOTING_FAQ_Q_3' => 'I changed a setting or a template, but the site still looks the same.',
    'HELP_TROUBLESHOOTING_FAQ_A_3' =>
        'Kleeja keeps a cache to stay fast. Open <strong>Maintenance</strong>, select <strong>Delete</strong> next to Delete Cache, then reload your site.',
    'HELP_TROUBLESHOOTING_FAQ_Q_4' => 'The Dashboard numbers are wrong, or Files Control is empty after an upgrade.',
    'HELP_TROUBLESHOOTING_FAQ_A_4' =>
        'Open <strong>Maintenance</strong> and run <strong>re-sync</strong> for the files, images, users and total size. Kleeja keeps these totals ready to stay fast, and re-sync counts them again.',
    'HELP_TROUBLESHOOTING_FAQ_Q_5' => 'Links stopped working after I enabled Mod Rewrite.',
    'HELP_TROUBLESHOOTING_FAQ_A_5' =>
        'Rename <code>htaccess.txt</code> in the Kleeja folder to <code>.htaccess</code>. If your server doesn\'t support rewrite rules, turn Mod Rewrite off again in Advanced settings.',
    'HELP_TROUBLESHOOTING_FAQ_Q_6' => 'Thumbnails don\'t appear.',
    'HELP_TROUBLESHOOTING_FAQ_A_6' =>
        'Check that thumbnails are enabled in Upload settings, and that the <code>thumbs</code> folder exists inside your uploads folder and the server can write to it. The Dashboard warns you when the folder is missing.',
    'HELP_TROUBLESHOOTING_FAQ_Q_7' => 'Kleeja doesn\'t send emails.',
    'HELP_TROUBLESHOOTING_FAQ_A_7' =>
        'Many hosts block the PHP mail function. Install an SMTP plugin, such as Kleeja SMTP Mailer, and send mail through a real mail server.',
    'HELP_TROUBLESHOOTING_FAQ_Q_8' => 'The automatic update failed. Is my site broken?',
    'HELP_TROUBLESHOOTING_FAQ_A_8' =>
        'No. Kleeja backs up every file it replaces to <code>cache/backup.zip</code>, and puts them back when the update fails. The error usually lists the files or folders the server can\'t write to: fix their permissions and try again, or download the new version and upload it by hand.',
    'HELP_TROUBLESHOOTING_FAQ_Q_9' => 'The control panel asks me to sign in again.',
    'HELP_TROUBLESHOOTING_FAQ_A_9' =>
        'For your safety, the control panel session ends after a few hours, even while you stay signed in on your site. Sign in again to continue.',

    //
    // the pages of the control panel
    //
    'HELP_DASHBOARD_INTRO' =>
        'The dashboard is the first page you see after signing in. It shows what needs your attention and how your service is doing.',
    'HELP_DASHBOARD_FEATURE_1' =>
        '<strong>Notifications</strong> list the problems Kleeja found, such as a missing <code>.htaccess</code> file in the uploads folder, PHP limits lower than your largest allowed file, or a new version of Kleeja.',
    'HELP_DASHBOARD_FEATURE_2' =>
        'The <strong>stats boxes</strong> count messages, file reports, uploaded files and images, and registered users, with shortcuts to each page.',
    'HELP_DASHBOARD_FEATURE_3' =>
        'The <strong>Statistics</strong> chart compares the files and images uploaded each day. It appears once Kleeja has collected a few days of data.',
    'HELP_DASHBOARD_FEATURE_4' =>
        '<strong>Other Info</strong> shows how much of Max service size is used, when inactive files were last deleted, and your Kleeja, PHP and database versions.',
    'HELP_DASHBOARD_FEATURE_5' =>
        '<strong>Last visit</strong> opens the files or images uploaded since you last looked, or in the past hour, day, week, month or year.',
    'HELP_DASHBOARD_FEATURE_6' =>
        '<strong>Quick Actions</strong> change the language of a group in one step, and delete the cache.',
    'HELP_DASHBOARD_FEATURE_7' =>
        'Under Dashboard in the sidebar, sub-pages show your server details (PHP settings and search engine visits), the Kleeja blog and the Kleeja development team.',
    'HELP_DASHBOARD_STEP_TITLE' => 'Choose which boxes the dashboard shows',
    'HELP_DASHBOARD_STEP_1' => 'Select <strong>Customization</strong> at the top of the dashboard.',
    'HELP_DASHBOARD_STEP_2' => 'Turn off the switch of every box you don\'t need.',
    'HELP_DASHBOARD_STEP_3' => 'The change is saved right away. Turn a switch back on to show its box again.',
    'HELP_DASHBOARD_TIP_1' =>
        'Read the notifications first: each one points to a real problem on your server and says how to fix it.',
    'HELP_DASHBOARD_TIP_2' => 'Use Last visit once a day to review new uploads quickly, before visitors report them.',
    'HELP_DASHBOARD_TIP_3' => 'Kleeja checks for a new version by itself every ten days, when you open the dashboard.',
    'HELP_DASHBOARD_WARNING_1' =>
        'Watch the storage bar. When the uploaded files reach Max service size, Kleeja stops the whole site for visitors until you raise the size or free some space.',

    'HELP_SETTINGS_INTRO' =>
        'Settings control how the whole service behaves. They are split into categories, which you open from the sub-menu under Settings in the sidebar.',
    'HELP_SETTINGS_FEATURE_1' =>
        '<strong>General settings</strong>: the service name, its URL, the contact and reports email addresses, the time zone, Maintenance Mode with its message, and whether visitors can register.',
    'HELP_SETTINGS_FEATURE_2' =>
        '<strong>Upload settings</strong>: Max service size, the upload folder and file name prefix, file renaming, the link format of files and images, thumbnails, the deletion link, the download safety code, and the extensions that skip the waiting page.',
    'HELP_SETTINGS_FEATURE_3' =>
        '<strong>Interface and design settings</strong>: the welcome message, the statistics page, Who is Online, page statistics in the footer, Google Analytics and the captcha.',
    'HELP_SETTINGS_FEATURE_4' =>
        '<strong>Advanced settings</strong>: automatic deletion of files nobody downloads, the users system, Mod Rewrite (HTML links) and the cookie options.',
    'HELP_SETTINGS_FEATURE_5' =>
        '<strong>Display all the settings</strong> shows every option on one page. Plugins can add their own categories to this menu.',
    'HELP_SETTINGS_STEP_TITLE' => 'Change a setting',
    'HELP_SETTINGS_STEP_1' => 'Open Settings, then pick a category from the sub-menu in the sidebar.',
    'HELP_SETTINGS_STEP_2' => 'Change the values you need. The hints next to a field explain what it accepts.',
    'HELP_SETTINGS_STEP_3' => 'Select <strong>Update Settings</strong> at the bottom of the page to save.',
    'HELP_SETTINGS_TIP_1' =>
        'Turn on Maintenance Mode before big changes, such as moving to a new server. Admins can still use the site while visitors see your message.',
    'HELP_SETTINGS_TIP_2' =>
        'Put <code>{year}</code> and <code>{month}</code> in the upload folder name, so uploads are split into smaller folders that are easier to back up.',
    'HELP_SETTINGS_TIP_3' =>
        'Keep <strong>Change file name</strong> on MD5 or time. It prevents name clashes and makes file links hard to guess.',
    'HELP_SETTINGS_TIP_4' =>
        'Direct links skip the waiting page, statistics and download protection, and their files are never deleted automatically. Use them only when you really need them.',
    'HELP_SETTINGS_WARNING_1' =>
        'Changing the <strong>Users system</strong> or the <strong>cookie</strong> options can sign you out and stop you from signing in again. Change them only if you know what they do.',
    'HELP_SETTINGS_WARNING_2' =>
        'Mod Rewrite only works after you rename <code>htaccess.txt</code> in the Kleeja folder to <code>.htaccess</code>, on a server that supports rewrite rules.',
    'HELP_SETTINGS_WARNING_3' =>
        'Small numbers in <strong>Auto Delete undownloaded files</strong> can remove files your visitors still need.',
    'HELP_SETTINGS_WARNING_4' =>
        'Some options are set for each group, such as the number of upload fields, the waiting period and the watermark. Find them in <strong>Users & Groups → Edit data</strong>.',

    'HELP_FILES_INTRO' =>
        'Files Control lists every file uploaded to your service, newest first, with its size, uploader, IP address, downloads and reports.',
    'HELP_FILES_FEATURE_1' => 'Sort the list by name, size or number of downloads from the column headings.',
    'HELP_FILES_FEATURE_2' =>
        'See who uploaded each file, from which IP address and when. Select an IP address to see every file uploaded from it.',
    'HELP_FILES_FEATURE_3' => 'Spot reported files: the number of reports stands out in the list.',
    'HELP_FILES_FEATURE_4' => 'Select files and delete them together, or delete every file of a search result at once.',
    'HELP_FILES_FEATURE_5' =>
        'Open <strong>Search for files</strong> to filter by name, type, size, uploader, IP address, downloads or last download.',
    'HELP_FILES_STEP_TITLE' => 'Remove files that break your terms',
    'HELP_FILES_STEP_1' =>
        'Find the files: look at the reports, show the files of one IP address, or use <strong>Search for files</strong>.',
    'HELP_FILES_STEP_2' => 'Tick the files you want to remove. <strong>Check all</strong> selects the whole page.',
    'HELP_FILES_STEP_3' =>
        'Select <strong>Delete selected</strong> and confirm. On a search result, <strong>Delete all results</strong> removes every match.',
    'HELP_FILES_TIP_1' =>
        'After removing the files of an abuser, ban their IP address or username in <strong>Ban Control</strong> so they can\'t come back.',
    'HELP_FILES_TIP_2' => 'If the list is empty after an upgrade, run <strong>re-sync</strong> in Maintenance.',
    'HELP_FILES_WARNING_1' =>
        'Deleted files are removed from the server for good. Download a copy first if you might need it.',
    'HELP_FILES_WARNING_2' => 'Files with direct links have no statistics, so their download count stays at zero.',

    'HELP_IMAGES_INTRO' =>
        'Image control shows the uploaded images as a grid of thumbnails, so you can review them much faster than in a list.',
    'HELP_IMAGES_FEATURE_1' =>
        'Open any image in a larger view, with its name, size, date, uploader and number of downloads.',
    'HELP_IMAGES_FEATURE_2' => 'Show every image uploaded from the same IP address, or by the same user, in one step.',
    'HELP_IMAGES_FEATURE_3' => 'Select several images and delete them together.',
    'HELP_IMAGES_TIP_1' =>
        'Keep thumbnails enabled in Upload settings. Without them, this page loads every image at full size, which is slow and wastes bandwidth.',
    'HELP_IMAGES_TIP_2' =>
        'Use <strong>Last visit</strong> on the Dashboard to open only the images uploaded since you last looked.',

    'HELP_MESSAGES_INTRO' => 'Messages collects what visitors send you through the Contact Us page of your site.',
    'HELP_MESSAGES_FEATURE_1' => 'Read each message with the sender\'s email address, IP address and time.',
    'HELP_MESSAGES_FEATURE_2' => 'Reply from the control panel: Kleeja emails your answer to the sender.',
    'HELP_MESSAGES_FEATURE_3' => 'Show only the messages of the past 24 hours.',
    'HELP_MESSAGES_FEATURE_4' =>
        'Delete messages one by one, a selection of them, every message older than 30 days, or all of them.',
    'HELP_MESSAGES_TIP_1' =>
        'Replies are sent by email, so make sure your server can send mail before you count on them.',
    'HELP_MESSAGES_TIP_2' =>
        'To close the Contact Us page, remove the <strong>Access "call us" page</strong> permission from the groups in Users & Groups.',
    'HELP_MESSAGES_WARNING_1' =>
        'Deleting old messages, or all of them, happens in the background through the queue, and can take a while on a busy site.',

    'HELP_REPORTS_INTRO' =>
        'Reports lists the files that visitors flagged from the download page, with a link to each file and the reason they gave.',
    'HELP_REPORTS_FEATURE_1' => 'Open the reported file from its link and judge it yourself.',
    'HELP_REPORTS_FEATURE_2' => 'Reply to the person who sent the report, by email.',
    'HELP_REPORTS_FEATURE_3' => 'Show only the reports of the past 24 hours.',
    'HELP_REPORTS_FEATURE_4' =>
        'Delete reports one by one, a selection of them, every report older than 30 days, or all of them.',
    'HELP_REPORTS_STEP_TITLE' => 'Handle a report',
    'HELP_REPORTS_STEP_1' => 'Open the link in the report and check the file against your terms.',
    'HELP_REPORTS_STEP_2' =>
        'If it breaks them, delete it in <strong>Files Control</strong>, and consider banning the uploader.',
    'HELP_REPORTS_STEP_3' => 'Reply to the person who reported it, then delete the report.',
    'HELP_REPORTS_TIP_1' =>
        'Act on reports quickly. Hosting companies and copyright holders expect illegal content to be removed fast.',
    'HELP_REPORTS_TIP_2' =>
        'Files Control shows how many reports each file has. A file with several reports deserves a look first.',

    'HELP_USERS_INTRO' =>
        'Every visitor belongs to a group, and the group decides what they can do: which files they may upload, how big, and which pages they can use. Here you manage the groups and the users in them.',
    'HELP_USERS_FEATURE_1' =>
        '<strong>Fundamental Groups</strong> are Admins, Guests (visitors who are not signed in) and Users. You can edit them, but not delete them.',
    'HELP_USERS_FEATURE_2' =>
        '<strong>Add new group</strong> creates a group of your own, for example for trusted members, starting from the settings of an existing group.',
    'HELP_USERS_FEATURE_3' =>
        '<strong>Edit data</strong> sets the group name, its language, the number of upload fields, the waiting period before downloads, the seconds between uploads, the watermark and personal file folders.',
    'HELP_USERS_FEATURE_4' =>
        '<strong>Edit Permissions</strong> decides whether the group can enter the control panel, browse its own files or the files of others, and use the Contact Us, Report and statistics pages.',
    'HELP_USERS_FEATURE_5' =>
        '<strong>Files\' Extensions Settings</strong> lists the file types the group may upload and the largest size of each, in kilobytes. The Byte Converter helps with the numbers.',
    'HELP_USERS_FEATURE_6' =>
        '<strong>Users</strong> lists the members of a group, where you can edit a user, move them to another group or delete their files. <strong>New user</strong> creates an account by hand.',
    'HELP_USERS_STEP_TITLE' => 'Let a group upload a new file type',
    'HELP_USERS_STEP_1' => 'Select <strong>Files\' Extensions Settings</strong> on the group.',
    'HELP_USERS_STEP_2' => 'Type the extension without a dot, for example <code>pdf</code>, and add it.',
    'HELP_USERS_STEP_3' => 'Set its largest size in kilobytes (1024 KB is 1 MB), and save.',
    'HELP_USERS_TIP_1' => 'Give Guests smaller limits than Users. It encourages people to register, and limits abuse.',
    'HELP_USERS_TIP_2' => 'The default group is the one new members join, so choose it with care.',
    'HELP_USERS_TIP_3' =>
        'To give trusted members more room, create a group for them instead of raising the limits for everyone.',
    'HELP_USERS_WARNING_1' =>
        'The <strong>Access Admin control panel</strong> permission gives a group full control of your service.',
    'HELP_USERS_WARNING_2' => 'When you delete a group, choose the group its users move to.',
    'HELP_USERS_WARNING_3' => 'Only a founder can edit the account of a founder.',
    'HELP_USERS_WARNING_4' =>
        'If Kleeja uses the users system of another script, manage your users in that script instead.',

    'HELP_SEARCH_INTRO' => 'Advanced search finds the files or users that match several conditions at once.',
    'HELP_SEARCH_FEATURE_1' =>
        '<strong>Search for files</strong> by name, type, uploader, IP address, size, number of downloads, number of reports, or how long ago they were last downloaded.',
    'HELP_SEARCH_FEATURE_2' => '<strong>Search for users</strong> by username or email address.',
    'HELP_SEARCH_FEATURE_3' => 'The results open in Files Control or in the users list, where you can act on them.',
    'HELP_SEARCH_TIP_1' => 'Leave a field empty to ignore it. Fill in only what you know.',
    'HELP_SEARCH_TIP_2' => 'To free space, search for large files that nobody has downloaded for a long time.',
    'HELP_SEARCH_TIP_3' => 'Separate several file types with commas, for example <code>zip,rar</code>.',

    'HELP_PLUGINS_INTRO' =>
        'Plugins add features to Kleeja without changing its code. Here you install, update, enable, disable and delete them.',
    'HELP_PLUGINS_FEATURE_1' =>
        '<strong>Installed Plugins</strong> lists your enabled and disabled plugins, and links to their settings.',
    'HELP_PLUGINS_FEATURE_2' =>
        '<strong>Local Plugins</strong> are plugin folders on your server that are not installed yet.',
    'HELP_PLUGINS_FEATURE_3' => 'The <strong>Kleeja Store</strong> lists plugins made for Kleeja, ready to download.',
    'HELP_PLUGINS_FEATURE_4' =>
        '<strong>Check for updates</strong> finds newer versions of your plugins, and <strong>Update All</strong> installs them.',
    'HELP_PLUGINS_FEATURE_5' => '<strong>Upload from Your Computer</strong> adds a plugin from a zip file.',
    'HELP_PLUGINS_STEP_TITLE' => 'Add a plugin from the store',
    'HELP_PLUGINS_STEP_1' =>
        'Open the <strong>Kleeja Store</strong>, find the plugin and select <strong>Install</strong>. Kleeja downloads it to your server.',
    'HELP_PLUGINS_STEP_2' => 'Open <strong>Local Plugins</strong> and select <strong>Install</strong> on the plugin.',
    'HELP_PLUGINS_STEP_3' =>
        'Open its settings from Installed Plugins, if it has any. Many plugins also add their guide to this Help page.',
    'HELP_PLUGINS_TIP_1' =>
        'Disable a plugin to find out whether it causes a problem. Disabling keeps it installed; deleting it usually removes its settings too.',
    'HELP_PLUGINS_TIP_2' => 'Keep only the plugins you use. Every plugin adds code that runs on your site.',
    'HELP_PLUGINS_WARNING_1' =>
        'A plugin can change anything in Kleeja. Install only official plugins, or plugins from developers you trust.',
    'HELP_PLUGINS_WARNING_2' =>
        'Only a founder can add, update, enable, disable or delete plugins. A plugin that doesn\'t support your Kleeja version can\'t be installed.',

    'HELP_BAN_INTRO' => 'Ban Control stops people from using your service, by IP address or by username.',
    'HELP_BAN_FEATURE_1' => 'Ban one IP address, such as <code>116.10.191.20</code>.',
    'HELP_BAN_FEATURE_2' => 'Ban a whole range with a star, such as <code>116.10.191.*</code>.',
    'HELP_BAN_FEATURE_3' => 'Ban a username, so that account can\'t use the service.',
    'HELP_BAN_FEATURE_4' => 'Delete a ban when it is no longer needed.',
    'HELP_BAN_TIP_1' => 'Find the IP address of an abuser in Files Control, next to the files they uploaded.',
    'HELP_BAN_TIP_2' =>
        'On mobile networks, many people share the same IP addresses. Ban ranges with care, or you may block innocent visitors.',
    'HELP_BAN_WARNING_1' => 'Never ban your own IP address or username.',

    'HELP_TERMS_INTRO' => 'Terms is the text of your service rules, shown on the terms page of your site.',
    'HELP_TERMS_FEATURE_1' => 'Write the rules in the text box. HTML works, for headings, lists and links.',
    'HELP_TERMS_FEATURE_2' => 'Select <strong>Update</strong> to publish them right away.',
    'HELP_TERMS_TIP_1' =>
        'Say clearly which files are not allowed, how long files are kept, and how people can report abuse.',
    'HELP_TERMS_TIP_2' =>
        'Keep the terms in step with your settings: if files are deleted after 30 days without downloads, say so.',
    'HELP_TERMS_TIP_3' => 'If your visitors speak several languages, write the terms in each of them.',

    'HELP_STYLES_INTRO' => 'Styles change the look of your public site. The control panel keeps its own design.',
    'HELP_STYLES_FEATURE_1' =>
        'See the installed styles, and choose the one your visitors see with <strong>Set as default</strong>.',
    'HELP_STYLES_FEATURE_2' =>
        'Add new styles from the <strong>Kleeja Store</strong>, or from a zip file with <strong>Upload from Your Computer</strong>.',
    'HELP_STYLES_FEATURE_3' => '<strong>Check for updates</strong> finds newer versions of your styles.',
    'HELP_STYLES_FEATURE_4' =>
        'A style can be based on another one, and use its templates for the pages it doesn\'t change.',
    'HELP_STYLES_TIP_1' =>
        'After switching styles, visit the upload, download and member pages to check them. Delete the cache if the old look remains.',
    'HELP_STYLES_TIP_2' =>
        'For small changes, such as a banner, use <strong>Extra Templates</strong> instead of editing a style, so your change survives style updates.',
    'HELP_STYLES_WARNING_1' =>
        'Keep the default style installed: other styles use its templates for the pages they don\'t have.',
    'HELP_STYLES_WARNING_2' => 'Only a founder can add or update styles.',

    'HELP_EXTRA_INTRO' =>
        'Extra Templates add your own HTML to every page of your public site, without editing the style.',
    'HELP_EXTRA_FEATURE_1' =>
        'The <strong>Extra Header</strong> shows under the header of your style, for example an announcement or a banner.',
    'HELP_EXTRA_FEATURE_2' =>
        'The <strong>Extra footer</strong> shows above the footer, for example links or a copyright notice.',
    'HELP_EXTRA_TIP_1' =>
        'Use it for announcements, ads and counters, so your changes survive style updates and style changes.',
    'HELP_EXTRA_TIP_2' =>
        'Check your site right after saving. A missing closing tag can break the layout of every page.',
    'HELP_EXTRA_WARNING_1' =>
        'This code runs on every page, for every visitor. Paste only code you understand, from sources you trust.',

    'HELP_UPDATES_INTRO' =>
        'Check for updates compares your version of Kleeja with the latest release, and can update Kleeja for you.',
    'HELP_UPDATES_FEATURE_1' => 'See your version, the latest version and its release notes.',
    'HELP_UPDATES_FEATURE_2' =>
        'Update in one step: Kleeja downloads the release, backs up the files it will replace, copies the new files and upgrades the database.',
    'HELP_UPDATES_FEATURE_3' =>
        'During the update, your site switches to Maintenance Mode, then goes back to how it was.',
    'HELP_UPDATES_FEATURE_4' => 'If something fails, Kleeja puts the old files back, so your site keeps working.',
    'HELP_UPDATES_STEP_TITLE' => 'Update Kleeja',
    'HELP_UPDATES_STEP_1' =>
        'Back up your database and files. The automatic backup only covers the files the update replaces.',
    'HELP_UPDATES_STEP_2' => 'Read the release notes to see what changes.',
    'HELP_UPDATES_STEP_3' =>
        'Select <strong>update now!</strong> and wait for all three steps to finish. Don\'t close the page meanwhile.',
    'HELP_UPDATES_STEP_4' => 'Check your site, then update your plugins and styles too.',
    'HELP_UPDATES_TIP_1' => 'Update when your site is quiet, so fewer visitors see the maintenance message.',
    'HELP_UPDATES_TIP_2' => 'Kleeja checks for a new version by itself every ten days, and shows it on the Dashboard.',
    'HELP_UPDATES_WARNING_1' => 'Only a founder can run the update.',
    'HELP_UPDATES_WARNING_2' =>
        'The server must be able to write to the files of Kleeja. If it can\'t, the update stops before changing anything, and lists the files to fix.',

    'HELP_MAINTENANCE_INTRO' => 'Maintenance keeps the data of Kleeja accurate and its database healthy.',
    'HELP_MAINTENANCE_FEATURE_1' =>
        '<strong>re-sync</strong> counts all files, images, users and the total size again. Kleeja keeps these totals ready to stay fast, and they can drift after an upgrade or a manual change.',
    'HELP_MAINTENANCE_FEATURE_2' =>
        '<strong>Delete Cache</strong> removes temporary files, so Kleeja rebuilds them from your latest settings and templates.',
    'HELP_MAINTENANCE_FEATURE_3' =>
        '<strong>Repair database tables</strong> fixes damaged tables, for example after a crash or a full disk.',
    'HELP_MAINTENANCE_FEATURE_4' =>
        'The <strong>queue link</strong> runs the background tasks of Kleeja, such as deleting inactive files and old messages.',
    'HELP_MAINTENANCE_STEP_TITLE' => 'Run the queue as a cron job',
    'HELP_MAINTENANCE_STEP_1' => 'Select <strong>Copy</strong> next to the queue link.',
    'HELP_MAINTENANCE_STEP_2' =>
        'In the control panel of your hosting, open <strong>Cron jobs</strong> and add a job that runs every hour or two.',
    'HELP_MAINTENANCE_STEP_3' =>
        'Use a command that opens the link, for example <code>curl -s "QUEUE_LINK" &gt; /dev/null</code>, with your queue link in place of QUEUE_LINK.',
    'HELP_MAINTENANCE_TIP_1' =>
        'Without a cron job, the queue runs only when visitors open your pages, so tasks are late on a quiet site.',
    'HELP_MAINTENANCE_TIP_2' => 'Delete the cache after you edit templates or language files by hand.',

    'HELP_SESSION_INTRO' =>
        'Clear Session signs you out of the control panel, but keeps you signed in on your site. You find it in the account menu, at the top of every page.',
    'HELP_SESSION_FEATURE_1' => 'Use it when you finish, so nobody else can use the control panel from your browser.',
    'HELP_SESSION_FEATURE_2' => 'To come back to the control panel, sign in again with your password.',
    'HELP_SESSION_TIP_1' => 'The control panel session also ends by itself after a few hours.',

    //
    // plugins
    //
    'HELP_PLUGIN_DEV_TITLE' => 'Add the guide of your plugin to this page',
    'HELP_PLUGIN_DEV_INTRO' =>
        'Plugin developers can add a guide for their plugin here, or add sections to the guides of Kleeja, with the <code>admin_help_guides</code> hook.',
    'HELP_PLUGIN_DEV_FEATURE_TITLE' => 'How it works',
    'HELP_PLUGIN_DEV_FEATURE_1' =>
        'A guide has a title, an introduction, an icon, and sections of these types: <code>features</code>, <code>steps</code>, <code>tips</code>, <code>warnings</code>, <code>faq</code>, <code>text</code> and <code>code</code>.',
    'HELP_PLUGIN_DEV_FEATURE_2' =>
        'Every text can be a plain string, or its translations, like <code>[\'en\' => \'...\', \'ar\' => \'...\']</code>. The language of the admin is used, then English.',
    'HELP_PLUGIN_DEV_FEATURE_3' =>
        'Set <code>page</code> to your admin page, the value of <code>cp</code>, and the help button on that page opens your guide.',
    'HELP_PLUGIN_DEV_FEATURE_4' =>
        'Use the plugin name as the key of the guide. An installed plugin without a guide is listed here with its description.',
];
