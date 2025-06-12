<?php

namespace Tests\Unit\Controllers\Api;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class UserPreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(): array
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        return ['Authorization' => "Bearer $token"];
    }

    public function test_it_creates_and_returns_default_preferences(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)
            ->getJson('/api/user/preference');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id','user_id','is_available','block_lunch_break',
                    'block_public_holiday','timezone',
                    'lunch_break_start_time','lunch_break_end_time',
                    'created_at','updated_at',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(true, $data['is_available']);
        $this->assertEquals(false, $data['block_lunch_break']);
        $this->assertEquals(false, $data['block_public_holiday']);
        $this->assertEquals(config('app.timezone'), $data['timezone']);
        $this->assertEquals('12:00:00', $data['lunch_break_start_time']);
        $this->assertEquals('13:00:00', $data['lunch_break_end_time']);
    }

    public function test_it_returns_existing_preferences(): void
    {
        $user = User::factory()->create();
        $pref = UserPreference::factory()
            ->for($user)
            ->create([
                'is_available' => false,
                'block_lunch_break' => true,
                'lunch_break_start_time' => '11:00:00',
                'lunch_break_end_time' => '12:00:00',
                'block_public_holiday' => true,
                'timezone' => 'UTC',
            ]);

        $token = JWTAuth::fromUser($user);
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/user/preference');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $pref->id)
            ->assertJsonPath('data.is_available', false)
            ->assertJsonPath('data.block_lunch_break', true)
            ->assertJsonPath('data.lunch_break_start_time', date('H:i:s', strtotime($pref->lunch_break_start_time)))
            ->assertJsonPath('data.lunch_break_end_time', date('H:i:s', strtotime($pref->lunch_break_end_time)))
            ->assertJsonPath('data.block_public_holiday', true)
            ->assertJsonPath('data.timezone', 'UTC');
    }

    public function test_it_updates_preferences(): void
    {
        $headers = $this->authHeaders();
        $payload = [
            'is_available' => false,
            'block_lunch_break' => true,
            'lunch_break_start_time' => '10:00:00',
            'lunch_break_end_time' => '11:00:00',
            'block_public_holiday' => true,
            'timezone' => 'Europe/London',
        ];

        $response = $this->withHeaders($headers)
            ->putJson('/api/user/preference', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_available', false)
            ->assertJsonPath('data.block_lunch_break', true)
            ->assertJsonPath('data.lunch_break_start_time', '10:00:00')
            ->assertJsonPath('data.lunch_break_end_time', '11:00:00')
            ->assertJsonPath('data.block_public_holiday', true)
            ->assertJsonPath('data.timezone', 'Europe/London');

        $this->assertDatabaseHas('user_preferences', array_merge($payload, ['user_id' => $response->json('data.user_id')]));
    }

    public function test_it_validates_update_request(): void
    {
        $headers = $this->authHeaders();
        $payload = ['lunch_break_end_time' => '09:00:00'];

        $response = $this->withHeaders($headers)
            ->putJson('/api/user/preference', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['success','message','errors']);
    }

    public function test_lunch_end_requires_start(): void
    {
        $headers = $this->authHeaders();
        $payload = ['lunch_break_end_time' => '13:00:00'];

        $response = $this->withHeaders($headers)
            ->putJson('/api/user/preference', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lunch_break_start_time']);
    }

    public function test_lunch_start_requires_end(): void
    {
        $headers = $this->authHeaders();
        $payload = ['lunch_break_start_time' => '11:00:00'];

        $response = $this->withHeaders($headers)
            ->putJson('/api/user/preference', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lunch_break_end_time']);
    }

    public function test_lunch_end_after_start(): void
    {
        $headers = $this->authHeaders();
        $payload = [
            'lunch_break_start_time' => '14:00:00',
            'lunch_break_end_time' => '13:00:00',
        ];

        $response = $this->withHeaders($headers)
            ->putJson('/api/user/preference', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lunch_break_end_time']);
    }
} 