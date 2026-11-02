<?php

namespace App\Controllers;

use App\Libraries\Cms;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Fynefield Studio: serves the single-page CMS and its JSON API.
 * Every write checks the signed-in role's permissions and cleans input against
 * the same field definitions the Studio renders, so the database only ever holds
 * values the public site knows how to show.
 */
class Studio extends BaseController
{
    private const COLLECTION_TABLES = ['news' => 'posts', 'services' => 'services', 'team' => 'team_members', 'locations' => 'locations'];

    // ------------------------------------------------------------------ shell

    public function index(): string
    {
        return view('studio/index', ['boot' => $this->bootstrap()]);
    }

    private function bootstrap(): array
    {
        $db = db_connect();
        $userId = (int) session('admin_id');
        $me = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $role = $me['role'];
        $pages = $db->table('pages')->orderBy('sort_order')->orderBy('id')->get()->getResultArray();
        $services = $db->table('services')->orderBy('sort_order')->get()->getResultArray();
        $userNames = array_column($db->table('users')->select('id, name')->get()->getResultArray(), 'name', 'id');
        $settings = [];
        foreach (Cms::settingsTabs() as $tab) $settings[$tab['k']] = Cms::get($tab['k']);
        $settings['advanced']['redirects'] = array_map(static fn (array $r): array => ['from' => $r['source_path'], 'to' => $r['target_url']], $db->table('redirects')->where('is_active', 1)->orderBy('id')->get()->getResultArray());
        $settings['integrations']['smtp'] = Cms::smtpStatus();
        return [
            'me' => ['id' => $userId, 'name' => $me['name'], 'email' => $me['email'], 'role' => Cms::roleLabel($role), 'perms' => array_combine(array_keys(Cms::PERMISSIONS), array_map(static fn ($key) => Cms::can($key, $role), array_keys(Cms::PERMISSIONS)))],
            'siteUrl' => rtrim(site_url('/'), '/'), 'siteHost' => parse_url(site_url('/'), PHP_URL_HOST),
            'today' => date('l, j F Y'), 'emailConfigured' => Cms::emailConfigured(),
            'pages' => array_map(fn (array $page): array => $this->pageItem($page, $userNames), $pages),
            'colls' => [
                'news' => array_map([$this, 'newsItem'], $db->table('posts')->orderBy('published_at', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray()),
                'services' => array_map([$this, 'serviceItem'], $services),
                'team' => array_map([$this, 'teamItem'], $db->table('team_members')->orderBy('sort_order')->get()->getResultArray()),
                'locations' => array_map([$this, 'locationItem'], $db->table('locations')->orderBy('sort_order')->get()->getResultArray()),
            ],
            'collections' => $this->collectionDefs(),
            'media' => $this->mediaItems(),
            'nav' => Cms::get('nav'), 'theme' => Cms::get('theme'), 'defaultTheme' => Cms::defaultTheme(), 'settings' => $settings,
            'users' => $this->userItems(),
            'roles' => array_values(Cms::ROLES),
            'perms' => array_map(static fn ($key) => ['k' => $key, 'label' => Cms::PERMISSIONS[$key], 'v' => array_map('intval', Cms::get('perms')[$key] ?? Cms::DEFAULT_PERMS[$key])], array_keys(Cms::PERMISSIONS)),
            'inquiries' => $this->inquiryItems(),
            'activity' => $this->activityItems(),
            'health' => $this->healthChecks($pages, $services),
            'linkOptions' => Cms::linkOptions($pages, $services),
            'serviceOptions' => array_map(static fn (array $s): array => ['value' => $s['slug'], 'label' => $s['title']], $services),
            'sectionTypes' => Cms::sectionTypes(), 'sectionDefaults' => Cms::sectionDefaults(), 'sectionCommon' => Cms::sectionCommon(),
            'themeFields' => Cms::themeFields(), 'settingsTabs' => Cms::settingsTabs(), 'fonts' => array_map(static fn ($family) => 'https://fonts.googleapis.com/css2?family=' . $family . '&display=swap', Cms::FONTS),
        ];
    }

    // ---------------------------------------------------------------- helpers


    private function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON($data + ['csrf' => csrf_hash()]);
    }

    private function fail(string $message, int $status = 422): ResponseInterface
    {
        return $this->json(['error' => $message], $status);
    }

    private function denied(): ResponseInterface
    {
        return $this->fail('Your role does not allow this. Ask an administrator.', 403);
    }

    private function input(): array
    {
        $body = $this->request->getJSON(true);
        return is_array($body) ? $body : [];
    }

    private function record(string $what, ?string $entity = null, ?int $id = null): void
    {
        db_connect()->table('activity_log')->insert(['user_id' => session('admin_id'), 'action' => mb_substr($what, 0, 250), 'entity_type' => $entity, 'entity_id' => $id, 'created_at' => date('Y-m-d H:i:s')]);
    }

    private function ago(?string $datetime): string
    {
        if (! $datetime || ! strtotime($datetime)) return '—';
        $seconds = time() - strtotime($datetime);
        if ($seconds < 60) return 'just now';
        if ($seconds < 3600) return floor($seconds / 60) . 'm ago';
        if ($seconds < 86400) return floor($seconds / 3600) . 'h ago';
        if ($seconds < 172800) return 'Yesterday';
        if ($seconds < 604800) return floor($seconds / 86400) . ' days ago';
        if ($seconds < 2592000) { $weeks = floor($seconds / 604800); return $weeks . ($weeks > 1 ? ' weeks ago' : ' week ago'); }
        return date('j M Y', strtotime($datetime));
    }

