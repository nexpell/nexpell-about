<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;
use nexpell\AccessControl;

global $languageService;
global $_database;
global $plugin_path;

AccessControl::checkAdminAccess('about');

$pluginBasePath = (isset($plugin_path) && is_string($plugin_path) && trim($plugin_path) !== '')
    ? rtrim(trim($plugin_path), '/') . '/'
    : 'includes/plugins/about/';

// Plugin language file for admin page (admin_about.php) explicit load.
if (isset($languageService) && is_object($languageService) && property_exists($languageService, 'module')) {
    $activeIso = strtolower((string)$languageService->detectLanguage());
    $langBase  = dirname(__DIR__) . '/languages/';
    $langOrder = array_values(array_unique(['en', $activeIso]));

    foreach ($langOrder as $iso) {
        $langFile = $langBase . $iso . '/admin_about.php';
        if (!is_file($langFile)) {
            continue;
        }

        $language_array = [];
        include $langFile;
        if (is_array($language_array) && !empty($language_array)) {
            $languageService->module = array_replace($languageService->module, $language_array);
        }
    }
}

function getAboutMap(mysqli $db): array
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

function aboutValue(array $map, string $key, string $lang): string
{
    $lang = strtolower($lang);
    foreach ([$lang, 'en', 'gb', 'de', 'it'] as $iso) {
        if (!empty($map[$key][$iso])) {
            return (string)$map[$key][$iso];
        }
    }
    return '';
}

function aboutAllowedPages(): array
{
    return ['about', 'info', 'leistung'];
}

$languages = [];
$resLang = mysqli_query($_database, "SELECT iso_639_1, name_de FROM settings_languages WHERE active = 1 ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($resLang)) {
    $languages[strtolower((string)$row['iso_639_1'])] = (string)$row['name_de'];
}
if (empty($languages)) {
    $languages = ['de' => 'Deutsch', 'en' => 'English', 'it' => 'Italiano'];
}

if (!empty($_SESSION['about_active_lang'])) {
    $currentLang = strtolower((string)$_SESSION['about_active_lang']);
    unset($_SESSION['about_active_lang']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['active_lang'])) {
    $currentLang = strtolower((string)$_POST['active_lang']);
} elseif (!empty($_SESSION['language'])) {
    $currentLang = strtolower((string)$_SESSION['language']);
} else {
    $currentLang = strtolower((string)$languageService->detectLanguage());
}
if (!isset($languages[$currentLang])) {
    $currentLang = (string)array_key_first($languages);
}

if (!empty($_SESSION['about_active_page'])) {
    $currentPage = strtolower((string)$_SESSION['about_active_page']);
    unset($_SESSION['about_active_page']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['active_page'])) {
    $currentPage = strtolower((string)$_POST['active_page']);
} elseif (!empty($_GET['page'])) {
    $currentPage = strtolower((string)$_GET['page']);
} else {
    $currentPage = 'about';
}
if (!in_array($currentPage, aboutAllowedPages(), true)) {
    $currentPage = 'about';
}

$contentMap = getAboutMap($_database);

