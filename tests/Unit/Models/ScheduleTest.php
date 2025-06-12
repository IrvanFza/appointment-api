<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_a_user(): void
    {
        $schedule = Schedule::factory()->create();
        $this->assertInstanceOf(User::class, $schedule->user);
    }

    public function test_belongs_to_an_event(): void
    {
        $schedule = Schedule::factory()->create();
        $this->assertInstanceOf(Event::class, $schedule->event);
    }

    public function test_has_validation_rules(): void
    {
        $rules = Schedule::validationRules();
        
        $this->assertArrayHasKey('user_id', $rules);
        $this->assertArrayHasKey('event_id', $rules);
        $this->assertArrayHasKey('start_time', $rules);
        $this->assertArrayHasKey('end_time', $rules);
        $this->assertArrayHasKey('client_name', $rules);
        $this->assertArrayHasKey('client_email', $rules);
        $this->assertArrayHasKey('status', $rules);
    }

    public function test_has_correct_fillable_attributes(): void
    {
        $schedule = new Schedule();
        
        $this->assertContains('user_id', $schedule->getFillable());
        $this->assertContains('event_id', $schedule->getFillable());
        $this->assertContains('start_time', $schedule->getFillable());
        $this->assertContains('end_time', $schedule->getFillable());
        $this->assertContains('client_name', $schedule->getFillable());
        $this->assertContains('client_email', $schedule->getFillable());
        $this->assertContains('status', $schedule->getFillable());
    }

    public function test_casts_attributes_correctly(): void
    {
        $schedule = new Schedule();
        $casts = $schedule->getCasts();
        
        $this->assertArrayHasKey('start_time', $casts);
        $this->assertArrayHasKey('end_time', $casts);
        $this->assertEquals('datetime', $casts['start_time']);
        $this->assertEquals('datetime', $casts['end_time']);
    }

    public function test_can_be_confirmed(): void
    {
        $schedule = Schedule::factory()->confirmed()->create();
        $this->assertEquals('confirmed', $schedule->status);
    }

    public function test_can_be_cancelled(): void
    {
        $schedule = Schedule::factory()->cancelled()->create();
        $this->assertEquals('cancelled', $schedule->status);
    }

    public function test_unique_constraint_on_user_id_and_start_time(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $startTime = now();
        
        // Create first schedule
        Schedule::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'start_time' => $startTime,
        ]);
        
        // Expect exception when creating a second schedule with same user_id and start_time
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Schedule::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'start_time' => $startTime,
        ]);
    }
} 