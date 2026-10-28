<?php

namespace App\Libraries;

/**
 * The Fynefield Studio content model.
 *
 * Mirrors the "Fynefield CMS" Claude Design export: section types, defaults,
 * navigation, theme, settings and role permissions. The Studio edits these
 * structures and the public site renders from them, so both read one definition.
 */
class Cms
{
    public const ROLES = ['administrator' => 'Administrator', 'editor' => 'Editor', 'contributor' => 'Contributor', 'sales' => 'Sales'];

    public const PERMISSIONS = [
        'edit_pages' => 'Edit pages',
        'publish_pages' => 'Publish pages',
        'write_news' => 'Write news',
        'publish_news' => 'Publish news',
        'manage_media' => 'Manage media',
        'inquiries' => 'Read & reply to inquiries',
        'nav_theme' => 'Navigation & theme',
        'settings' => 'Site settings',
        'manage_users' => 'Manage users',
    ];

    // Columns follow ROLES order: Administrator, Editor, Contributor, Sales.
    public const DEFAULT_PERMS = [
        'edit_pages' => [1, 1, 1, 0], 'publish_pages' => [1, 1, 0, 0], 'write_news' => [1, 1, 1, 0], 'publish_news' => [1, 1, 0, 0],
        'manage_media' => [1, 1, 1, 0], 'inquiries' => [1, 0, 0, 1], 'nav_theme' => [1, 1, 0, 0], 'settings' => [1, 0, 0, 0], 'manage_users' => [1, 0, 0, 0],
    ];

    public const PAGE_STATUSES = ['published' => 'Published', 'draft' => 'Draft', 'scheduled' => 'Scheduled', 'in_review' => 'In review'];
    public const TEMPLATES = ['landing' => 'Landing', 'standard' => 'Standard', 'listing' => 'Listing', 'legal' => 'Legal'];
    public const NEWS_CATEGORIES = ['Company news', 'Operations', 'Industry', 'Safety'];
    public const MEDIA_FOLDERS = ['Brand', 'Operations', 'People', 'News', 'Uploads'];
    public const FONTS = [
        'Instrument Sans' => 'Instrument+Sans:wght@400;500;600;700',
        'IBM Plex Sans' => 'IBM+Plex+Sans:wght@400;500;600;700',
        'Hanken Grotesk' => 'Hanken+Grotesk:wght@400;500;600;700',
        'Source Sans 3' => 'Source+Sans+3:wght@400;500;600;700',
    ];

    private static ?array $settingsCache = null;