if (isset($_POST['save'])) {
    $_SESSION['about_active_lang'] = $currentLang;
    $_SESSION['about_active_page'] = $currentPage;

    if ($currentPage === 'about') {
        $textFields = ['title', 'intro', 'history', 'core_values', 'team', 'cta'];

        foreach ($textFields as $field) {
            foreach ($languages as $iso => $_label) {
                $value = mysqli_real_escape_string($_database, (string)($_POST[$field . '_lang'][$iso] ?? ''));
                $isoEsc = mysqli_real_escape_string($_database, (string)$iso);
                $fieldEsc = mysqli_real_escape_string($_database, $field);

                safe_query("
                    INSERT INTO plugins_about (content_key, language, content, updated_at)
                    VALUES ('$fieldEsc', '$isoEsc', '$value', NOW())
                    ON DUPLICATE KEY UPDATE
                        content = VALUES(content),
                        updated_at = NOW()
                ");
            }
        }

        $uploadPath = $pluginBasePath . 'images/';
        $imageFields = ['image1', 'image2', 'image3'];
        $imageNames = [];

        foreach ($imageFields as $field) {
            $currentImage = aboutValue($contentMap, $field, $currentLang);

            if (!empty($_FILES[$field]['tmp_name']) && is_uploaded_file($_FILES[$field]['tmp_name'])) {
                $filename = basename((string)$_FILES[$field]['name']);
                $targetPath = $uploadPath . $filename;
                move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath);
                $imageNames[$field] = $filename;
            } else {
                $imageNames[$field] = (string)($_POST['existing_' . $field] ?? $currentImage);
            }
        }

        foreach ($imageFields as $field) {
            $fieldEsc = mysqli_real_escape_string($_database, $field);
            $imageEsc = mysqli_real_escape_string($_database, (string)$imageNames[$field]);

            foreach ($languages as $iso => $_label) {
                $isoEsc = mysqli_real_escape_string($_database, (string)$iso);
                safe_query("
                    INSERT INTO plugins_about (content_key, language, content, updated_at)
                    VALUES ('$fieldEsc', '$isoEsc', '$imageEsc', NOW())
                    ON DUPLICATE KEY UPDATE
                        content = VALUES(content),
                        updated_at = NOW()
                ");
            }
        }
    } else {
        $fieldKey = mysqli_real_escape_string($_database, $currentPage . '_html');
        $pageHtmlInput = $_POST['page_html_lang'][$currentPage] ?? [];
        foreach ($languages as $iso => $_label) {
            $value = mysqli_real_escape_string($_database, (string)($pageHtmlInput[$iso] ?? ''));
            $isoEsc = mysqli_real_escape_string($_database, (string)$iso);
            safe_query("
                INSERT INTO plugins_about (content_key, language, content, updated_at)
                VALUES ('$fieldKey', '$isoEsc', '$value', NOW())
                ON DUPLICATE KEY UPDATE
                    content = VALUES(content),
                    updated_at = NOW()
            ");
        }
    }

    nx_audit_update('about', null, true, null, 'admincenter.php?site=admin_about');
    nx_redirect('admincenter.php?site=admin_about', 'success', 'alert_saved', false);
}
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center gap-2">
            <div class="card-title mb-0">
                <i class="bi bi-card-text"></i> <span><?= $languageService->get('about_title') ?></span>
            </div>
            <div class="d-flex gap-2">
                <div class="btn-group" id="page-switch">
                    <?php foreach (aboutAllowedPages() as $pageKey): ?>
                        <?php $fallbackLabels = ['about' => 'About', 'info' => 'Info', 'leistung' => 'Leistung']; ?>
                        <?php $label = $languageService->module['page_' . $pageKey] ?? $fallbackLabels[$pageKey]; ?>
                        <button type="button"
                                class="btn <?= $pageKey === $currentPage ? 'btn-success' : 'btn-outline-success' ?>"
                                data-page="<?= htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="btn-group" id="lang-switch">
                <?php foreach ($languages as $iso => $label): ?>
                    <button type="button"
                            class="btn <?= $iso === $currentLang ? 'btn-primary' : 'btn-secondary' ?>"
                            data-lang="<?= htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') ?>">
                        <?= strtoupper(htmlspecialchars($iso, ENT_QUOTES, 'UTF-8')) ?>
                    </button>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <form method="post" id="aboutForm" enctype="multipart/form-data" class="row g-4" data-nx-lang-pane="1">
            <input type="hidden" name="active_lang" id="active_lang" value="<?= htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="active_page" id="active_page" value="<?= htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8') ?>">

            <div class="page-pane page-about" style="<?= $currentPage === 'about' ? '' : 'display:none;' ?>">
            <?php
            $fields = [
                'title' => $languageService->get('label_title'),
                'intro' => $languageService->get('label_intro'),
                'history' => $languageService->get('label_history'),
                'core_values' => $languageService->get('label_skills'),
                'team' => $languageService->get('label_team'),
                'cta' => $languageService->get('label_call2action'),
            ];

            foreach ($fields as $key => $label) {
                echo '<div class="col-12">';
                echo '<label class="form-label fw-bold">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label>';

                foreach ($languages as $iso => $langLabel) {
                    $id = 'editor_' . $key . '_' . $iso;
                    $display = ($iso === $currentLang) ? '' : 'display:none;';
                    $value = aboutValue($contentMap, $key, $iso);
                    echo '<div class="lang-pane lang-' . htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') . '" style="' . $display . '">';
                    echo '<textarea id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '_lang[' . htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') . ']" class="form-control about-field" data-editor="nx_editor" rows="6">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</textarea>';
                    echo '</div>';
                }

                echo '</div>';
            }
            ?>

            <?php
            $images = [
                'image1' => $languageService->get('label_img_intro'),
                'image2' => $languageService->get('label_img_history'),
                'image3' => $languageService->get('label_img_team'),
            ];

            echo '<div class="row g-4">';
            foreach ($images as $key => $label) {
                $imgName = aboutValue($contentMap, $key, $currentLang);
                $imgSrc = '../' . $pluginBasePath . 'images/' . $imgName;
                echo '<div class="col-md-4">';
                echo "<label class='form-label'>$label</label>";
                echo "<input type='file' name='$key' class='form-control'>";
                if ($imgName !== '') {
                    echo "<div class='mt-2'><img src='$imgSrc' alt='$key' style='max-width: 100%; max-height: 200px;' class='img-thumbnail'></div>";
                    echo "<input type='hidden' name='existing_$key' value='" . htmlspecialchars($imgName, ENT_QUOTES, 'UTF-8') . "'>";
                }
                echo '</div>';
            }
            echo '</div>';
            ?>
            </div>

            <div class="page-pane page-info" style="<?= $currentPage === 'info' ? '' : 'display:none;' ?>">
                <div class="col-12">
                    <label class="form-label fw-bold"><?= htmlspecialchars($languageService->module['label_page_content'] ?? 'Seiteninhalt', ENT_QUOTES, 'UTF-8') ?></label>
                    <?php foreach ($languages as $iso => $langLabel): ?>
                        <?php $display = ($iso === $currentLang) ? '' : 'display:none;'; ?>
                        <?php $value = aboutValue($contentMap, 'info_html', $iso); ?>
                        <div class="lang-pane lang-<?= htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') ?>" style="<?= $display ?>">
                            <textarea name="page_html_lang[info][<?= htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') ?>]" class="form-control about-field" data-editor="nx_editor" rows="18"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="page-pane page-leistung" style="<?= $currentPage === 'leistung' ? '' : 'display:none;' ?>">
                <div class="col-12">
                    <label class="form-label fw-bold"><?= htmlspecialchars($languageService->module['label_page_content'] ?? 'Seiteninhalt', ENT_QUOTES, 'UTF-8') ?></label>
                    <?php foreach ($languages as $iso => $langLabel): ?>
                        <?php $display = ($iso === $currentLang) ? '' : 'display:none;'; ?>
                        <?php $value = aboutValue($contentMap, 'leistung_html', $iso); ?>
                        <div class="lang-pane lang-<?= htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') ?>" style="<?= $display ?>">
                            <textarea name="page_html_lang[leistung][<?= htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') ?>]" class="form-control about-field" data-editor="nx_editor" rows="18"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" name="save" class="btn btn-primary"><?= $languageService->get('save') ?></button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const langButtons = document.querySelectorAll('#lang-switch [data-lang]');
    const pageButtons = document.querySelectorAll('#page-switch [data-page]');
    const activeLangInput = document.getElementById('active_lang');
    const activePageInput = document.getElementById('active_page');

    function switchLang(lang) {
        activeLangInput.value = lang;
        langButtons.forEach((btn) => {
            btn.classList.toggle('btn-primary', btn.dataset.lang === lang);
            btn.classList.toggle('btn-secondary', btn.dataset.lang !== lang);
        });
        document.querySelectorAll('.lang-pane').forEach((pane) => {
            pane.style.display = pane.classList.contains('lang-' + lang) ? '' : 'none';
        });
    }

    function switchPage(page) {
        activePageInput.value = page;
        pageButtons.forEach((btn) => {
            btn.classList.toggle('btn-success', btn.dataset.page === page);
            btn.classList.toggle('btn-outline-success', btn.dataset.page !== page);
        });
        document.querySelectorAll('.page-pane').forEach((pane) => {
            pane.style.display = pane.classList.contains('page-' + page) ? '' : 'none';
        });
    }

    langButtons.forEach((btn) => btn.addEventListener('click', function () {
        switchLang(this.dataset.lang);
    }));
    pageButtons.forEach((btn) => btn.addEventListener('click', function () {
        switchPage(this.dataset.page);
    }));

    switchLang(activeLangInput.value);
    switchPage(activePageInput.value);
});
</script>