    /** Prevent spreadsheet applications from evaluating exported CSV cells as formulas. */
    private function safeCsvValue(mixed $value): string
    {
        $value = (string) $value;
        return preg_match('/^\s*[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    private function json_list(mixed $value): array
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        return is_array($decoded) ? array_values($decoded) : [];
    }

    // ------------------------------------------------------- input cleaning

    /** Valid link tokens: existing pages and services, phone, email or an external URL. */
    private function linkTokens(): array
    {
        static $tokens = null;
        if ($tokens === null) $tokens = array_column(Cms::linkOptions(), 'value');
        return $tokens;
    }

    /** Cleans one value against a Studio field definition. */
    private function clean(array $def, mixed $value, array &$target, string $key): void
    {
        $type = $def['type'] ?? 'text';
        $max = (int) ($def['max'] ?? 0);
        switch ($type) {
            case 'toggle': $target[$key] = (bool) $value; break;
            case 'range': $target[$key] = max((int) ($def['min'] ?? 0), min((int) ($def['max'] ?? 100), (int) $value)); break;
            case 'select':
                $options = array_map(static fn ($o) => is_array($o) ? $o['value'] : $o, $def['options'] ?? []);
                $target[$key] = in_array($value, $options, true) ? $value : ($options[0] ?? '');
                break;
            case 'color': $target[$key] = Cms::color($value, '#10263b'); break;
            case 'image': $target[$key] = Cms::safeMediaPath($value); break;
            case 'service': $target[$key] = preg_match('/^[a-z0-9-]{1,120}$/', (string) $value) ? (string) $value : ''; break;
            case 'datetime': $target[$key] = $value && strtotime((string) $value) ? date('Y-m-d H:i:s', strtotime((string) $value)) : ''; break;
            case 'link':
                $target[$key] = in_array($value, $this->linkTokens(), true) ? $value : 'url';
                $target[$key . 'Url'] = mb_substr(Cms::safeUrl((string) ($target[$key . 'Url'] ?? '')), 0, 500);
                break;
            case 'list':
                $items = [];
                foreach (array_slice(is_array($value) ? $value : [], 0, 100) as $item) {
                    if (! is_array($item)) continue;
                    $clean = [];
                    foreach ($def['sub'] ?? [] as $sub) {
                        if (($sub['type'] ?? '') === 'link') $clean[$sub['k'] . 'Url'] = $item[$sub['k'] . 'Url'] ?? '';
                        $this->clean($sub, $item[$sub['k']] ?? '', $clean, $sub['k']);
                    }
                    $items[] = $clean;
                }
                $target[$key] = $items;
                break;
            case 'textarea': $target[$key] = mb_substr(str_replace("\r\n", "\n", trim((string) $value)), 0, $max ?: 20000); break;
            default: $target[$key] = mb_substr(trim((string) $value), 0, $max ?: 255);
        }
    }

    private function cleanFields(array $fields, array $input, array $current = []): array
    {
        $out = $current;
        foreach ($fields as $def) {
            if (! array_key_exists($def['k'], $input)) continue;
            if (($def['type'] ?? '') === 'link') $out[$def['k'] . 'Url'] = $input[$def['k'] . 'Url'] ?? '';
            $this->clean($def, $input[$def['k']], $out, $def['k']);
        }
        return $out;
    }

    private function cleanSections(mixed $sections): array
    {
        $types = Cms::sectionTypes(); $out = [];
        $common = [['k' => 'hidden', 'type' => 'toggle'], ['k' => 'bg', 'type' => 'select', 'options' => ['Default', 'Soft', 'Navy']], ['k' => 'spacing', 'type' => 'select', 'options' => ['Standard', 'Compact', 'Spacious']], ['k' => 'devices', 'type' => 'select', 'options' => ['All devices', 'Desktop only', 'Mobile only']]];
        foreach (array_slice(is_array($sections) ? $sections : [], 0, 60) as $section) {
            $type = $section['type'] ?? '';
            if (! is_array($section) || ! isset($types[$type])) continue;
            $clean = ['id' => preg_match('/^s[\w]{1,40}$/', (string) ($section['id'] ?? '')) ? $section['id'] : 's' . bin2hex(random_bytes(4)), 'type' => $type];
            $clean = $this->cleanFields(array_merge($types[$type]['fields'], $common), array_replace(Cms::sectionCommon(), Cms::sectionDefaults()[$type], $section), $clean);
            $clean['anchor'] = preg_replace('/[^\w-]/', '', mb_substr((string) ($section['anchor'] ?? ''), 0, 60));
            $out[] = $clean;
        }
        return $out;
    }

    // ------------------------------------------------------------------ pages

    private function pageItem(array $page, array $userNames = []): array
    {
        $doc = Cms::pageDocument($page);
        $draft = json_decode((string) $page['draft'], true) ?: [];
        $status = Cms::PAGE_STATUSES[$page['status']] ?? 'Draft';
        if (! empty($draft['_scheduledAt'])) $status = 'Scheduled';
        elseif (! empty($draft['_review'])) $status = 'In review';
        unset($doc['_scheduledAt'], $doc['_review']);
        return $doc + [
            'id' => (int) $page['id'], 'status' => $status, 'live' => $page['status'] === 'published',
            'scheduledAt' => $draft['_scheduledAt'] ?? ($page['status'] === 'scheduled' ? $page['published_at'] : null),
            'hasDraft' => $page['draft'] !== null, 'hasPassword' => ! empty($page['page_password']),
            'updated' => $this->ago($page['updated_at']), 'by' => $userNames[$page['updated_by'] ?? 0] ?? 'Fynefield Admin',
            'previewUrl' => $page['preview_token'] && strtotime((string) $page['preview_token_expires']) > time() ? site_url('preview/' . $page['slug']) . '?token=' . $page['preview_token'] : null,
        ];
    }

    private function pageOr404(int $id): array
    {
        $page = db_connect()->table('pages')->where('id', $id)->get()->getRowArray();
        if (! $page) throw PageNotFoundException::forPageNotFound();
        return $page;
    }

    private function uniqueSlug(string $table, string $slug, ?int $exceptId = null): string
    {
        $slug = trim(url_title($slug, '-', true), '-') ?: 'page';
        $db = db_connect(); $candidate = $slug; $n = 2;
        while (true) {
            $query = $db->table($table)->where('slug', $candidate);
            if ($exceptId) $query->where('id !=', $exceptId);
            if (! $query->countAllResults() && ! in_array($candidate, ['admin', 'preview', 'unlock', 'newsletter', 'sitemap.xml', 'robots.txt', 'healthz'], true)) return $candidate;
            $candidate = $slug . '-' . $n++;
        }
    }

    public function createPage()
    {
        if (! Cms::can('edit_pages')) return $this->denied();
        $db = db_connect(); $now = date('Y-m-d H:i:s');
        $types = Cms::sectionDefaults();
        $sections = [array_replace(['id' => 's' . bin2hex(random_bytes(4)), 'type' => 'hero'], Cms::sectionCommon(), $types['hero']), array_replace(['id' => 's' . bin2hex(random_bytes(4)), 'type' => 'text'], Cms::sectionCommon(), $types['text'])];
        $slug = $this->uniqueSlug('pages', 'untitled-page');
        $db->table('pages')->insert(['title' => 'Untitled page', 'slug' => $slug, 'template' => 'standard', 'status' => 'draft', 'sections' => json_encode($sections, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'sort_order' => 50, 'preview_token' => bin2hex(random_bytes(32)), 'preview_token_expires' => date('Y-m-d H:i:s', strtotime('+7 days')), 'created_at' => $now, 'updated_at' => $now, 'updated_by' => session('admin_id')]);
        $id = (int) $db->insertID();
        $this->record('created a new draft page', 'pages', $id);
        return $this->json(['page' => $this->pageItem($this->pageOr404($id), [session('admin_id') => session('admin_name')]), 'linkOptions' => Cms::linkOptions()]);
    }

    public function saveDraft(int $id)
    {
        if (! Cms::can('edit_pages')) return $this->denied();
        $page = $this->pageOr404($id);
        $in = $this->input();
        $current = json_decode((string) $page['draft'], true) ?: [];
        $doc = [
            'title' => mb_substr(trim((string) ($in['title'] ?? $page['title'])), 0, 160) ?: 'Untitled page',
            'slug' => '/' . trim(url_title(trim((string) ($in['slug'] ?? $page['slug']), '/'), '-', true), '-'),
            'template' => in_array($in['template'] ?? '', Cms::TEMPLATES, true) ? $in['template'] : 'Standard',
            'inNav' => (bool) ($in['inNav'] ?? false),
            'headerStyle' => in_array($in['headerStyle'] ?? '', ['Transparent over hero', 'Solid'], true) ? $in['headerStyle'] : 'Transparent over hero',
            'visibility' => in_array($in['visibility'] ?? '', ['Public', 'Password protected', 'Hidden from search'], true) ? $in['visibility'] : 'Public',
            'footerCta' => (bool) ($in['footerCta'] ?? false),
            'seoTitle' => mb_substr(trim((string) ($in['seoTitle'] ?? '')), 0, 120), 'seoDesc' => mb_substr(trim((string) ($in['seoDesc'] ?? '')), 0, 300),
            'shareImage' => Cms::safeMediaPath($in['shareImage'] ?? ''), 'sections' => $this->cleanSections($in['sections'] ?? []),
        ];
        if ($page['slug'] === 'home') $doc['slug'] = '/';
        if ($doc['slug'] === '/' && $page['slug'] !== 'home') $doc['slug'] = '/' . $page['slug'];
        foreach (['_scheduledAt', '_review'] as $meta) if (isset($current[$meta])) $doc[$meta] = $current[$meta];
        $update = ['draft' => json_encode($doc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => session('admin_id')];
        $password = (string) ($in['password'] ?? '');
        if ($password !== '') $update['page_password'] = password_hash($password, PASSWORD_DEFAULT);
        db_connect()->table('pages')->where('id', $id)->update($update);
        return $this->json(['ok' => true]);
    }

    public function publishPage(int $id)
    {
        $page = $this->pageOr404($id);
        $in = $this->input(); $action = (string) ($in['action'] ?? '');
        $db = db_connect(); $now = date('Y-m-d H:i:s');
        $draft = json_decode((string) $page['draft'], true) ?: [];
        $title = $draft['title'] ?? $page['title'];
        if ($action === 'review') {
            if (! Cms::can('edit_pages')) return $this->denied();
            $draft = $draft ?: ['title' => $page['title']];
            if ($page['status'] === 'published') {
                $doc = Cms::pageDocument($page); $doc['_review'] = $now; unset($doc['_scheduledAt']);
                $db->table('pages')->where('id', $id)->update(['draft' => json_encode($doc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'submitted_for_review_at' => $now]);
            } else {
                $db->table('pages')->where('id', $id)->update(['status' => 'in_review', 'submitted_for_review_at' => $now]);
            }
            $this->notifyReviewers("“{$title}” is ready for review", "A page was submitted for review in Fynefield Studio:\n\n{$title}\n\n" . site_url('admin'));
            $this->record("submitted “{$title}” for review", 'pages', $id);
        } elseif ($action === 'publish' || $action === 'schedule') {
            if (! Cms::can('publish_pages')) return $this->denied();
            $doc = Cms::pageDocument($page);
            $slug = trim($doc['slug'], '/') ?: 'home';
            if ($slug !== $page['slug'] && $db->table('pages')->where('slug', $slug)->where('id !=', $id)->countAllResults()) return $this->fail('Another page already uses that URL. Choose a different one.');
            if ($action === 'publish') {
                Cms::publishDraft($page, (int) session('admin_id'));
                $this->record("published “{$title}”", 'pages', $id);
            } else {
                $at = strtotime((string) ($in['at'] ?? ''));
                if (! $at || $at <= time()) return $this->fail('Choose a future date and time to schedule.');
                $doc['_scheduledAt'] = date('Y-m-d H:i:s', $at); unset($doc['_review']);
                $update = ['draft' => json_encode($doc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)];
                if ($page['status'] !== 'published') $update += ['status' => 'scheduled', 'published_at' => date('Y-m-d H:i:s', $at)];
                $db->table('pages')->where('id', $id)->update($update);
                $this->record("scheduled “{$title}” for " . date('j M, H:i', $at), 'pages', $id);
            }
        } elseif ($action === 'draft') {
            if (! Cms::can('publish_pages')) return $this->denied();
            if ($page['slug'] === 'home') return $this->fail('The homepage cannot be unpublished.');
            $doc = Cms::pageDocument($page); unset($doc['_scheduledAt'], $doc['_review']);
            $db->table('pages')->where('id', $id)->update(['status' => 'draft', 'draft' => json_encode($doc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
            $this->record("unpublished “{$title}”", 'pages', $id);
        } else {
            return $this->fail('Unknown action.');
        }
        Cms::forget();
        return $this->json(['page' => $this->pageItem($this->pageOr404($id), [session('admin_id') => session('admin_name')]), 'nav' => Cms::get('nav'), 'linkOptions' => Cms::linkOptions()]);
    }

    private function notifyReviewers(string $subject, string $message): void
    {
        foreach (db_connect()->table('users')->where('is_active', 1)->get()->getResultArray() as $user) {
            if ((int) $user['id'] !== (int) session('admin_id') && Cms::can('publish_pages', $user['role'])) Cms::sendEmail($user['email'], $subject, $message);
        }
    }

    public function duplicatePage(int $id)
    {
        if (! Cms::can('edit_pages')) return $this->denied();
        $page = $this->pageOr404($id);
        $doc = Cms::pageDocument($page);
        $db = db_connect(); $now = date('Y-m-d H:i:s');
        $copy = $page; unset($copy['id'], $copy['legacy_source_id']);
        $copy['title'] = $doc['title'] . ' (copy)'; $copy['slug'] = $this->uniqueSlug('pages', trim($doc['slug'], '/') . '-copy');
        $copy['sections'] = json_encode($doc['sections'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); $copy['draft'] = null;
        $copy['status'] = 'draft'; $copy['published_at'] = null; $copy['approved_at'] = null; $copy['approved_by'] = null; $copy['page_password'] = null;
        $copy['preview_token'] = bin2hex(random_bytes(32)); $copy['preview_token_expires'] = date('Y-m-d H:i:s', strtotime('+7 days'));
        $copy['created_at'] = $now; $copy['updated_at'] = $now; $copy['updated_by'] = session('admin_id');
        $db->table('pages')->insert($copy); $newId = (int) $db->insertID();
        $this->record("duplicated “{$doc['title']}”", 'pages', $newId);
        return $this->json(['page' => $this->pageItem($this->pageOr404($newId), [session('admin_id') => session('admin_name')]), 'linkOptions' => Cms::linkOptions()]);
    }

    public function deletePage(int $id)
    {
        if (! Cms::can('publish_pages')) return $this->denied();
        $page = $this->pageOr404($id);
        if ($page['slug'] === 'home') return $this->fail('The homepage cannot be deleted.');
        $db = db_connect();
        $db->table('content_revisions')->insert(['entity_type' => 'pages', 'entity_id' => $id, 'snapshot' => json_encode($page, JSON_UNESCAPED_UNICODE), 'editor_id' => session('admin_id'), 'created_at' => date('Y-m-d H:i:s')]);
        $db->table('pages')->where('id', $id)->delete();
        $this->record("deleted the page “{$page['title']}”", 'pages', $id);
        return $this->json(['ok' => true, 'linkOptions' => Cms::linkOptions()]);
    }

    public function previewLink(int $id)
    {
        if (! Cms::can('edit_pages')) return $this->denied();
        $page = $this->pageOr404($id);
        $token = $page['preview_token'];
        if (! $token || strtotime((string) $page['preview_token_expires']) < time() + 86400) {
            $token = bin2hex(random_bytes(32));
            db_connect()->table('pages')->where('id', $id)->update(['preview_token' => $token, 'preview_token_expires' => date('Y-m-d H:i:s', strtotime('+7 days'))]);
        }
        return $this->json(['url' => site_url('preview/' . $page['slug']) . '?token=' . $token]);
    }

    public function revisions(int $id)
    {
        $this->pageOr404($id);
        $db = db_connect();
        $names = array_column($db->table('users')->select('id, name')->get()->getResultArray(), 'name', 'id');
        $rows = $db->table('content_revisions')->where(['entity_type' => 'pages', 'entity_id' => $id])->orderBy('id', 'DESC')->limit(12)->get()->getResultArray();
        return $this->json(['versions' => array_map(fn (array $r): array => ['id' => (int) $r['id'], 'when' => date('j M, H:i', strtotime($r['created_at'])), 'who' => ($names[$r['editor_id']] ?? 'Fynefield Admin') . ' · published'], $rows)]);
    }

    public function restoreRevision(int $id, int $revisionId)
    {
        if (! Cms::can('edit_pages')) return $this->denied();
        $page = $this->pageOr404($id);
        $revision = db_connect()->table('content_revisions')->where(['id' => $revisionId, 'entity_type' => 'pages', 'entity_id' => $id])->get()->getRowArray();
        if (! $revision) throw PageNotFoundException::forPageNotFound();
        $snapshot = json_decode($revision['snapshot'], true) ?: [];
        if (! isset($snapshot['sections'])) return $this->fail('That version predates the Studio and cannot be restored here.');
        // Restoring loads the old version into the draft; it goes live only when published.
        $snapshot['draft'] = null;
        $doc = Cms::pageDocument($snapshot + $page);
        db_connect()->table('pages')->where('id', $id)->update(['draft' => json_encode($doc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => session('admin_id')]);
        $this->record("restored an earlier version of “{$page['title']}”", 'pages', $id);
        return $this->json(['page' => $this->pageItem($this->pageOr404($id), [session('admin_id') => session('admin_name')])]);
    }

    // ------------------------------------------------------------ collections

    private function collectionDefs(): array
    {
        $publishStatuses = ['Published', 'Draft', 'Scheduled', 'In review'];
        return [
            'news' => ['label' => 'News', 'singular' => 'article', 'titleKey' => 'title', 'cols' => [['title', 'Title'], ['category', 'Category'], ['dateLabel', 'Date'], ['status', 'Status']],
                'fields' => [['k' => 'title', 'label' => 'Title', 'max' => 80], ['k' => 'slug', 'label' => 'URL slug'], ['k' => 'date', 'label' => 'Publish date', 'type' => 'datetime'],
                    ['k' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $publishStatuses], ['k' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => Cms::NEWS_CATEGORIES],
                    ['k' => 'featured', 'label' => 'Featured on News page', 'type' => 'toggle', 'onText' => 'Featured', 'offText' => 'Not featured'], ['k' => 'cover', 'label' => 'Cover image', 'type' => 'image'],
                    ['k' => 'excerpt', 'label' => 'Excerpt', 'type' => 'textarea', 'rows' => 3, 'max' => 200], ['k' => 'body', 'label' => 'Article body', 'type' => 'textarea', 'rows' => 10, 'hint' => 'Markdown: **bold**, [link](url), - lists'],
                    ['k' => 'quote', 'label' => 'Pull quote', 'type' => 'textarea', 'rows' => 2, 'hint' => 'Optional'], ['k' => 'author', 'label' => 'Author'],
                    ['k' => 'seoTitle', 'label' => 'SEO title', 'max' => 60], ['k' => 'seoDesc', 'label' => 'Meta description', 'type' => 'textarea', 'rows' => 3, 'max' => 155]]],
            'services' => ['label' => 'Services', 'singular' => 'service', 'titleKey' => 'title', 'cols' => [['title', 'Service'], ['kicker', 'Category'], ['slug', 'URL'], ['status', 'Status']],
                'fields' => [['k' => 'title', 'label' => 'Service name'], ['k' => 'slug', 'label' => 'URL slug'], ['k' => 'kicker', 'label' => 'Category label'],
                    ['k' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Published', 'Draft']], ['k' => 'summary', 'label' => 'Short summary', 'type' => 'textarea', 'rows' => 2, 'max' => 160],
                    ['k' => 'lede', 'label' => 'Overview lead', 'type' => 'textarea', 'rows' => 3], ['k' => 'body', 'label' => 'Overview body', 'type' => 'textarea', 'rows' => 5],
                    ['k' => 'cta', 'label' => 'Call-to-action label'], ['k' => 'hero', 'label' => 'Hero image', 'type' => 'image'], ['k' => 'card', 'label' => 'Card image', 'type' => 'image'],
                    ['k' => 'facts', 'label' => 'At a glance', 'type' => 'list', 'itemLabel' => 'fact', 'sub' => [['k' => 'l', 'label' => 'Label'], ['k' => 'v', 'label' => 'Value']]],
                    ['k' => 'gallery1', 'label' => 'Gallery image 1', 'type' => 'image'], ['k' => 'gallery2', 'label' => 'Gallery image 2', 'type' => 'image'], ['k' => 'gallery3', 'label' => 'Gallery image 3', 'type' => 'image'],
                    ['k' => 'inFinder', 'label' => 'Show in service finder', 'type' => 'toggle']]],
            'team' => ['label' => 'Team', 'singular' => 'person', 'titleKey' => 'name', 'cols' => [['name', 'Name'], ['role', 'Role'], ['group', 'Group'], ['visible', 'Visible']],
                'fields' => [['k' => 'name', 'label' => 'Full name'], ['k' => 'role', 'label' => 'Role / title'], ['k' => 'group', 'label' => 'Group', 'type' => 'select', 'options' => ['Board', 'Management Team', 'Operations']],
                    ['k' => 'photo', 'label' => 'Portrait', 'type' => 'image'], ['k' => 'bio', 'label' => 'Short bio', 'type' => 'textarea', 'rows' => 2, 'max' => 140], ['k' => 'fullBio', 'label' => 'Full biography', 'type' => 'textarea', 'rows' => 8],
                    ['k' => 'facts', 'label' => 'Profile details', 'type' => 'list', 'itemLabel' => 'detail', 'sub' => [['k' => 'l', 'label' => 'Label'], ['k' => 'v', 'label' => 'Value']]],
                    ['k' => 'linkedin', 'label' => 'LinkedIn URL', 'hint' => 'Optional'], ['k' => 'visible', 'label' => 'Show on website', 'type' => 'toggle', 'onText' => 'Visible', 'offText' => 'Hidden']]],
            'locations' => ['label' => 'Locations', 'singular' => 'location', 'titleKey' => 'state', 'cols' => [['state', 'State'], ['city', 'Map point'], ['role', 'Operations'], ['visible', 'Visible']],
                'fields' => [['k' => 'state', 'label' => 'State / territory'], ['k' => 'city', 'label' => 'Map point (city)'], ['k' => 'role', 'label' => 'Operations label', 'hint' => 'e.g. Depot · Retail'],
                    ['k' => 'type', 'label' => 'Pin type', 'type' => 'select', 'options' => ['Retail & operations', 'Depot', 'Head office']],
                    ['k' => 'lat', 'label' => 'Latitude'], ['k' => 'lng', 'label' => 'Longitude'], ['k' => 'outlets', 'label' => 'Number of outlets'], ['k' => 'address', 'label' => 'Address', 'type' => 'textarea', 'rows' => 2, 'hint' => 'Optional'],
                    ['k' => 'route', 'label' => 'Draw supply route from depot', 'type' => 'toggle'], ['k' => 'visible', 'label' => 'Show on map', 'type' => 'toggle', 'onText' => 'Visible', 'offText' => 'Hidden']]],
        ];
    }

    private function newsItem(array $row): array
    {
        $status = Cms::PAGE_STATUSES[$row['status']] ?? 'Draft';
        if ($row['status'] === 'scheduled' && $row['published_at'] && strtotime($row['published_at']) <= time()) $status = 'Published';
        return ['id' => (int) $row['id'], 'title' => $row['title'], 'slug' => $row['slug'], 'date' => $row['published_at'] ? date('Y-m-d\TH:i', strtotime($row['published_at'])) : '', 'dateLabel' => Cms::date($row['published_at']) ?: '—',
            'status' => $status, 'category' => $row['category'], 'featured' => (bool) $row['featured'], 'cover' => (string) $row['cover_image'], 'excerpt' => (string) $row['excerpt'],
            'body' => (string) $row['content'], 'quote' => (string) $row['quote'], 'author' => (string) $row['author'], 'seoTitle' => (string) $row['seo_title'], 'seoDesc' => (string) $row['meta_description'],
            'url' => site_url('news/' . $row['slug'])];
    }

    private function serviceItem(array $row): array
    {
        $gallery = $this->json_list($row['gallery']);
        return ['id' => (int) $row['id'], 'title' => $row['title'], 'slug' => $row['slug'], 'kicker' => (string) $row['kicker'], 'status' => $row['status'] === 'published' ? 'Published' : 'Draft',
            'summary' => (string) $row['summary'], 'lede' => (string) $row['lede'], 'body' => (string) $row['body'], 'cta' => (string) $row['cta_label'], 'hero' => (string) $row['image'], 'card' => (string) $row['card_image'],
            'facts' => $this->json_list($row['facts']), 'gallery1' => $gallery[0] ?? '', 'gallery2' => $gallery[1] ?? '', 'gallery3' => $gallery[2] ?? '', 'inFinder' => (bool) $row['in_finder'],
            'url' => site_url('services/' . $row['slug'])];
    }

    private function teamItem(array $row): array
    {
        return ['id' => (int) $row['id'], 'name' => $row['name'], 'role' => $row['position'], 'group' => $row['group_name'], 'photo' => (string) $row['image'], 'bio' => (string) $row['bio'],
            'fullBio' => (string) $row['full_bio'], 'facts' => $this->json_list($row['facts']), 'linkedin' => (string) $row['linkedin'], 'visible' => $row['status'] === 'published'];
    }

    private function locationItem(array $row): array
    {
        return ['id' => (int) $row['id'], 'state' => $row['state'], 'city' => (string) $row['city'], 'role' => (string) $row['role_label'], 'type' => $row['pin_type'], 'lat' => $row['latitude'] === null ? '' : (string) (float) $row['latitude'], 'lng' => $row['longitude'] === null ? '' : (string) (float) $row['longitude'],
            'outlets' => (string) $row['outlets'], 'address' => (string) $row['address'], 'route' => (bool) $row['show_route'], 'visible' => $row['status'] === 'published'];
    }

    private function itemFor(string $coll, array $row): array
    {
        return match ($coll) { 'news' => $this->newsItem($row), 'services' => $this->serviceItem($row), 'team' => $this->teamItem($row), default => $this->locationItem($row) };
    }

    /** Collection item fields as database columns, with status permission checks. */
    private function itemColumns(string $coll, array $item, ?array $current): array|string
    {
        $statusKey = array_flip(Cms::PAGE_STATUSES);
        switch ($coll) {
            case 'news':
                $status = $statusKey[$item['status']] ?? 'draft';
                $live = in_array($status, ['published', 'scheduled'], true) || ($current && in_array($current['status'], ['published', 'scheduled'], true));
                if ($live && ! Cms::can('publish_news')) return 'Your role can save drafts and request review; an editor publishes articles.';
                if (in_array($status, ['published', 'scheduled'], true) && ! $item['date']) $item['date'] = date('Y-m-d H:i:s');
                if ($status === 'scheduled' && strtotime($item['date']) <= time()) return 'Scheduled articles need a future publish date.';
                return ['title' => $item['title'] ?: 'Untitled article', 'slug' => $item['slug'], 'published_at' => $item['date'] ?: null, 'status' => $status, 'category' => $item['category'], 'featured' => (int) $item['featured'],
                    'cover_image' => $item['cover'], 'excerpt' => $item['excerpt'], 'content' => $item['body'], 'quote' => $item['quote'], 'author' => $item['author'], 'seo_title' => $item['seoTitle'], 'meta_description' => $item['seoDesc'],
                    'submitted_for_review_at' => $status === 'in_review' ? date('Y-m-d H:i:s') : ($current['submitted_for_review_at'] ?? null)];
            case 'services':
                if ((($item['status'] === 'Published') || ($current && $current['status'] === 'published')) && ! Cms::can('publish_pages')) return 'Your role cannot change published services.';
                return ['title' => $item['title'] ?: 'Untitled service', 'slug' => $item['slug'], 'kicker' => $item['kicker'], 'status' => $item['status'] === 'Published' ? 'published' : 'draft', 'summary' => $item['summary'],
                    'lede' => $item['lede'], 'body' => $item['body'], 'cta_label' => $item['cta'], 'image' => $item['hero'], 'card_image' => $item['card'], 'facts' => json_encode($item['facts'], JSON_UNESCAPED_UNICODE),
                    'gallery' => json_encode([$item['gallery1'], $item['gallery2'], $item['gallery3']], JSON_UNESCAPED_SLASHES), 'in_finder' => (int) $item['inFinder']];
            case 'team':
                if (($item['visible'] || ($current && $current['status'] === 'published')) && ! Cms::can('publish_pages')) return 'Your role cannot change people shown on the website.';
                return ['name' => $item['name'] ?: 'Untitled person', 'position' => $item['role'], 'group_name' => $item['group'], 'image' => $item['photo'], 'bio' => $item['bio'], 'full_bio' => $item['fullBio'],
                    'facts' => json_encode($item['facts'], JSON_UNESCAPED_UNICODE), 'linkedin' => Cms::safeUrl($item['linkedin']), 'status' => $item['visible'] ? 'published' : 'draft'];
            default:
                if (($item['visible'] || ($current && $current['status'] === 'published')) && ! Cms::can('publish_pages')) return 'Your role cannot change locations shown on the website.';
                $lat = is_numeric($item['lat']) ? max(-90, min(90, (float) $item['lat'])) : null;
                $lng = is_numeric($item['lng']) ? max(-180, min(180, (float) $item['lng'])) : null;
                return ['name' => $item['state'] ?: 'Untitled location', 'state' => $item['state'] ?: 'Untitled location', 'city' => $item['city'], 'role_label' => $item['role'], 'pin_type' => $item['type'], 'latitude' => $lat, 'longitude' => $lng,
                    'outlets' => mb_substr($item['outlets'], 0, 20), 'address' => $item['address'], 'show_route' => (int) $item['route'], 'status' => $item['visible'] ? 'published' : 'draft'];
        }
    }

    public function saveItem(string $coll, ?int $id = null)
    {
        $table = self::COLLECTION_TABLES[$coll] ?? null;
        if (! $table) throw PageNotFoundException::forPageNotFound();
        if (! Cms::can($coll === 'news' ? 'write_news' : 'edit_pages')) return $this->denied();
        $db = db_connect();
        $current = $id ? $db->table($table)->where('id', $id)->get()->getRowArray() : null;
        if ($id && ! $current) throw PageNotFoundException::forPageNotFound();
        $def = $this->collectionDefs()[$coll];
        $base = $current ? $this->itemFor($coll, $current) : [];
        $item = $this->cleanFields($def['fields'], $this->input(), $base);
        foreach ($def['fields'] as $field) if (! array_key_exists($field['k'], $item)) $this->clean($field, ($field['type'] ?? '') === 'toggle' ? false : '', $item, $field['k']);
        if (in_array($coll, ['news', 'services'], true)) $item['slug'] = $this->uniqueSlug($table, $item['slug'] ?: $item['title'], $id);
        $columns = $this->itemColumns($coll, $item, $current);
        if (is_string($columns)) return $this->fail($columns, 403);
        $now = date('Y-m-d H:i:s');
        $columns['updated_at'] = $now;
        if ($current) {
            $db->table('content_revisions')->insert(['entity_type' => $table, 'entity_id' => $id, 'snapshot' => json_encode($current, JSON_UNESCAPED_UNICODE), 'editor_id' => session('admin_id'), 'created_at' => $now]);
            if ($coll === 'services' && $current['slug'] !== $columns['slug']) $db->table('redirects')->insert(['source_path' => '/services/' . $current['slug'], 'target_url' => '/services/' . $columns['slug'], 'http_code' => 301, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            if ($coll === 'news' && $current['slug'] !== $columns['slug']) $db->table('redirects')->insert(['source_path' => '/news/' . $current['slug'], 'target_url' => '/news/' . $columns['slug'], 'http_code' => 301, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            $db->table($table)->where('id', $id)->update($columns);
        } else {
            $columns['created_at'] = $now;
            if ($table !== 'posts') $columns['sort_order'] = (int) ($db->table($table)->selectMax('sort_order')->get()->getRow()->sort_order ?? 0) + 1;
            if ($table === 'posts') { $columns['preview_token'] = bin2hex(random_bytes(32)); $columns['preview_token_expires'] = date('Y-m-d H:i:s', strtotime('+7 days')); }
            $db->table($table)->insert($columns); $id = (int) $db->insertID();
        }
        if ($coll === 'news' && $columns['status'] === 'in_review' && ($current['status'] ?? '') !== 'in_review') $this->notifyReviewers("“{$columns['title']}” awaits approval", "An article was submitted for review in Fynefield Studio:\n\n{$columns['title']}\n\n" . site_url('admin'));
        $label = $item[$def['titleKey']] ?? $def['singular'];
        $this->record(($current ? 'updated' : 'created') . " the {$def['singular']} “{$label}”", $table, $id);
        return $this->json(['item' => $this->itemFor($coll, $db->table($table)->where('id', $id)->get()->getRowArray()), 'linkOptions' => Cms::linkOptions(), 'serviceOptions' => array_map(static fn (array $s): array => ['value' => $s['slug'], 'label' => $s['title']], $db->table('services')->orderBy('sort_order')->get()->getResultArray())]);
    }

    public function deleteItem(string $coll, int $id)
    {
        $table = self::COLLECTION_TABLES[$coll] ?? null;
        if (! $table) throw PageNotFoundException::forPageNotFound();
        if (! Cms::can($coll === 'news' ? 'publish_news' : 'publish_pages')) return $this->denied();
        $db = db_connect();
        $row = $db->table($table)->where('id', $id)->get()->getRowArray();
        if (! $row) throw PageNotFoundException::forPageNotFound();
        $db->table('content_revisions')->insert(['entity_type' => $table, 'entity_id' => $id, 'snapshot' => json_encode($row, JSON_UNESCAPED_UNICODE), 'editor_id' => session('admin_id'), 'created_at' => date('Y-m-d H:i:s')]);
        $db->table($table)->where('id', $id)->delete();
        $this->record('deleted the ' . $this->collectionDefs()[$coll]['singular'] . ' “' . ($row['title'] ?? $row['name'] ?? $row['state'] ?? '') . '”', $table, $id);
        return $this->json(['ok' => true, 'linkOptions' => Cms::linkOptions()]);
    }

    public function reorderItems(string $coll)
    {
        $table = self::COLLECTION_TABLES[$coll] ?? null;
        if (! $table || $table === 'posts') throw PageNotFoundException::forPageNotFound();
        if (! Cms::can('edit_pages')) return $this->denied();
        $ids = array_map('intval', (array) ($this->input()['ids'] ?? []));
        $db = db_connect(); $db->transStart();
        foreach ($ids as $position => $id) $db->table($table)->where('id', $id)->update(['sort_order' => $position + 1]);
        $db->transComplete();
        return $this->json(['ok' => true]);
    }

    // ------------------------------------------------------------------ media

    /** Every place an image path is used, for the media library's "Used on" list. */
    private function mediaUsage(): array
    {
        $db = db_connect(); $used = [];
        $add = static function (string $path, string $where) use (&$used): void { if ($path !== '') $used[$path][$where] = true; };
        $types = Cms::sectionTypes();
        foreach ($db->table('pages')->get()->getResultArray() as $page) {
            foreach ([Cms::sections($page['sections']), Cms::pageDocument($page)['sections']] as $sections) {
                foreach ($sections as $section) {
                    array_walk_recursive($section, static function ($value) use ($add, $page, $section, $types): void {
                        if (is_string($value) && preg_match('#^/(uploads|assets/media)/#', $value)) $add($value, $page['title'] . ' → ' . ($types[$section['type']]['label'] ?? $section['type']));
                    });
                }
            }
            if ($page['share_image']) $add($page['share_image'], $page['title'] . ' → Share image');
        }
        foreach ($db->table('services')->get()->getResultArray() as $s) {
            foreach (array_merge([$s['image'], $s['card_image']], $this->json_list($s['gallery'])) as $path) $add((string) $path, $s['title']);
        }
        foreach ($db->table('team_members')->get()->getResultArray() as $t) $add((string) $t['image'], 'Team → ' . $t['name']);
        foreach ($db->table('posts')->get()->getResultArray() as $p) $add((string) $p['cover_image'], 'News → ' . $p['title']);
        $general = Cms::get('general');
        $add((string) $general['logo'], 'Header'); $add((string) $general['logo'], 'Settings → General');
        $add((string) $general['favicon'], 'Settings → Favicon');
        $add((string) Cms::setting('seo', 'shareImage'), 'Settings → SEO share image');
        return array_map('array_keys', $used);
    }

    private function mediaItems(): array
    {
        $usage = $this->mediaUsage();
        return array_map(fn (array $m): array => $this->mediaItem($m, $usage), db_connect()->table('media')->orderBy('id', 'DESC')->get()->getResultArray());
    }

    private function mediaItem(array $m, ?array $usage = null): array
    {
        $usage ??= $this->mediaUsage();
        $size = (int) $m['size_bytes'];
        return ['id' => (int) $m['id'], 'name' => $m['filename'], 'src' => $m['path'], 'alt' => (string) $m['alt_text'], 'caption' => (string) $m['caption'], 'folder' => $m['folder'] ?: 'Uploads',
            'size' => $size >= 1048576 ? round($size / 1048576, 1) . ' MB' : max(1, round($size / 1024)) . ' KB', 'dims' => $m['width'] ? $m['width'] . ' × ' . $m['height'] : '—', 'used' => $usage[$m['path']] ?? []];
    }

    /** Validates and stores one uploaded image, returning its new path or an error message. */
    private function storeUpload($file): array|string
    {
        if (! $file || ! $file->isValid() || $file->hasMoved()) return 'Upload a valid image.';
        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) return 'Images must be JPG, PNG or WebP.';
        if ($file->getSize() > 10 * 1024 * 1024) return 'Images must be 10 MB or smaller.';
        $dimensions = @getimagesize($file->getTempName());
        if (! $dimensions || $dimensions[0] < 1 || ((int) $dimensions[0] * (int) $dimensions[1]) > 40_000_000) return 'Images must be valid and no larger than 40 megapixels.';
        $name = $file->getRandomName();
        $file->move(FCPATH . 'uploads', $name);
        return ['path' => '/uploads/' . $name, 'mime_type' => $file->getMimeType(), 'size_bytes' => filesize(FCPATH . 'uploads/' . $name), 'width' => $dimensions[0], 'height' => $dimensions[1]];
    }

    public function uploadMedia()
    {
        if (! Cms::can('manage_media')) return $this->denied();
        $files = $this->request->getFileMultiple('files') ?: [];
        $folder = in_array($this->request->getPost('folder'), Cms::MEDIA_FOLDERS, true) ? $this->request->getPost('folder') : 'Uploads';
        $db = db_connect(); $created = []; $errors = [];
        foreach (array_slice($files, 0, 20) as $file) {
            $stored = $this->storeUpload($file);
            if (is_string($stored)) { $errors[] = $file->getClientName() . ': ' . $stored; continue; }
            $db->table('media')->insert($stored + ['filename' => mb_substr($file->getClientName(), 0, 190), 'folder' => $folder, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            $created[] = $this->mediaItem($db->table('media')->where('id', $db->insertID())->get()->getRowArray(), []);
        }
        if ($created) $this->record('uploaded ' . count($created) . ' image' . (count($created) > 1 ? 's' : ''), 'media');
        if (! $created) return $this->fail($errors[0] ?? 'Choose at least one image.');
        return $this->json(['media' => $created, 'errors' => $errors]);
    }

    public function saveMedia(int $id)
    {
        if (! Cms::can('manage_media')) return $this->denied();
        $in = $this->input(); $db = db_connect();
        if (! $db->table('media')->where('id', $id)->countAllResults()) throw PageNotFoundException::forPageNotFound();
        $db->table('media')->where('id', $id)->update(['alt_text' => mb_substr(trim((string) ($in['alt'] ?? '')), 0, 255) ?: null, 'caption' => mb_substr(trim((string) ($in['caption'] ?? '')), 0, 255) ?: null, 'folder' => in_array($in['folder'] ?? '', Cms::MEDIA_FOLDERS, true) ? $in['folder'] : 'Uploads', 'updated_at' => date('Y-m-d H:i:s')]);
        return $this->json(['ok' => true]);
    }

    /** Points every reference to an image at its replacement. */
    private function replaceMediaReferences(string $old, string $new): void
    {
        $db = db_connect();
        $swap = static function (mixed $value) use (&$swap, $old, $new): mixed { return is_array($value) ? array_map($swap, $value) : ($value === $old ? $new : $value); };
        foreach ($db->table('pages')->select('id, sections, draft, share_image')->get()->getResultArray() as $page) {
            $update = [];
            foreach (['sections', 'draft'] as $column) { $decoded = json_decode((string) $page[$column], true); if (is_array($decoded)) $update[$column] = json_encode($swap($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); }
            if ($page['share_image'] === $old) $update['share_image'] = $new;
            if ($update) $db->table('pages')->where('id', $page['id'])->update($update);
        }
        foreach ($db->table('services')->get()->getResultArray() as $s) {
            $db->table('services')->where('id', $s['id'])->update(['image' => $s['image'] === $old ? $new : $s['image'], 'card_image' => $s['card_image'] === $old ? $new : $s['card_image'], 'gallery' => json_encode($swap($this->json_list($s['gallery'])), JSON_UNESCAPED_SLASHES)]);
        }
        $db->table('team_members')->where('image', $old)->update(['image' => $new]);
        $db->table('posts')->where('cover_image', $old)->update(['cover_image' => $new]);
        foreach (['general', 'seo'] as $key) Cms::put($key, $swap(Cms::get($key)));
    }

    public function replaceMedia(int $id)
    {
        if (! Cms::can('manage_media')) return $this->denied();
        $db = db_connect();
        $media = $db->table('media')->where('id', $id)->get()->getRowArray();
        if (! $media) throw PageNotFoundException::forPageNotFound();
        $file = $this->request->getFile('file');
        $stored = $this->storeUpload($file);
        if (is_string($stored)) return $this->fail($stored);
        $this->replaceMediaReferences($media['path'], $stored['path']);
        if (str_starts_with($media['path'], '/uploads/') && is_file(FCPATH . ltrim($media['path'], '/'))) @unlink(FCPATH . ltrim($media['path'], '/'));
        $db->table('media')->where('id', $id)->update($stored + ['filename' => mb_substr($file->getClientName(), 0, 190), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->record('replaced the image ' . $media['filename'], 'media', $id);
        return $this->json(['media' => $this->mediaItem($db->table('media')->where('id', $id)->get()->getRowArray()), 'previous' => $media['path']]);
    }

    public function deleteMedia(int $id)
    {
        if (! Cms::can('manage_media')) return $this->denied();
        $db = db_connect();
        $media = $db->table('media')->where('id', $id)->get()->getRowArray();
        if (! $media) throw PageNotFoundException::forPageNotFound();
        $used = $this->mediaUsage()[$media['path']] ?? [];
        if ($used) return $this->fail('In use on ' . count($used) . ' place(s) — replace it there first.');
        $db->table('media')->where('id', $id)->delete();
        // Only files uploaded through the Studio are removed from disk; the bundled library stays in the repository.
        if (str_starts_with($media['path'], '/uploads/') && is_file(FCPATH . ltrim($media['path'], '/'))) @unlink(FCPATH . ltrim($media['path'], '/'));
        $this->record('deleted the image ' . $media['filename'], 'media', $id);
        return $this->json(['ok' => true]);
    }

    // -------------------------------------------------------------- inquiries

    private function inquiryItems(): array
    {
        $db = db_connect();
        $names = array_column($db->table('users')->select('id, name')->get()->getResultArray(), 'name', 'id');
        $overdueHours = (int) Cms::setting('forms', 'overdueHours', '4 hours') ?: 4;
        $replies = [];
        foreach ($db->table('inquiry_replies')->orderBy('id')->get()->getResultArray() as $reply) $replies[$reply['inquiry_id']][] = ['who' => $names[$reply['user_id']] ?? 'Team', 'body' => $reply['body'], 'when' => $this->ago($reply['created_at']), 'status' => $reply['delivery_status']];
        return array_map(fn (array $q): array => [
            'id' => (int) $q['id'], 'ref' => $q['reference'] ?: '—', 'name' => $q['name'], 'company' => $q['company'] ?: 'Independent', 'email' => $q['email'], 'phone' => $q['phone'] ?: '—',
            'subject' => $q['subject'], 'topic' => $q['topic'] ?: 'General', 'message' => $q['message'], 'status' => in_array($q['status'], ['new', 'in_progress', 'resolved'], true) ? $q['status'] : 'new',
            'assignee' => $names[$q['assignee_id']] ?? 'Unassigned', 'notes' => (string) $q['internal_notes'], 'ago' => $this->ago($q['created_at']), 'received' => $q['created_at'],
            'overdue' => $q['status'] === 'new' && strtotime($q['created_at']) < time() - $overdueHours * 3600, 'notification' => $q['notification_status'], 'replies' => $replies[$q['id']] ?? [],
        ], $db->table('inquiries')->orderBy('created_at', 'DESC')->limit(500)->get()->getResultArray());
    }

    public function saveInquiry(int $id)
    {
        if (! Cms::can('inquiries')) return $this->denied();
        $db = db_connect(); $in = $this->input();
        $inquiry = $db->table('inquiries')->where('id', $id)->get()->getRowArray();
        if (! $inquiry) throw PageNotFoundException::forPageNotFound();
        $update = ['updated_at' => date('Y-m-d H:i:s')];
        if (isset($in['status']) && in_array($in['status'], ['new', 'in_progress', 'resolved'], true)) $update['status'] = $in['status'];
        if (array_key_exists('assignee', $in)) $update['assignee_id'] = $db->table('users')->where('name', (string) $in['assignee'])->where('is_active', 1)->get()->getRow()->id ?? null;
        if (array_key_exists('notes', $in)) $update['internal_notes'] = mb_substr((string) $in['notes'], 0, 5000);
        $db->table('inquiries')->where('id', $id)->update($update);
        if (isset($update['status']) && $update['status'] !== $inquiry['status']) $this->record('marked inquiry ' . $inquiry['reference'] . ' as ' . str_replace('_', ' ', $update['status']), 'inquiries', $id);
        return $this->json(['ok' => true]);
    }

    public function replyInquiry(int $id)
    {
        if (! Cms::can('inquiries')) return $this->denied();
        $db = db_connect();
        $inquiry = $db->table('inquiries')->where('id', $id)->get()->getRowArray();
        if (! $inquiry) throw PageNotFoundException::forPageNotFound();
        $body = trim((string) ($this->input()['body'] ?? ''));
        if ($body === '') return $this->fail('Write a reply first.');
        $contact = Cms::get('contact');
        $status = ! Cms::emailConfigured() ? 'not_configured' : (Cms::sendEmail($inquiry['email'], 'Re: ' . $inquiry['subject'] . ' · ' . $inquiry['reference'], mb_substr($body, 0, 20000), $contact['email'], Cms::setting('general', 'siteName')) ? 'sent' : 'failed');
        $now = date('Y-m-d H:i:s');
        $db->table('inquiry_replies')->insert(['inquiry_id' => $id, 'user_id' => session('admin_id'), 'body' => mb_substr($body, 0, 20000), 'sent_to' => $inquiry['email'], 'delivery_status' => $status, 'created_at' => $now]);
        $db->table('inquiries')->where('id', $id)->update(['status' => $inquiry['status'] === 'resolved' ? 'resolved' : 'in_progress', 'replied_at' => $now, 'updated_at' => $now]);
        $this->record('replied to inquiry ' . $inquiry['reference'], 'inquiries', $id);
        return $this->json(['status' => $status, 'inquiry' => array_values(array_filter($this->inquiryItems(), static fn ($q) => $q['id'] === $id))[0] ?? null]);
    }

    public function exportInquiries()
    {
        if (! Cms::can('inquiries')) return redirect()->to('/admin');
        $rows = db_connect()->table('inquiries')->select('reference,name,company,email,phone,topic,subject,message,status,notification_status,created_at,updated_at,internal_notes')->orderBy('id', 'DESC')->get()->getResultArray();
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Reference', 'Name', 'Company', 'Email', 'Phone', 'Topic', 'Subject', 'Message', 'Status', 'Notification', 'Received at', 'Updated at', 'Internal notes']);
        // Spreadsheet applications treat cells that start with a formula character as executable content.
        foreach ($rows as $row) fputcsv($stream, array_map(fn ($value): string => $this->safeCsvValue($value), array_values($row)));
        rewind($stream); $csv = stream_get_contents($stream); fclose($stream);
        $this->record('exported inquiries');
        return $this->response->setContentType('text/csv', 'UTF-8')->setHeader('Content-Disposition', 'attachment; filename="inquiries-' . date('Y-m-d') . '.csv"')->setBody($csv);
    }

    // --------------------------------------------- navigation, theme, settings

    private function navFields(): array
    {
        $linkList = ['type' => 'list', 'sub' => [['k' => 'label', 'label' => 'Label'], ['k' => 'link', 'type' => 'link'], ['k' => 'show', 'type' => 'select', 'options' => ['Visible', 'Hidden']]]];
        return [
            ['k' => 'header'] + $linkList,
            ['k' => 'sticky', 'type' => 'toggle'], ['k' => 'transparent', 'type' => 'toggle'], ['k' => 'mobileBreakpoint', 'type' => 'select', 'options' => ['768px', '960px', '1100px']],
            ['k' => 'columns', 'type' => 'list', 'sub' => [['k' => 'heading', 'label' => 'Heading'], ['k' => 'links', 'type' => 'list', 'sub' => [['k' => 'label'], ['k' => 'link', 'type' => 'link']]]]],
            ['k' => 'showAddress', 'type' => 'toggle'], ['k' => 'showCta', 'type' => 'toggle'], ['k' => 'showWordmark', 'type' => 'toggle'], ['k' => 'wordmark', 'max' => 40], ['k' => 'copyright', 'max' => 160],
            ['k' => 'legal', 'type' => 'list', 'sub' => [['k' => 'label'], ['k' => 'link', 'type' => 'link']]],
        ];
    }

    public function saveNav()
    {
        if (! Cms::can('nav_theme')) return $this->denied();
        $in = $this->input();
        $nav = $this->cleanFields($this->navFields(), $in, Cms::get('nav'));
        $cta = is_array($in['cta'] ?? null) ? $in['cta'] : [];
        $nav['cta'] = $this->cleanFields([['k' => 'label', 'max' => 40], ['k' => 'link', 'type' => 'link'], ['k' => 'show', 'type' => 'select', 'options' => ['Visible', 'Hidden']]], $cta, []);
        Cms::put('nav', $nav);
        $this->record('published navigation changes');
        return $this->json(['nav' => Cms::get('nav')]);
    }

    public function saveTheme()
    {
        if (! Cms::can('nav_theme')) return $this->denied();
        $fields = array_merge(...array_column(Cms::themeFields(), 'fields'));
        Cms::put('theme', $this->cleanFields($fields, $this->input(), Cms::defaultTheme()));
        $this->record('published the theme');
        return $this->json(['theme' => Cms::get('theme')]);
    }

    public function saveSettings(string $tab)
    {
        if (! Cms::can('settings')) return $this->denied();
        $tabs = array_column(Cms::settingsTabs(), null, 'k');
        if (! isset($tabs[$tab])) throw PageNotFoundException::forPageNotFound();
        $in = $this->input();
        $fields = array_values(array_filter($tabs[$tab]['fields'], static fn (array $f): bool => $f['k'] !== 'smtp' && $f['k'] !== 'redirects'));
        $values = $this->cleanFields($fields, $in, Cms::get($tab));
        if ($tab === 'contact' && $values['email'] !== '' && ! filter_var($values['email'], FILTER_VALIDATE_EMAIL)) return $this->fail('Enter a valid contact email address.');
        if ($tab === 'contact') $values['mapsLink'] = Cms::safeUrl($values['mapsLink']);
        if ($tab === 'forms') {
            foreach ($values['topics'] as $topic) if ($topic['route'] !== '' && ! filter_var($topic['route'], FILTER_VALIDATE_EMAIL)) return $this->fail("“{$topic['route']}” is not a valid email address.");
            $values['refPrefix'] = preg_replace('/[^\w-]/', '', $values['refPrefix']) ?: 'FP-';
        }
        if ($tab === 'seo') foreach (['linkedin', 'x', 'facebook', 'instagram'] as $social) $values[$social] = Cms::safeUrl($values[$social]);
        if ($tab === 'integrations') {
            foreach (['analytics', 'tagManager'] as $id) $values[$id] = preg_replace('/[^\w-]/', '', $values[$id]);
            unset($values['smtp']);
        }
        if ($tab === 'advanced') {
            $redirects = [];
            foreach (array_slice((array) ($in['redirects'] ?? []), 0, 500) as $row) {
                $from = '/' . trim((string) ($row['from'] ?? ''), '/'); $to = trim((string) ($row['to'] ?? ''));
                if ($from === '/' && $to === '') continue;
                if (! preg_match('~^/(?!/)[^\s?#]*$~', $from) || ! preg_match('#^(?:/(?!/)|https?://)#i', $to)) return $this->fail("Redirect “{$from}” needs a site path and a site path or https:// address to send it to.");
                $redirects[$from] = $to;
            }
            $db = db_connect(); $now = date('Y-m-d H:i:s');
            $db->transStart();
            $db->table('redirects')->truncate();
            foreach ($redirects as $from => $to) $db->table('redirects')->insert(['source_path' => $from, 'target_url' => $to, 'http_code' => 301, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            $db->transComplete();
            unset($values['redirects']);
        }
        Cms::put($tab, $values);
        $this->record('saved ' . strtolower($tabs[$tab]['label']) . ' settings');
        return $this->json(['settings' => Cms::get($tab)]);
    }

    // ---------------------------------------------------------- users & roles

    private function userItems(): array
    {
        $db = db_connect();
        // "Invited" means the person has never signed in; last_active_at only exists since the Studio.
        $signedIn = array_flip(array_column($db->table('activity_log')->select('user_id')->where('action', 'signed in')->distinct()->get()->getResultArray(), 'user_id'));
        return array_map(fn (array $u): array => ['id' => (int) $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => Cms::roleLabel($u['role']), 'last' => $u['last_active_at'] ? $this->ago($u['last_active_at']) : '—',
            'status' => ! $u['is_active'] ? 'Inactive' : ($u['last_active_at'] || isset($signedIn[$u['id']]) ? 'Active' : 'Invited')], $db->table('users')->orderBy('id')->get()->getResultArray());
    }

    private function roleKey(string $label): ?string
    {
        $key = array_search($label, Cms::ROLES, true);
        return $key === false ? null : $key;
    }

    public function inviteUser()
    {
        if (! Cms::can('manage_users')) return $this->denied();
        $in = $this->input(); $db = db_connect();
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        $role = $this->roleKey((string) ($in['role'] ?? '')) ?? 'editor';
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) return $this->fail('Enter a valid email address.');
        if ($db->table('users')->where('email', $email)->countAllResults()) return $this->fail('Someone with that email already has an account.');
        $now = date('Y-m-d H:i:s');
        $name = ucwords(str_replace(['.', '_', '-'], ' ', strstr($email, '@', true)));
        $db->table('users')->insert(['name' => $name, 'email' => $email, 'password_hash' => password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT), 'role' => $role, 'must_change_password' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        $userId = (int) $db->insertID();
        // The invitation is a one-time "set your password" link, valid for seven days.
        $token = bin2hex(random_bytes(32));
        $db->table('password_resets')->insert(['user_id' => $userId, 'token_hash' => hash('sha256', $token), 'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')), 'requested_ip' => (string) $this->request->getIPAddress(), 'created_at' => $now]);
        $link = site_url('admin/reset-password') . '?token=' . $token;
        $sent = Cms::sendEmail($email, 'You have been invited to Fynefield Studio', session('admin_name') . " invited you to manage the " . Cms::setting('general', 'siteName') . " website as " . Cms::roleLabel($role) . ".\n\nSet your password within seven days:\n{$link}\n");
        $this->record("invited {$email} as " . Cms::roleLabel($role), 'users', $userId);
        return $this->json(['users' => $this->userItems(), 'sent' => $sent, 'link' => $sent ? null : $link]);
    }

    private function administratorsAfter(int $userId, ?string $newRole, bool $removing): int
    {
        $db = db_connect();
        $count = $db->table('users')->where('role', 'administrator')->where('is_active', 1)->where('id !=', $userId)->countAllResults();
        return $count + (! $removing && $newRole === 'administrator' ? 1 : 0);
    }

    public function saveUser(int $id)
    {
        if (! Cms::can('manage_users')) return $this->denied();
        $db = db_connect();
        $user = $db->table('users')->where('id', $id)->get()->getRowArray();
        if (! $user) throw PageNotFoundException::forPageNotFound();
        $role = $this->roleKey((string) ($this->input()['role'] ?? ''));
        if (! $role) return $this->fail('Choose a valid role.');
        if ($user['role'] === 'administrator' && $role !== 'administrator' && $this->administratorsAfter($id, $role, false) < 1) return $this->fail('You need at least one administrator.');
        if ($id === (int) session('admin_id') && $role !== 'administrator') return $this->fail('You cannot remove your own administrator access.');
        $db->table('users')->where('id', $id)->update(['role' => $role, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->record("changed {$user['name']}'s role to " . Cms::roleLabel($role), 'users', $id);
        return $this->json(['users' => $this->userItems()]);
    }

    public function deleteUser(int $id)
    {
        if (! Cms::can('manage_users')) return $this->denied();
        $db = db_connect();
        $user = $db->table('users')->where('id', $id)->get()->getRowArray();
        if (! $user) throw PageNotFoundException::forPageNotFound();
        if ($id === (int) session('admin_id')) return $this->fail('You cannot remove your own account.');
        if ($user['role'] === 'administrator' && $this->administratorsAfter($id, null, true) < 1) return $this->fail('You need at least one administrator.');
        $db->table('password_resets')->where('user_id', $id)->delete();
        $db->table('users')->where('id', $id)->delete();
        $this->record("removed {$user['name']}", 'users', $id);
        return $this->json(['users' => $this->userItems()]);
    }

    public function savePerms()
    {
        if (! Cms::can('manage_users')) return $this->denied();
        $in = $this->input(); $perms = [];
        foreach (Cms::PERMISSIONS as $key => $label) {
            $row = array_values(array_map('intval', (array) ($in[$key] ?? Cms::DEFAULT_PERMS[$key])));
            $row = array_pad(array_slice($row, 0, 4), 4, 0);
            $row[0] = 1; // Administrators always have every permission.
            $perms[$key] = array_map(static fn ($v) => $v ? 1 : 0, $row);
        }
        Cms::put('perms', $perms);
        $this->record('updated role permissions');
        return $this->json(['ok' => true]);
    }

    // ------------------------------------------------------------------ today

    private function activityItems(): array
    {
        $rows = db_connect()->table('activity_log')->select('activity_log.*, users.name AS user_name')->join('users', 'users.id = activity_log.user_id', 'left')
            ->whereNotIn('action', ['signed in', 'requested password reset', 'completed password reset'])->orderBy('activity_log.id', 'DESC')->limit(8)->get()->getResultArray();
        $nouns = ['pages' => 'a page', 'posts' => 'an article', 'services' => 'a service', 'team_members' => 'a team profile', 'locations' => 'a location', 'redirects' => 'a redirect', 'navigation_items' => 'a menu link', 'users' => 'a user', 'media' => 'an image'];
        return array_map(fn (array $r): array => [
            'who' => (int) $r['user_id'] === (int) session('admin_id') ? 'You' : ($r['user_name'] ?: ($r['user_id'] ? 'A former user' : 'Website')),
            // Entries from the old admin were a single verb; give them their subject.
            'what' => preg_match('/^\w+$/', $r['action']) && isset($nouns[$r['entity_type']]) ? $r['action'] . ' ' . $nouns[$r['entity_type']] : $r['action'],
            'when' => str_replace(' ago', '', $this->ago($r['created_at'])),
        ], $rows);
    }

    /** Links that point at pages or services visitors cannot open. */
    private function healthChecks(array $pages, array $services): array
    {
        $livePages = array_column(array_filter($pages, static fn ($p) => in_array($p['status'], ['published', 'scheduled'], true)), 'slug');
        $liveServices = array_column(array_filter($services, static fn ($s) => $s['status'] === 'published'), 'slug');
        $broken = [];
        $check = static function (mixed $value, string $where) use (&$broken, $livePages, $liveServices): void {
            if (! is_string($value)) return;
            if (str_starts_with($value, 'page:') && ! in_array(substr($value, 5), $livePages, true)) $broken[] = $where;
            if (str_starts_with($value, 'service:') && ! in_array(substr($value, 8), $liveServices, true)) $broken[] = $where;
        };
        $nav = Cms::get('nav');
        array_walk_recursive($nav, static fn ($v) => $check($v, 'Navigation'));
        foreach ($pages as $page) {
            if (! in_array($page['slug'], $livePages, true)) continue;
            $sections = Cms::sections($page['sections']);
            array_walk_recursive($sections, static fn ($v) => $check($v, $page['title']));
        }
        return ['brokenLinks' => count($broken), 'brokenWhere' => array_values(array_unique($broken))];
    }
}
