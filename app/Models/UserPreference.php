<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'is_available',
        'block_lunch_break',
        'lunch_break_start_time',
        'lunch_break_end_time',
        'block_public_holiday',
        'timezone',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'block_lunch_break' => 'boolean',
            'block_public_holiday' => 'boolean',
            'lunch_break_start_time' => 'datetime:H:i:s',
            'lunch_break_end_time' => 'datetime:H:i:s',
        ];
    }

    /**
     * Get the validation rules for the model.
     *
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'user_id' => 'required|uuid|exists:users,id|unique:user_preferences,user_id',
            'is_available' => 'boolean',
            'block_lunch_break' => 'boolean',
            'lunch_break_start_time' => 'date_format:H:i:s',
            'lunch_break_end_time' => 'date_format:H:i:s|after:lunch_break_start_time',
            'block_public_holiday' => 'boolean',
            'timezone' => 'string|max:100|timezone',
        ];
    }

    /**
     * Get the validation rules for updating the model.
     *
     * @param string $id
     * @return array<string, string>
     */
    public static function updateRules(string $id): array
    {
        $rules = self::rules();
        // user_id is derived from auth session, no need to validate on update
        unset($rules['user_id']);
        // require start time if end time is provided, and ensure it's before end
        $rules['lunch_break_start_time'] = 'date_format:H:i:s|required_with:lunch_break_end_time|before:lunch_break_end_time';
        // require end time if start time is provided, and ensure it's after start
        $rules['lunch_break_end_time'] = 'date_format:H:i:s|required_with:lunch_break_start_time|after:lunch_break_start_time';
        return $rules;
    }

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