    /** Section types offered by the page editor, with the fields each one exposes. */
    public static function sectionTypes(): array
    {
        $overlay = ['Strong', 'Medium', 'Light', 'None'];
        return [
            'hero' => ['label' => 'Hero / page header', 'group' => 'Headers', 'fields' => [
                ['k' => 'eyebrow', 'label' => 'Eyebrow'], ['k' => 'heading', 'label' => 'Heading', 'max' => 70], ['k' => 'body', 'label' => 'Intro text', 'type' => 'textarea', 'rows' => 3, 'max' => 200],
                ['k' => 'primaryLabel', 'label' => 'Primary button label'], ['k' => 'primaryLink', 'label' => 'Primary button link', 'type' => 'link'],
                ['k' => 'secondaryLabel', 'label' => 'Secondary button label', 'hint' => 'Leave empty to hide'], ['k' => 'secondaryLink', 'label' => 'Secondary button link', 'type' => 'link'],
                ['k' => 'image', 'label' => 'Background image', 'type' => 'image'], ['k' => 'overlay', 'label' => 'Image overlay', 'type' => 'select', 'options' => $overlay],
                ['k' => 'height', 'label' => 'Height', 'type' => 'select', 'options' => ['Compact', 'Standard', 'Tall']], ['k' => 'breadcrumb', 'label' => 'Breadcrumb', 'type' => 'toggle', 'onText' => 'Shown', 'offText' => 'Hidden']]],
            'stats' => ['label' => 'Statistics', 'group' => 'Content', 'fields' => [
                ['k' => 'items', 'label' => 'Figures', 'type' => 'list', 'itemLabel' => 'figure', 'sub' => [['k' => 'v', 'label' => 'Value'], ['k' => 'l', 'label' => 'Label']]],
                ['k' => 'style', 'label' => 'Style', 'type' => 'select', 'options' => ['Floating card', 'Plain row']], ['k' => 'animate', 'label' => 'Count-up animation', 'type' => 'toggle']]],
            'tabs' => ['label' => 'Value chain tabs', 'group' => 'Content', 'fields' => [
                ['k' => 'eyebrow', 'label' => 'Eyebrow'], ['k' => 'heading', 'label' => 'Heading'],
                ['k' => 'items', 'label' => 'Tabs', 'type' => 'list', 'itemLabel' => 'tab', 'sub' => [['k' => 'title', 'label' => 'Title'], ['k' => 'body', 'label' => 'Description', 'type' => 'textarea'], ['k' => 'link', 'label' => 'Link', 'type' => 'link'], ['k' => 'image', 'label' => 'Image', 'type' => 'image'], ['k' => 'facts', 'label' => 'Key figures', 'type' => 'textarea', 'hint' => 'One per line: Value | Label']]],
                ['k' => 'autoplay', 'label' => 'Auto-advance tabs', 'type' => 'toggle'], ['k' => 'interval', 'label' => 'Time per tab', 'type' => 'select', 'options' => ['5 seconds', '7 seconds', '10 seconds']],
                ['k' => 'linkLabel', 'label' => 'Section link label'], ['k' => 'linkTo', 'label' => 'Section link goes to', 'type' => 'link']]],
            'split' => ['label' => 'Image + text', 'group' => 'Content', 'fields' => [
                ['k' => 'eyebrow', 'label' => 'Eyebrow'], ['k' => 'heading', 'label' => 'Heading'], ['k' => 'body', 'label' => 'Body', 'type' => 'textarea', 'rows' => 5],
                ['k' => 'image', 'label' => 'Image', 'type' => 'image'], ['k' => 'imageSide', 'label' => 'Image position', 'type' => 'select', 'options' => ['Right', 'Left']],
                ['k' => 'buttonLabel', 'label' => 'Button label', 'hint' => 'Optional'], ['k' => 'buttonLink', 'label' => 'Button link', 'type' => 'link']]],
            'cards' => ['label' => 'Cards', 'group' => 'Content', 'fields' => [
                ['k' => 'eyebrow', 'label' => 'Eyebrow'], ['k' => 'heading', 'label' => 'Heading'],
                ['k' => 'items', 'label' => 'Cards', 'type' => 'list', 'itemLabel' => 'card', 'sub' => [['k' => 'title', 'label' => 'Title'], ['k' => 'body', 'label' => 'Text', 'type' => 'textarea'], ['k' => 'link', 'label' => 'Link', 'type' => 'link']]],
                ['k' => 'columns', 'label' => 'Columns', 'type' => 'select', 'options' => ['4', '3', '2']],
                ['k' => 'linkLabel', 'label' => 'Section link label', 'hint' => 'Optional'], ['k' => 'linkTo', 'label' => 'Section link goes to', 'type' => 'link']]],
            'picker' => ['label' => 'Service finder', 'group' => 'Interactive', 'fields' => [
                ['k' => 'eyebrow', 'label' => 'Eyebrow'], ['k' => 'heading', 'label' => 'Question'],
                ['k' => 'items', 'label' => 'Answers', 'type' => 'list', 'itemLabel' => 'answer', 'sub' => [['k' => 'label', 'label' => 'Answer label'], ['k' => 'service', 'label' => 'Recommends', 'type' => 'service']]]]],
            'serviceList' => ['label' => 'Service list', 'group' => 'Collections', 'source' => 'services', 'fields' => [
                ['k' => 'layout', 'label' => 'Layout', 'type' => 'select', 'options' => ['Alternating rows', 'Grid']], ['k' => 'showFacts', 'label' => 'Show key figures', 'type' => 'toggle'], ['k' => 'buttonLabel', 'label' => 'Button label']]],
            'team' => ['label' => 'Team grid', 'group' => 'Collections', 'source' => 'team', 'fields' => [
                ['k' => 'heading', 'label' => 'Heading', 'hint' => 'Optional'], ['k' => 'columns', 'label' => 'Columns', 'type' => 'select', 'options' => ['4', '3', '2']],
                ['k' => 'showBio', 'label' => 'Show short bio', 'type' => 'toggle'], ['k' => 'profiles', 'label' => 'Open full profile panel', 'type' => 'toggle']]],
            'map' => ['label' => 'Network map', 'group' => 'Collections', 'source' => 'locations', 'fields' => [
                ['k' => 'eyebrow', 'label' => 'Eyebrow'], ['k' => 'heading', 'label' => 'Heading'], ['k' => 'body', 'label' => 'Text', 'type' => 'textarea', 'rows' => 3],
                ['k' => 'showChips', 'label' => 'Location chips', 'type' => 'toggle'], ['k' => 'mapSide', 'label' => 'Map position', 'type' => 'select', 'options' => ['Left', 'Right']]]],
            'news' => ['label' => 'News feed', 'group' => 'Collections', 'source' => 'news', 'fields' => [
                ['k' => 'eyebrow', 'label' => 'Eyebrow'], ['k' => 'heading', 'label' => 'Heading'],
                ['k' => 'count', 'label' => 'Number of articles', 'type' => 'select', 'options' => ['2', '3', '4', '6', 'All']],
                ['k' => 'layout', 'label' => 'Layout', 'type' => 'select', 'options' => ['Cards', 'Compact list', 'Featured + grid']],
                ['k' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => array_merge(['All categories'], self::NEWS_CATEGORIES)],
                ['k' => 'yearFilter', 'label' => 'Year filter', 'type' => 'toggle', 'onText' => 'Shown', 'offText' => 'Hidden']]],
            'cta' => ['label' => 'Call to action', 'group' => 'Conversion', 'fields' => [
                ['k' => 'heading', 'label' => 'Heading'], ['k' => 'body', 'label' => 'Text', 'type' => 'textarea', 'rows' => 3],
                ['k' => 'button', 'label' => 'Button label'], ['k' => 'link', 'label' => 'Button link', 'type' => 'link'],
                ['k' => 'style', 'label' => 'Style', 'type' => 'select', 'options' => ['Orange split', 'Navy band']], ['k' => 'showContact', 'label' => 'Show phone & email', 'type' => 'toggle']]],
            'form' => ['label' => 'Inquiry form', 'group' => 'Conversion', 'fields' => [
                ['k' => 'heading', 'label' => 'Heading'], ['k' => 'body', 'label' => 'Intro text', 'type' => 'textarea', 'rows' => 2, 'hint' => 'Shown beside the short form'], ['k' => 'button', 'label' => 'Submit button label'],
                ['k' => 'fields', 'label' => 'Visible fields', 'type' => 'select', 'options' => ['Name, company, email, phone, subject, message', 'Name, email, message']],
                ['k' => 'consent', 'label' => 'Consent checkbox', 'type' => 'toggle'], ['k' => 'success', 'label' => 'Success message', 'type' => 'textarea', 'rows' => 2],
                ['k' => 'note', 'label' => 'Topics & routing', 'type' => 'select', 'options' => ['Managed in Settings → Forms']]]],
            'faq' => ['label' => 'FAQ', 'group' => 'Content', 'fields' => [
                ['k' => 'heading', 'label' => 'Heading'], ['k' => 'items', 'label' => 'Questions', 'type' => 'list', 'itemLabel' => 'question', 'sub' => [['k' => 'q', 'label' => 'Question'], ['k' => 'a', 'label' => 'Answer', 'type' => 'textarea']]],
                ['k' => 'openFirst', 'label' => 'First answer open', 'type' => 'toggle']]],
            'newsletter' => ['label' => 'Newsletter sign-up', 'group' => 'Conversion', 'fields' => [
                ['k' => 'heading', 'label' => 'Heading'], ['k' => 'body', 'label' => 'Text'], ['k' => 'button', 'label' => 'Button label'], ['k' => 'list', 'label' => 'Mailing list', 'type' => 'select', 'options' => ['Operational updates', 'Investors', 'Press']]]],
            'contactCards' => ['label' => 'Contact details', 'group' => 'Conversion', 'fields' => [
                ['k' => 'showPhone', 'label' => 'Phone', 'type' => 'toggle'], ['k' => 'showEmail', 'label' => 'Email', 'type' => 'toggle'], ['k' => 'showOffice', 'label' => 'Office address', 'type' => 'toggle'],
                ['k' => 'officeLabel', 'label' => 'Office label'], ['k' => 'directions', 'label' => 'Directions link label']]],
            'text' => ['label' => 'Rich text', 'group' => 'Content', 'fields' => [['k' => 'heading', 'label' => 'Heading'], ['k' => 'body', 'label' => 'Body', 'type' => 'textarea', 'rows' => 10, 'hint' => 'Markdown: **bold**, [link](url), - lists'], ['k' => 'width', 'label' => 'Width', 'type' => 'select', 'options' => ['Narrow', 'Wide']]]],
            'gallery' => ['label' => 'Image gallery', 'group' => 'Content', 'fields' => [['k' => 'heading', 'label' => 'Heading', 'hint' => 'Optional'], ['k' => 'image1', 'label' => 'Image 1', 'type' => 'image'], ['k' => 'image2', 'label' => 'Image 2', 'type' => 'image'], ['k' => 'image3', 'label' => 'Image 3', 'type' => 'image']]],
        ];
    }

