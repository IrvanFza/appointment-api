<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Schedule extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduleFactory> */
    use HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'event_id',
        'start_time',
        'end_time',
        'client_name',
        'client_email',
        'status',
        'serial',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($schedule) {
            $schedule->serial = $schedule->serial ?? self::generateSerial();
        });
    }

    /**
     * Generate a unique serial for the schedule.
     *
     * @return string
     */
    public static function generateSerial(): string
    {
        $serial = 'SCH-' . strtoupper(Str::random(8));
        
        // Ensure the serial is unique
        while (self::where('serial', $serial)->exists()) {
            $serial = 'SCH-' . strtoupper(Str::random(8));
        }
        
        return $serial;
    }

    /**
     * Get the validation rules for the model.
     *
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'event_id' => ['required', 'uuid', 'exists:events,id'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:254'],
            'status' => ['required', 'string', 'max:50', 'in:confirmed,cancelled'],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
