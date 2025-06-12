<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Location;
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
} 