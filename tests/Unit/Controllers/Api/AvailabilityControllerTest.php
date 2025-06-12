<?php

namespace Tests\Unit\Controllers\Api;

use App\Models\Availability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AvailabilityControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    public function test_index_returns_user_availabilities(): void
    {
        // Create availabilities for the authenticated user
        $availability1 = Availability::create([
            'user_id' => $this->user->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $availability2 = Availability::create([
            'user_id' => $this->user->id,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);

        // Create an availability for another user
        $otherUser = User::factory()->create();
        Availability::create([
            'user_id' => $otherUser->id,
            'day_of_week' => 3,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/availabilities');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $availability1->id]);
        $response->assertJsonFragment(['id' => $availability2->id]);
        $response->assertJsonFragment(['day_string' => 'Monday']);
        $response->assertJsonFragment(['day_string' => 'Tuesday']);
    }

    public function test_store_creates_new_availability(): void
    {
        $data = [
            'day_of_week' => 3,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/availabilities', $data);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'day_of_week' => 3,
            'day_string' => 'Wednesday',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $this->assertDatabaseHas('availabilities', [
            'user_id' => $this->user->id,
            'day_of_week' => 3,
        ]);
    }

    public function test_store_validates_input(): void
    {
        $data = [
            'day_of_week' => 8, // Invalid day (must be 0-6)
            'start_time' => '09:00',
            'end_time' => '08:00', // End time before start time
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/availabilities', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['day_of_week', 'end_time']);
    }

    public function test_show_returns_availability(): void
    {
        $availability = Availability::create([
            'user_id' => $this->user->id,
            'day_of_week' => 4,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/availabilities/' . $availability->id);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $availability->id,
            'day_of_week' => 4,
            'day_string' => 'Thursday',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
    }

    public function test_show_returns_not_found_for_nonexistent_availability(): void
    {
        $nonexistentId = '00000000-0000-0000-0000-000000000000';
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/availabilities/' . $nonexistentId);

        $response->assertStatus(404);
    }

    public function test_show_returns_unauthorized_for_other_users_availability(): void
    {
        $otherUser = User::factory()->create();
        $availability = Availability::create([
            'user_id' => $otherUser->id,
            'day_of_week' => 5,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/availabilities/' . $availability->id);

        $response->assertStatus(401);
    }

    public function test_update_modifies_availability(): void
    {
        $availability = Availability::create([
            'user_id' => $this->user->id,
            'day_of_week' => 5,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $data = [
            'day_of_week' => 6,
            'start_time' => '10:00',
            'end_time' => '18:00',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/availabilities/' . $availability->id, $data);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'day_of_week' => 6,
            'day_string' => 'Saturday',
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);

        $this->assertDatabaseHas('availabilities', [
            'id' => $availability->id,
            'day_of_week' => 6,
        ]);
    }

    public function test_update_allows_partial_updates(): void
    {
        $availability = Availability::create([
            'user_id' => $this->user->id,
            'day_of_week' => 5,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        // Update only the end_time
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/availabilities/' . $availability->id, [
            'end_time' => '18:00',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'day_of_week' => 5,
            'day_string' => 'Friday',
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // Update only the start_time
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/availabilities/' . $availability->id, [
            'start_time' => '10:00',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'day_of_week' => 5,
            'day_string' => 'Friday',
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);

        // Update only the day_of_week
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/availabilities/' . $availability->id, [
            'day_of_week' => 6,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'day_of_week' => 6,
            'day_string' => 'Saturday',
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);
    }

    public function test_update_returns_unauthorized_for_other_users_availability(): void
    {
        $otherUser = User::factory()->create();
        $availability = Availability::create([
            'user_id' => $otherUser->id,
            'day_of_week' => 5,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $data = [
            'day_of_week' => 6,
            'start_time' => '10:00',
            'end_time' => '18:00',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/availabilities/' . $availability->id, $data);

        $response->assertStatus(401);
    }

    public function test_destroy_deletes_availability(): void
    {
        $availability = Availability::create([
            'user_id' => $this->user->id,
            'day_of_week' => 5,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/availabilities/' . $availability->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('availabilities', [
            'id' => $availability->id,
        ]);
    }

    public function test_destroy_returns_unauthorized_for_other_users_availability(): void
    {
        $otherUser = User::factory()->create();
        $availability = Availability::create([
            'user_id' => $otherUser->id,
            'day_of_week' => 5,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/availabilities/' . $availability->id);

        $response->assertStatus(401);
        $this->assertDatabaseHas('availabilities', [
            'id' => $availability->id,
        ]);
    }
} 