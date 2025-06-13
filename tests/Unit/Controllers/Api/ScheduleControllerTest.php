<?php

namespace Tests\Unit\Controllers\Api;

use App\Models\Event;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $location = Location::create([
            'id' => '00000000-0000-0000-0000-000000000001',
            'name' => 'Test Location',
        ]);
        
        $this->event = Event::create([
            'user_id' => $this->user->id,
            'name' => 'Test Event',
            'slug' => 'test-event',
            'location_id' => $location->id,
            'location_value' => 'Room 101',
            'duration_mins' => 60,
            'max_appointment_days' => 30,
        ]);
        
        // Create PostgreSQL extension for range types if not exists
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
    }

    public function test_store_creates_new_schedule(): void
    {
        $data = [
            'event_id' => $this->event->id,
            'start_time' => now()->addDays(2)->toDateTimeString(),
            'end_time' => now()->addDays(2)->addHour()->toDateTimeString(),
            'client_name' => 'New Client',
            'client_email' => 'newclient@example.com',
        ];

        $response = $this->postJson('/api/schedules', $data);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'client_name' => 'New Client',
            'client_email' => 'newclient@example.com',
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('schedules', [
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'client_name' => 'New Client',
        ]);
        
        // Verify serial is returned in response
        $this->assertArrayHasKey('serial', $response->json('data'));
        $this->assertStringStartsWith('SCH-', $response->json('data.serial'));
    }

    public function test_store_prevents_double_booking(): void
    {
        // Create an existing schedule
        Schedule::create([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'start_time' => now()->addDays(3)->setTime(10, 0),
            'end_time' => now()->addDays(3)->setTime(11, 0),
            'client_name' => 'Existing Client',
            'client_email' => 'existing@example.com',
            'status' => 'confirmed',
        ]);

        // Try to book the same time slot
        $data = [
            'event_id' => $this->event->id,
            'start_time' => now()->addDays(3)->setTime(10, 30)->toDateTimeString(),
            'end_time' => now()->addDays(3)->setTime(11, 30)->toDateTimeString(),
            'client_name' => 'Conflicting Client',
            'client_email' => 'conflict@example.com',
        ];

        $response = $this->postJson('/api/schedules', $data);

        $response->assertStatus(422);
        $response->assertJsonFragment(['time_conflict' => 'The selected time slot conflicts with an existing appointment']);
    }

    public function test_store_validates_input(): void
    {
        $data = [
            'event_id' => 'invalid-uuid',
            'start_time' => 'not-a-date',
            'end_time' => now()->toDateTimeString(), // End time before start time
            'client_name' => '',
            'client_email' => 'not-an-email',
        ];

        $response = $this->postJson('/api/schedules', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['event_id', 'start_time', 'client_name', 'client_email']);
    }

    public function test_show_returns_schedule(): void
    {
        $schedule = Schedule::create([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'start_time' => now()->addDays(4),
            'end_time' => now()->addDays(4)->addHour(),
            'client_name' => 'Test Client',
            'client_email' => 'test@client.com',
            'status' => 'confirmed',
        ]);

        $response = $this->getJson('/api/schedules/' . $schedule->serial);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $schedule->id,
            'serial' => $schedule->serial,
            'client_name' => 'Test Client',
            'client_email' => 'test@client.com',
            'status' => 'confirmed',
        ]);
    }

    public function test_show_returns_not_found_for_nonexistent_schedule(): void
    {
        $nonexistentSerial = 'SCH-NOTFOUND';
        
        $response = $this->getJson('/api/schedules/' . $nonexistentSerial);

        $response->assertStatus(404);
    }

    public function test_update_modifies_schedule(): void
    {
        $schedule = Schedule::create([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'start_time' => now()->addDays(6),
            'end_time' => now()->addDays(6)->addHour(),
            'client_name' => 'Original Client',
            'client_email' => 'original@client.com',
            'status' => 'confirmed',
        ]);

        $data = [
            'client_name' => 'Updated Client',
        ];

        $response = $this->putJson('/api/schedules/' . $schedule->serial, $data);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'client_name' => 'Updated Client',
            'client_email' => 'original@client.com',
            'serial' => $schedule->serial,
        ]);

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'client_name' => 'Updated Client',
            'client_email' => 'original@client.com',
        ]);
    }

    public function test_update_prevents_double_booking(): void
    {
        // Create two schedules
        $schedule1 = Schedule::create([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'start_time' => now()->addDays(7)->setTime(9, 0),
            'end_time' => now()->addDays(7)->setTime(10, 0),
            'client_name' => 'Client 1',
            'client_email' => 'client1@example.com',
            'status' => 'confirmed',
        ]);

        $schedule2 = Schedule::create([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'start_time' => now()->addDays(7)->setTime(11, 0),
            'end_time' => now()->addDays(7)->setTime(12, 0),
            'client_name' => 'Client 2',
            'client_email' => 'client2@example.com',
            'status' => 'confirmed',
        ]);

        // Try to update schedule2 to overlap with schedule1
        $data = [
            'start_time' => now()->addDays(7)->setTime(9, 30)->toDateTimeString(),
            'end_time' => now()->addDays(7)->setTime(10, 30)->toDateTimeString(),
        ];

        $response = $this->putJson('/api/schedules/' . $schedule2->serial, $data);

        $response->assertStatus(422);
        $response->assertJsonFragment(['time_conflict' => 'The selected time slot conflicts with an existing appointment']);
    }

    public function test_cancel_schedule(): void
    {
        $schedule = Schedule::create([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'start_time' => now()->addDays(8),
            'end_time' => now()->addDays(8)->addHour(),
            'client_name' => 'Cancel Test Client',
            'client_email' => 'cancel@test.com',
            'status' => 'confirmed',
        ]);

        $response = $this->postJson('/api/schedules/' . $schedule->serial . '/cancel');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'cancelled',
            'serial' => $schedule->serial,
        ]);

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cancel_returns_error_for_already_cancelled_schedule(): void
    {
        $schedule = Schedule::create([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'start_time' => now()->addDays(10),
            'end_time' => now()->addDays(10)->addHour(),
            'client_name' => 'Already Cancelled Client',
            'client_email' => 'already@cancelled.com',
            'status' => 'cancelled',
        ]);

        $response = $this->postJson('/api/schedules/' . $schedule->serial . '/cancel');

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Schedule is already cancelled']);
    }
} 