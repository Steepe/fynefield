<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Cms;

class CmsContentAudit extends BaseCommand
{
    protected $group = 'CMS';
    protected $name = 'cms:content-audit';
    protected $description = 'Checks published CMS records, active navigation, and managed media references for release-blocking errors.';

    private function checkMediaPath(string $path, string $context, array &$issues): void
    {
        if ($path === '') return;
        if (! preg_match('#^/(?:assets/media|assets/redesign|uploads)/#', $path)) {
            $issues[] = "Non-Fynefield image source for {$context}: {$path}";
            return;
        }
        $file = FCPATH . ltrim($path, '/');
        if (! is_file($file)) $issues[] = "Missing local media for {$context}: {$path}";
    }

    private function checkHtmlImages(string $html, string $context, array &$issues): void
    {
        preg_match_all('/<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/i', $html, $matches);
        foreach ($matches[2] ?? [] as $path) $this->checkMediaPath((string) $path, $context, $issues);
    }

    public function run(array $params)
    {
        $db = db_connect();
        $issues = [];
        $warnings = [];

        foreach (['posts' => ['cover_image', 'content'], 'services' => ['image', 'body']] as $table => [$imageColumn, $bodyColumn]) {
            $records = $db->table($table)->select("id,title,slug,{$imageColumn},{$bodyColumn}")->where('status', 'published')->get()->getResultArray();
            foreach ($records as $record) {
                $label = "{$table} #{$record['id']}";
                if (trim((string) $record['title']) === '' || trim((string) $record['slug']) === '') $issues[] = "Published {$label} is missing a title or URL slug.";
                $this->checkMediaPath((string) ($record[$imageColumn] ?? ''), $label, $issues);
                $this->checkHtmlImages((string) ($record[$bodyColumn] ?? ''), $label, $issues);
            }
        }

        foreach ($db->table('pages')->select('id,title,slug,sections')->where('status', 'published')->get()->getResultArray() as $page) {
            if (trim((string) $page['title']) === '' || trim((string) $page['slug']) === '') $issues[] = "Published page #{$page['id']} is missing a title or URL slug.";
            foreach (Cms::sections($page['sections']) as $section) {
                $label = "page section {$section['id']} on {$page['title']}";
                foreach (['image', 'image1', 'image2', 'image3'] as $key) $this->checkMediaPath((string) ($section[$key] ?? ''), $label, $issues);
                $this->checkHtmlImages((string) ($section['body'] ?? ''), $label, $issues);
                foreach (is_array($section['items'] ?? null) ? $section['items'] : [] as $item) if (is_array($item)) $this->checkMediaPath((string) ($item['image'] ?? ''), $label, $issues);
            }
        }

        $media = $db->table('media')->select('id,path,alt_text')->get()->getResultArray();
        foreach ($media as $item) {
            $this->checkMediaPath((string) $item['path'], "media #{$item['id']}", $issues);
            if (trim((string) ($item['alt_text'] ?? '')) === '') $warnings[] = "Media #{$item['id']} has no alternative text; confirm it is decorative.";
        }

        foreach (Cms::get('nav')['header'] as $item) {
            if (($item['show'] ?? '') === 'Hidden') continue;
            if (trim(Cms::linkText($item)) === '' || Cms::href($item['link'] ?? '', $item['linkUrl'] ?? '') === '#') $issues[] = 'An active navigation item is missing a label or has an invalid URL.';
        }

        foreach ($warnings as $warning) CLI::write('Warning: ' . $warning, 'yellow');
        if ($issues) {
            CLI::error('Content audit found ' . count($issues) . ' release-blocking item(s):');
            foreach ($issues as $issue) CLI::write('- ' . $issue, 'red');
            return EXIT_ERROR;
        }

        CLI::write('CMS content audit passed' . ($warnings ? ' with ' . count($warnings) . ' accessibility reminder(s).' : '.') , 'green');
        return EXIT_SUCCESS;
    }
}