    /** Starting content for a newly added section of each type. */
    public static function sectionDefaults(): array
    {
        return [
            'hero' => ['eyebrow' => 'Eyebrow', 'heading' => 'New page heading', 'body' => 'A short introduction.', 'primaryLabel' => '', 'primaryLink' => 'page:contact-us', 'secondaryLabel' => '', 'secondaryLink' => 'page:about-us', 'image' => '', 'overlay' => 'Strong', 'height' => 'Standard', 'breadcrumb' => true],
            'stats' => ['items' => [['v' => '00', 'l' => 'Label'], ['v' => '00', 'l' => 'Label'], ['v' => '00', 'l' => 'Label']], 'style' => 'Floating card', 'animate' => true],
            'tabs' => ['eyebrow' => 'What we do', 'heading' => 'Heading', 'items' => [['title' => 'Tab one', 'body' => 'Description', 'link' => 'page:services', 'image' => '', 'facts' => ''], ['title' => 'Tab two', 'body' => 'Description', 'link' => 'page:services', 'image' => '', 'facts' => '']], 'autoplay' => true, 'interval' => '7 seconds', 'linkLabel' => 'All services', 'linkTo' => 'page:services'],
            'split' => ['eyebrow' => 'Eyebrow', 'heading' => 'Heading', 'body' => 'Body text.', 'image' => '', 'imageSide' => 'Right', 'buttonLabel' => '', 'buttonLink' => 'page:contact-us'],
            'cards' => ['eyebrow' => 'Eyebrow', 'heading' => 'Heading', 'items' => [['title' => 'Card', 'body' => 'Text', 'link' => 'page:services'], ['title' => 'Card', 'body' => 'Text', 'link' => 'page:services'], ['title' => 'Card', 'body' => 'Text', 'link' => 'page:services']], 'columns' => '3', 'linkLabel' => '', 'linkTo' => 'page:services'],
            'picker' => ['eyebrow' => 'Find the right solution', 'heading' => 'What can we help you move?', 'items' => [['label' => 'Supply or bulk storage', 'service' => 'bulk-storage'], ['label' => 'Delivery and logistics', 'service' => 'logistics'], ['label' => 'Find a retail outlet', 'service' => 'retail-outlets']]],
            'serviceList' => ['layout' => 'Alternating rows', 'showFacts' => true, 'buttonLabel' => 'Learn more'],
            'team' => ['heading' => '', 'columns' => '3', 'showBio' => true, 'profiles' => true],
            'map' => ['eyebrow' => 'Our footprint', 'heading' => 'One depot. A growing national network.', 'body' => 'Product flows from our Calabar depot to outlets and customers.', 'showChips' => true, 'mapSide' => 'Left'],
            'news' => ['eyebrow' => 'Newsroom', 'heading' => 'Latest news', 'count' => '3', 'layout' => 'Cards', 'category' => 'All categories', 'yearFilter' => false],
            'cta' => ['heading' => "Let's talk supply.", 'body' => 'Bulk supply, storage, haulage or a retail partnership — tell us what you need.', 'button' => 'Send an inquiry', 'link' => 'page:contact-us', 'style' => 'Orange split', 'showContact' => true],
            'form' => ['heading' => 'Send an inquiry', 'body' => '', 'button' => 'Send inquiry', 'fields' => 'Name, company, email, phone, subject, message', 'consent' => true, 'success' => "Thank you — we're on it.", 'note' => 'Managed in Settings → Forms'],
            'faq' => ['heading' => 'Common questions', 'items' => [['q' => 'Question?', 'a' => 'Answer.']], 'openFirst' => true],
            'newsletter' => ['heading' => 'Stay informed', 'body' => 'Receive operational updates and news from Fynefield.', 'button' => 'Subscribe', 'list' => 'Operational updates'],
            'contactCards' => ['showPhone' => true, 'showEmail' => true, 'showOffice' => true, 'officeLabel' => 'Abuja office', 'directions' => 'Get directions'],
            'text' => ['heading' => 'Heading', 'body' => 'Start writing…', 'width' => 'Narrow'],
            'gallery' => ['heading' => '', 'image1' => '', 'image2' => '', 'image3' => ''],
        ];
    }

