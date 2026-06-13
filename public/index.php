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
    </main>
</body>
</html>
