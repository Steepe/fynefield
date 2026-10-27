# Fynefield Petroleum — CodeIgniter 4 CMS

This is a clean rebuild of the Fynefield Petroleum website and CMS. WordPress code and tables are not used at runtime. Only selected public media and business content were recovered from the supplied archive.

## Run locally

1. Create the local database: `mysql -e 'CREATE DATABASE fynefield_ci4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'`
2. Run the clean schema and seed data: `php spark migrate && php spark db:seed FynefieldSeeder`
3. Start the app: `php spark serve --host localhost --port 8080`

The CMS is available at `/admin`.

Initial local administrator: `admin@fynefield.com` / `ChangeMe!2026`. The CMS forces this one-time password to be changed before any other administration screen can be used.

Change that password before any deployment. Configure production database credentials, `app.baseURL`, TLS, mail delivery, and a production encryption key before going live.

## Production email and inquiry delivery

SMTP settings are environment-only configuration. Copy the commented `email.*` entries from `env` into the production environment; do not put SMTP credentials in the CMS or source control. Then, in **CMS → Site settings**, set both **Inquiry notification recipient** and **Inquiry notification sender**. Every inquiry records whether delivery was sent, failed, or not configured, and administrators can export the audited lead register as CSV.

Before release, run `php spark cms:preflight` and `php spark cms:content-audit`. Preflight checks the deployment mode, public HTTPS URL, encryption key, SMTP configuration, CMS inquiry addresses, migrations, and whether the initial administrator password is still in use—without printing secrets. The content audit checks published records, active navigation, and local media references; it reports missing alternative text as a reminder rather than treating decorative images as errors.

The unauthenticated `GET /healthz` endpoint returns only `{"status":"ok"}` and a UTC timestamp after confirming the application can reach its database. Configure an external uptime monitor to alert on a non-200 response; it deliberately exposes no configuration, credentials, or content data.

## CMS capabilities

- Every relevant recovered WordPress page record is recreated in the CMS (28 published pages, plus the privacy-policy and former private-system drafts); obsolete WooCommerce pages are intentionally excluded
- Authenticated admin dashboard and CRUD for pages, news, services, team, locations, and customer inquiries
- Site settings (branding, contacts, footer) and image library uploads with managed alternative text
- Rich-text authoring with a controlled media picker, server-side HTML sanitisation, and image type/size checks
- Page builder with standard/landing templates and reusable Hero, Rich text, Image + text, CTA, Statistics, Gallery, and FAQ sections
- Editorial workflow for drafts, review, scheduled publishing, immediate publishing, seven-day private previews, revision restore, and recent-activity history
- Content search and status filters, SEO metadata and canonical controls, no-index support, navigation management, version history with safe restore, and administrator/editor account management with immediate account deactivation
- CSRF protection, login/reset throttling, one-hour single-use password recovery links, session renewal, forced first-login password replacement, password-hashed administrator accounts, security headers, explicit routes, and a fresh MySQL schema. Public-page caching is intentionally disabled until cache invalidation is introduced for CMS edits.

## Recovery boundary

The original archive passed an integrity test. It was never executed. The new `public/assets/media` folder contains only explicitly selected image assets, extracted after a path-safety check. The legacy SQL export was imported only into a temporary recovery database for read-only content inventory; it is not used by this application.
