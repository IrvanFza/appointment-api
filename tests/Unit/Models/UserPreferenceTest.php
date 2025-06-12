<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_preference_has_correct_fillable_attributes(): void
    {
        $preference = new UserPreference();
        $expected = [
            'user_id',
            'is_available',
            'block_lunch_break',
            'lunch_break_start_time',
            'lunch_break_end_time',
            'block_public_holiday',
            'timezone',
        ];
        
        $this->assertEquals($expected, $preference->getFillable());
    }

    public function test_user_preference_has_correct_casts(): void
    {
        $preference = new UserPreference();
        $casts = $preference->getCasts();
        
        $this->assertArrayHasKey('is_available', $casts);
        $this->assertEquals('boolean', $casts['is_available']);
        $this->assertArrayHasKey('block_lunch_break', $casts);
        $this->assertEquals('boolean', $casts['block_lunch_break']);
        $this->assertArrayHasKey('block_public_holiday', $casts);
        $this->assertEquals('boolean', $casts['block_public_holiday']);
        $this->assertArrayHasKey('lunch_break_start_time', $casts);
        $this->assertEquals('datetime:H:i:s', $casts['lunch_break_start_time']);
        $this->assertArrayHasKey('lunch_break_end_time', $casts);
        $this->assertEquals('datetime:H:i:s', $casts['lunch_break_end_time']);
    }

    public function test_user_preference_uses_uuid_as_primary_key(): void
    {
        $preference = new UserPreference();
        
        $this->assertFalse($preference->getIncrementing());
        $this->assertEquals('string', $preference->getKeyType());
    }

    public function test_user_preference_generates_uuid_on_creation(): void
    {
        $user = User::factory()->create();
        
        $preference = UserPreference::create([
            'user_id' => $user->id,
            'is_available' => true,
            'timezone' => 'Asia/Jakarta'
        ]);

        $this->assertNotNull($preference->id);
        $this->assertIsString($preference->id);
    }

    public function test_user_preference_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $preference = UserPreference::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $preference->user);
        $this->assertEquals($user->id, $preference->user->id);
    }

    public function test_user_has_one_preference(): void
    {
        $user = User::factory()->create();
        $preference = UserPreference::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(UserPreference::class, $user->preference);
        $this->assertEquals($preference->id, $user->preference->id);
    }

    public function test_user_preference_can_be_created_with_factory(): void
    {
        $preference = UserPreference::factory()->create();
        
        $this->assertInstanceOf(UserPreference::class, $preference);
        $this->assertNotNull($preference->id);
        $this->assertNotNull($preference->user_id);
        $this->assertIsBool($preference->is_available);
        $this->assertIsBool($preference->block_lunch_break);
        $this->assertIsBool($preference->block_public_holiday);
        $this->assertNotNull($preference->timezone);
    }

    public function test_user_preference_can_be_created_with_specific_attributes(): void
    {
        $user = User::factory()->create();
        $preferenceData = [
            'user_id' => $user->id,
            'is_available' => false,
            'block_lunch_break' => true,
            'lunch_break_start_time' => '12:30:00',
            'lunch_break_end_time' => '13:30:00',
            'block_public_holiday' => true,
            'timezone' => 'UTC'
        ];

        $preference = UserPreference::factory()->create($preferenceData);
        
        $this->assertEquals($preferenceData['user_id'], $preference->user_id);
        $this->assertEquals($preferenceData['is_available'], $preference->is_available);
        $this->assertEquals($preferenceData['block_lunch_break'], $preference->block_lunch_break);
        $this->assertEquals($preferenceData['lunch_break_start_time'], $preference->lunch_break_start_time->format('H:i:s'));
        $this->assertEquals($preferenceData['lunch_break_end_time'], $preference->lunch_break_end_time->format('H:i:s'));
        $this->assertEquals($preferenceData['block_public_holiday'], $preference->block_public_holiday);
        $this->assertEquals($preferenceData['timezone'], $preference->timezone);
    }

    public function test_user_preference_validation_rules(): void
    {
        $rules = UserPreference::rules();
        
        $this->assertArrayHasKey('user_id', $rules);
        $this->assertStringContainsString('required', $rules['user_id']);
        $this->assertStringContainsString('uuid', $rules['user_id']);
        $this->assertStringContainsString('exists:users,id', $rules['user_id']);
        $this->assertStringContainsString('unique:user_preferences,user_id', $rules['user_id']);
        
        $this->assertArrayHasKey('is_available', $rules);
        $this->assertEquals('boolean', $rules['is_available']);
        
        $this->assertArrayHasKey('block_lunch_break', $rules);
        $this->assertEquals('boolean', $rules['block_lunch_break']);
        
        $this->assertArrayHasKey('lunch_break_start_time', $rules);
        $this->assertEquals('date_format:H:i:s', $rules['lunch_break_start_time']);
        
        $this->assertArrayHasKey('lunch_break_end_time', $rules);
        $this->assertStringContainsString('date_format:H:i:s', $rules['lunch_break_end_time']);
        $this->assertStringContainsString('after:lunch_break_start_time', $rules['lunch_break_end_time']);
        
        $this->assertArrayHasKey('block_public_holiday', $rules);
        $this->assertEquals('boolean', $rules['block_public_holiday']);
        
        $this->assertArrayHasKey('timezone', $rules);
        $this->assertStringContainsString('string', $rules['timezone']);
        $this->assertStringContainsString('max:100', $rules['timezone']);
        $this->assertStringContainsString('timezone', $rules['timezone']);
    }

    public function test_user_preference_validation_passes_with_valid_data(): void
    {
        $user = User::factory()->create();
        $data = [
            'user_id' => $user->id,
            'is_available' => true,
            'block_lunch_break' => true,
            'lunch_break_start_time' => '12:00:00',
            'lunch_break_end_time' => '13:00:00',
            'block_public_holiday' => false,
            'timezone' => 'Asia/Jakarta'
        ];

        $validator = Validator::make($data, UserPreference::rules());
        
        $this->assertTrue($validator->passes());
    }

    public function test_user_preference_validation_fails_with_invalid_user_id(): void
    {
        $data = [
            'user_id' => 'invalid-uuid',
            'timezone' => 'Asia/Jakarta'
        ];

        $validator = Validator::make($data, UserPreference::rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    public function test_user_preference_validation_fails_with_nonexistent_user(): void
    {
        $data = [
            'user_id' => '123e4567-e89b-12d3-a456-426614174000',
            'timezone' => 'Asia/Jakarta'
        ];

        $validator = Validator::make($data, UserPreference::rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    public function test_user_preference_validation_fails_with_duplicate_user_id(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create(['user_id' => $user->id]);
        
        $data = [
            'user_id' => $user->id,
            'timezone' => 'Asia/Jakarta'
        ];

        $validator = Validator::make($data, UserPreference::rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    public function test_user_preference_validation_fails_with_invalid_time_format(): void
    {
        $user = User::factory()->create();
        $data = [
            'user_id' => $user->id,
            'lunch_break_start_time' => '25:00:00', // Invalid hour
            'lunch_break_end_time' => '12:60:00', // Invalid minute
            'timezone' => 'Asia/Jakarta'
        ];

        $validator = Validator::make($data, UserPreference::rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('lunch_break_start_time', $validator->errors()->toArray());
        $this->assertArrayHasKey('lunch_break_end_time', $validator->errors()->toArray());
    }

    public function test_user_preference_validation_fails_when_end_time_before_start_time(): void
    {
        $user = User::factory()->create();
        $data = [
            'user_id' => $user->id,
            'lunch_break_start_time' => '13:00:00',
            'lunch_break_end_time' => '12:00:00', // Before start time
            'timezone' => 'Asia/Jakarta'
        ];

        $validator = Validator::make($data, UserPreference::rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('lunch_break_end_time', $validator->errors()->toArray());
    }

    public function test_user_preference_validation_fails_with_invalid_timezone(): void
    {
        $user = User::factory()->create();
        $data = [
            'user_id' => $user->id,
            'timezone' => 'Invalid/Timezone'
        ];

        $validator = Validator::make($data, UserPreference::rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('timezone', $validator->errors()->toArray());
    }

    public function test_user_preference_update_rules_allow_same_user_id(): void
    {
        $user = User::factory()->create();
        $preference = UserPreference::factory()->create(['user_id' => $user->id]);
        
        $data = [
            'user_id' => $user->id,
            'timezone' => 'UTC'
        ];

        $validator = Validator::make($data, UserPreference::updateRules($preference->id));
        
        $this->assertTrue($validator->passes());
    }

    public function test_user_preference_can_be_updated(): void
    {
        $preference = UserPreference::factory()->create([
            'is_available' => true,
            'timezone' => 'Asia/Jakarta'
        ]);
        
        $preference->update([
            'is_available' => false,
            'timezone' => 'UTC'
        ]);
        
        $this->assertFalse($preference->fresh()->is_available);
        $this->assertEquals('UTC', $preference->fresh()->timezone);
    }

    public function test_user_preference_can_be_deleted(): void
    {
        $preference = UserPreference::factory()->create();
        $preferenceId = $preference->id;
        
        $preference->delete();
        
        $this->assertNull(UserPreference::find($preferenceId));
    }

    public function test_user_preference_timestamps_are_working(): void
    {
        $preference = UserPreference::factory()->create();
        
        $this->assertNotNull($preference->created_at);
        $this->assertNotNull($preference->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $preference->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $preference->updated_at);
    }

    public function test_user_preference_factory_states(): void
    {
        $availablePreference = UserPreference::factory()->available()->create();
        $this->assertTrue($availablePreference->is_available);

        $unavailablePreference = UserPreference::factory()->unavailable()->create();
        $this->assertFalse($unavailablePreference->is_available);

        $withLunchBreak = UserPreference::factory()->withLunchBreak()->create();
        $this->assertTrue($withLunchBreak->block_lunch_break);
        $this->assertEquals('12:00:00', $withLunchBreak->lunch_break_start_time->format('H:i:s'));
        $this->assertEquals('13:00:00', $withLunchBreak->lunch_break_end_time->format('H:i:s'));

        $withoutLunchBreak = UserPreference::factory()->withoutLunchBreak()->create();
        $this->assertFalse($withoutLunchBreak->block_lunch_break);

        $blockingHolidays = UserPreference::factory()->blockingHolidays()->create();
        $this->assertTrue($blockingHolidays->block_public_holiday);
    }

    public function test_user_preference_has_factory_trait(): void
    {
        $preference = new UserPreference();
        $traits = class_uses_recursive(UserPreference::class);
        
        $this->assertContains(\Illuminate\Database\Eloquent\Factories\HasFactory::class, $traits);
    }

    public function test_deleting_user_cascades_to_preference(): void
    {
        $user = User::factory()->create();
        $preference = UserPreference::factory()->create(['user_id' => $user->id]);
        
        $user->delete();
        
        // Due to foreign key constraint, this should fail or cascade
        $this->assertNull(UserPreference::find($preference->id));
    }
} 