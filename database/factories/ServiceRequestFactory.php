<?php

namespace Database\Factories;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceRequest> */
class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4), 'description' => fake()->paragraph(),
            'assignee_id' => User::factory(), 'status' => 'new', 'version' => 1,
        ];
    }
}
