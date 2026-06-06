<?php
global $str, $modulname, $version, $_database;

$modulname = 'about';
$version = '1.0.1';
$str = 'About';

echo "<div class='card'><div class='card-header'>{$str} Database Update</div><div class='card-body'>";

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

if (!function_exists('about_extract_lang')) {
    function about_extract_lang(string $multiLangText, string $lang): string
    {
        if (preg_match('/\[\[lang:' . preg_quote($lang, '/') . '\]\](.*?)(?=\[\[lang:|$)/s', $multiLangText, $m)) {
            return trim((string) $m[1]);
        }
        if ($lang === 'gb' && preg_match('/\[\[lang:en\]\](.*?)(?=\[\[lang:|$)/s', $multiLangText, $m)) {
            return trim((string) $m[1]);
        }
        if ($lang === 'en' && preg_match('/\[\[lang:gb\]\](.*?)(?=\[\[lang:|$)/s', $multiLangText, $m)) {
            return trim((string) $m[1]);
        }
        return trim($multiLangText);
    }
}

$hasContentKey = safe_query("SHOW COLUMNS FROM plugins_about LIKE 'content_key'");
if (!$hasContentKey || mysqli_num_rows($hasContentKey) === 0) {
    safe_query("DROP TABLE IF EXISTS plugins_about_legacy");
    safe_query("RENAME TABLE plugins_about TO plugins_about_legacy");

    safe_query("
    CREATE TABLE plugins_about (
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

    $legacyRes = safe_query("SELECT * FROM plugins_about_legacy ORDER BY id ASC LIMIT 1");
    if ($legacyRes && mysqli_num_rows($legacyRes) > 0) {
        $legacy = mysqli_fetch_assoc($legacyRes);
        $langs = ['de', 'en', 'it'];
        $keys = ['title', 'intro', 'history', 'core_values', 'team', 'cta'];

        foreach ($keys as $key) {
            $raw = (string) ($legacy[$key] ?? '');
            foreach ($langs as $iso) {
                $text = escape(about_extract_lang($raw, $iso));
                safe_query("
                    INSERT INTO plugins_about (content_key, language, content, updated_at)
                    VALUES ('" . escape($key) . "', '" . escape($iso) . "', '$text', NOW())
                    ON DUPLICATE KEY UPDATE
                        content = VALUES(content),
                        updated_at = NOW()
                ");
            }
        }

        foreach (['image1', 'image2', 'image3'] as $imgKey) {
            $img = escape((string) ($legacy[$imgKey] ?? ''));
            foreach ($langs as $iso) {
                safe_query("
                    INSERT INTO plugins_about (content_key, language, content, updated_at)
                    VALUES ('" . escape($imgKey) . "', '" . escape($iso) . "', '$img', NOW())
                    ON DUPLICATE KEY UPDATE
                        content = VALUES(content),
                        updated_at = NOW()
                ");
            }
        }
    }
}

$countRes = safe_query("SELECT COUNT(*) AS cnt FROM plugins_about");
$countRow = mysqli_fetch_assoc($countRes);
if ((int) ($countRow['cnt'] ?? 0) === 0) {
    safe_query("
    INSERT IGNORE INTO plugins_about (content_key, language, content, updated_at) VALUES
    ('title','de','Ueber uns',NOW()),
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
    ('image3','it','team.jpg',NOW())
    ");
}

safe_query("
    INSERT IGNORE INTO settings_plugins
    (pluginID, modulname, admin_file, activate, author, website,
     index_link, hiddenfiles, version, path,
     status_display, plugin_display, widget_display,
     delete_display, sidebar)
    VALUES
    (
     '',
     'about',
     'admin_about',
     1,
     'T-Seven',
     'https://www.nexpell.de',
     'about,leistung,info',
     '',
     '1.0.1',
     'includes/plugins/about/',
     1,1,1,1,'deactivated'
    )
");

safe_query("
    INSERT INTO settings_plugins_lang
    (content_key, language, content, modulname, updated_at)
    VALUES
    ('plugin_name_about', 'de', 'Ueber uns', 'about', NOW()),
    ('plugin_name_about', 'en', 'About Us', 'about', NOW()),
    ('plugin_name_about', 'it', 'Chi siamo', 'about', NOW()),
    ('plugin_info_about', 'de', 'Dieses Widget zeigt allgemeine Informationen ...', 'about', NOW()),
    ('plugin_info_about', 'en', 'This widget shows general information ...', 'about', NOW()),
    ('plugin_info_about', 'it', 'Questo widget mostra informazioni generali ...', 'about', NOW())
    ON DUPLICATE KEY UPDATE
        content = VALUES(content),
        modulname = VALUES(modulname),
        updated_at = VALUES(updated_at)
");

safe_query("
    UPDATE settings_plugins
    SET
        version = '1.0.1',
        path = 'includes/plugins/about/',
        activate = 1
    WHERE modulname = 'about'
");

$linkID = 0;
$linkRes = safe_query("
SELECT linkID FROM navigation_dashboard_links
WHERE modulname = 'about' AND url = 'admincenter.php?site=admin_about'
ORDER BY linkID ASC LIMIT 1
");
if ($linkRes && ($linkRow = mysqli_fetch_assoc($linkRes))) {
    $linkID = (int) ($linkRow['linkID'] ?? 0);
} else {
    safe_query("
    INSERT INTO navigation_dashboard_links
    (catID, modulname, url, sort)
    VALUES
    (
     5,
     'about',
     'admincenter.php?site=admin_about',
     1
    )
    ");
    $linkID = (int) mysqli_insert_id($_database);
}
if ($linkID > 0) {
    safe_query("
    INSERT INTO navigation_dashboard_lang
    (content_key, language, content, modulname, updated_at)
    VALUES
    ('nav_link_{$linkID}', 'de', 'Ueber uns', 'about', NOW()),
    ('nav_link_{$linkID}', 'en', 'About Us', 'about', NOW()),
    ('nav_link_{$linkID}', 'it', 'Chi siamo', 'about', NOW())
    ON DUPLICATE KEY UPDATE
        content = VALUES(content),
        modulname = VALUES(modulname),
        updated_at = VALUES(updated_at)
    ");
}

$snavID = 0;
$snavRes = safe_query("
SELECT snavID FROM navigation_website_sub
WHERE modulname = 'about' AND url = 'index.php?site=about'
ORDER BY snavID ASC LIMIT 1
");
if ($snavRes && ($snavRow = mysqli_fetch_assoc($snavRes))) {
    $snavID = (int) ($snavRow['snavID'] ?? 0);
} else {
    safe_query("
    INSERT INTO navigation_website_sub
    (mnavID, modulname, url, sort, indropdown, last_modified)
    VALUES
    (
     2,
     'about',
     'index.php?site=about',
     1,
     1,
     NOW()
    )
    ");
    $snavID = (int) mysqli_insert_id($_database);
}
if ($snavID > 0) {
    safe_query("
    INSERT INTO navigation_website_lang
    (content_key, language, content, modulname, updated_at)
    VALUES
    ('nav_sub_{$snavID}', 'de', 'Ueber uns', 'about', NOW()),
    ('nav_sub_{$snavID}', 'en', 'About Us', 'about', NOW()),
    ('nav_sub_{$snavID}', 'it', 'Chi siamo', 'about', NOW())
    ON DUPLICATE KEY UPDATE
        content = VALUES(content),
        modulname = VALUES(modulname),
        updated_at = VALUES(updated_at)
    ");
}

$snavID = 0;
$snavRes = safe_query("
SELECT snavID FROM navigation_website_sub
WHERE modulname = 'leistung' AND url = 'index.php?site=leistung'
ORDER BY snavID ASC LIMIT 1
");
if ($snavRes && ($snavRow = mysqli_fetch_assoc($snavRes))) {
    $snavID = (int) ($snavRow['snavID'] ?? 0);
} else {
    safe_query("
    INSERT INTO navigation_website_sub
    (mnavID, modulname, url, sort, indropdown, last_modified)
    VALUES
    (
     2,
     'leistung',
     'index.php?site=leistung',
     2,
     1,
     NOW()
    )
    ");
    $snavID = (int) mysqli_insert_id($_database);
}
if ($snavID > 0) {
    safe_query("
    INSERT INTO navigation_website_lang
    (content_key, language, content, modulname, updated_at)
    VALUES
    ('nav_sub_{$snavID}', 'de', 'Leistung', 'about', NOW()),
    ('nav_sub_{$snavID}', 'en', 'Services', 'about', NOW()),
    ('nav_sub_{$snavID}', 'it', 'Servizi', 'about', NOW())
    ON DUPLICATE KEY UPDATE
        content = VALUES(content),
        modulname = VALUES(modulname),
        updated_at = VALUES(updated_at)
    ");
}

$snavID = 0;
$snavRes = safe_query("
SELECT snavID FROM navigation_website_sub
WHERE modulname = 'info' AND url = 'index.php?site=info'
ORDER BY snavID ASC LIMIT 1
");
if ($snavRes && ($snavRow = mysqli_fetch_assoc($snavRes))) {
    $snavID = (int) ($snavRow['snavID'] ?? 0);
} else {
    safe_query("
    INSERT INTO navigation_website_sub
    (mnavID, modulname, url, sort, indropdown, last_modified)
    VALUES
    (
     2,
     'info',
     'index.php?site=info',
     3,
     1,
     NOW()
    )
    ");
    $snavID = (int) mysqli_insert_id($_database);
}
if ($snavID > 0) {
    safe_query("
    INSERT INTO navigation_website_lang
    (content_key, language, content, modulname, updated_at)
    VALUES
    ('nav_sub_{$snavID}', 'de', 'Info', 'about', NOW()),
    ('nav_sub_{$snavID}', 'en', 'Info', 'about', NOW()),
    ('nav_sub_{$snavID}', 'it', 'Info', 'about', NOW())
    ON DUPLICATE KEY UPDATE
        content = VALUES(content),
        modulname = VALUES(modulname),
        updated_at = VALUES(updated_at)
    ");
}

safe_query("
INSERT IGNORE INTO user_role_admin_navi_rights
(roleID, type, modulname)
VALUES
(1, 'link', 'about')
");

echo "</div></div>";
?>
