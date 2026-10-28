<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php
// Sections that sit in a card pulled up over the hero above them.
$overlaps = static fn (array $section): bool => in_array($section['type'], ['stats', 'picker', 'form'], true) && ($section['type'] !== 'stats' || $section['style'] === 'Floating card') || ($section['type'] === 'news' && $section['layout'] === 'Featured + grid');
$shared = ['contact' => $contact, 'forms' => $forms, 'data' => $data, 'slug' => $slug, 'pageTitle' => $pageTitle];
$count = count($sections);
?>
<?php if ($isPreview): ?><div class="preview-notice ff-preview-notice">Private preview — this shows the working draft, which may not be published yet.</div><?php endif ?>
<?php for ($i = 0; $i < $count; $i++): ?>
<?php
$s = $sections[$i];
$prev = $sections[$i - 1] ?? null;
$next = $sections[$i + 1] ?? null;
$classes = ['ff-sp-' . strtolower($s['spacing']), 'ff-bg-' . strtolower($s['bg'])];
if ($s['devices'] === 'Desktop only') $classes[] = 'ff-desktop-only';
if ($s['devices'] === 'Mobile only') $classes[] = 'ff-mobile-only';
$anchor = preg_replace('/[^\w-]/', '', (string) $s['anchor']);
$vars = $shared + [
    's' => $s, 'cls' => ' ' . implode(' ', $classes), 'idAttr' => $anchor !== '' ? ' id="' . esc($anchor, 'attr') . '"' : '',
    'afterHero' => ($prev['type'] ?? '') === 'hero', 'prevTall' => ($prev['height'] ?? '') === 'Tall',
    'overlapped' => $next !== null && $overlaps($next), 'isFirst' => $i === 0, 'aside' => [], 'inAside' => false,
];
// A form followed by contact details and/or questions becomes the two-column contact layout.
if ($s['type'] === 'form') {
    while (isset($sections[$i + 1]) && in_array($sections[$i + 1]['type'], ['contactCards', 'faq'], true)) $vars['aside'][] = $sections[++$i];
}
?>
<?= view('site/sections/' . $s['type'], $vars) ?>
<?php endfor ?>
<?= $this->endSection() ?>
