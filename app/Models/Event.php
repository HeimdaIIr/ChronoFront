<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    

    protected $fillable = [
        'name',
        'date_start',
        'date_end',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the races for the event
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class);
    }

    /**
     * Check if event is currently active
     * An event is active if:
     * - is_active flag is true
     * - current datetime is between date_start and date_end
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        return $now >= $this->date_start && $now <= $this->date_end;
    }

    /**
     * Scope to get only currently active events
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('date_start', '<=', now())
                    ->where('date_end', '>=', now());
    }
}
