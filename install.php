<?php

global $plugin;

PluginInstallerHelper::registerPlugin([
    'modulname'      => 'about',
    'name'           => 'About',
    'version'        => (string)($plugin['version'] ?? '0.0.0'),
    'admin_file'     => 'admin_about',
    'path'           => 'includes/plugins/about/',
    'author'         => 'Nexpell',
    'website'        => 'https://www.nexpell.de',
    'index_link'     => 'about,info,leistung',
    'hiddenfiles'    => '',
    'sidebar'        => 'deactivated'
]);

safe_query("
    UPDATE settings_plugins
    SET index_link = 'about,info,leistung',
        admin_file = 'admin_about',
        path = 'includes/plugins/about/'
    WHERE modulname = 'about'
");

PluginInstallerHelper::registerAdminNavigation([
    'modulname' => 'about',
    'url'       => 'admincenter.php?site=admin_about',
    'catID'     => 5,
    'sort'      => 1,
    'labels'    => [
        'de' => 'Über uns',
        'en' => 'About Us',
        'it' => 'Chi siamo'
    ]
]);

PluginInstallerHelper::registerWebsiteNavigation([
    'modulname' => 'about',
    'url'       => 'index.php?site=about',
    'mnavID'    => 2,
    'sort'      => 1,
    'labels'    => [
        'de' => 'Über uns',
        'en' => 'About Us',
        'it' => 'Chi siamo'
    ]
]);

PluginInstallerHelper::registerWebsiteNavigation([
    'modulname' => 'leistung',
    'url'       => 'index.php?site=leistung',
    'mnavID'    => 2,
    'sort'      => 2,
    'labels'    => [
        'de' => 'Leistung',
        'en' => 'Services',
        'it' => 'Servizi'
    ]
]);

PluginInstallerHelper::registerWebsiteNavigation([
    'modulname' => 'info',
    'url'       => 'index.php?site=info',
    'mnavID'    => 2,
    'sort'      => 3,
    'labels'    => [
        'de' => 'Info',
        'en' => 'Info',
        'it' => 'Info'
    ]
]);

if (!function_exists('about_upsert_website_nav')) {
    function about_upsert_website_nav(string $modulname, string $url, int $mnavID, int $sort, array $labels): void
    {
        global $_database;

        $mod = escape($modulname);
        $navUrl = escape($url);

        $res = safe_query("
            SELECT snavID
            FROM navigation_website_sub
            WHERE modulname = '$mod'
              AND url = '$navUrl'
            LIMIT 1
        ");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        $snavID = (int)($row['snavID'] ?? 0);

        if ($snavID > 0) {
            safe_query("
                UPDATE navigation_website_sub
                SET mnavID = $mnavID,
                    sort = $sort,
                    indropdown = 1,
                    last_modified = NOW()
                WHERE snavID = $snavID
            ");
        } else {
            safe_query("
                INSERT INTO navigation_website_sub
                    (mnavID, modulname, url, sort, indropdown, last_modified)
                VALUES
                    ($mnavID, '$mod', '$navUrl', $sort, 1, NOW())
            ");
            $snavID = (int)mysqli_insert_id($_database);
        }

        if ($snavID <= 0) {
            $res = safe_query("
                SELECT snavID
                FROM navigation_website_sub
                WHERE modulname = '$mod'
                  AND url = '$navUrl'
                LIMIT 1
            ");
            $row = $res ? mysqli_fetch_assoc($res) : null;
            $snavID = (int)($row['snavID'] ?? 0);
        }

        if ($snavID <= 0) {
            return;
        }

        foreach ($labels as $lang => $label) {
            safe_query("
                INSERT INTO navigation_website_lang
                    (content_key, language, content, modulname, updated_at)
                VALUES
                    ('nav_sub_$snavID', '" . escape((string)$lang) . "', '" . escape((string)$label) . "', '$mod', NOW())
                ON DUPLICATE KEY UPDATE
                    content = VALUES(content),
                    modulname = VALUES(modulname),
                    updated_at = NOW()
            ");
        }
    }
}

about_upsert_website_nav('about', 'index.php?site=about', 2, 1, [
    'de' => 'Über uns',
    'en' => 'About Us',
    'it' => 'Chi siamo'
]);

about_upsert_website_nav('leistung', 'index.php?site=leistung', 2, 2, [
    'de' => 'Leistung',
    'en' => 'Services',
    'it' => 'Servizi'
]);

about_upsert_website_nav('info', 'index.php?site=info', 2, 3, [
    'de' => 'Info',
    'en' => 'Info',
    'it' => 'Info'
]);

PluginInstallerHelper::registerAdminRight('about');


















/* =========================================================
   ABOUT PLUGIN - INSTALL / REPAIR
   SAFE & IDEMPOTENT
========================================================= */

/* ---------------------------
   CONTENT TABLE (rules-style)
---------------------------- */
safe_query("
CREATE TABLE IF NOT EXISTS plugins_about (
  id INT(11) NOT NULL AUTO_INCREMENT,
  content_key VARCHAR(50) NOT NULL,
  language CHAR(2) NOT NULL,
  content MEDIUMTEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_content_lang (content_key, language),
  KEY idx_content_key (content_key),
  KEY idx_language (language)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
");


    safe_query("
    INSERT IGNORE INTO plugins_about (content_key, language, content, updated_at) VALUES
    ('title','de','Über uns',NOW()),
    ('title','en','About us',NOW()),
    ('title','it','Chi siamo',NOW()),
    ('intro','de','Willkommen auf unserer Website.',NOW()),
    ('intro','en','Welcome to our website.',NOW()),
    ('intro','it','Benvenuto sul nostro sito web.',NOW()),
    ('history','de','Unsere Geschichte.',NOW()),
    ('history','en','Our history.',NOW()),
    ('history','it','La nostra storia.',NOW()),
    ('core_values','de','Unsere Werte.',NOW()),
    ('core_values','en','Our values.',NOW()),
    ('core_values','it','I nostri valori.',NOW()),
    ('team','de','Unser Team.',NOW()),
    ('team','en','Our team.',NOW()),
    ('team','it','Il nostro team.',NOW()),
    ('cta','de','Mach mit und werde Teil der Community.',NOW()),
    ('cta','en','Join and become part of the community.',NOW()),
    ('cta','it','Unisciti e diventa parte della community.',NOW()),
    ('image1','de','intro.jpg',NOW()),
    ('image1','en','intro.jpg',NOW()),
    ('image1','it','intro.jpg',NOW()),
    ('image2','de','history.jpg',NOW()),
    ('image2','en','history.jpg',NOW()),
    ('image2','it','history.jpg',NOW()),
    ('image3','de','team.jpg',NOW()),
    ('image3','en','team.jpg',NOW()),
    ('image3','it','team.jpg',NOW()),
    ('info_html','de','<p>Hier findest du weitere Informationen zu unserer Community.</p>',NOW()),
    ('info_html','en','<p>Here you can find more information about our community.</p>',NOW()),
    ('info_html','it','<p>Qui trovi ulteriori informazioni sulla nostra community.</p>',NOW()),
    ('leistung_html','de','<p>Hier findest du unsere Leistungen und Angebote.</p>',NOW()),
    ('leistung_html','en','<p>Here you can find our services and offers.</p>',NOW()),
    ('leistung_html','it','<p>Qui trovi i nostri servizi e le nostre offerte.</p>',NOW())
    ");
