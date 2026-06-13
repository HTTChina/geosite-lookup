<?php

declare(strict_types=1);

use GeoSitePhp\LookupService;

require __DIR__ . '/../src/bootstrap.php';

$query = trim((string) ($_GET['q'] ?? ''));
$lang = (string) ($_GET['lang'] ?? 'en');
$lang = $lang === 'zh' ? 'zh' : 'en';
$copy = [
    'en' => [
        'html_lang' => 'en',
        'title' => 'GeoSite PHP Lookup',
        'eyebrow' => 'GeoSite / GeoIP',
        'heading' => 'Rule Label Lookup',
        'placeholder' => 'Enter a domain, URL, or IP, such as google.com / 8.8.8.8',
        'submit' => 'Lookup',
        'label_hits' => 'label matches',
        'rules' => 'rules',
        'no_matches' => 'No matches in the dat database or text lists.',
        'dat_matches' => 'Dat Matches',
        'list_matches' => 'Text List Matches',
        'english' => 'English',
        'chinese' => '中文',
        'open_source' => 'Open source project',
        'source_code' => 'Source code',
    ],
    'zh' => [
        'html_lang' => 'zh-CN',
        'title' => 'GeoSite PHP 查询',
        'eyebrow' => 'GeoSite / GeoIP',
        'heading' => '规则标签查询',
        'placeholder' => '输入域名、URL 或 IP，例如 google.com / 8.8.8.8',
        'submit' => '查询',
        'label_hits' => '个标签命中',
        'rules' => '条规则',
        'no_matches' => '没有命中 dat 数据库或文本列表。',
        'dat_matches' => 'Dat 标签命中',
        'list_matches' => '文本列表命中',
        'english' => 'English',
        'chinese' => '中文',
        'open_source' => '开源项目',
        'source_code' => '开源地址',
    ],
][$lang];
$result = null;

if ($query !== '') {
    $service = new LookupService(__DIR__ . '/../data');
    $result = $service->lookup($query);
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars($copy['html_lang'], ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($copy['title'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <main class="shell">
        <section class="lookup-panel">
            <div class="topbar">
                <div>
                    <p class="eyebrow"><?= htmlspecialchars($copy['eyebrow'], ENT_QUOTES, 'UTF-8') ?></p>
                    <h1><?= htmlspecialchars($copy['heading'], ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
                <nav class="language-switch" aria-label="Language">
                    <a class="<?= $lang === 'en' ? 'active' : '' ?>" href="?<?= htmlspecialchars(http_build_query(['q' => $query, 'lang' => 'en']), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($copy['english'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a class="<?= $lang === 'zh' ? 'active' : '' ?>" href="?<?= htmlspecialchars(http_build_query(['q' => $query, 'lang' => 'zh']), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($copy['chinese'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </nav>
            </div>

            <form method="get" class="search-form">
                <input type="hidden" name="lang" value="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
                <input
                    name="q"
                    value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="<?= htmlspecialchars($copy['placeholder'], ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="off"
                    autofocus
                >
                <button type="submit"><?= htmlspecialchars($copy['submit'], ENT_QUOTES, 'UTF-8') ?></button>
            </form>

            <?php if ($result !== null): ?>
                <?php
                $summaryCount = count($result['matches']);
                $summaryLabel = $copy['label_hits'];
                if ($result['type'] === 'geosite' && isset($result['matches'][0]['rules']) && is_array($result['matches'][0]['rules'])) {
                    $summaryCount = count($result['matches'][0]['rules']);
                    $summaryLabel = $copy['rules'];
                }
                ?>
                <section class="result">
                    <div class="result-head">
                        <div>
                            <span class="type"><?= htmlspecialchars((string) $result['type'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="type"><?= htmlspecialchars((string) $result['source'], ENT_QUOTES, 'UTF-8') ?></span>
                            <h2><?= htmlspecialchars((string) $result['normalized'], ENT_QUOTES, 'UTF-8') ?></h2>
                        </div>
                        <span class="count">
                            <?= $summaryCount ?>
                            <?= htmlspecialchars($summaryLabel, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <?php if (($result['versions']['geosite'] ?? null) || ($result['versions']['geoip'] ?? null)): ?>
                        <div class="versions">
                            <?php foreach (['geosite' => 'GeoSite', 'geoip' => 'GeoIP'] as $key => $label): ?>
                                <?php $version = $result['versions'][$key] ?? null; ?>
                                <?php if (is_array($version)): ?>
                                    <a href="<?= htmlspecialchars((string) ($version['release_url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer">
                                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                        <?= htmlspecialchars((string) ($version['tag_name'] ?? 'unknown'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($result['matches'] === [] && ($result['list_matches'] ?? []) === []): ?>
                        <p class="empty"><?= htmlspecialchars($copy['no_matches'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <?php if ($result['matches'] !== []): ?>
                            <h3 class="section-title"><?= htmlspecialchars($copy['dat_matches'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="match-list">
                                <?php foreach ($result['matches'] as $match): ?>
                                    <article class="match-card">
                                        <h3><?= htmlspecialchars((string) $match['label'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <?php if (isset($match['rules']) && is_array($match['rules'])): ?>
                                            <ul>
                                                <?php foreach ($match['rules'] as $rule): ?>
                                                    <li>
                                                        <span><?= htmlspecialchars((string) $rule['type'], ENT_QUOTES, 'UTF-8') ?></span>
                                                        <?= htmlspecialchars((string) $rule['value'], ENT_QUOTES, 'UTF-8') ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                        <?php if (isset($match['cidr']) && is_array($match['cidr'])): ?>
                                            <ul>
                                                <?php foreach ($match['cidr'] as $cidr): ?>
                                                    <li><span>cidr</span><?= htmlspecialchars((string) $cidr, ENT_QUOTES, 'UTF-8') ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (($result['list_matches'] ?? []) !== []): ?>
                            <h3 class="section-title"><?= htmlspecialchars($copy['list_matches'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="match-list">
                                <?php foreach ($result['list_matches'] as $match): ?>
                                    <article class="match-card">
                                        <h3><?= htmlspecialchars((string) $match['list'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <ul>
                                            <?php foreach ($match['rules'] as $rule): ?>
                                                <li>
                                                    <span><?= htmlspecialchars((string) $rule['type'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?= htmlspecialchars((string) $rule['value'], ENT_QUOTES, 'UTF-8') ?>
                                                    <em>#<?= htmlspecialchars((string) $rule['line'], ENT_QUOTES, 'UTF-8') ?></em>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </section>
        <footer class="site-footer">
            <span><?= htmlspecialchars($copy['open_source'], ENT_QUOTES, 'UTF-8') ?></span>
            <a href="https://github.com/HTTChina/geosite-lookup" target="_blank" rel="noreferrer">
                <svg class="github-mark" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                    <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.22 2.2.82A7.52 7.52 0 0 1 8 3.86c.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"></path>
                </svg>
                <?= htmlspecialchars($copy['source_code'], ENT_QUOTES, 'UTF-8') ?>:
                HTTChina/geosite-lookup
            </a>
            <a href="https://github.com/Loyalsoldier/geoip/" target="_blank" rel="noreferrer">
                <svg class="github-mark" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                    <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.22 2.2.82A7.52 7.52 0 0 1 8 3.86c.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"></path>
                </svg>
                Loyalsoldier/geoip
            </a>
        </footer>
    </main>
</body>
</html>
