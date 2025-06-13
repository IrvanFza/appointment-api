<?php

namespace Tests\Unit\Controllers\Api;

use App\Models\Event;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->token = JWTAuth::fromUser($this->user);
        
        $this->location = Location::create([
            'id' => '00000000-0000-0000-0000-000000000001',
            'name' => 'Test Location',
        ]);
    }

    public function test_index_returns_user_events_with_pagination(): void
    {
        // Create events for the authenticated user
        $event1 = Event::create([
            'user_id' => $this->user->id,
            'name' => 'Event 1',
            'slug' => 'event-1',
            'location_id' => $this->location->id,
            'location_value' => 'Room 1',
            'duration_mins' => 60,
            'max_appointment_days' => 30,
        ]);

        $event2 = Event::create([
            'user_id' => $this->user->id,
            'name' => 'Event 2',
            'slug' => 'event-2',
            'location_id' => $this->location->id,
            'location_value' => 'Room 2',
            'duration_mins' => 30,
            'max_appointment_days' => 14,
        ]);

        // Create an event for another user
        $otherUser = User::factory()->create();
        Event::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Event',
            'slug' => 'other-event',
            'location_id' => $this->location->id,
            'location_value' => 'Room 3',
            'duration_mins' => 45,
            'max_appointment_days' => 7,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/events');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.items');
        $response->assertJsonFragment(['id' => $event1->id]);
        $response->assertJsonFragment(['id' => $event2->id]);
        $response->assertJsonFragment(['name' => 'Event 1']);
        $response->assertJsonFragment(['name' => 'Event 2']);
        
        // Assert pagination structure
        $response->assertJsonStructure([
            'data' => [
                'items',
                'pagination' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page'
                ]
            ]
        ]);
        
        // Assert pagination values
        $response->assertJson([
            'data' => [
                'pagination' => [
                    'total' => 2,
                    'per_page' => 10,
                    'current_page' => 1,
                ]
            ]
        ]);
    }
    
    public function test_index_filters_by_name(): void
    {
        // Create events with different names
        $event1 = Event::create([
            'user_id' => $this->user->id,
            'name' => 'Meeting with Client',
            'slug' => 'meeting-with-client',
            'location_id' => $this->location->id,
            'location_value' => 'Room 1',
            'duration_mins' => 60,
            'max_appointment_days' => 30,
        ]);

        $event2 = Event::create([
            'user_id' => $this->user->id,
            'name' => 'Team Workshop',
            'slug' => 'team-workshop',
            'location_id' => $this->location->id,
            'location_value' => 'Room 2',
            'duration_mins' => 120,
            'max_appointment_days' => 14,
        ]);

        // Filter by name "Meeting"
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/events?name=Meeting');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.items');
        $response->assertJsonFragment(['id' => $event1->id]);
        $response->assertJsonMissing(['name' => 'Team Workshop']);
    }
    
    public function test_index_paginates_results(): void
    {
        // Create 20 events
        for ($i = 1; $i <= 20; $i++) {
            Event::create([
                'user_id' => $this->user->id,
                'name' => "Event $i",
                'slug' => "event-$i",
                'location_id' => $this->location->id,
                'location_value' => "Room $i",
                'duration_mins' => 30,
                'max_appointment_days' => 14,
            ]);
        }

        // Test with default pagination (10 per page)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/events');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data.items');
        $response->assertJson([
            'data' => [
                'pagination' => [
                    'total' => 20,
                    'per_page' => 10,
                    'current_page' => 1,
                    'last_page' => 2,
                ]
            ]
        ]);
        
        // Test with custom pagination (5 per page)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/events?per_page=5');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data.items');
        $response->assertJson([
            'data' => [
                'pagination' => [
                    'total' => 20,
                    'per_page' => 5,
                    'current_page' => 1,
                    'last_page' => 4,
                ]
            ]
        ]);
        
        // Test second page
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/events?per_page=5&page=2');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data.items');
        $response->assertJson([
            'data' => [
                'pagination' => [
                    'current_page' => 2,
                ]
            ]
        ]);
    }

    public function test_store_creates_new_event(): void
    {
        $data = [
            'name' => 'New Event',
            'location_id' => $this->location->id,
            'location_value' => 'Room 101',
            'duration_mins' => 45,
            'max_appointment_days' => 14,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/events', $data);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'name' => 'New Event',
            'slug' => 'new-event',
            'location_value' => 'Room 101',
            'duration_mins' => 45,
            'max_appointment_days' => 14,
        ]);

        $this->assertDatabaseHas('events', [
            'user_id' => $this->user->id,
            'name' => 'New Event',
            'slug' => 'new-event',
        ]);
    }

    public function test_store_validates_input(): void
    {
        $data = [
            'name' => '', // Empty name
            'location_id' => 'invalid-uuid',
            'location_value' => '',
            'duration_mins' => 0, // Invalid duration
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/events', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'location_id', 'location_value', 'duration_mins']);
    }

    public function test_show_returns_event(): void
    {
        $event = Event::create([
            'user_id' => $this->user->id,
            'name' => 'Test Event',
            'slug' => 'test-event',
            'location_id' => $this->location->id,
            'location_value' => 'Room 200',
            'duration_mins' => 60,
            'max_appointment_days' => 30,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/events/' . $event->id);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $event->id,
            'name' => 'Test Event',
            'slug' => 'test-event',
            'location_value' => 'Room 200',
            'duration_mins' => 60,
            'max_appointment_days' => 30,
        ]);
    }

    public function test_show_returns_not_found_for_nonexistent_event(): void
    {
        $nonexistentId = '00000000-0000-0000-0000-000000000000';
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/events/' . $nonexistentId);

        $response->assertStatus(404);
    }

    public function test_show_returns_unauthorized_for_other_users_event(): void
    {
        $otherUser = User::factory()->create();
        $event = Event::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Event',
            'slug' => 'other-user-event',
            'location_id' => $this->location->id,
            'location_value' => 'Room 300',
            'duration_mins' => 30,
            'max_appointment_days' => 7,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/events/' . $event->id);

        $response->assertStatus(401);
    }

    public function test_update_modifies_event(): void
    {
        $event = Event::create([
            'user_id' => $this->user->id,
            'name' => 'Original Event',
            'slug' => 'original-event',
            'location_id' => $this->location->id,
            'location_value' => 'Room 400',
            'duration_mins' => 60,
            'max_appointment_days' => 30,
        ]);

        $data = [
            'name' => 'Updated Event',
            'slug' => 'updated-event',
            'location_value' => 'Room 401',
            'duration_mins' => 45,
            'max_appointment_days' => 14,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/events/' . $event->id, $data);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Updated Event',
            'slug' => 'updated-event',
            'location_value' => 'Room 401',
            'duration_mins' => 45,
            'max_appointment_days' => 14,
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Updated Event',
            'slug' => 'updated-event',
            'location_value' => 'Room 401',
        ]);
    }

    public function test_update_allows_partial_updates(): void
    {
        $event = Event::create([
            'user_id' => $this->user->id,
            'name' => 'Original Event',
            'slug' => 'original-event',
            'location_id' => $this->location->id,
            'location_value' => 'Room 500',
            'duration_mins' => 60,
            'max_appointment_days' => 30,
        ]);

        // Update only the name
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/events/' . $event->id, [
            'name' => 'Renamed Event',
            'slug' => 'renamed-event',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Renamed Event',
            'slug' => 'renamed-event',
            'location_value' => 'Room 500',
        ]);

        // Update only the duration
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/events/' . $event->id, [
            'duration_mins' => 90,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Renamed Event',
            'slug' => 'renamed-event',
            'duration_mins' => 90,
        ]);
    }

    public function test_update_returns_unauthorized_for_other_users_event(): void
    {
        $otherUser = User::factory()->create();
        $event = Event::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Event',
            'slug' => 'other-user-event',
            'location_id' => $this->location->id,
            'location_value' => 'Room 600',
            'duration_mins' => 30,
            'max_appointment_days' => 7,
        ]);

        $data = [
            'name' => 'Trying to Update',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/events/' . $event->id, $data);

        $response->assertStatus(401);
    }

    public function test_destroy_deletes_event(): void
    {
        $event = Event::create([
            'user_id' => $this->user->id,
            'name' => 'Event to Delete',
            'slug' => 'event-to-delete',
            'location_id' => $this->location->id,
            'location_value' => 'Room 700',
            'duration_mins' => 60,
            'max_appointment_days' => 30,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/events/' . $event->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    public function test_destroy_returns_unauthorized_for_other_users_event(): void
    {
        $otherUser = User::factory()->create();
        $event = Event::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Event',
            'slug' => 'other-user-event',
            'location_id' => $this->location->id,
            'location_value' => 'Room 800',
            'duration_mins' => 30,
            'max_appointment_days' => 7,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/events/' . $event->id);

        $response->assertStatus(401);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
        ]);
    }
} 