    /** Fields every section carries alongside its own, edited at the bottom of the inspector. */
    public static function sectionCommon(): array
    {
        return ['hidden' => false, 'bg' => 'Default', 'spacing' => 'Standard', 'anchor' => '', 'devices' => 'All devices'];
    }

    public static function defaultTheme(): array
    {
        return ['primary' => '#10263b', 'accent' => '#e59b23', 'surface' => '#f4f6f8', 'ink' => '#10263b', 'font' => 'Instrument Sans', 'headingWeight' => '600', 'headingScale' => 100, 'radius' => 10, 'button' => 'Pill', 'shadow' => 'Soft', 'motion' => 'Some', 'link' => 'Accent'];
    }

    public static function themeFields(): array
    {
        return [
            ['group' => 'Colours', 'fields' => [['k' => 'primary', 'label' => 'Primary (navy)', 'type' => 'color'], ['k' => 'accent', 'label' => 'Accent (orange)', 'type' => 'color'], ['k' => 'surface', 'label' => 'Soft background', 'type' => 'color'], ['k' => 'ink', 'label' => 'Text', 'type' => 'color']]],
            ['group' => 'Typography', 'fields' => [['k' => 'font', 'label' => 'Font', 'type' => 'select', 'options' => array_keys(self::FONTS)], ['k' => 'headingWeight', 'label' => 'Heading weight', 'type' => 'select', 'options' => ['500', '600', '700']], ['k' => 'headingScale', 'label' => 'Heading size', 'type' => 'range', 'min' => 80, 'max' => 120, 'step' => 5, 'unit' => '%']]],
            ['group' => 'Shape & depth', 'fields' => [['k' => 'radius', 'label' => 'Corner radius', 'type' => 'range', 'min' => 0, 'max' => 20, 'step' => 1, 'unit' => 'px'], ['k' => 'button', 'label' => 'Button shape', 'type' => 'select', 'options' => ['Pill', 'Rounded', 'Square']], ['k' => 'shadow', 'label' => 'Shadows', 'type' => 'select', 'options' => ['Soft', 'Strong', 'None']]]],
            ['group' => 'Behaviour', 'fields' => [['k' => 'motion', 'label' => 'Animation', 'type' => 'select', 'options' => ['Some', 'Subtle fades only', 'None']], ['k' => 'link', 'label' => 'Link colour', 'type' => 'select', 'options' => ['Accent', 'Primary']]]],
        ];
    }

    public static function defaultNav(): array
    {
        return [
            'header' => [
                ['label' => 'Home', 'link' => 'page:home', 'show' => 'Visible'], ['label' => 'About', 'link' => 'page:about-us', 'show' => 'Visible'], ['label' => 'Services', 'link' => 'page:services', 'show' => 'Visible'],
                ['label' => 'Leadership', 'link' => 'page:management-team', 'show' => 'Visible'], ['label' => 'News', 'link' => 'page:news', 'show' => 'Visible'], ['label' => 'Contact', 'link' => 'page:contact-us', 'show' => 'Visible'],
            ],
            'cta' => ['label' => 'Get in touch', 'link' => 'page:contact-us', 'show' => 'Visible'], 'sticky' => true, 'transparent' => true, 'mobileBreakpoint' => '960px',
            'columns' => [
                ['heading' => 'Company', 'links' => [['label' => 'About us', 'link' => 'page:about-us'], ['label' => 'Leadership', 'link' => 'page:management-team'], ['label' => 'News', 'link' => 'page:news']]],
                ['heading' => 'Services', 'links' => [['label' => 'Bulk storage', 'link' => 'service:bulk-storage'], ['label' => 'Logistics', 'link' => 'service:logistics'], ['label' => 'Retail outlets', 'link' => 'service:retail-outlets']]],
                ['heading' => 'Contact', 'links' => [['label' => '', 'link' => 'tel'], ['label' => '', 'link' => 'mailto'], ['label' => 'Make an inquiry', 'link' => 'page:contact-us']]],
            ],
            'showAddress' => true, 'showWordmark' => true, 'wordmark' => 'FYNEFIELD', 'copyright' => '© {year} Fynefield Petroleum Company Limited',
            'legal' => [['label' => 'Privacy policy', 'link' => 'page:privacy-policy'], ['label' => 'Sitemap', 'link' => 'url', 'linkUrl' => '/sitemap.xml']], 'showCta' => true,
        ];
    }

    public static function defaultSettings(): array
    {
        return [
            'general' => ['siteName' => 'Fynefield Petroleum', 'legalName' => 'Fynefield Petroleum Company Limited', 'tagline' => 'Energy solutions built on service excellence.', 'logo' => '/assets/media/fynefield-logo.png', 'favicon' => '', 'language' => 'English (Nigeria)', 'timezone' => 'West Africa Time (WAT)', 'dateFormat' => '12 Jan 2024'],
            'contact' => ['phone' => '+234 807 119 1635', 'email' => 'info@fynefield.com', 'address' => 'Suite 3, 4th Floor, Sinoki House, P.M.B 603, Ahmadu Bello Way, CBD, Abuja.', 'mapsLink' => '', 'hours' => 'Mon–Fri, 8:00–17:00 WAT', 'whatsapp' => ''],
            'forms' => [
                'topics' => [
                    ['label' => 'Bulk supply', 'route' => '', 'hint' => 'e.g. Weekly PMS supply to Warri', 'message' => 'Product, volume and delivery location'],
                    ['label' => 'Storage', 'route' => '', 'hint' => 'e.g. AGO storage for 60 days', 'message' => 'Product and storage duration'],
                    ['label' => 'Haulage', 'route' => '', 'hint' => 'e.g. Haulage Calabar → Abuja', 'message' => 'Pickup, destination and volume'],
                    ['label' => 'Retail partnership', 'route' => '', 'hint' => 'e.g. Operating a site in Makurdi', 'message' => 'Location and proposal'],
                    ['label' => 'Something else', 'route' => '', 'hint' => 'How can we help?', 'message' => 'Message'],
                ],
                'refPrefix' => 'FP-26-', 'responseTime' => 'One business day', 'autoReply' => false,
                'autoReplyText' => 'Thank you for contacting Fynefield Petroleum. Your reference is {ref}. Our {topic} team will respond within one business day.',
                'consent' => 'I consent to Fynefield using these details to respond to this inquiry. Please do not send payment or card information.', 'spam' => true, 'overdueHours' => '4 hours',
            ],
            'seo' => ['titleTemplate' => '{page} | Fynefield Petroleum', 'defaultDesc' => 'Integrated petroleum trading, bulk storage, logistics and retail across Nigeria since 2013.', 'shareImage' => '/assets/media/fynefield-commissioning.jpeg', 'indexing' => true, 'sitemap' => true, 'linkedin' => '', 'x' => '', 'facebook' => '', 'instagram' => ''],
            'integrations' => ['analytics' => '', 'tagManager' => '', 'recaptcha' => false, 'smtp' => '', 'newsletter' => 'Not connected', 'cookieBanner' => false],
            'advanced' => ['maintenance' => false, 'maintenanceMsg' => 'We’re making improvements. Please check back shortly.', 'redirects' => [], 'customCss' => '', 'backups' => 'Off'],
        ];
    }

