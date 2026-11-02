# cPanel deployment setup

This project deploys the tested application release from GitHub Actions to cPanel
using explicit FTPS. It preserves server-only files and checks `/healthz` over
HTTPS after the upload.

## One-time cPanel configuration

1. Choose an application directory outside `public_html`, for example
   `/home/CPANEL_USER/fynefield`. Point the domain or subdomain document root at
   `/home/CPANEL_USER/fynefield/public`.
2. In that application directory, create the production `.env` file directly on
   the server. It must contain the production database, mail, encryption key, and
   `app.baseURL` configuration. The deployment deliberately never uploads or
   overwrites `.env`.
3. Ensure `writable/` and any uploaded files have been created on the server and
   are writable by the cPanel account. The deployment deliberately preserves the
   entire `writable/` directory.

Create these folders with cPanel File Manager before the first deployment:

```text
fynefield/public
fynefield/writable/cache
fynefield/writable/logs
fynefield/writable/session
fynefield/writable/uploads
fynefield/writable/backups
```

## First production database

The application already has a populated local CI4 database containing the CMS
documents, pages, navigation, and recovered content. For the first production
release, import that known-good database into an **empty** cPanel MySQL database.
Do not rerun the legacy `FynefieldSeeder` against the current Studio schema.

From the development machine, export the local CI4 database (replace the example
name if yours differs):

```bash
mysqldump --single-transaction --routines --triggers --default-character-set=utf8mb4 \
  -u root -p fynefield_ci4 > fynefield-production.sql
```

Create the production database and database user in **cPanel → MySQL Database
Wizard**, grant that user **ALL PRIVILEGES** on the new database, then import
`fynefield-production.sql` using **cPanel → phpMyAdmin → Import**. Confirm the
target database is empty before importing; importing into a database with live
content can overwrite or duplicate records.

Use those production credentials in the server-only `.env` before the first
deployment. Future deployments run migrations only, so the production content is
never reseeded or replaced.

## GitHub configuration

In **GitHub → Settings → Secrets and variables → Actions**, add these repository
secrets:

| Secret | Value |
| --- | --- |
| `FTPS_SERVER` | FTP host, such as `162.0.229.41` |
| `FTPS_PORT` | `21` |
| `FTPS_USERNAME` | cPanel FTP username, such as `rainwqbl` |
| `FTPS_PASSWORD` | FTP password; do not commit or share it |
| `FTPS_SERVER_DIR` | FTP-relative app directory, normally `fynefield/` |
| `PRODUCTION_URL` | Canonical HTTPS URL with no trailing slash, such as `https://fynefield.com` |

Create a GitHub Environment named `production` and add a required reviewer if a
manual approval is wanted before each release.

Finally, add the repository variable `DEPLOY_ENABLED` with value `true`. Until that
variable is set, the deployment workflow cannot run automatically.

## Release behaviour

`Continuous Integration` runs on every push and pull request. A successful CI run
on `main` triggers `Deploy to cPanel` only after `DEPLOY_ENABLED=true` is set.

The FTPS deployment excludes `.env`, `writable/`, tests, Git history, and build
output. It does not delete unrelated server files.

FTP cannot execute `php spark` commands. The first production database import
already contains all migrations. For future releases that add a migration, run the
migration through cPanel Terminal when available, or ask the host to run:

```bash
php /home/CPANEL_USER/fynefield/spark migrate --all --no-interaction
php /home/CPANEL_USER/fynefield/spark cache:clear
```

For the first release, manually run **Actions → Deploy to cPanel via FTPS → Run workflow**
after adding all secrets. GitHub will retain the deployment logs and the health
check result for review.
