<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;
use nexpell\SeoUrlHandler;

global $languageService;
global $_database;

function aboutGetMap(): array
{
    $map = [];
    $res = safe_query("SELECT content_key, language, content FROM plugins_about");
    while ($row = mysqli_fetch_assoc($res)) {
        $key = (string)$row['content_key'];
        $iso = strtolower((string)$row['language']);
        $map[$key][$iso] = (string)$row['content'];
    }
    return $map;
}

function aboutGetValue(array $map, string $key, string $lang): string
{
    $lang = strtolower($lang);
    foreach ([$lang, 'en', 'gb', 'de', 'it'] as $iso) {
        if (!empty($map[$key][$iso])) {
            return (string)$map[$key][$iso];
        }
    }
    return '';
}

$config = mysqli_fetch_array(safe_query("SELECT selected_style FROM settings_headstyle_config WHERE id=1"));
$class = htmlspecialchars($config['selected_style'] ?? '');
$currentLang = strtolower((string)($languageService->detectLanguage() ?: ($_SESSION['language'] ?? 'en')));

$map = aboutGetMap();

$title = aboutGetValue($map, 'title', $currentLang);
$intro = aboutGetValue($map, 'intro', $currentLang);
$history = aboutGetValue($map, 'history', $currentLang);
$coreValues = aboutGetValue($map, 'core_values', $currentLang);
$team = aboutGetValue($map, 'team', $currentLang);
$cta = aboutGetValue($map, 'cta', $currentLang);
$image1 = aboutGetValue($map, 'image1', $currentLang);
$image2 = aboutGetValue($map, 'image2', $currentLang);
$image3 = aboutGetValue($map, 'image3', $currentLang);

echo '<link rel="stylesheet" href="/includes/plugins/about/css/about.css">';

echo $tpl->loadTemplate("about", "head", [
    'class' => $class,
    'title' => $languageService->get('title'),
    'subtitle' => 'About'
], 'plugin');

if ($title !== '' || $intro !== '' || $history !== '' || $coreValues !== '' || $team !== '' || $cta !== '') {
    echo $tpl->loadTemplate("about", "content", [
        'title' => $title,
        'intro' => $intro,
        'history' => $history,
        'core_values' => $coreValues,
        'team' => $team,
        'cta' => $cta,
        'image_intro' => $image1,
        'image_history' => $image2,
        'image_team' => $image3 !== '' ? $image3 : 'team.jpg',
        'contact_url' => SeoUrlHandler::convertToSeoUrl('index.php?site=contact'),
        'leistung_url' => SeoUrlHandler::convertToSeoUrl('index.php?site=leistung')
    ], 'plugin');
} else {
    echo '<p>' . $languageService->get('no_about') . '</p>';
}

?>
