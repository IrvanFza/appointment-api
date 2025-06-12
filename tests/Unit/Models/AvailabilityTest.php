<?php

namespace Tests\Unit\Models;

use App\Models\Availability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_can_be_created(): void
    {
        $user = User::factory()->create();

        $availability = Availability::create([
            'user_id' => $user->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $this->assertModelExists($availability);
        $this->assertEquals($user->id, $availability->user_id);
        $this->assertEquals(1, $availability->day_of_week);
        $this->assertEquals('09:00', $availability->start_time->format('H:i'));
        $this->assertEquals('17:00', $availability->end_time->format('H:i'));
    }

    public function test_availability_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $availability = Availability::create([
            'user_id' => $user->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $this->assertInstanceOf(User::class, $availability->user);
        $this->assertEquals($user->id, $availability->user->id);
    }

    public function test_user_has_many_availabilities(): void
    {
        $user = User::factory()->create();

        Availability::create([
            'user_id' => $user->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        Availability::create([
            'user_id' => $user->id,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);

        $this->assertCount(2, $user->availabilities);
    }

    public function test_day_of_week_must_be_between_0_and_6(): void
    {
        $user = User::factory()->create();

        // Should work with valid values
        $availability = Availability::create([
            'user_id' => $user->id,
            'day_of_week' => 0, // Sunday
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
        $this->assertModelExists($availability);

        $availability = Availability::create([
            'user_id' => $user->id,
            'day_of_week' => 6, // Saturday
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);
        $this->assertModelExists($availability);

        // Should fail with invalid values
        $this->expectException(\Illuminate\Database\QueryException::class);
        Availability::create([
            'user_id' => $user->id,
            'day_of_week' => 7, // Invalid
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
    }

    public function test_unique_constraint_on_user_day_start_end(): void
    {
        $user = User::factory()->create();

        Availability::create([
            'user_id' => $user->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        // Should fail with the same values
        $this->expectException(\Illuminate\Database\QueryException::class);
        Availability::create([
            'user_id' => $user->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
    }

    public function test_validation_rules(): void
    {
        $rules = Availability::validationRules();

        $this->assertArrayHasKey('user_id', $rules);
        $this->assertArrayHasKey('day_of_week', $rules);
        $this->assertArrayHasKey('start_time', $rules);
        $this->assertArrayHasKey('end_time', $rules);

        $this->assertContains('required', $rules['day_of_week']);
        $this->assertContains('integer', $rules['day_of_week']);
        $this->assertContains('min:0', $rules['day_of_week']);
        $this->assertContains('max:6', $rules['day_of_week']);

        $this->assertContains('required', $rules['start_time']);
        $this->assertContains('date_format:H:i', $rules['start_time']);

        $this->assertContains('required', $rules['end_time']);
        $this->assertContains('after:start_time', $rules['end_time']);
    }
} 