<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;

global $_database, $languageService;

if (!$languageService instanceof LanguageService) {
    $languageService = new LanguageService($_database);
}

$languageService->readPluginModule('about');
$currentLang = strtolower((string)($languageService->detectLanguage() ?: ($_SESSION['language'] ?? 'de')));

if (!function_exists('aboutInfoValue')) {
    function aboutInfoValue(string $key, string $lang): string
    {
        $keyEsc = mysqli_real_escape_string($GLOBALS['_database'], $key);
        foreach (array_unique([strtolower($lang), 'en', 'gb', 'de', 'it']) as $iso) {
            $langEsc = mysqli_real_escape_string($GLOBALS['_database'], $iso);
            $res = safe_query("SELECT content FROM plugins_about WHERE content_key = '$keyEsc' AND language = '$langEsc' LIMIT 1");
            if ($res && ($row = mysqli_fetch_assoc($res)) && trim((string)($row['content'] ?? '')) !== '') {
                return (string)$row['content'];
            }
        }

        return '';
    }
}

$config = mysqli_fetch_array(safe_query("SELECT selected_style FROM settings_headstyle_config WHERE id=1"));
$class = htmlspecialchars((string)($config['selected_style'] ?? ''));
$dbContent = trim(aboutInfoValue('info_html', $currentLang));
$pageContent = $dbContent !== ''
    ? $dbContent
    : '<div class="alert alert-info mb-0">Noch kein Inhalt für diese Info-Seite hinterlegt.</div>';

echo '<link rel="stylesheet" href="/includes/plugins/about/css/about.css">';

echo $tpl->loadTemplate('info', 'head', [
    'class' => $class,
    'title' => $languageService->get('info_title'),
    'subtitle' => 'Information'
], 'plugin');

echo $tpl->loadTemplate('info', 'content', [
    'content' => $pageContent
], 'plugin');

?>
