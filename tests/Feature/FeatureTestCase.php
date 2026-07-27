<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobListing;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createListing(
        string $title,
        string $url,
        CarbonInterface $lastSeenAt,
        CarbonInterface $createdAt,
    ): JobListing {
        $listing = new JobListing([
            'source' => 'LinkedIn',
            'title' => $title,
            'company_id' => Company::findOrCreateByName('Acme')?->id,
            'location' => 'Remote',
            'url' => $url,
            'url_hash' => hash('sha256', $url),
            'posted_at' => $lastSeenAt->toDateString(),
            'first_seen_at' => $lastSeenAt,
            'last_seen_at' => $lastSeenAt,
        ]);
        $listing->created_at = $createdAt;
        $listing->updated_at = $lastSeenAt;
        $listing->save();

        return $listing;
    }
}
