<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_implements_jwt_subject_interface()
    {
        $user = new User();
        
        $this->assertInstanceOf(JWTSubject::class, $user);
    }

    public function test_user_has_correct_fillable_attributes()
    {
        $user = new User();
        $expected = ['name', 'email', 'password'];
        
        $this->assertEquals($expected, $user->getFillable());
    }

    public function test_user_has_correct_hidden_attributes()
    {
        $user = new User();
        $expected = ['password', 'remember_token'];
        
        $this->assertEquals($expected, $user->getHidden());
    }

    public function test_user_has_correct_casts()
    {
        $user = new User();
        $casts = $user->getCasts();
        
        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertEquals('datetime', $casts['email_verified_at']);
        $this->assertArrayHasKey('password', $casts);
        $this->assertEquals('hashed', $casts['password']);
    }

    public function test_user_uses_uuid_as_primary_key()
    {
        $user = new User();
        
        $this->assertFalse($user->getIncrementing());
        $this->assertEquals('string', $user->getKeyType());
    }

    public function test_user_generates_uuid_on_creation()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->assertNotNull($user->id);
        $this->assertIsString($user->id);
    }

    public function test_user_id_is_not_assignable()
    {
        $customUuid = '123e4567-e89b-12d3-a456-426614174000';
        
        $user = User::create([
            'id' => $customUuid,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        // ID should be auto-generated, not the custom one we tried to pass
        $this->assertNotEquals($customUuid, $user->id);
        $this->assertNotNull($user->id);
        $this->assertIsString($user->id);
    }

    public function test_password_is_automatically_hashed()
    {
        $plainPassword = 'password123';
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $plainPassword
        ]);

        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }

    public function test_get_jwt_identifier_returns_user_id()
    {
        $user = User::factory()->create();
        
        $this->assertEquals($user->id, $user->getJWTIdentifier());
    }

    public function test_get_jwt_custom_claims_returns_empty_array()
    {
        $user = new User();
        
        $this->assertEquals([], $user->getJWTCustomClaims());
        $this->assertIsArray($user->getJWTCustomClaims());
    }

    public function test_user_can_be_created_with_factory()
    {
        $user = User::factory()->create();
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->id);
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
        $this->assertNotNull($user->password);
    }

    public function test_user_can_be_created_with_specific_attributes()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secretpassword'
        ];

        $user = User::factory()->create($userData);
        
        $this->assertEquals($userData['name'], $user->name);
        $this->assertEquals($userData['email'], $user->email);
        $this->assertTrue(Hash::check($userData['password'], $user->password));
    }

    public function test_user_email_must_be_unique()
    {
        $email = 'unique@example.com';
        
        User::factory()->create(['email' => $email]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => $email]);
    }

    public function test_user_timestamps_are_working()
    {
        $user = User::factory()->create();
        
        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->updated_at);
    }

    public function test_user_can_be_updated()
    {
        $user = User::factory()->create(['name' => 'Original Name']);
        $originalUpdatedAt = $user->updated_at;

        $user->update(['name' => 'Updated Name']);
        
        $this->assertEquals('Updated Name', $user->fresh()->name);
    }

    public function test_user_can_be_deleted()
    {
        $user = User::factory()->create();
        $userId = $user->id;
        
        $user->delete();
        
        $this->assertNull(User::find($userId));
    }

    public function test_user_has_notifiable_trait()
    {
        $user = new User();
        $traits = class_uses_recursive(User::class);
        
        $this->assertContains(\Illuminate\Notifications\Notifiable::class, $traits);
    }

    public function test_user_has_factory_trait()
    {
        $user = new User();
        $traits = class_uses_recursive(User::class);
        
        $this->assertContains(\Illuminate\Database\Eloquent\Factories\HasFactory::class, $traits);
    }
} 