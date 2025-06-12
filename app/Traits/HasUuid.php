<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the trait and add event listeners.
     */
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Initialize the trait for an instance.
     */
    public function initializeHasUuid()
    {
        // Disable default Laravel incrementing, since we're using UUIDs
        $this->incrementing = false;
        $this->keyType = 'string';
    }
} 