<?php

namespace Tests\Unit\Crm;

use App\Crm\Models\BusinessProfile;
use App\Crm\Models\Company;
use App\Crm\Models\GooglePlacesUsageLog;
use App\Crm\Models\Note;
use App\Crm\Models\PhoneCall;
use App\Crm\Models\Relationship;
use App\Crm\Models\SavedSearch;
use App\Crm\Models\Status;
use App\Crm\Models\Tag;
use Tests\TestCase;

class TableNamesTest extends TestCase
{
    public function test_crm_models_use_configured_table_names(): void
    {
        config()->set('crm.tables', [
            'companies' => 'crm_companies',
            'statuses' => 'crm_statuses',
            'relationships' => 'crm_relationships',
            'notes' => 'crm_notes',
            'phone_calls' => 'crm_phone_calls',
            'saved_searches' => 'crm_saved_searches',
            'google_places_usage_logs' => 'crm_google_places_usage_logs',
            'business_profiles' => 'crm_business_profiles',
            'tags' => 'crm_tags',
            'taggables' => 'crm_taggables',
        ]);
        config()->set('tags.taggable.table_name', 'crm_taggables');

        $this->assertSame('crm_companies', (new Company)->getTable());
        $this->assertSame('crm_statuses', (new Status)->getTable());
        $this->assertSame('crm_relationships', (new Relationship)->getTable());
        $this->assertSame('crm_notes', (new Note)->getTable());
        $this->assertSame('crm_phone_calls', (new PhoneCall)->getTable());
        $this->assertSame('crm_saved_searches', (new SavedSearch)->getTable());
        $this->assertSame('crm_google_places_usage_logs', (new GooglePlacesUsageLog)->getTable());
        $this->assertSame('crm_business_profiles', (new BusinessProfile)->getTable());
        $this->assertSame('crm_tags', (new Tag)->getTable());
        $this->assertSame('crm_taggables', config('tags.taggable.table_name'));
    }
}
