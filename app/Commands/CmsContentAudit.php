<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CmsContentAudit extends BaseCommand
{
    protected $group = 'CMS';
    protected $name = 'cms:content-audit';
    protected $description = 'Checks published CMS records, active navigation, and managed media references for release-blocking errors.';

    private function checkMediaPath(string $path, string $context, array &$issues): void
    {
        if ($path === '') return;
        if (! preg_match('#^/(?:assets/media|uploads)/#', $path)) {
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

        foreach (['pages' => 'cover_image', 'posts' => 'cover_image', 'services' => 'image'] as $table => $imageColumn) {
            $records = $db->table($table)->select("id,title,slug,{$imageColumn},content")->where('status', 'published')->get()->getResultArray();
            foreach ($records as $record) {
                $label = "{$table} #{$record['id']}";
                if (trim((string) $record['title']) === '' || trim((string) $record['slug']) === '') $issues[] = "Published {$label} is missing a title or URL slug.";
                $this->checkMediaPath((string) ($record[$imageColumn] ?? ''), $label, $issues);
                $this->checkHtmlImages((string) ($record['content'] ?? ''), $label, $issues);
            }
        }

        $sections = $db->table('page_sections')->select('page_sections.id,page_sections.section_type,page_sections.image,page_sections.items,page_sections.content,pages.title')->join('pages', 'pages.id = page_sections.page_id')->where('pages.status', 'published')->get()->getResultArray();
        foreach ($sections as $section) {
            $label = "page section #{$section['id']} on {$section['title']}";
            $this->checkMediaPath((string) ($section['image'] ?? ''), $label, $issues);
            $this->checkHtmlImages((string) ($section['content'] ?? ''), $label, $issues);
            if ($section['section_type'] === 'gallery') {
                foreach (preg_split('/\R/', (string) ($section['items'] ?? '')) ?: [] as $line) $this->checkMediaPath(trim(explode('|', $line, 2)[0]), $label, $issues);
            }
        }

        $media = $db->table('media')->select('id,path,alt_text')->get()->getResultArray();
        foreach ($media as $item) {
            $this->checkMediaPath((string) $item['path'], "media #{$item['id']}", $issues);
            if (trim((string) ($item['alt_text'] ?? '')) === '') $warnings[] = "Media #{$item['id']} has no alternative text; confirm it is decorative.";
        }

        $navigation = $db->table('navigation_items')->select('id,label,url')->where('is_active', 1)->get()->getResultArray();
        foreach ($navigation as $item) {
            if (trim((string) $item['label']) === '' || ! preg_match('#^(?:https?://|/(?!/))#i', (string) $item['url'])) $issues[] = "Active navigation item #{$item['id']} is missing a label or has an invalid URL.";
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
