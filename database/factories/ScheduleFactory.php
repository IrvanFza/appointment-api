<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('now', '+30 days');
        $endTime = clone $startTime;
        $endTime->modify('+30 minutes');

        return [
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'client_name' => $this->faker->name(),
            'client_email' => $this->faker->safeEmail(),
            'status' => $this->faker->randomElement(['confirmed', 'cancelled']),
        ];
    }

    /**
     * Indicate that the schedule is confirmed.
     *
     * @return static
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Indicate that the schedule is cancelled.
     *
     * @return static
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
