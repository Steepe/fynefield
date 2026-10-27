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

- Public pages, service detail pages, news, leadership, locations and contact inquiry form
- Authenticated admin dashboard and CRUD for pages, news, services, team, locations, and customer inquiries
- Site settings (branding, contacts, footer) and image library uploads
- CSRF protection, session renewal on login, password-hashed administrator account, explicit routes, and a fresh MySQL schema

## Recovery boundary

The original archive passed an integrity test. It was never executed. The new `public/assets/media` folder contains only explicitly selected image assets, extracted after a path-safety check. The legacy SQL export was imported only into a temporary recovery database for read-only content inventory; it is not used by this application.
