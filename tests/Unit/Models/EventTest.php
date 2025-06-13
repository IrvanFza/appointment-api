<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_a_user(): void
    {
        $event = Event::factory()->create();
        $this->assertInstanceOf(User::class, $event->user);
    }

    public function test_belongs_to_a_location(): void
    {
        $event = Event::factory()->create();
        $this->assertInstanceOf(Location::class, $event->location);
    }

    public function test_has_many_schedules(): void
    {
        $event = Event::factory()->create();
        Schedule::factory()->count(3)->create(['event_id' => $event->id]);
        
        $this->assertCount(3, $event->schedules);
        $this->assertInstanceOf(Schedule::class, $event->schedules->first());
    }

    public function test_has_validation_rules(): void
    {
        $rules = Event::validationRules();
        
        $this->assertArrayHasKey('user_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('location_id', $rules);
        $this->assertArrayHasKey('location_value', $rules);
        $this->assertArrayHasKey('duration_mins', $rules);
        $this->assertArrayHasKey('max_appointment_days', $rules);
    }

    public function test_has_correct_fillable_attributes(): void
    {
        $event = new Event();
        
        $this->assertContains('user_id', $event->getFillable());
        $this->assertContains('name', $event->getFillable());
        $this->assertContains('location_id', $event->getFillable());
        $this->assertContains('location_value', $event->getFillable());
        $this->assertContains('duration_mins', $event->getFillable());
        $this->assertContains('max_appointment_days', $event->getFillable());
    }

    public function test_casts_attributes_correctly(): void
    {
        $event = new Event();
        $casts = $event->getCasts();
        
        $this->assertArrayHasKey('duration_mins', $casts);
        $this->assertArrayHasKey('max_appointment_days', $casts);
        $this->assertEquals('integer', $casts['duration_mins']);
        $this->assertEquals('integer', $casts['max_appointment_days']);
    }

    public function test_slug_is_automatically_generated(): void
    {
        $event = Event::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Test Event Name',
            'location_id' => Location::factory()->create()->id,
            'location_value' => 'Test Location',
            'duration_mins' => 60,
        ]);

        $this->assertEquals('test-event-name', $event->slug);

        // Test that slug is updated when name changes
        $event->update(['name' => 'Updated Event Name']);
        $this->assertEquals('updated-event-name', $event->slug);
    }

    public function test_slug_is_unique_within_user_scope(): void
    {
        $user = User::factory()->create();
        
        $event1 = Event::create([
            'user_id' => $user->id,
            'name' => 'Same Name',
            'location_id' => Location::factory()->create()->id,
            'location_value' => 'Test Location 1',
            'duration_mins' => 60,
        ]);

        $event2 = Event::create([
            'user_id' => $user->id,
            'name' => 'Same Name',
            'location_id' => Location::factory()->create()->id,
            'location_value' => 'Test Location 2',
            'duration_mins' => 30,
        ]);

        $this->assertEquals('same-name', $event1->slug);
        $this->assertEquals('same-name-1', $event2->slug);

        // Test that different users can have same slug
        $otherUser = User::factory()->create();
        $event3 = Event::create([
            'user_id' => $otherUser->id,
            'name' => 'Same Name',
            'location_id' => Location::factory()->create()->id,
            'location_value' => 'Test Location 3',
            'duration_mins' => 45,
        ]);

        $this->assertEquals('same-name', $event3->slug);
    }
} 