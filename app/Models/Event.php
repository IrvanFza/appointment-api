<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        'slug',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = $event->generateUniqueSlug($event->name);
            }
        });

        static::updating(function ($event) {
            if ($event->isDirty('name') && !$event->isDirty('slug')) {
                $event->slug = $event->generateUniqueSlug($event->name);
            }
        });
    }

    /**
     * Generate a unique slug for the event.
     *
     * @param string $name
     * @return string
     */
    protected function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        // Check if the slug already exists for this user
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists for the current user.
     *
     * @param string $slug
     * @return bool
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where('slug', $slug)
            ->where('user_id', $this->user_id);
        
        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }
        
        return $query->exists();
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'string',
                'max:255',
                Rule::unique('events')->where(function ($query) {
                    return $query->where('user_id', request()->user_id);
                }),
            ],
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

    /**
     * Get the schedules for the event.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
