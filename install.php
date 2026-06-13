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

PluginInstallerHelper::registerAdminNavigation([
    'modulname' => 'about',
    'url'       => 'admincenter.php?site=admin_about',
    'catID'     => 5,
    'sort'      => 1,
    'labels'    => [
        'de' => 'Ãœber uns',
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
        'de' => 'Ãœber uns',
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
    ('title','de','Ãœber uns',NOW()),
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

