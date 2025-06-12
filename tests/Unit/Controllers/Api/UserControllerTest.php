<?php

namespace Tests\Unit\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_profile_with_token()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user/profile');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'User profile fetched successfully',
                     'data' => [
                         'email' => 'test@example.com',
                         'name' => 'Test User'
                     ]
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'created_at',
                         'updated_at'
                     ]
                 ]);
    }

    public function test_profile_access_without_token_returns_401()
    {
        $response = $this->getJson('/api/user/profile');

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.'
                 ]);
    }

    public function test_profile_access_with_invalid_token_returns_401()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token_here',
        ])->getJson('/api/user/profile');

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.'
                 ]);
    }

    public function test_profile_access_with_malformed_token_returns_401()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.malformed.token',
        ])->getJson('/api/user/profile');

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.'
                 ]);
    }

    public function test_profile_access_with_expired_token_returns_401()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = auth('api')->login($user);

        // Logout to invalidate the token
        auth('api')->logout();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user/profile');

        $response->assertStatus(401)
                 ->assertHeader('Content-Type', 'application/json')
                 ->assertJson([
                     'message' => 'Unauthenticated.'
                 ]);
    }
} 