    public static function settingsTabs(): array
    {
        return [
            ['k' => 'general', 'label' => 'General', 'desc' => 'Site identity used across every page.', 'fields' => [['k' => 'siteName', 'label' => 'Site name'], ['k' => 'legalName', 'label' => 'Legal company name'], ['k' => 'tagline', 'label' => 'Tagline'], ['k' => 'logo', 'label' => 'Logo', 'type' => 'image'], ['k' => 'favicon', 'label' => 'Favicon', 'type' => 'image', 'hint' => '512 × 512 PNG'], ['k' => 'language', 'label' => 'Language', 'type' => 'select', 'options' => ['English (Nigeria)', 'English (UK)', 'English (US)']], ['k' => 'timezone', 'label' => 'Time zone', 'type' => 'select', 'options' => ['West Africa Time (WAT)', 'UTC']], ['k' => 'dateFormat', 'label' => 'Date format', 'type' => 'select', 'options' => ['12 Jan 2024', '12/01/2024', 'January 12, 2024']]]],
            ['k' => 'contact', 'label' => 'Contact details', 'desc' => 'Shown in the footer, contact page and calls to action.', 'fields' => [['k' => 'phone', 'label' => 'Phone'], ['k' => 'email', 'label' => 'Email'], ['k' => 'address', 'label' => 'Office address', 'type' => 'textarea', 'rows' => 3], ['k' => 'mapsLink', 'label' => 'Google Maps link', 'hint' => 'Used for "Get directions"'], ['k' => 'hours', 'label' => 'Opening hours'], ['k' => 'whatsapp', 'label' => 'WhatsApp number', 'hint' => 'Optional']]],
            ['k' => 'forms', 'label' => 'Forms & notifications', 'desc' => 'Inquiry topics, who receives them, and what customers see.', 'fields' => [['k' => 'topics', 'label' => 'Inquiry topics & routing', 'type' => 'list', 'itemLabel' => 'topic', 'sub' => [['k' => 'label', 'label' => 'Topic'], ['k' => 'route', 'label' => 'Send to email'], ['k' => 'hint', 'label' => 'Subject hint'], ['k' => 'message', 'label' => 'Message label']]], ['k' => 'refPrefix', 'label' => 'Reference prefix'], ['k' => 'responseTime', 'label' => 'Promised response time', 'type' => 'select', 'options' => ['Same day', 'One business day', 'Two business days']], ['k' => 'overdueHours', 'label' => 'Flag as overdue after', 'type' => 'select', 'options' => ['2 hours', '4 hours', '8 hours', '24 hours']], ['k' => 'autoReply', 'label' => 'Auto-reply email to customer', 'type' => 'toggle'], ['k' => 'autoReplyText', 'label' => 'Auto-reply message', 'type' => 'textarea', 'rows' => 4, 'hint' => '{ref} {topic} {name}'], ['k' => 'consent', 'label' => 'Consent text', 'type' => 'textarea', 'rows' => 3], ['k' => 'spam', 'label' => 'Spam protection', 'type' => 'toggle']]],
            ['k' => 'seo', 'label' => 'SEO & social', 'desc' => 'Defaults used when a page has no SEO of its own.', 'fields' => [['k' => 'titleTemplate', 'label' => 'Title template', 'hint' => '{page} is replaced'], ['k' => 'defaultDesc', 'label' => 'Default meta description', 'type' => 'textarea', 'rows' => 3, 'max' => 155], ['k' => 'shareImage', 'label' => 'Default share image', 'type' => 'image', 'hint' => '1200 × 630'], ['k' => 'indexing', 'label' => 'Allow search engines to index the site', 'type' => 'toggle'], ['k' => 'sitemap', 'label' => 'Generate sitemap.xml', 'type' => 'toggle'], ['k' => 'linkedin', 'label' => 'LinkedIn'], ['k' => 'x', 'label' => 'X (Twitter)'], ['k' => 'facebook', 'label' => 'Facebook'], ['k' => 'instagram', 'label' => 'Instagram']]],
            ['k' => 'integrations', 'label' => 'Integrations', 'desc' => 'Connect analytics, email and other services.', 'fields' => [['k' => 'analytics', 'label' => 'Google Analytics ID', 'placeholder' => 'G-XXXXXXX'], ['k' => 'tagManager', 'label' => 'Tag Manager ID', 'placeholder' => 'GTM-XXXXXX'], ['k' => 'recaptcha', 'label' => 'reCAPTCHA on forms', 'type' => 'toggle', 'hint' => 'Needs keys in .env'], ['k' => 'smtp', 'label' => 'Outgoing email', 'type' => 'select', 'options' => [self::smtpStatus()]], ['k' => 'newsletter', 'label' => 'Newsletter provider', 'type' => 'select', 'options' => ['Not connected', 'Mailchimp', 'Brevo']], ['k' => 'cookieBanner', 'label' => 'Cookie consent banner', 'type' => 'toggle']]],
            ['k' => 'advanced', 'label' => 'Advanced', 'desc' => 'Maintenance, redirects and custom code.', 'fields' => [['k' => 'maintenance', 'label' => 'Maintenance mode', 'type' => 'toggle', 'onText' => 'Site is offline for visitors', 'offText' => 'Site is live'], ['k' => 'maintenanceMsg', 'label' => 'Maintenance message', 'type' => 'textarea', 'rows' => 2], ['k' => 'redirects', 'label' => 'Redirects', 'type' => 'list', 'itemLabel' => 'redirect', 'sub' => [['k' => 'from', 'label' => 'From path'], ['k' => 'to', 'label' => 'To path']]], ['k' => 'customCss', 'label' => 'Custom CSS', 'type' => 'textarea', 'rows' => 5, 'hint' => 'Advanced users only'], ['k' => 'backups', 'label' => 'Automatic backups', 'type' => 'select', 'options' => ['Daily at 02:00', 'Weekly', 'Off']]]],
        ];
    }

