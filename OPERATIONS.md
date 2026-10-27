# Fynefield operations release gate

The CMS is ready for application deployment only after every item below has an accountable owner and a recorded result. Do not substitute local development results for these checks.

## Before production traffic

1. Set `CI_ENVIRONMENT=production`, the final HTTPS `app.baseURL`, production database connection, cookie domain, and a distinct production encryption key through the host's secret manager. CMS cookies automatically require HTTPS in production.
2. Configure authenticated SMTP through environment variables, including `email.fromEmail` for CMS password-recovery messages. In CMS **Site settings**, enter the approved inquiry sender and recipient addresses; submit a real inquiry and confirm both delivery and the inquiry audit record.
3. Change the seeded administrator password on first sign-in, create named administrator/editor accounts, and remove access for people who should not retain it.
4. Run `php spark migrate`, then `php spark cms:preflight` and `php spark cms:content-audit`. Both commands must exit `0` before release; review accessibility reminders from the content audit.
5. Configure an uptime monitor for `GET /healthz`. It returns `200` only when the application and database are reachable and contains no sensitive details.
6. Store encrypted, automated backups of both the MySQL database and `public/uploads` outside the application host. Perform and record a restore test before launch and at a regular agreed interval.
7. Put uploads on durable shared/object storage before using multiple web servers or ephemeral containers. The local filesystem is appropriate only for a single durable host.
8. Have the approved privacy policy reviewed and published; decide lead-retention and deletion periods before collecting customer inquiries.
9. Run a human mobile, keyboard-navigation, screen-reader, and form-submission check against the final production domain. Check legacy redirects and the XML sitemap after DNS/TLS are live.

## Each release

1. Deploy through a staging environment first; run PHP lint, `vendor/bin/phpunit --no-coverage`, migrations, and `php spark cms:preflight`.
2. Take a verified backup before migrations; apply migrations once, using the deployment lock appropriate to the database host.
3. Check `/healthz`, `/sitemap.xml`, contact-form delivery, and the administrator login after deployment.
4. Review application/error-mail logs and the CMS activity history for unexpected failures.

## Recurring housekeeping

Run `php spark cms:purge-expired-password-resets` daily through the host scheduler. It deletes only password-reset records whose one-hour validity has already elapsed; it does not modify accounts, content, or inquiries.

## Boundary of this repository

Hosting, TLS certificates, DNS, secret storage, SMTP provider credentials, backup destinations, uptime-monitor accounts, legal policy approval, and retention periods require organization-level choices or credentials. They cannot be safely invented or configured from this codebase.
