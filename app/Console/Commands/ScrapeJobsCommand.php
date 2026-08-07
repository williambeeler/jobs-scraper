<?php

namespace App\Console\Commands;

use App\Data\JobListingData;
use App\Enums\JobListingStatus;
use App\Models\Company;
use App\Models\ExcludedTerm;
use App\Models\JobListing;
use App\Models\ScrapeRun;
use App\Models\ScrapeSchedule;
use App\Services\JobScraping\CaciCareersScraper;
use App\Services\JobScraping\ChristianTechJobsScraper;
use App\Services\JobScraping\DiceScraper;
use App\Services\JobScraping\HttpClient;
use App\Services\JobScraping\JobSourceScraper;
use App\Services\JobScraping\LaraJobsScraper;
use App\Services\JobScraping\LinkedInScraper;
use App\Services\JobScraping\UsaJobsScraper;
use App\Services\JobScraping\WordPressJobsScraper;
use App\Services\JobScraping\WorkingNomadsScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class ScrapeJobsCommand extends Command
{
    protected $signature = 'jobs:scrape
        {--keywords=wordpress remote : Search keywords}
        {--days=5 : Maximum listing age in days}
        {--source=* : Sources to scrape (repeat or use comma-separated values)}
        {--schedule= : Associate this run with a stored scrape schedule}';

    protected $description = 'Scrape remote job listings and store them in the database';

    public function handle(): int
    {
        $keywords = trim((string) $this->option('keywords'));
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $sources = $this->selectedSources();
        $allowedSources = ['linkedin', 'dice', 'working_nomads', 'wordpress_jobs', 'larajobs', 'usajobs', 'christian_tech_jobs', 'caci_careers'];
        $unknownSources = array_values(array_diff($sources, $allowedSources));

        if ($unknownSources !== []) {
            $this->error('Unknown source(s): '.implode(', ', $unknownSources).'.');

            return self::INVALID;
        }

        $keywordSources = array_intersect($sources, ['linkedin', 'dice', 'working_nomads', 'usajobs', 'christian_tech_jobs', 'caci_careers']);
        if ($keywords === '' && $keywordSources !== []) {
            $this->error('The --keywords option cannot be empty for keyword-based sources.');

            return self::INVALID;
        }

        if ($days === false) {
            $this->error('The --days option must be a positive integer.');

            return self::INVALID;
        }

        $schedule = $this->selectedSchedule();
        if ($this->option('schedule') !== null && $schedule === null) {
            $this->error('The --schedule option must reference an existing scrape schedule.');

            return self::INVALID;
        }

        $run = ScrapeRun::query()->create([
            'scrape_schedule_id' => $schedule?->id,
            'keywords' => $keywords,
            'max_days' => $days,
            'sources' => $sources,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $http = new HttpClient(
            logger: $this->output->isVerbose() ? fn (string $message) => $this->line($message) : null,
        );
        $scrapers = $this->scrapers($http, $days);
        $errors = [];
        $found = 0;
        $created = 0;
        $updated = 0;

        foreach ($sources as $source) {
            $this->components->task("Scraping {$source}", function () use ($scrapers, $source, $keywords, $run, &$errors, &$found, &$created, &$updated): bool {
                try {
                    $result = $scrapers[$source]->scrape($keywords);
                } catch (Throwable $exception) {
                    report($exception);
                    $errors[$source] = $exception->getMessage();

                    return false;
                }

                foreach ($result['jobs'] as $data) {
                    [$listing, $wasCreated] = $this->persist($data);
                    $run->jobListings()->syncWithoutDetaching([$listing->id]);
                    $found++;
                    $wasCreated ? $created++ : $updated++;
                }

                if ($result['error'] !== null) {
                    $errors[$source] = $result['error'];
                }

                return $result['error'] === null;
            });
        }

        $status = $errors === [] ? 'completed' : ($found === 0 ? 'failed' : 'completed_with_warnings');
        $run->update([
            'status' => $status,
            'jobs_found' => $found,
            'jobs_created' => $created,
            'jobs_updated' => $updated,
            'errors' => $errors === [] ? null : $errors,
            'completed_at' => now(),
        ]);

        $this->newLine();
        $this->table(['Run', 'Found', 'Created', 'Refreshed', 'Status'], [
            [$run->id, $found, $created, $updated, $status],
        ]);

        foreach ($errors as $source => $message) {
            $this->warn("[{$source}] {$message}");
        }

        return $status === 'failed' ? self::FAILURE : self::SUCCESS;
    }

    private function selectedSchedule(): ?ScrapeSchedule
    {
        $scheduleId = $this->option('schedule');
        if ($scheduleId === null) {
            return null;
        }

        $validatedId = filter_var($scheduleId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $validatedId === false ? null : ScrapeSchedule::query()->find($validatedId);
    }

    /**
     * @return string[]
     */
    private function selectedSources(): array
    {
        $options = $this->option('source');
        if ($options === []) {
            return ['linkedin', 'dice', 'working_nomads', 'wordpress_jobs', 'larajobs', 'usajobs', 'christian_tech_jobs', 'caci_careers'];
        }

        $sources = [];
        foreach ($options as $option) {
            foreach (explode(',', strtolower((string) $option)) as $source) {
                if (trim($source) !== '') {
                    $sources[] = trim($source);
                }
            }
        }

        return array_values(array_unique($sources));
    }

    /**
     * @return array<string, JobSourceScraper>
     */
    private function scrapers(HttpClient $http, int $days): array
    {
        return [
            'linkedin' => new LinkedInScraper($http, $days),
            'dice' => new DiceScraper($http, $days),
            'working_nomads' => new WorkingNomadsScraper($http, $days),
            'wordpress_jobs' => new WordPressJobsScraper($http, $days),
            'larajobs' => new LaraJobsScraper($http, $days),
            'usajobs' => new UsaJobsScraper($http, $days),
            'christian_tech_jobs' => new ChristianTechJobsScraper($http, $days),
            'caci_careers' => new CaciCareersScraper($http, $days),
        ];
    }

    /**
     * @return array{JobListing, bool}
     */
    private function persist(JobListingData $data): array
    {
        $listing = JobListing::query()->firstOrNew([
            'source' => $data->source,
            'url_hash' => hash('sha256', $data->url),
        ]);
        $wasCreated = ! $listing->exists;
        $now = now();

        $company = Company::findOrCreateByName($data->company);
        $isBlacklisted = $company?->isBlacklisted() === true || ExcludedTerm::matches($data->title);

        $listing->fill([
            'title' => $data->title,
            'company_id' => $company?->id,
            'location' => $data->location ?: null,
            'url' => $data->url,
            'posted_at' => $this->parseDate($data->postedDate),
            'salary' => $data->salary,
            'first_seen_at' => $listing->first_seen_at ?? $now,
            'last_seen_at' => $now,
            ...($isBlacklisted ? [
                'status' => JobListingStatus::NotInterested,
                'status_changed_at' => $now,
            ] : []),
        ])->save();

        return [$listing, $wasCreated];
    }

    private function parseDate(?string $date): ?Carbon
    {
        if ($date === null || $date === '') {
            return null;
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
