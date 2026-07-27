<?php

namespace WilliamBeeler\Crm\Database\Seeders;

use WilliamBeeler\Crm\Models\Relationship;
use Illuminate\Database\Seeder;

class RelationshipSeeder extends Seeder
{
    public function run(): void
    {
        $relationships = [
            ['name' => 'Lead', 'slug' => 'lead', 'description' => 'Someone who has not been contacted yet or minimally qualified.'],
            ['name' => 'Prospect', 'slug' => 'prospect', 'description' => 'A potential customer that has shown some interest or been contacted.'],
            ['name' => 'Customer', 'slug' => 'customer', 'description' => 'A paying client with an active or completed project.'],
            ['name' => 'Partner', 'slug' => 'partner', 'description' => 'A collaborator, reseller, referral source, or strategic relationship.'],
            ['name' => 'Vendor', 'slug' => 'vendor', 'description' => 'Company that provides services to you.'],
        ];

        foreach ($relationships as $relationship) {
            Relationship::updateOrCreate(['slug' => $relationship['slug']], $relationship);
        }
    }
}
