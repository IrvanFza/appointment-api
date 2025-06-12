<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'location_id' => Location::factory(),
            'location_value' => $this->faker->address(),
            'duration_mins' => $this->faker->randomElement([15, 30, 45, 60, 90, 120]),
            'max_appointment_days' => $this->faker->optional(0.7)->numberBetween(7, 90),
        ];
    }
}
