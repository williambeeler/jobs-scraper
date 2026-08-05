<?php

namespace App\Console\Commands\Crm;

use App\Crm\Models\GooglePlacesUsageLog;
use App\Crm\Services\LeadScraperService;
use Illuminate\Console\Command;
use RuntimeException;

class LeadsGoogleCommand extends Command
{
    protected $signature = 'leads:google 
        {--search= : Comma-separated search terms (e.g. restaurant,auto repair)} 
        {--location= : City and state (e.g. "Midwest City, OK")} 
        {--geolocation= : Latitude and longitude (e.g. 35.6528,-97.4781)}
        {--tier=essentials : Usage tier (essentials, pro, enterprise)}
        {--radius=10 : Search radius in miles}
        {--max-companies=20 : Maximum companies to import}';

    protected $description = 'Collect leads from Google Places API and import them into the database';

    public function handle(LeadScraperService $scraper)
    {
        $searchTerms = collect(explode(',', $this->option('search') ?? ''))
            ->map(fn ($s) => trim($s))
            ->filter();

        if ($searchTerms->isEmpty()) {
            $this->error('Please provide at least one search term via --search');

            return Command::FAILURE;
        }

        $location = $this->option('location');
        $geo = $this->option('geolocation');
        $tier = GooglePlacesUsageLog::normalizeTier((string) $this->option('tier'));
        $radius = (int) $this->option('radius');
        $maxCompanies = (int) $this->option('max-companies');

        if (! $location && ! $geo) {
            $this->error('Please specify either --location or --geolocation');

            return Command::FAILURE;
        }

        if (! in_array((string) $this->option('tier'), GooglePlacesUsageLog::supportedTiers(), true)) {
            $this->error('Please provide a valid tier via --tier. Supported values are essentials, pro, enterprise.');

            return Command::FAILURE;
        }

        if ($radius < 1) {
            $this->error('Please provide a radius of at least 1 mile via --radius');

            return Command::FAILURE;
        }

        if ($maxCompanies < 1) {
            $this->error('Please provide a max company count of at least 1 via --max-companies');

            return Command::FAILURE;
        }

        $this->info('🔍 Starting lead collection...');

        try {
            $count = $scraper->scrapeAndSave($searchTerms, $location, $geo, $tier, $radius, $maxCompanies);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return Command::FAILURE;
        }

        $this->info("✅ Completed. {$count} leads processed.");

        return Command::SUCCESS;
    }
}
