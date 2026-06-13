<?php

global $plugin;

$config = [
    'modulname' => 'about',
    'name'      => 'About',
    'version'   => (string)($plugin['version'] ?? '0.0.0'),
    'path'      => 'includes/plugins/about/',
    'author'    => 'Nexpell',
    'website'   => 'https://www.nexpell.de',
];

PluginInstallerHelper::registerPlugin($config);

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
    ('image3','it','team.jpg',NOW())
    ");

