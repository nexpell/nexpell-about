<?php

if (!function_exists('safe_query')) {
    die('Access denied');
}

global $_database, $plugin;

$modulname = 'about';
$version = isset($plugin['version']) ? (string)$plugin['version'] : ($version ?? '0.0.0');
$pluginName = 'About';
$pluginPath = 'includes/plugins/about/';

if (!function_exists('about_sql')) {
    function about_sql($value): string
    {
        return escape((string)$value);
    }
}

if (!function_exists('about_extract_lang')) {
    function about_extract_lang($value, string $lang = 'de'): string
    {
        if (is_array($value)) {
            return (string)($value[$lang] ?? $value['de'] ?? $value['en'] ?? reset($value));
        }

        return (string)$value;
    }
}

if (!function_exists('about_create_content_table')) {
    function about_create_content_table(): void
    {
        safe_query("CREATE TABLE IF NOT EXISTS `plugins_about` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `content_key` VARCHAR(80) NOT NULL,
            `language` CHAR(2) NOT NULL DEFAULT 'de',
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `content` MEDIUMTEXT NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_content_language` (`content_key`, `language`),
            KEY `idx_active_sort` (`is_active`, `sort_order`)
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

if (!function_exists('about_migrate_content_table')) {
    function about_migrate_content_table(): void
    {
        about_create_content_table();
        safe_query("ALTER TABLE `plugins_about` MODIFY `title` VARCHAR(255) NOT NULL DEFAULT ''");

        $columnCheck = safe_query("SHOW COLUMNS FROM `plugins_about` LIKE 'content_key'");
        if ($columnCheck && mysqli_num_rows($columnCheck) > 0) {
            return;
        }

        safe_query("DROP TABLE IF EXISTS `plugins_about_legacy`");
        safe_query("RENAME TABLE `plugins_about` TO `plugins_about_legacy`");
        about_create_content_table();

        $legacyCheck = safe_query("SHOW TABLES LIKE 'plugins_about_legacy'");
        if (!$legacyCheck || mysqli_num_rows($legacyCheck) === 0) {
            return;
        }

        $rows = safe_query("SELECT * FROM `plugins_about_legacy`");
        while ($rows && ($row = mysqli_fetch_assoc($rows))) {
            $lang = isset($row['language']) && preg_match('/^[a-z]{2}$/i', (string)$row['language']) ? strtolower((string)$row['language']) : 'de';
            $title = about_extract_lang($row['title'] ?? '', $lang);
            $content = about_extract_lang($row['content'] ?? '', $lang);
            $active = isset($row['is_active']) ? (int)$row['is_active'] : 1;
            $sort = isset($row['sort_order']) ? (int)$row['sort_order'] : (int)($row['id'] ?? 0);

            safe_query("INSERT INTO `plugins_about` (`content_key`, `language`, `title`, `content`, `is_active`, `sort_order`)
                VALUES ('main', '" . about_sql($lang) . "', '" . about_sql($title) . "', '" . about_sql($content) . "', " . $active . ", " . $sort . ")
                ON DUPLICATE KEY UPDATE
                    `title` = VALUES(`title`),
                    `content` = VALUES(`content`),
                    `is_active` = VALUES(`is_active`),
                    `sort_order` = VALUES(`sort_order`)");
        }
    }
}

if (!function_exists('about_seed_content')) {
    function about_seed_content(): void
    {
        $check = safe_query("SELECT COUNT(*) AS cnt FROM `plugins_about`");
        $row = $check ? mysqli_fetch_assoc($check) : ['cnt' => 0];
        if ((int)($row['cnt'] ?? 0) > 0) {
            return;
        }

        $defaults = [
            ['main', 'de', 'Ueber uns', 'Hier kannst du Informationen ueber dein Projekt, deinen Clan oder deine Organisation hinterlegen.', 1, 10],
            ['main', 'en', 'About us', 'Add information about your project, clan or organization here.', 1, 10],
            ['main', 'it', 'Chi siamo', 'Aggiungi qui le informazioni sul tuo progetto, clan o organizzazione.', 1, 10],
        ];

        foreach ($defaults as $entry) {
            safe_query("INSERT INTO `plugins_about` (`content_key`, `language`, `title`, `content`, `is_active`, `sort_order`)
                VALUES ('" . about_sql($entry[0]) . "', '" . about_sql($entry[1]) . "', '" . about_sql($entry[2]) . "', '" . about_sql($entry[3]) . "', " . (int)$entry[4] . ", " . (int)$entry[5] . ")
                ON DUPLICATE KEY UPDATE
                    `title` = VALUES(`title`),
                    `content` = VALUES(`content`),
                    `is_active` = VALUES(`is_active`),
                    `sort_order` = VALUES(`sort_order`)");
        }
    }
}

if (!function_exists('about_register_plugin')) {
    function about_register_plugin(string $version, string $pluginPath): void
    {
        global $_database;

        $plugin = safe_query("SELECT `pluginID` FROM `settings_plugins` WHERE `modulname` = 'about' LIMIT 1");
        if ($plugin && ($row = mysqli_fetch_assoc($plugin))) {
            safe_query("UPDATE `settings_plugins` SET
                `admin_file` = 'admin/about.php',
                `activate` = 1,
                `author` = 'Nexpell',
                `website` = 'https://www.nexpell.de',
                `index_link` = 'about,leistung,info',
                `hiddenfiles` = '',
                `version` = '" . about_sql($version) . "',
                `path` = '" . about_sql($pluginPath) . "',
                `status_display` = 1,
                `plugin_display` = 1,
                `widget_display` = 1,
                `delete_display` = 1,
                `sidebar` = 'deactivated'
                WHERE `pluginID` = " . (int)$row['pluginID']);
        } else {
            safe_query("INSERT INTO `settings_plugins`
                (`modulname`, `admin_file`, `activate`, `author`, `website`, `index_link`, `hiddenfiles`, `version`, `path`, `status_display`, `plugin_display`, `widget_display`, `delete_display`, `sidebar`)
                VALUES ('about', 'admin/about.php', 1, 'Nexpell', 'https://www.nexpell.de', 'about,leistung,info', '', '" . about_sql($version) . "', '" . about_sql($pluginPath) . "', 1, 1, 1, 1, 'deactivated')");
        }

        $labels = [
            'de' => 'About',
            'en' => 'About',
            'it' => 'About',
        ];

        foreach ($labels as $lang => $name) {
            safe_query("INSERT INTO `settings_plugins_lang` (`content_key`, `language`, `content`, `modulname`, `updated_at`)
                VALUES ('plugin_name_about', '" . about_sql($lang) . "', '" . about_sql($name) . "', 'about', NOW())
                ON DUPLICATE KEY UPDATE
                    `content` = VALUES(`content`),
                    `modulname` = VALUES(`modulname`),
                    `updated_at` = VALUES(`updated_at`)");

            safe_query("INSERT INTO `settings_plugins_lang` (`content_key`, `language`, `content`, `modulname`, `updated_at`)
                VALUES ('plugin_info_about', '" . about_sql($lang) . "', '', 'about', NOW())
                ON DUPLICATE KEY UPDATE
                    `content` = VALUES(`content`),
                    `modulname` = VALUES(`modulname`),
                    `updated_at` = VALUES(`updated_at`)");
        }
    }
}
if (!function_exists('about_upsert_dashboard_lang')) {
    function about_upsert_dashboard_lang(int $linkID): void
    {
        $labels = [
            'de' => 'About',
            'en' => 'About',
            'it' => 'About',
        ];

        foreach ($labels as $lang => $label) {
            safe_query("INSERT INTO `navigation_dashboard_lang` (`content_key`, `language`, `content`, `modulname`, `updated_at`)
                VALUES ('nav_link_" . $linkID . "', '" . about_sql($lang) . "', '" . about_sql($label) . "', 'about', NOW())
                ON DUPLICATE KEY UPDATE
                    `content` = VALUES(`content`),
                    `modulname` = VALUES(`modulname`),
                    `updated_at` = VALUES(`updated_at`)");
        }
    }
}
if (!function_exists('about_ensure_dashboard_nav')) {
    function about_ensure_dashboard_nav(): void
    {
        global $_database;

        $linkID = 0;
        $existing = safe_query("SELECT `linkID` FROM `navigation_dashboard_links` WHERE `modulname` = 'about' LIMIT 1");
        if ($existing && ($row = mysqli_fetch_assoc($existing))) {
            $linkID = (int)$row['linkID'];
            safe_query("UPDATE `navigation_dashboard_links` SET
                `catID` = 3,
                `url` = 'admincenter.php?site=admin_about',
                `sort` = 20
                WHERE `linkID` = " . $linkID);
        } else {
            safe_query("INSERT INTO `navigation_dashboard_links` (`catID`, `modulname`, `url`, `sort`)
                VALUES (3, 'about', 'admincenter.php?site=admin_about', 20)");
            $linkID = (int)mysqli_insert_id($_database);
        }

        if ($linkID > 0) {
            about_upsert_dashboard_lang($linkID);
        }
    }
}
if (!function_exists('about_upsert_website_lang')) {
    function about_upsert_website_lang(int $navSubID, string $modulname, array $labels): void
    {
        foreach ($labels as $lang => $label) {
            safe_query("INSERT INTO `navigation_website_lang` (`content_key`, `language`, `content`, `modulname`, `updated_at`)
                VALUES ('nav_sub_" . $navSubID . "', '" . about_sql($lang) . "', '" . about_sql($label) . "', '" . about_sql($modulname) . "', NOW())
                ON DUPLICATE KEY UPDATE
                    `content` = VALUES(`content`),
                    `modulname` = VALUES(`modulname`),
                    `updated_at` = VALUES(`updated_at`)");
        }
    }
}
if (!function_exists('about_ensure_website_nav')) {
    function about_ensure_website_nav(string $modulname, string $url, int $sort, array $labels): void
    {
        global $_database;

        $navSubID = 0;
        $existing = safe_query("SELECT `snavID` FROM `navigation_website_sub` WHERE `modulname` = '" . about_sql($modulname) . "' AND `url` = '" . about_sql($url) . "' LIMIT 1");
        if ($existing && ($row = mysqli_fetch_assoc($existing))) {
            $navSubID = (int)$row['snavID'];
            safe_query("UPDATE `navigation_website_sub` SET
                `mnavID` = 3,
                `sort` = " . (int)$sort . ",
                `indropdown` = 1,
                `last_modified` = NOW()
                WHERE `snavID` = " . $navSubID);
        } else {
            safe_query("INSERT INTO `navigation_website_sub` (`mnavID`, `modulname`, `url`, `sort`, `indropdown`, `last_modified`)
                VALUES (3, '" . about_sql($modulname) . "', '" . about_sql($url) . "', " . (int)$sort . ", 1, NOW())");
            $navSubID = (int)mysqli_insert_id($_database);
        }

        if ($navSubID > 0) {
            about_upsert_website_lang($navSubID, $modulname, $labels);
        }
    }
}


if (!function_exists('about_track_installation')) {
    function about_track_installation(string $version): void
    {
        $columns = [];
        $resCols = safe_query("SHOW COLUMNS FROM `settings_plugins_installed`");
        while ($resCols && ($col = mysqli_fetch_assoc($resCols))) {
            $columns[] = (string)$col['Field'];
        }

        if (!in_array('modulname', $columns, true)) {
            return;
        }

        $data = [
            'name' => 'About',
            'modulname' => 'about',
            'description' => 'About plugin',
            'version' => $version,
            'author' => 'Nexpell',
            'url' => 'https://www.nexpell.de',
            'folder' => 'about',
            'installed_date' => '__NOW__',
        ];

        $existing = safe_query("SELECT `id` FROM `settings_plugins_installed` WHERE `modulname` = 'about' ORDER BY `id` ASC LIMIT 1");
        $row = $existing ? mysqli_fetch_assoc($existing) : null;

        if ($row && isset($row['id'])) {
            $sets = [];
            foreach ($data as $column => $value) {
                if ($column === 'modulname' || !in_array($column, $columns, true)) {
                    continue;
                }
                $sets[] = $column === 'installed_date'
                    ? "`installed_date` = NOW()"
                    : "`" . $column . "` = '" . about_sql($value) . "'";
            }

            if (!empty($sets)) {
                safe_query("UPDATE `settings_plugins_installed` SET " . implode(', ', $sets) . " WHERE `id` = " . (int)$row['id']);
            }
            return;
        }

        $insertColumns = [];
        $insertValues = [];
        foreach ($data as $column => $value) {
            if (!in_array($column, $columns, true)) {
                continue;
            }
            $insertColumns[] = "`" . $column . "`";
            $insertValues[] = $column === 'installed_date' ? 'NOW()' : "'" . about_sql($value) . "'";
        }

        if (!empty($insertColumns)) {
            safe_query("INSERT INTO `settings_plugins_installed` (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")");
        }
    }
}
about_migrate_content_table();
about_seed_content();
about_register_plugin($version, $pluginPath);
about_ensure_dashboard_nav();
about_track_installation($version);

about_ensure_website_nav('about', 'index.php?site=about', 1, [
    'de' => 'Ueber uns',
    'en' => 'About us',
    'it' => 'Chi siamo',
]);

about_ensure_website_nav('leistung', 'index.php?site=leistung', 2, [
    'de' => 'Leistung',
    'en' => 'Services',
    'it' => 'Servizi',
]);

about_ensure_website_nav('info', 'index.php?site=info', 3, [
    'de' => 'Info',
    'en' => 'Info',
    'it' => 'Info',
]);

safe_query("INSERT IGNORE INTO `user_role_admin_navi_rights` (`id`, `type`, `modulname`) VALUES (1, 'link', 'about')");