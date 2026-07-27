<?php

namespace WilliamBeeler\Crm\Database\Factories;

use WilliamBeeler\Crm\Models\Status;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\WilliamBeeler\Crm\Models\Status>
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
