# `jobs:scrape` Artisan command

The `jobs:scrape` command searches supported job boards for recent remote job listings and stores the results in the application database. It does not write JSON or other result files.

## Setup

Run the database migrations before using the command:

```bash
php artisan migrate
```

The migration creates:

- `job_listings` for the current listing data.
- `scrape_runs` for each command execution, including its search parameters, result counts, status, and source errors.
- `job_listing_scrape_run` to associate listings with the runs in which they appeared.

## Basic usage

```bash
php artisan jobs:scrape
```

With no options, the command searches LinkedIn, Indeed, and ZipRecruiter for `wordpress remote` listings posted within the last five days.

Indeed and ZipRecruiter first use their native search pages. If a native request is blocked or returns no usable results, both sources fall back to a shared Google site-restricted search. The fallback searches up to two Google result pages, validates every result against the expected job-board domain, removes duplicate and tracking URLs, and stores the remaining links under their original source.

Google fallback results contain only the information exposed in the search result. Their company is therefore unknown, their location is recorded as Remote, and the configured age limit is passed to Google as an `after:` search operator rather than verified against structured posting data.

The fallback uses Google's server-rendered search HTML and does not attempt to bypass a security or JavaScript interstitial. Google can return such an interstitial instead of results; that condition is recorded as a source error so this interim provider can be replaced by a documented search API later.

## Options

| Option | Default | Description |
| --- | --- | --- |
| `--keywords=...` | `wordpress remote` | Search phrase sent to each selected source. |
| `--days=...` | `5` | Maximum listing age in days. Must be a positive integer. |
| `--source=...` | All sources | Limits the scrape to `linkedin`, `indeed`, or `ziprecruiter`. May be repeated or comma-separated. |
| `-v`, `--verbose` | Off | Shows each HTTP request and response status. This is Artisan's standard verbosity option. |

Use `php artisan jobs:scrape --help` to display the command's built-in help.

## Examples

Search all sources using the defaults:

```bash
php artisan jobs:scrape
```

Search for a different phrase and age range:

```bash
php artisan jobs:scrape --keywords="wordpress developer remote" --days=3
```

Search only LinkedIn:

```bash
php artisan jobs:scrape --source=linkedin
```

Search multiple sources by repeating the option:

```bash
php artisan jobs:scrape --source=linkedin --source=indeed
```

Search multiple sources using a comma-separated value:

```bash
php artisan jobs:scrape --source=linkedin,ziprecruiter
```

Show HTTP diagnostic information:

```bash
php artisan jobs:scrape --source=linkedin -v
```

## Persistence and deduplication

A listing is uniquely identified by its source and a SHA-256 hash of its URL. When the scraper encounters the same listing again, it refreshes the stored fields and `last_seen_at` timestamp instead of inserting a duplicate. The original `first_seen_at` timestamp is preserved.

Each run records the following counts:

- **Found:** Total listings returned during that execution.
- **Created:** Listings that did not already exist.
- **Refreshed:** Existing listings that were seen again and updated.

Listings are connected to every scrape run in which they appeared.

## Run statuses and exit codes

| Status | Meaning | Exit code |
| --- | --- | --- |
| `completed` | Every selected source completed without an error. | `0` |
| `completed_with_warnings` | At least one listing was stored, but one or more sources reported an error. | `0` |
| `failed` | Source errors occurred and no listings were found. | `1` |
| No run created | The keywords, days, or source options were invalid. | `2` |

Source warnings and failures are stored in the `errors` field on the associated `scrape_runs` record.

## Viewing stored listings

Authenticated and verified users can browse the stored results at:

```text
/job-listings
```

The page supports title, company, and location searches as well as source filtering. Selecting a listing opens its stored details and a link to the original posting.

## Automated scheduling

The command can be scheduled in `routes/console.php`. For example, to run every morning:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('jobs:scrape --days=5')
    ->dailyAt('07:00')
    ->withoutOverlapping();
```

Run Laravel's scheduler in the normal way for the environment, such as through the local scheduler worker or a production cron entry.

## Troubleshooting

Indeed and ZipRecruiter may return Cloudflare or security challenge pages, and LinkedIn may block automated requests. Indeed and ZipRecruiter automatically try Google site search after a native-source failure. If both the native request and Google fallback fail, the combined failure is recorded on the scrape run.

Use verbose mode to see the requested URLs and HTTP status codes:

```bash
php artisan jobs:scrape -v
```

If the command reports a database-table error, ensure `php artisan migrate` completed against the same database configured for the command's environment.