    // ---------------------------------------------------------------- storage

    private static function rows(): array
    {
        if (self::$settingsCache === null) {
            $rows = db_connect()->table('settings')->get()->getResultArray();
            self::$settingsCache = array_column($rows, 'setting_value', 'setting_key');
        }
        return self::$settingsCache;
    }

    public static function forget(): void { self::$settingsCache = null; }

    /** A stored JSON document merged over its defaults, so new keys always have a value. */
    public static function get(string $key): array
    {
        $defaults = match ($key) {
            'theme' => self::defaultTheme(),
            'nav' => self::defaultNav(),
            'perms' => self::DEFAULT_PERMS,
            default => self::defaultSettings()[$key] ?? [],
        };
        $stored = json_decode((string) (self::rows()[$key] ?? ''), true);
        return is_array($stored) ? array_replace($defaults, $stored) : $defaults;
    }

    public static function put(string $key, array $value): void
    {
        db_connect()->table('settings')->replace(['setting_key' => $key, 'setting_value' => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'updated_at' => date('Y-m-d H:i:s')]);
        self::forget();
    }

    public static function setting(string $group, string $key, mixed $fallback = ''): mixed
    {
        return self::get($group)[$key] ?? $fallback;
    }

    // ------------------------------------------------------------ permissions

    public static function roleLabel(?string $role): string { return self::ROLES[$role] ?? 'Editor'; }

    public static function can(string $permission, ?string $role = null): bool
    {
        $role ??= (string) session('admin_role');
        if ($role === 'administrator') return true;
        $index = array_search($role, array_keys(self::ROLES), true);
        if ($index === false) return false;
        return (bool) (self::get('perms')[$permission][$index] ?? 0);
    }

    // ------------------------------------------------------------------ links

    /**
     * Link fields store a token rather than a URL so renamed pages keep working:
     * page:{slug}, service:{slug}, tel, mailto or url (with the URL alongside).
     */
    public static function linkOptions(?array $pages = null, ?array $services = null): array
    {
        $db = db_connect();
        $pages ??= $db->table('pages')->select('slug, title')->orderBy('sort_order')->orderBy('id')->get()->getResultArray();
        $services ??= $db->table('services')->select('slug, title')->orderBy('sort_order')->get()->getResultArray();
        $options = [];
        foreach ($pages as $page) $options[] = ['value' => 'page:' . $page['slug'], 'label' => $page['title']];
        foreach ($services as $service) $options[] = ['value' => 'service:' . $service['slug'], 'label' => 'Service: ' . $service['title']];
        $options[] = ['value' => 'tel', 'label' => 'Phone number'];
        $options[] = ['value' => 'mailto', 'label' => 'Email address'];
        $options[] = ['value' => 'url', 'label' => 'External URL'];
        return $options;
    }

    public static function href(?string $token, ?string $url = ''): string
    {
        $token = (string) $token;
        if (str_starts_with($token, 'page:')) {
            $slug = substr($token, 5);
            return $slug === 'home' ? site_url('/') : site_url($slug);
        }
        if (str_starts_with($token, 'service:')) return site_url('services/' . substr($token, 8));
        if ($token === 'tel') return 'tel:' . preg_replace('/[^\d+]/', '', (string) self::setting('contact', 'phone'));
        if ($token === 'mailto') return 'mailto:' . self::setting('contact', 'email');
        if ($token === 'url') return self::safeUrl((string) $url) ?: '#';
        return '#';
    }

    /** Label shown for a link when the editor left the label empty (phone and email lines). */
    public static function linkText(array $link): string
    {
        $label = trim((string) ($link['label'] ?? ''));
        if ($label !== '') return $label;
        return match ($link['link'] ?? '') { 'tel' => (string) self::setting('contact', 'phone'), 'mailto' => (string) self::setting('contact', 'email'), default => '' };
    }

    public static function safeUrl(string $url): string
    {
        $url = trim($url);
        return preg_match('#^(?:https?://|/(?!/)|\#|mailto:|tel:)#i', $url) ? $url : '';
    }

    public static function safeMediaPath(mixed $path): string
    {
        $path = trim((string) $path);
        return preg_match('#^/(?:uploads|assets/media)/[\w.\-/]+$#', $path) && ! str_contains($path, '..') ? $path : '';
    }

    // ------------------------------------------------------------ formatting

    public static function date(?string $datetime, ?string $format = null): string
    {
        if (! $datetime || ! strtotime($datetime)) return '';
        $format ??= (string) self::setting('general', 'dateFormat', '12 Jan 2024');
        $pattern = match ($format) { '12/01/2024' => 'd/m/Y', 'January 12, 2024' => 'F j, Y', default => 'j M Y' };
        return date($pattern, strtotime($datetime));
    }

