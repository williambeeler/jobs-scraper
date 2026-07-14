# Local five-minute scrape cron

This guide configures macOS cron to call the application's protected scrape endpoint every five minutes:

```text
https://scrape-jobs.test/cron/run
```

The endpoint does not scrape every saved configuration every five minutes. It checks the enabled configurations on the Cron page and runs only those whose `next_run_at` value is due. A global lock prevents overlapping checks.

## Prerequisites

Before installing the crontab entry:

1. Make sure Laravel Valet is running and `https://scrape-jobs.test` loads locally.
2. Run `php artisan migrate`.
3. Add at least one enabled configuration on the `/cron` page.
4. Keep the Mac awake when the job needs to run. Standard cron does not wake a sleeping Mac.

## 1. Generate an endpoint token

From the project directory, generate a random token:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Copy the resulting value into `.env`:

```dotenv
SCRAPE_CRON_TOKEN=replace_with_the_generated_token
```

Clear Laravel's cached configuration:

```bash
php artisan config:clear
```

Do not commit the real token to source control.

## 2. Test the endpoint

Replace `YOUR_GENERATED_TOKEN` with the same value stored in `.env`:

```bash
/usr/bin/curl --fail --silent --show-error \
    --header 'Authorization: Bearer YOUR_GENERATED_TOKEN' \
    'https://scrape-jobs.test/cron/run'
```

A successful request returns HTTP `204 No Content`, so the command prints nothing. To see the response headers during testing, add `--include`:

```bash
/usr/bin/curl --include --fail --silent --show-error \
    --header 'Authorization: Bearer YOUR_GENERATED_TOKEN' \
    'https://scrape-jobs.test/cron/run'
```

An absent or incorrect token returns HTTP `403`.

## 3. Install the crontab entry

Open the current user's crontab:

```bash
crontab -e
```

Add this as one line, replacing `YOUR_GENERATED_TOKEN` with the real token:

```cron
*/5 * * * * /usr/bin/curl --fail --silent --show-error --connect-timeout 10 --header 'Authorization: Bearer YOUR_GENERATED_TOKEN' 'https://scrape-jobs.test/cron/run' >> "$HOME/Library/Logs/scrape-jobs-cron.log" 2>&1
```

Save and exit the editor. Cron will call the endpoint at minute `0`, `5`, `10`, and so on.

## 4. Verify the installation

Display the installed crontab:

```bash
crontab -l
```

Watch the cron log:

```bash
tail -f "$HOME/Library/Logs/scrape-jobs-cron.log"
```

Successful `204` responses do not add output to the log. Authentication, connection, certificate, or server errors will appear there because curl uses `--show-error` and `--fail`.

The Cron page will show `last_ran_at` after a configuration executes. Its `next_run_at` value is advanced by the configured interval in hours.

## How due configurations are selected

Every endpoint request runs the equivalent of:

```bash
php artisan jobs:run-schedules
```

A configuration is due when all of the following are true:

- It is enabled.
- `next_run_at` is empty, meaning it has never run, or is at or before the current time.
- Another scheduled scrape check is not already running.

After an attempted run, Laravel records `last_ran_at` and sets `next_run_at` using the configuration's interval. Each resulting scrape run is linked back to its configuration.

## Run the schedule check manually

The HTTP endpoint is useful for cron, but the same due-check can be run directly from the project directory:

```bash
php artisan jobs:run-schedules
```

## Remove the cron job

Run `crontab -e`, delete the `scrape-jobs.test/cron/run` line, then save and exit. Confirm its removal with:

```bash
crontab -l
```

## Troubleshooting

### `403 Forbidden`

The bearer token in the crontab does not match `SCRAPE_CRON_TOKEN` in `.env`. Update the value and run `php artisan config:clear`.

### Could not resolve host or connection failed

Confirm that Valet is running and that `https://scrape-jobs.test` opens from the same macOS user account that owns the crontab.

### Certificate error

Trust Valet's local certificate and test the curl command again. Avoid adding `--insecure` to the permanent cron command unless the local development environment cannot be configured to trust the certificate.

### No configuration ran

Visit `/cron` and confirm that the configuration is enabled. If it has run previously, it will not run again until `next_run_at` is due.
