<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserPreference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'is_available' => fake()->boolean(80), // 80% chance of being available
            'block_lunch_break' => fake()->boolean(70), // 70% chance of blocking lunch
            'lunch_break_start_time' => fake()->time('H:i:s', '13:00:00'),
            'lunch_break_end_time' => fake()->time('H:i:s', '14:00:00'),
            'block_public_holiday' => fake()->boolean(30), // 30% chance of blocking holidays
            'timezone' => fake()->randomElement([
                'Asia/Jakarta',
                'Asia/Singapore',
                'Asia/Kuala_Lumpur',
                'UTC',
                'America/New_York',
                'Europe/London'
            ]),
        ];
    }

    /**
     * Indicate that the user is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => true,
        ]);
    }

    /**
     * Indicate that the user is not available.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    /**
     * Indicate that lunch break is blocked.
     */
    public function withLunchBreak(): static
    {
        return $this->state(fn (array $attributes) => [
            'block_lunch_break' => true,
            'lunch_break_start_time' => '12:00:00',
            'lunch_break_end_time' => '13:00:00',
        ]);
    }

    /**
     * Indicate that lunch break is not blocked.
     */
    public function withoutLunchBreak(): static
    {
        return $this->state(fn (array $attributes) => [
            'block_lunch_break' => false,
        ]);
    }

    /**
     * Indicate that public holidays are blocked.
     */
    public function blockingHolidays(): static
    {
        return $this->state(fn (array $attributes) => [
            'block_public_holiday' => true,
        ]);
    }
} 