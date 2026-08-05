<?php

namespace App\Crm\Database\Factories;

use App\Crm\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Status>
 */
class StatusFactory extends Factory
{
    protected $model = Status::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Lead',
            'Prospect',
            'Try again later',
            'Active',
            'On Hold',
            'In Progress',
            'Closed',
            'Customer',
            'Partner',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => $this->faker->hexColor(),
            'is_default' => false,
        ];
    }
}
