# Fynefield Petroleum — CodeIgniter 4 CMS

This is a clean rebuild of the Fynefield Petroleum website and CMS. WordPress code and tables are not used at runtime. Only selected public media and business content were recovered from the supplied archive.

## Run locally

1. Create the local database: `mysql -e 'CREATE DATABASE fynefield_ci4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'`
2. Run the clean schema and seed data: `php spark migrate && php spark db:seed FynefieldSeeder`
3. Start the app: `php spark serve --host localhost --port 8080`

The CMS is available at `/admin`.

Initial local administrator: `admin@fynefield.com` / `ChangeMe!2026`.

Change that password before any deployment. Configure production database credentials, `app.baseURL`, TLS, mail delivery, and a production encryption key before going live.

## CMS capabilities

- Every relevant recovered WordPress page record is recreated in the CMS (28 published pages, plus the privacy-policy and former private-system drafts); obsolete WooCommerce pages are intentionally excluded
- Authenticated admin dashboard and CRUD for pages, news, services, team, locations, and customer inquiries
- Site settings (branding, contacts, footer) and image library uploads
- Rich-text authoring with a controlled media picker, server-side HTML sanitisation, and image type/size checks
- Page builder with standard/landing templates and reusable Hero, Rich text, Image + text, CTA, Statistics, Gallery, and FAQ sections
- Editorial workflow for drafts, review, scheduled publishing, immediate publishing, seven-day private previews, revision restore, and recent-activity history
- Content search and status filters, SEO metadata and canonical controls, no-index support, navigation management, version history with safe restore, and administrator/editor account management
- CSRF protection, session renewal on login, password-hashed administrator account, explicit routes, and a fresh MySQL schema. Public-page caching is intentionally disabled until cache invalidation is introduced for CMS edits.

## Recovery boundary

The original archive passed an integrity test. It was never executed. The new `public/assets/media` folder contains only explicitly selected image assets, extracted after a path-safety check. The legacy SQL export was imported only into a temporary recovery database for read-only content inventory; it is not used by this application.
