<?php

namespace WilliamBeeler\Crm\Database\Seeders;

use WilliamBeeler\Crm\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        if (env('APP_ENV') == 'local' || env('APP_ENV') == 'dev') {
            $rand = rand(40, 100);
            // Base pool
            Company::factory()->count($rand)->create();
        }
    }
}
