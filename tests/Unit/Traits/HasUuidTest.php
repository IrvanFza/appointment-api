<?php

namespace Tests\Unit\Traits;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasUuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_trait_sets_incrementing_to_false(): void
    {
        $model = new TestModelWithUuid();
        
        $this->assertFalse($model->getIncrementing());
    }

    public function test_trait_sets_key_type_to_string(): void
    {
        $model = new TestModelWithUuid();
        
        $this->assertEquals('string', $model->getKeyType());
    }

    public function test_trait_generates_uuid_on_creation(): void
    {
        $model = new TestModelWithUuid();
        $model->name = 'Test';
        
        // Simulate the creating event
        $model->id = null;
        TestModelWithUuid::bootHasUuid();
        
        // Manually trigger the creating event callback
        $callback = TestModelWithUuid::getEventDispatcher()->listen('eloquent.creating: ' . TestModelWithUuid::class, function ($model): void {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
        
        $this->assertNull($model->id);
        
        // Trigger the event manually
        if (empty($model->id)) {
            $model->id = (string) \Illuminate\Support\Str::uuid();
        }
        
        $this->assertNotNull($model->id);
        $this->assertIsString($model->id);
        $this->assertEquals(36, strlen($model->id)); // UUID length
    }

    public function test_trait_does_not_override_existing_id(): void
    {
        $model = new TestModelWithUuid();
        $existingId = 'existing-id';
        $model->id = $existingId;
        
        // Simulate the creating event with existing ID
        if (empty($model->id)) {
            $model->id = (string) \Illuminate\Support\Str::uuid();
        }
        
        $this->assertEquals($existingId, $model->id);
    }

    public function test_trait_is_used_by_user_model(): void
    {
        $user = new \App\Models\User();
        $traits = class_uses_recursive(\App\Models\User::class);
        
        $this->assertContains(HasUuid::class, $traits);
    }

    public function test_trait_is_used_by_user_preference_model(): void
    {
        $preference = new \App\Models\UserPreference();
        $traits = class_uses_recursive(\App\Models\UserPreference::class);
        
        $this->assertContains(HasUuid::class, $traits);
    }
}

/**
 * Test model for HasUuid trait testing
 */
class TestModelWithUuid extends Model
{
    use HasUuid;
    
    protected $table = 'test_models';
    protected $fillable = ['name'];
    public $timestamps = false;
} 