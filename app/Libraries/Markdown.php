<?php

namespace App\Libraries;

/**
 * The small Markdown dialect used by Studio text boxes: paragraphs, ## headings,
 * > quotes, - and 1. lists, **bold**, *italic*, [links](url) and ![images](src).
 * Input is escaped first, so editors cannot inject HTML.
 */
class Markdown
{
    public static function render(?string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim((string) $text));
        if ($text === '') return '';
        $html = '';
        foreach (preg_split('/\n{2,}/', $text) ?: [] as $block) {
            $lines = explode("\n", trim($block));
            if (preg_match('/^(#{2,4})\s+(.+)$/', $lines[0], $heading) && count($lines) === 1) {
                $level = strlen($heading[1]);
                $html .= "<h{$level}>" . self::inline($heading[2]) . "</h{$level}>";
            } elseif (self::all($lines, '/^>\s?/')) {
                $html .= '<blockquote>' . self::inline(implode(' ', array_map(static fn ($line) => preg_replace('/^>\s?/', '', $line), $lines))) . '</blockquote>';
            } elseif (self::all($lines, '/^[-*]\s+/')) {
                $html .= '<ul>' . implode('', array_map(static fn ($line) => '<li>' . self::inline(preg_replace('/^[-*]\s+/', '', $line)) . '</li>', $lines)) . '</ul>';
            } elseif (self::all($lines, '/^\d+[.)]\s+/')) {
                $html .= '<ol>' . implode('', array_map(static fn ($line) => '<li>' . self::inline(preg_replace('/^\d+[.)]\s+/', '', $line)) . '</li>', $lines)) . '</ol>';
            } else {
                $html .= '<p>' . implode('<br>', array_map([self::class, 'inline'], $lines)) . '</p>';
            }
        }
        return $html;
    }

    /** Plain text with Markdown markers removed, for excerpts and reading time. */
    public static function plain(?string $text): string
    {
        return trim(html_entity_decode(strip_tags(self::render($text)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function all(array $lines, string $pattern): bool
    {
        foreach ($lines as $line) if (! preg_match($pattern, $line)) return false;
        return true;
    }

    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)\s]+)\)/', static function (array $m): string {
            $src = Cms::safeMediaPath(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            return $src ? '<img src="' . esc($src, 'attr') . '" alt="' . $m[1] . '">' : '';
        }, $text) ?? $text;
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', static function (array $m): string {
            $url = Cms::safeUrl(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($url === '') return $m[1];
            $external = preg_match('#^https?://#i', $url) && ! str_starts_with($url, base_url());
            return '<a href="' . esc($url, 'attr') . '"' . ($external ? ' target="_blank" rel="noopener"' : '') . '>' . $m[1] . '</a>';
        }, $text) ?? $text;
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        return preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/', '<em>$1</em>', $text) ?? $text;
    }

    /** One-off conversion of the old editor's HTML into this Markdown dialect. */
    public static function fromHtml(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') return '';
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $blocks = [];
        foreach ($document->documentElement?->childNodes ?? [] as $node) {
            $block = self::block($node);
            if ($block !== '') $blocks[] = $block;
        }
        return trim(implode("\n\n", $blocks));
    }

    private static function block(\DOMNode $node): string
    {
        if ($node instanceof \DOMText) return trim($node->textContent);
        if (! $node instanceof \DOMElement) return '';
        $tag = strtolower($node->tagName);
        return match (true) {
            in_array($tag, ['h1', 'h2'], true) => '## ' . self::inlineFrom($node),
            in_array($tag, ['h3', 'h4', 'h5', 'h6'], true) => '### ' . self::inlineFrom($node),
            $tag === 'blockquote' => '> ' . self::inlineFrom($node),
            $tag === 'ul' => implode("\n", array_map(static fn ($li) => '- ' . self::inlineFrom($li), self::children($node, 'li'))),
            $tag === 'ol' => implode("\n", array_map(static fn ($li, $i) => ($i + 1) . '. ' . self::inlineFrom($li), self::children($node, 'li'), array_keys(self::children($node, 'li')))),
            in_array($tag, ['div', 'section', 'figure'], true) => implode("\n\n", array_filter(array_map([self::class, 'block'], iterator_to_array($node->childNodes)))),
            default => self::inlineFrom($node),
        };
    }

    private static function children(\DOMElement $node, string $tag): array
    {
        return array_values(array_filter(iterator_to_array($node->childNodes), static fn ($child) => $child instanceof \DOMElement && strtolower($child->tagName) === $tag));
    }

    private static function inlineFrom(\DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMText) { $out .= preg_replace('/\s+/', ' ', $child->textContent); continue; }
            if (! $child instanceof \DOMElement) continue;
            $inner = self::inlineFrom($child);
            $out .= match (strtolower($child->tagName)) {
                'strong', 'b' => '**' . trim($inner) . '**',
                'em', 'i' => '*' . trim($inner) . '*',
                'a' => ($href = $child->getAttribute('href')) ? '[' . trim($inner) . '](' . $href . ')' : $inner,
                'img' => '![' . $child->getAttribute('alt') . '](' . $child->getAttribute('src') . ')',
                'br' => "\n",
                default => $inner,
            };
        }
        return trim($out);
    }
}
