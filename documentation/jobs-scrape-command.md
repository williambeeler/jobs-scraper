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

With no options, the command searches LinkedIn, Dice, and Working Nomads for `wordpress remote` and sweeps all listings from WordPress Jobs and LaraJobs posted within the last five days.

Dice uses its server-rendered search results. The scraper accepts only cards whose displayed location contains `Remote` and whose displayed relative posting age is within the configured limit.

Working Nomads uses its public jobs API and applies keywords locally to titles, companies, categories, tags, and locations. Because all Working Nomads listings are remote, the word `remote` is treated as implicit rather than as a required match.

WordPress Jobs and LaraJobs are full-board sources. They ignore `--keywords`, collect every listing within the configured age limit, and rely on WordPress Jobs' server-rendered cards and LaraJobs' public RSS feed respectively.

## Options

| Option | Default | Description |
| --- | --- | --- |
| `--keywords=...` | `wordpress remote` | Search phrase sent to each selected source. |
| `--days=...` | `5` | Maximum listing age in days. Must be a positive integer. |
| `--source=...` | All sources | Limits the scrape to `linkedin`, `dice`, `working_nomads`, `wordpress_jobs`, `larajobs`, `usajobs`, or `christian_tech_jobs`. May be repeated or comma-separated. |
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

Sweep all recent WordPress Jobs listings:

```bash
php artisan jobs:scrape --source=wordpress_jobs
```

Search multiple sources by repeating the option:

```bash
php artisan jobs:scrape --source=linkedin --source=dice
```

Search multiple sources using a comma-separated value:

```bash
php artisan jobs:scrape --source=linkedin,dice
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

LinkedIn may block automated requests. Dice and WordPress Jobs may change their server-rendered markup, Working Nomads may change its public API, and LaraJobs may change its RSS feed. These failures are recorded on the scrape run.

Use verbose mode to see the requested URLs and HTTP status codes:

```bash
php artisan jobs:scrape -v
```

If the command reports a database-table error, ensure `php artisan migrate` completed against the same database configured for the command's environment.