    public static function longDate(?string $datetime): string
    {
        return $datetime && strtotime($datetime) ? date('j F Y', strtotime($datetime)) : '';
    }

    public static function pageTitle(string $title): string
    {
        $template = (string) self::setting('seo', 'titleTemplate', '{page}');
        return str_contains($template, '{page}') ? str_replace('{page}', $title, $template) : $title;
    }

    public static function htmlLang(): string
    {
        return match ((string) self::setting('general', 'language')) { 'English (UK)' => 'en-GB', 'English (US)' => 'en-US', default => 'en-NG' };
    }

    /** Value and label pairs from "Value | Label" lines (tab key figures). */
    public static function factLines(?string $text): array
    {
        $facts = [];
        foreach (preg_split('/\R/', (string) $text) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            if (($parts[0] ?? '') !== '') $facts[] = [$parts[0], $parts[1] ?? ''];
        }
        return $facts;
    }

    // ------------------------------------------------------------------ theme

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) return [0, 0, 0];
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /** Mixes a colour towards white (positive amount) or black (negative amount). */
    private static function shade(string $hex, float $amount): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        $target = $amount >= 0 ? 255 : 0; $amount = abs($amount);
        $mix = static fn (int $c): int => (int) round($c + ($target - $c) * $amount);
        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    }

    public static function color(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : $fallback;
    }

    /** CSS custom properties for the published theme; the stylesheet reads every token from these. */
    public static function themeCss(?array $theme = null): string
    {
        $t = array_replace(self::defaultTheme(), $theme ?? self::get('theme'));
        $d = self::defaultTheme();
        $primary = self::color($t['primary'], $d['primary']);
        $accent = self::color($t['accent'], $d['accent']);
        $surface = self::color($t['surface'], $d['surface']);
        $ink = self::color($t['ink'], $d['ink']);
        $radius = max(0, min(20, (int) $t['radius']));
        $button = match ($t['button']) { 'Rounded' => '6px', 'Square' => '0px', default => '999px' };
        [$shadow, $shadowCard, $shadowHover] = match ($t['shadow']) {
            'None' => ['none', 'none', 'none'],
            'Strong' => ['0 28px 70px rgba(16,38,59,.24)', '0 28px 70px rgba(16,38,59,.2)', '0 22px 48px rgba(16,38,59,.18)'],
            default => ['0 24px 60px rgba(16,38,59,.14)', '0 24px 60px rgba(16,38,59,.12)', '0 18px 40px rgba(16,38,59,.1)'],
        };
        $font = array_key_exists($t['font'], self::FONTS) ? $t['font'] : $d['font'];
        $weight = in_array((string) $t['headingWeight'], ['500', '600', '700'], true) ? (string) $t['headingWeight'] : '600';
        $scale = max(80, min(120, (int) $t['headingScale'])) / 100;
        $link = $t['link'] === 'Primary' ? $primary : self::shade($accent, -0.12);
        return ':root{'
            . "--ff-ink:{$primary};--ff-text:{$ink};--ff-ink-hover:" . self::shade($primary, 0.1) . ';--ff-deep:' . self::shade($primary, -0.35)
            . ";--ff-amber:{$accent};--ff-amber-light:" . self::shade($accent, 0.12) . ';--ff-amber-text:' . self::shade($accent, -0.12) . ";--ff-link:{$link}"
            . ";--ff-surface:{$surface};--ff-radius:{$radius}px;--ff-radius-lg:" . ($radius + ($radius ? 2 : 0)) . "px;--ff-radius-sm:" . max(0, $radius - 2) . "px;--ff-btn-radius:{$button}"
            . ";--ff-shadow:{$shadow};--ff-shadow-card:{$shadowCard};--ff-shadow-hover:{$shadowHover}"
            . ";--ff-sans:'{$font}',system-ui,sans-serif;--ff-hw:{$weight};--ff-hs:{$scale}}";
    }

    public static function fontUrl(?array $theme = null): string
    {
        $font = ($theme ?? self::get('theme'))['font'] ?? 'Instrument Sans';
        return 'https://fonts.googleapis.com/css2?family=' . (self::FONTS[$font] ?? self::FONTS['Instrument Sans']) . '&display=swap';
    }

    // ------------------------------------------------------------------ email

    public static function emailConfigured(): bool
    {
        $email = config('Email');
        if (! filter_var($email->fromEmail, FILTER_VALIDATE_EMAIL)) return false;
        if ($email->protocol !== 'smtp') return true;
        return $email->SMTPHost !== '' && $email->SMTPUser !== '' && $email->SMTPPass !== '';
    }

    public static function smtpStatus(): string
    {
        $email = config('Email');
        if (! self::emailConfigured()) return 'Not connected';
        return 'Connected · ' . ($email->protocol === 'smtp' ? $email->SMTPHost : $email->protocol);
    }

    /** Sends a plain-text email. Returns false when email is not configured or sending fails. */
    public static function sendEmail(string $to, string $subject, string $message, ?string $replyTo = null, ?string $replyName = null): bool
    {
        if (! self::emailConfigured() || ! filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
        try {
            $config = config('Email');
            $mailer = service('email');
            $mailer->clear();
            $mailer->setFrom($config->fromEmail, $config->fromName ?: (string) self::setting('general', 'siteName'));
            $mailer->setTo($to);
            if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $mailer->setReplyTo($replyTo, (string) $replyName);
            $mailer->setSubject($subject);
            $mailer->setMessage($message);
            return $mailer->send(false);
        } catch (\Throwable $exception) {
            log_message('error', 'Email to {to} failed: {message}', ['to' => $to, 'message' => $exception->getMessage()]);
            return false;
        }
    }

    // ---------------------------------------------------------- page documents

    public static function slugPath(string $slug): string { return $slug === 'home' ? '/' : '/' . $slug; }

    public static function inNav(string $slug): bool
    {
        foreach (self::get('nav')['header'] as $item) if (($item['link'] ?? '') === 'page:' . $slug && ($item['show'] ?? '') !== 'Hidden') return true;
        return false;
    }

    /** The editable page document, built from the live columns when no draft exists. */
    public static function pageDocument(array $page): array
    {
        $draft = json_decode((string) ($page['draft'] ?? ''), true);
        $live = [
            'title' => $page['title'], 'slug' => self::slugPath($page['slug']), 'template' => self::TEMPLATES[$page['template']] ?? 'Standard',
            'inNav' => self::inNav($page['slug']), 'headerStyle' => $page['header_style'] ?: 'Transparent over hero', 'visibility' => $page['visibility'] ?: 'Public',
            'footerCta' => (bool) $page['footer_cta'], 'seoTitle' => (string) $page['seo_title'], 'seoDesc' => (string) $page['meta_description'], 'shareImage' => (string) $page['share_image'],
            'sections' => self::sections($page['sections']),
        ];
        $document = is_array($draft) ? array_replace($live, $draft) : $live;
        $document['sections'] = self::sections($document['sections']);
        return $document;
    }

    /** Sections decoded and completed with each type's defaults and the shared section fields. */
    public static function sections(mixed $sections): array
    {
        if (is_string($sections)) $sections = json_decode($sections, true);
        if (! is_array($sections)) return [];
        $types = self::sectionTypes(); $defaults = self::sectionDefaults(); $out = [];
        foreach ($sections as $section) {
            if (! is_array($section) || ! isset($types[$section['type'] ?? ''])) continue;
            $out[] = array_replace(['id' => 's' . bin2hex(random_bytes(4)), 'type' => $section['type']], self::sectionCommon(), $defaults[$section['type']], $section);
        }
        return $out;
    }

    /**
     * Applies scheduled page drafts whose publish time has arrived. Cheap enough to run per request.
     * A schedule lives in the draft (as _scheduledAt) so a live page stays online until its update is due.
     */
    public static function applyScheduled(): void
    {
        $db = db_connect();
        $candidates = $db->table('pages')->like('draft', '"_scheduledAt"')->get()->getResultArray();
        foreach ($candidates as $page) {
            $at = json_decode((string) $page['draft'], true)['_scheduledAt'] ?? null;
            if ($at && strtotime($at) <= time()) self::publishDraft($page, (int) ($page['updated_by'] ?? 0) ?: null, $at);
        }
    }

    /** Copies a page's draft onto its live columns, keeping the previous live version as a revision. */
    public static function publishDraft(array $page, ?int $editorId, ?string $publishedAt = null): void
    {
        $db = db_connect();
        $doc = self::pageDocument($page);
        $now = date('Y-m-d H:i:s');
        $db->table('content_revisions')->insert(['entity_type' => 'pages', 'entity_id' => $page['id'], 'snapshot' => json_encode($page, JSON_UNESCAPED_UNICODE), 'editor_id' => $editorId, 'created_at' => $now]);
        $slug = trim($doc['slug'], '/') ?: 'home';
        if ($slug !== $page['slug']) {
            // "Old URL redirects automatically" — keep inbound links working after a rename.
            $from = self::slugPath($page['slug']);
            $db->table('redirects')->where('source_path', $from)->delete();
            $db->table('redirects')->insert(['source_path' => $from, 'target_url' => self::slugPath($slug), 'http_code' => 301, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            self::renamePageLinks($page['slug'], $slug);
        }
        $db->table('pages')->where('id', $page['id'])->update([
            'title' => $doc['title'], 'slug' => $slug, 'template' => array_search($doc['template'], self::TEMPLATES, true) ?: 'standard',
            'header_style' => $doc['headerStyle'], 'visibility' => $doc['visibility'], 'footer_cta' => (int) $doc['footerCta'],
            'seo_title' => $doc['seoTitle'], 'meta_description' => $doc['seoDesc'], 'share_image' => self::safeMediaPath($doc['shareImage']),
            'noindex' => (int) ($doc['visibility'] === 'Hidden from search'),
            'sections' => json_encode($doc['sections'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'draft' => null,
            'status' => 'published', 'published_at' => $publishedAt ?: ($page['status'] === 'published' && $page['published_at'] ? $page['published_at'] : $now),
            'approved_at' => $now, 'approved_by' => $editorId, 'updated_at' => $now,
        ]);
        self::syncNavForPage($slug, (bool) $doc['inNav'], $doc['title']);
    }

    /** Keeps the header menu in step with a page's "Show in header menu" toggle. */
    public static function syncNavForPage(string $slug, bool $inNav, string $title): void
    {
        $nav = self::get('nav'); $token = 'page:' . $slug; $found = false;
        foreach ($nav['header'] as &$item) {
            if (($item['link'] ?? '') !== $token) continue;
            $found = true; $item['show'] = $inNav ? 'Visible' : 'Hidden';
        }
        unset($item);
        if (! $found && $inNav) $nav['header'][] = ['label' => $title, 'link' => $token, 'show' => 'Visible'];
        self::put('nav', $nav);
    }

    /** Rewrites page:{old} tokens in navigation and page sections after a slug change. */
    private static function renamePageLinks(string $old, string $new): void
    {
        $replace = static function (mixed $value) use (&$replace, $old, $new): mixed {
            if (is_array($value)) return array_map($replace, $value);
            return $value === 'page:' . $old ? 'page:' . $new : $value;
        };
        self::put('nav', $replace(self::get('nav')));
        $db = db_connect();
        foreach ($db->table('pages')->select('id, sections, draft')->get()->getResultArray() as $row) {
            $update = [];
            foreach (['sections', 'draft'] as $column) {
                $decoded = json_decode((string) $row[$column], true);
                if (is_array($decoded)) $update[$column] = json_encode($replace($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            if ($update) $db->table('pages')->where('id', $row['id'])->update($update);
        }
    }
}
