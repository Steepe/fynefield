<?php

namespace App\Database\Migrations;

use App\Libraries\Cms;
use App\Libraries\Markdown;
use CodeIgniter\Database\Migration;

/**
 * Moves the site onto the Fynefield Studio content model: pages become ordered
 * sections, collections gain the fields the Studio edits, and site-wide settings,
 * navigation, theme and permissions become JSON documents. Existing content is
 * converted so the public site renders exactly as before.
 */
class BuildStudioContentModel extends Migration
{
    private const CORE_PAGES = ['home', 'about-us', 'services', 'management-team', 'news', 'contact-us', 'privacy-policy'];

    public function up()
    {
        $this->addColumns();
        $this->convertSettings();
        $this->convertPages();
        $this->convertServices();
        $this->convertPosts();
        $this->convertTeam();
        $this->convertLocations();
        $this->registerMedia();
        $this->convertInquiries();
        $this->db->table('users')->where('role', 'admin')->update(['role' => 'administrator']);
        $this->forge->dropColumn('pages', ['content', 'excerpt', 'cover_image']);
        $this->forge->dropTable('page_sections', true);
    }

    public function down()
    {
        $this->forge->addColumn('pages', [
            'excerpt' => ['type' => 'TEXT', 'null' => true, 'after' => 'meta_description'],
            'content' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'excerpt'],
            'cover_image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'content'],
        ]);
        $this->forge->dropColumn('pages', ['sections', 'draft', 'header_style', 'visibility', 'page_password', 'footer_cta', 'share_image', 'updated_by']);
        $this->forge->dropColumn('posts', ['category', 'featured', 'quote', 'author']);
        $this->db->query('ALTER TABLE services CHANGE body content LONGTEXT NULL');
        $this->forge->dropColumn('services', ['kicker', 'lede', 'cta_label', 'card_image', 'facts', 'gallery', 'in_finder']);
        $this->forge->dropColumn('team_members', ['group_name', 'full_bio', 'facts', 'linkedin']);
        $this->forge->dropColumn('locations', ['role_label', 'pin_type', 'outlets', 'show_route', 'sort_order']);
        $this->forge->dropColumn('media', ['caption', 'folder', 'size_bytes', 'width', 'height', 'updated_at']);
        $this->forge->dropColumn('inquiries', ['company', 'topic', 'reference', 'assignee_id', 'replied_at']);
        $this->forge->dropColumn('users', ['last_active_at']);
        $this->forge->dropTable('inquiry_replies', true);
        $this->forge->dropTable('newsletter_subscribers', true);
        $this->db->table('users')->where('role', 'administrator')->update(['role' => 'admin']);
        $this->db->table('users')->whereIn('role', ['contributor', 'sales'])->update(['role' => 'editor']);
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true], 'page_id' => ['type' => 'INT', 'unsigned' => true],
            'section_type' => ['type' => 'VARCHAR', 'constraint' => 40], 'eyebrow' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'heading' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], 'content' => ['type' => 'LONGTEXT', 'null' => true],
            'image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], 'button_label' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'button_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], 'items' => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0], 'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('page_sections', true);
    }

    private function addColumns(): void
    {
        // MySQL cannot roll back DDL, so a partly applied run must be resumable.
        if ($this->db->fieldExists('sections', 'pages')) return;
        $this->forge->addColumn('pages', [
            'sections' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'meta_description'],
            'draft' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'sections'],
            'header_style' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'Transparent over hero', 'after' => 'template'],
            'visibility' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'Public', 'after' => 'header_style'],
            'page_password' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'visibility'],
            'footer_cta' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'page_password'],
            'share_image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'footer_cta'],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'updated_at'],
        ]);
        $this->forge->addColumn('posts', [
            'category' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'Company news', 'after' => 'slug'],
            'featured' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'category'],
            'quote' => ['type' => 'TEXT', 'null' => true, 'after' => 'content'],
            'author' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'quote'],
        ]);
        $this->db->query('ALTER TABLE services CHANGE content body LONGTEXT NULL');
        $this->forge->addColumn('services', [
            'kicker' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'slug'],
            'lede' => ['type' => 'TEXT', 'null' => true, 'after' => 'summary'],
            'cta_label' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'body'],
            'card_image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'image'],
            'facts' => ['type' => 'TEXT', 'null' => true, 'after' => 'card_image'],
            'gallery' => ['type' => 'TEXT', 'null' => true, 'after' => 'facts'],
            'in_finder' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'gallery'],
        ]);
        $this->forge->addColumn('team_members', [
            'group_name' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => 'Management Team', 'after' => 'position'],
            'full_bio' => ['type' => 'TEXT', 'null' => true, 'after' => 'bio'],
            'facts' => ['type' => 'TEXT', 'null' => true, 'after' => 'full_bio'],
            'linkedin' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'facts'],
        ]);
        $this->forge->addColumn('locations', [
            'role_label' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'state'],
            'pin_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'Retail & operations', 'after' => 'role_label'],
            'outlets' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'pin_type'],
            'show_route' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'longitude'],
            'sort_order' => ['type' => 'INT', 'default' => 0, 'after' => 'show_route'],
        ]);
        $this->forge->addColumn('media', [
            'caption' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'alt_text'],
            'folder' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'Uploads', 'after' => 'caption'],
            'size_bytes' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'folder'],
            'width' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'size_bytes'],
            'height' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'width'],
            'updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'created_at'],
        ]);
        $this->forge->addColumn('inquiries', [
            'company' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'name'],
            'topic' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'phone'],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'id'],
            'assignee_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'status'],
            'replied_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'notified_at'],
        ]);
        $this->forge->addColumn('users', ['last_active_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'is_active']]);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'inquiry_id' => ['type' => 'INT', 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'body' => ['type' => 'TEXT'],
            'sent_to' => ['type' => 'VARCHAR', 'constraint' => 190],
            'delivery_status' => ['type' => 'VARCHAR', 'constraint' => 20],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('inquiry_id');
        $this->forge->createTable('inquiry_replies', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190],
            'list_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['email', 'list_name']);
        $this->forge->createTable('newsletter_subscribers', true);
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function convertSettings(): void
    {
        $old = array_column($this->db->table('settings')->get()->getResultArray(), 'setting_value', 'setting_key');
        if (isset($old['general'])) return;
        $settings = Cms::defaultSettings();
        $settings['general']['siteName'] = $old['site_name'] ?? $settings['general']['siteName'];
        $settings['general']['tagline'] = $old['site_tagline'] ?? $settings['general']['tagline'];
        $settings['general']['logo'] = $old['logo'] ?? $settings['general']['logo'];
        $settings['contact']['phone'] = $old['contact_phone'] ?? $settings['contact']['phone'];
        $settings['contact']['email'] = $old['contact_email'] ?? $settings['contact']['email'];
        $settings['contact']['address'] = $old['address'] ?? $settings['contact']['address'];
        // Earlier inquiries went to a single recipient; keep that as every topic's route.
        $recipient = trim((string) ($old['inquiry_recipient_email'] ?? ''));
        if ($recipient !== '') foreach ($settings['forms']['topics'] as &$topic) $topic['route'] = $recipient;
        unset($topic);
        $settings['forms']['refPrefix'] = 'FP-' . date('y') . '-';

        $this->db->table('settings')->truncate();
        $now = date('Y-m-d H:i:s');
        $documents = $settings + ['nav' => Cms::defaultNav(), 'theme' => Cms::defaultTheme(), 'perms' => Cms::DEFAULT_PERMS];
        unset($documents['advanced']['redirects']);
        foreach ($documents as $key => $value) $this->db->table('settings')->insert(['setting_key' => $key, 'setting_value' => $this->json($value), 'updated_at' => $now]);
        Cms::forget();
    }

    private function s(string $type, array $data, array $common = []): array
    {
        return array_replace(['id' => 's' . bin2hex(random_bytes(4)), 'type' => $type], Cms::sectionCommon(), Cms::sectionDefaults()[$type], $data, $common);
    }

    private function convertPages(): void
    {
        $commissioning = '/assets/media/fynefield-commissioning.jpeg';
        $media = static fn (string $file): string => '/assets/media/' . $file;
        $cta = fn (): array => $this->s('cta', []);
        $byId = [];
        foreach ($this->db->table('pages')->get()->getResultArray() as $page) $byId[$page['id']] = $page;
        $slugOf = array_column($byId, 'slug', 'id');

        // The old News page carried a placeholder slug because /news was a fixed route.
        $newsId = array_search('news-2', $slugOf, true);
        if ($newsId !== false) $this->db->table('pages')->where('id', $newsId)->update(['slug' => 'news']);

        $about = $byId[array_search('about-us', $slugOf, true)] ?? null;
        $aboutParagraphs = $about ? array_values(array_filter(array_map('trim', explode("\n\n", Markdown::fromHtml($about['content']))))) : [];
        $aboutBody = implode("\n\n", array_slice($aboutParagraphs, 1)) ?: 'Our operations span petroleum product trading, bulk storage, haulage and distribution, and retail outlets.';
        if (str_contains($aboutBody, 'We bring disciplined')) $aboutBody = str_replace(' We bring disciplined', "\n\nWe bring disciplined", $aboutBody);

        $privacy = $byId[array_search('privacy-policy', $slugOf, true)] ?? null;

        $core = [
            'home' => ['title' => 'Home', 'template' => 'landing', 'sort' => 1, 'seo' => 'Fynefield Petroleum | Downstream energy in Nigeria', 'desc' => 'Integrated petroleum trading, bulk storage, logistics and retail across Nigeria since 2013.', 'sections' => [
                $this->s('hero', ['eyebrow' => 'Fynefield Petroleum Company Limited', 'heading' => "Powering Nigeria's downstream energy, responsibly.", 'body' => 'Integrated trading, storage, logistics and retail — built for dependable supply since 2013.', 'primaryLabel' => 'Explore our services', 'primaryLink' => 'url', 'primaryLinkUrl' => '#services', 'secondaryLabel' => 'About us', 'secondaryLink' => 'page:about-us', 'image' => $commissioning, 'height' => 'Tall', 'breadcrumb' => false]),
                $this->s('stats', ['items' => [['v' => '2013', 'l' => 'Year incorporated'], ['v' => '50M', 'l' => 'Litres of storage'], ['v' => '13+', 'l' => 'Tank trucks'], ['v' => '12+', 'l' => 'Retail outlets']], 'animate' => true]),
                $this->s('tabs', ['eyebrow' => 'What we do', 'heading' => 'Every link in the downstream value chain.', 'linkLabel' => 'All services', 'linkTo' => 'page:services', 'items' => [
                    ['title' => 'Trading', 'body' => 'Petroleum product trading that secures dependable supply for depots, stations and commercial customers.', 'link' => 'page:services', 'image' => $media('frontpage-image6.jpg'), 'facts' => 'PMS · AGO | Core products'],
                    ['title' => 'Bulk storage', 'body' => 'Our multi-product depot in the Calabar Free Trade Zone keeps product available when markets tighten.', 'link' => 'service:bulk-storage', 'image' => $commissioning, 'facts' => "50M L | Capacity\nCalabar FTZ | Location"],
                    ['title' => 'Logistics', 'body' => 'A dedicated fleet delivers product safely to our stations and customers in Calabar, Delta and Abuja.', 'link' => 'service:logistics', 'image' => $media('frontpage-image4.jpg'), 'facts' => "13+ | Tank trucks\n3 | Core corridors"],
                    ['title' => 'Retail', 'body' => 'A growing network of outlets offering dependable PMS to motorists and businesses.', 'link' => 'service:retail-outlets', 'image' => $media('oil-small.jpg'), 'facts' => "12+ | Outlets\n6 | States & FCT"],
                ]], ['anchor' => 'services']),
                $this->s('map', ['body' => 'Product flows from our Calabar Free Trade Zone depot to outlets and customers across five states and the FCT.']),
                $this->s('news', ['count' => '2', 'layout' => 'Compact list']),
                $this->s('form', ['heading' => "Let's talk supply.", 'body' => 'Bulk supply, storage, haulage or a retail partnership — tell us what you need.', 'fields' => 'Name, email, message', 'consent' => true, 'success' => "Thank you — we'll reply within one business day."], ['anchor' => 'inquiry']),
            ]],
            'about-us' => ['title' => 'About us', 'template' => 'standard', 'sort' => 2, 'seo' => 'About Fynefield | Downstream energy in Nigeria', 'desc' => 'Fynefield Petroleum is a Nigerian vertically integrated downstream company — trading, bulk storage, haulage and retail since 2013.', 'sections' => [
                $this->s('hero', ['eyebrow' => '', 'heading' => 'Built for dependable supply.', 'body' => 'A Nigerian downstream energy company built for dependable supply and service.', 'image' => $commissioning]),
                $this->s('split', ['eyebrow' => 'Our story', 'heading' => 'A Nigerian, vertically integrated downstream company, incorporated on 31 January 2013.', 'body' => $aboutBody, 'image' => $commissioning]),
                $this->s('stats', ['items' => [['v' => '2013', 'l' => 'Year incorporated'], ['v' => '50M', 'l' => 'Litres depot capacity'], ['v' => '13+', 'l' => 'Tank trucks'], ['v' => '12+', 'l' => 'Retail outlets, expanding']], 'animate' => false]),
                $this->s('cards', ['eyebrow' => 'What we do', 'heading' => 'Every link in the value chain.', 'columns' => '4', 'linkLabel' => 'All services', 'linkTo' => 'page:services', 'items' => [
                    ['title' => 'Trading', 'body' => 'Petroleum product trading that secures dependable supply.', 'link' => 'page:services'],
                    ['title' => 'Bulk storage', 'body' => 'A 50-million-litre multi-product depot in the Calabar Free Trade Zone.', 'link' => 'service:bulk-storage'],
                    ['title' => 'Logistics', 'body' => 'Product haulage to stations and customers in Calabar, Delta and Abuja.', 'link' => 'service:logistics'],
                    ['title' => 'Retail', 'body' => 'Outlets across Cross River, Akwa Ibom, Delta, Benue, Borno and the FCT.', 'link' => 'service:retail-outlets'],
                ]]),
                $cta(),
            ]],
            'services' => ['title' => 'Services', 'template' => 'standard', 'sort' => 3, 'seo' => 'Services | Fynefield Petroleum', 'desc' => 'Bulk storage, logistics and retail outlets from Fynefield Petroleum.', 'sections' => [
                $this->s('hero', ['eyebrow' => '', 'heading' => 'Solutions that move energy forward.', 'body' => 'Integrated downstream expertise — product trading, storage, logistics and retail — for customers and communities across Nigeria.', 'height' => 'Compact']),
                $this->s('picker', []), $this->s('serviceList', []), $cta(),
            ]],
            'management-team' => ['title' => 'Leadership', 'template' => 'standard', 'sort' => 4, 'seo' => 'Leadership | Fynefield Petroleum', 'desc' => 'Meet the people guiding Fynefield Petroleum.', 'sections' => [
                $this->s('hero', ['eyebrow' => '', 'heading' => 'Our leadership', 'body' => 'Practical energy-sector experience, combined with a commitment to safe, reliable service.', 'height' => 'Compact']),
                $this->s('team', ['columns' => '4']), $cta(),
            ]],
            'news' => ['title' => 'News', 'template' => 'listing', 'sort' => 5, 'seo' => 'News & insights | Fynefield Petroleum', 'desc' => 'Updates from our operations, people and industry engagement.', 'sections' => [
                $this->s('hero', ['eyebrow' => '', 'heading' => 'News & insights', 'body' => 'Updates from our operations, people and industry engagement.', 'height' => 'Compact']),
                $this->s('news', ['eyebrow' => '', 'heading' => 'All stories', 'count' => 'All', 'layout' => 'Featured + grid', 'yearFilter' => true]),
                $this->s('newsletter', []), $cta(),
            ]],
            'contact-us' => ['title' => 'Contact', 'template' => 'standard', 'sort' => 6, 'seo' => 'Contact Fynefield Petroleum', 'desc' => 'Tell us about your energy, supply or service needs.', 'sections' => [
                $this->s('hero', ['eyebrow' => '', 'heading' => 'Contact us', 'body' => 'Tell us about your energy, supply or service needs. Every request is routed straight to the right team.', 'height' => 'Compact']),
                $this->s('form', ['heading' => '', 'success' => "Thank you — we're on it."]),
                $this->s('contactCards', []),
                $this->s('faq', ['items' => [['q' => 'How can I make an enquiry?', 'a' => 'Use this form to send a supply, logistics, or service request to our team. You can also call or email us directly.'], ['q' => 'Where does Fynefield operate?', 'a' => 'Our operations and retail network serve key locations across Nigeria, including Cross River, Akwa Ibom, Delta, Benue, Borno and the FCT.']]]),
            ]],
            'privacy-policy' => ['title' => 'Privacy policy', 'template' => 'legal', 'sort' => 7, 'seo' => 'Privacy policy | Fynefield Petroleum', 'desc' => 'How Fynefield Petroleum collects and uses personal information.', 'header' => 'Solid', 'sections' => [
                $this->s('hero', ['eyebrow' => '', 'heading' => 'Privacy policy', 'body' => '', 'height' => 'Compact']),
                $this->s('text', ['heading' => '', 'body' => $privacy ? Markdown::fromHtml($privacy['content']) : 'Add your privacy policy here.']),
            ]],
        ];

        // Closest live destination for each retired WordPress page, so old links keep working.
        $retired = [
            'bulk-storage' => '/services/bulk-storage', 'logistics' => '/services/logistics', 'retail-outlets' => '/services/retail-outlets',
            'news-2' => '/news', 'news-events' => '/news', '3-column-news' => '/news', 'newsletter' => '/news', 'home' => '/',
            'projects' => '/services', 'energy-engineering' => '/services', 'material-engineering' => '/services', 'cnc-industry' => '/services',
            'quote' => '/contact-us', 'faq' => '/contact-us', 'locator' => '/contact-us', 'gallery' => '/about-us',
        ];
        $now = date('Y-m-d H:i:s');
        foreach ($byId as $id => $page) {
            $slug = $id === $newsId ? 'news' : $page['slug'];
            if (isset($core[$slug])) {
                $c = $core[$slug];
                $this->db->table('pages')->where('id', $id)->update([
                    'title' => $c['title'], 'template' => $c['template'], 'sort_order' => $c['sort'], 'seo_title' => $c['seo'], 'meta_description' => $c['desc'],
                    'header_style' => $c['header'] ?? 'Transparent over hero', 'visibility' => 'Public', 'footer_cta' => 0, 'share_image' => null,
                    'sections' => $this->json($c['sections']), 'draft' => null, 'updated_at' => $now,
                ]);
                continue;
            }
            // Leftover theme demo pages: unpublished, kept as a hero plus their text.
            $sections = [$this->s('hero', ['eyebrow' => '', 'heading' => $page['title'], 'body' => (string) $page['excerpt'], 'height' => 'Compact']), $this->s('text', ['heading' => '', 'body' => Markdown::fromHtml($page['content'])])];
            $this->db->table('pages')->where('id', $id)->update(['status' => 'draft', 'sort_order' => 100 + $id, 'header_style' => 'Transparent over hero', 'visibility' => 'Public', 'sections' => $this->json($sections), 'draft' => null, 'updated_at' => $now]);
        }
        foreach (array_merge($retired, ['typography' => '/', 'tabs' => '/', 'tables' => '/', 'progress-bars' => '/', 'notifications' => '/', 'toggles-accordions' => '/', 'video' => '/', 'sample-page' => '/']) as $from => $to) {
            $source = '/' . $from;
            if ($this->db->table('redirects')->where('source_path', $source)->countAllResults()) continue;
            $this->db->table('redirects')->insert(['source_path' => $source, 'target_url' => $to, 'http_code' => 301, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    private function convertServices(): void
    {
        $details = [
            'bulk-storage' => ['kicker' => 'Infrastructure', 'summary' => 'Multi-product depot capacity in the Calabar Free Trade Zone, supporting reliable petroleum product availability.', 'lede' => 'We operate a 50-million-litre capacity multi-product depot in the Calabar Free Trade Zone, Cross River State.', 'body' => 'Our storage infrastructure supports dependable supply for customers and for our own growing network — keeping product available when markets tighten.', 'cta' => 'Enquire about storage', 'facts' => [['l' => 'Capacity', 'v' => '50,000,000 litres'], ['l' => 'Location', 'v' => 'Calabar Free Trade Zone'], ['l' => 'State', 'v' => 'Cross River'], ['l' => 'Products', 'v' => 'Multi-product']]],
            'logistics' => ['kicker' => 'Haulage', 'summary' => 'Essential product haulage to our stations and customers, through a dedicated fleet serving Calabar, Delta and Abuja.', 'lede' => 'Our logistics business provides essential product haulage to our stations and customers in Calabar, Delta and Abuja.', 'body' => 'The operation runs on a dedicated fleet of more than 13 tank trucks, connecting our depot to the stations and commercial customers who rely on steady supply.', 'cta' => 'Arrange a delivery', 'facts' => [['l' => 'Fleet', 'v' => '13+ tank trucks'], ['l' => 'Corridors', 'v' => 'Calabar · Delta · Abuja'], ['l' => 'Serving', 'v' => 'Stations & customers']]],
            'retail-outlets' => ['kicker' => 'Stations', 'summary' => 'Convenient access to dependable PMS through a network expanding to more than 12 strategically positioned outlets.', 'lede' => 'Our retail network is expanding to more than 12 strategically positioned outlets.', 'body' => 'Fynefield stations serve motorists and businesses across Cross River, Akwa Ibom, Delta, Benue, Borno and the FCT with dependable PMS.', 'cta' => 'Discuss a retail partnership', 'facts' => [['l' => 'Outlets', 'v' => '12+, expanding'], ['l' => 'Coverage', 'v' => '5 states + FCT'], ['l' => 'Product', 'v' => 'PMS']]],
        ];
        foreach ($this->db->table('services')->get()->getResultArray() as $service) {
            $d = $details[$service['slug']] ?? null;
            $gallery = [$service['image'], '/assets/media/fynefield-commissioning.jpeg', '/assets/media/frontpage-image4.jpg'];
            $this->db->table('services')->where('id', $service['id'])->update($d ? [
                'kicker' => $d['kicker'], 'summary' => $d['summary'], 'lede' => $d['lede'], 'body' => $d['body'], 'cta_label' => $d['cta'],
                'facts' => $this->json($d['facts']), 'gallery' => $this->json($gallery), 'in_finder' => 1,
            ] : ['body' => Markdown::fromHtml($service['body']), 'facts' => '[]', 'gallery' => $this->json($gallery)]);
        }
    }

    private function convertPosts(): void
    {
        foreach ($this->db->table('posts')->get()->getResultArray() as $post) {
            $this->db->table('posts')->where('id', $post['id'])->update([
                'content' => Markdown::fromHtml($post['content']),
                'category' => str_contains($post['slug'], 'depot') ? 'Operations' : 'Company news',
                'featured' => (int) str_contains($post['slug'], 'otl'),
                'author' => 'Fynefield Communications',
            ]);
        }
    }

    private function convertTeam(): void
    {
        foreach ($this->db->table('team_members')->get()->getResultArray() as $member) {
            $this->db->table('team_members')->where('id', $member['id'])->update([
                'group_name' => $member['position'] === 'Chairman' ? 'Board' : 'Management Team',
                'full_bio' => 'Fynefield’s leadership team combines practical sector experience with disciplined attention to dependable supply, safe operations and customer service.',
                'facts' => $this->json([['l' => 'Role', 'v' => $member['position']], ['l' => 'Company', 'v' => 'Fynefield Petroleum']]),
            ]);
        }
    }

    private function convertLocations(): void
    {
        $now = date('Y-m-d H:i:s');
        $sites = [
            ['Cross River', 'Calabar', 'Depot · Retail', 'Depot', 4.95, 8.32, 'Calabar Free Trade Zone', 0],
            ['FCT Abuja', 'Abuja', 'Head office · Retail', 'Head office', 9.06, 7.49, null, 1],
            ['Delta', 'Warri', 'Haulage · Retail', 'Retail & operations', 5.52, 5.75, null, 1],
            ['Akwa Ibom', 'Uyo', 'Retail', 'Retail & operations', 5.03, 7.93, null, 1],
            ['Benue', 'Makurdi', 'Retail', 'Retail & operations', 7.73, 8.54, null, 1],
            ['Borno', 'Maiduguri', 'Retail', 'Retail & operations', 11.83, 13.15, null, 1],
        ];
        $existing = $this->db->table('locations')->get()->getResultArray();
        $abuja = null;
        foreach ($existing as $row) if (stripos((string) $row['city'], 'abuja') !== false) $abuja = $row;
        foreach ($sites as $order => [$state, $city, $role, $type, $lat, $lng, $address, $route]) {
            $data = ['name' => $state, 'state' => $state, 'city' => $city, 'role_label' => $role, 'pin_type' => $type, 'latitude' => $lat, 'longitude' => $lng, 'show_route' => $route, 'sort_order' => $order + 1, 'status' => 'published', 'updated_at' => $now];
            if ($city === 'Abuja' && $abuja) {
                $this->db->table('locations')->where('id', $abuja['id'])->update($data);
                continue;
            }
            if ($this->db->table('locations')->where('state', $state)->countAllResults()) continue;
            $this->db->table('locations')->insert($data + ['address' => (string) $address, 'created_at' => $now]);
        }
    }

    private function registerMedia(): void
    {
        $known = array_column($this->db->table('media')->get()->getResultArray(), 'id', 'path');
        $alts = ['fynefield-logo.png' => 'Fynefield Petroleum logo', 'fynefield-commissioning.jpeg' => 'Commissioning of the Fynefield depot, Calabar'];
        $captions = ['fynefield-commissioning.jpeg' => 'Commissioning of the Calabar FTZ depot'];
        $files = array_merge(glob(FCPATH . 'assets/media/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [], glob(FCPATH . 'uploads/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: []);
        foreach ($files as $file) {
            $path = str_starts_with($file, FCPATH . 'uploads/') ? '/uploads/' . basename($file) : '/assets/media/' . basename($file);
            $name = basename($file);
            $size = @getimagesize($file) ?: [null, null];
            $folder = match (true) { str_contains($name, 'logo') => 'Brand', str_contains($name, 'Ogbechie') || str_contains($name, 'Ibekwe') => 'People', str_starts_with($path, '/uploads/') => 'Uploads', default => 'Operations' };
            $data = ['folder' => $folder, 'size_bytes' => filesize($file), 'width' => $size[0], 'height' => $size[1], 'updated_at' => date('Y-m-d H:i:s')];
            if (isset($known[$path])) { $this->db->table('media')->where('id', $known[$path])->update($data); continue; }
            $this->db->table('media')->insert($data + ['filename' => $name, 'path' => $path, 'mime_type' => mime_content_type($file) ?: 'image/jpeg', 'alt_text' => $alts[$name] ?? null, 'caption' => $captions[$name] ?? null, 'created_at' => date('Y-m-d H:i:s')]);
        }
    }

    private function convertInquiries(): void
    {
        $prefix = Cms::get('forms')['refPrefix'];
        foreach ($this->db->table('inquiries')->get()->getResultArray() as $inquiry) {
            $update = ['reference' => $prefix . str_pad((string) $inquiry['id'], 4, '0', STR_PAD_LEFT)];
            // Before this change the optional company was written at the top of the message.
            if (preg_match('/^Company: (.+?)\n\n(.*)$/s', (string) $inquiry['message'], $match)) {
                $update['company'] = $match[1];
                $update['message'] = $match[2];
            }
            $this->db->table('inquiries')->where('id', $inquiry['id'])->update($update);
        }
    }
}
