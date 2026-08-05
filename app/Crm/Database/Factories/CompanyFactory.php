<?php

namespace App\Crm\Database\Factories;

use App\Crm\Models\Company;
use App\Crm\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $companyName = $this->faker->unique()->company();
        $domain = Str::of($companyName)->slug('-').'.'.$this->faker->randomElement(['com', 'io', 'net', 'org']);

        // Some realistic meta extras
        $meta = [];

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $companyName,
            'alias' => $this->faker->boolean(25) ? $this->faker->companySuffix() : null,
            'website' => "https://{$domain}",
            'email' => "info@{$domain}",
            'phone' => $this->faker->e164PhoneNumber(),
            'poc_name' => $this->faker->name(),
            'poc_email' => "contact@{$domain}",
            'poc_phone' => $this->faker->e164PhoneNumber(),
            'address_line1' => $this->faker->streetAddress(),
            'address_line2' => $this->faker->boolean(30) ? 'Suite '.$this->faker->buildingNumber() : null,
            'city' => $this->faker->city(),
            'state' => $this->faker->state(), // Use abbreviations for US states
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(), // All of these will be US for now
            'source' => 'Faker',
            'status_id' => Status::inRandomOrder()->first()->id,
            'meta' => json_encode($meta),
        ];
    }
}
