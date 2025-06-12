<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'location_id',
        'location_value',
        'duration_mins',
        'max_appointment_days',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration_mins' => 'integer',
        'max_appointment_days' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the validation rules for the model.
     *
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'uuid', 'exists:locations,id'],
            'location_value' => ['required', 'string', 'max:255'],
            'duration_mins' => ['required', 'integer', 'min:1'],
            'max_appointment_days' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
