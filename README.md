# Job Scraper

Job Scraper is a Laravel application for collecting remote job listings from LinkedIn, Indeed, and ZipRecruiter. Scraped jobs are stored in a database, deduplicated, organized by the day they were first discovered, and presented in a simple Bootstrap interface for reviewing and tracking applications.

## Features

- Scrapes LinkedIn, Indeed, and ZipRecruiter from an Artisan command.
- Stores job listings instead of writing JSON or text result files.
- Deduplicates listings by source and canonical URL.
- Records scrape history, source errors, and result counts.
- Displays jobs by their local first-scraped date.
- Supports date selection through `?date=YYYY-MM-DD`.
- Marks jobs as **Applied** or **Not Interested** without reloading the page.
- Lists applied jobs on a dedicated page.
- Stores reusable keyword and source configurations.
- Runs due configurations through a token-protected HTTP endpoint.
- Prevents overlapping scheduled scrape checks.

## Technology

- PHP 8.3+
- Laravel 13
- MySQL or SQLite
- Bootstrap 5 through jsDelivr
- Blade templates for the public job workflow
- Vue, Inertia, Tailwind CSS, and Vite for the authenticated starter-kit screens
- PHPUnit, PHPStan/Larastan, Laravel Pint, ESLint, and Vue TypeScript checking

## Requirements

- PHP 8.3 or newer
- Composer
- PHP cURL and DOM extensions
- A supported database such as MySQL or SQLite
- Node.js and npm when building the authenticated frontend assets
- Laravel Valet or another local web server

## Installation

Install the PHP dependencies:

```bash
composer install
```

Create the environment file and application key:

```bash
cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`. A MySQL example:

```dotenv
APP_NAME="Job Scraper"
APP_URL=https://scrape-jobs.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scrapejobs
DB_USERNAME=root
DB_PASSWORD=
```

Run all database migrations:

```bash
php artisan migrate
```

Install and build the frontend dependencies:

```bash
npm install
npm run build
```

When using Laravel Valet, link and secure the site as needed. Otherwise, start the application's local development process:

```bash
composer dev
```

## Timezone configuration

Database timestamps remain in UTC. Public dates, selected-day boundaries, and displayed times use the configured display timezone:

```dotenv
APP_DISPLAY_TIMEZONE=America/Chicago
```

This prevents the homepage from advancing to the next date early when UTC crosses midnight before the local timezone.

## Scraping jobs manually

Run all supported sources with the defaults:

```bash
php artisan jobs:scrape
```

The default search is `wordpress remote` with a five-day listing-age window.

Specify keywords and an age window:

```bash
php artisan jobs:scrape \
    --keywords="wordpress developer remote" \
    --days=3
```

Limit the scrape to one or more sources:

```bash
php artisan jobs:scrape --source=linkedin
php artisan jobs:scrape --source=linkedin --source=indeed
php artisan jobs:scrape --source=linkedin,ziprecruiter
```

Enable HTTP diagnostics with Artisan's verbosity option:

```bash
php artisan jobs:scrape -v
```

See [the complete command documentation](documentation/jobs-scrape-command.md) for all options, statuses, exit codes, and troubleshooting notes.

## Listing deduplication

Each listing is identified by its source and a SHA-256 hash of its canonical URL. LinkedIn tracking query parameters are removed before hashing.

When the same source URL is discovered again:

- The existing row is refreshed instead of duplicated.
- `last_seen_at` is updated.
- The original `created_at` and `first_seen_at` values are preserved.
- Any Applied or Not Interested decision remains unchanged.
- The listing is associated with the additional scrape run.

The same opening can remain as separate rows when it appears on different sources.

## Reviewing jobs

The homepage displays undecided jobs first created on the selected local date:

```text
/
/?date=2026-07-13
```

Each row links to the original posting and includes two decisions:

- **Not Interested:** Hides the job from dated lists while retaining it for a future archive.
- **Applied:** Hides the job from dated lists and adds it to `/applied-to`.

Decisions are saved asynchronously with `fetch()`. The row fades away and the count updates without a browser reload. The underlying forms remain functional when JavaScript is unavailable.

## Saved scrape configurations

Visit `/cron` to store recurring scrape configurations. Each configuration contains:

- Keywords
- One or more sources
- Maximum listing age in days
- Run interval in hours
- Enabled or disabled status
- Last and next run timestamps

Saving the same normalized keyword and source combination updates its existing configuration rather than creating a duplicate.

Run all enabled configurations that are currently due:

```bash
php artisan jobs:run-schedules
```

The runner updates `last_ran_at` and `next_run_at`, links resulting scrape runs to their configuration, and uses a lock to prevent overlapping checks.

## Local cron automation

The application provides a protected endpoint for a local five-minute cron check:

```text
GET /cron/run
```

Set a long random bearer token in `.env`:

```dotenv
SCRAPE_CRON_TOKEN=replace_with_a_random_token
```

Then clear the configuration cache:

```bash
php artisan config:clear
```

The endpoint checks schedules every five minutes but runs only configurations whose `next_run_at` value is due. See [the local cron setup guide](documentation/local-cron-setup.md) for the exact crontab entry, token generation, verification, logging, removal, and troubleshooting instructions.

## Main routes

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/` | View undecided jobs for today or the selected date. |
| `POST` | `/jobs/{jobListing}/status` | Save an Applied or Not Interested decision. |
| `GET` | `/applied-to` | View jobs marked as Applied. |
| `GET` | `/cron` | List and create saved scrape configurations. |
| `POST` | `/cron` | Store or update a scrape configuration. |
| `GET` | `/cron/run` | Token-protected trigger for due configurations. |
| `GET` | `/job-listings` | Authenticated stored-listing browser. |

## Database overview

| Table | Purpose |
| --- | --- |
| `job_listings` | Canonical listing data, timestamps, and user decision status. |
| `scrape_runs` | Search parameters, source errors, counts, and execution status. |
| `scrape_schedules` | Persistent recurring scrape configurations. |
| `job_listing_scrape_run` | Links listings to every run in which they appeared. |

Laravel's standard user, session, cache, queue, passkey, and failed-job tables are also present.

## Testing and quality checks

Run the complete PHP test suite:

```bash
php artisan test
```

Run the PHP formatter check and static analysis:

```bash
vendor/bin/pint app config database/migrations routes tests --test
vendor/bin/phpstan analyse
```

Run the JavaScript and Vue checks:

```bash
npm run lint:check
npm run types:check
```

Build the frontend assets:

```bash
npm run build
```

## Scraper limitations

The job boards may change their markup or block automated requests:

- LinkedIn may reject or rate-limit guest job searches.
- Indeed may return a security or Cloudflare challenge.
- ZipRecruiter may return a Cloudflare challenge.

These conditions are stored as scrape-run errors. A partial run can still save listings from sources that succeeded.

## Security notes

This project is currently designed for local or private use. The cron trigger requires a bearer token, and state-changing browser forms use Laravel CSRF protection. Before exposing the application publicly, place configuration and job-decision screens behind authentication and use a production-grade scheduler rather than a development-domain endpoint.

Never commit `.env`, database credentials, or `SCRAPE_CRON_TOKEN`.

## Additional documentation

- [Artisan scraper command](documentation/jobs-scrape-command.md)
- [Local five-minute cron setup](documentation/local-cron-setup.md)

## License

This project is licensed under the MIT License.
