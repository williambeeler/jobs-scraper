<?php

namespace WilliamBeeler\Crm\Tests\Unit;

use Tests\TestCase;
use WilliamBeeler\Crm\Models\BusinessProfile;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\GooglePlacesUsageLog;
use WilliamBeeler\Crm\Models\Note;
use WilliamBeeler\Crm\Models\PhoneCall;
use WilliamBeeler\Crm\Models\Relationship;
use WilliamBeeler\Crm\Models\SavedSearch;
use WilliamBeeler\Crm\Models\Status;
use WilliamBeeler\Crm\Models\Tag;

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
