<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reader extends Model
{
    
    protected $table = 'readers';

    protected $fillable = [
        'serial',
        'name',
        'event_id',
        'race_id',
        'location',
        'anti_rebounce_seconds',
        'date_min',
        'date_max',
        'is_active',
        'clone_reader_id',
        'test_terrain',
        'date_test',
    ];

    protected $casts = [
        'date_min' => 'datetime',
        'date_max' => 'datetime',
        'date_test' => 'datetime',
        'is_active' => 'boolean',
        'test_terrain' => 'boolean',
        'anti_rebounce_seconds' => 'integer',
    ];

    /**
     * Get the event this reader belongs to
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the race this reader is assigned to (optional)
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * Check if reader is currently active based on date range
     */
    public function isCurrentlyActive(): bool
    {
        $now = now();
        return $this->is_active
            && $now >= $this->date_min
            && $now <= $this->date_max;
    }

    /**
     * Get active configuration for a reader by serial number
     */
    public static function getActiveConfig(string $serial): ?self
    {
        $now = now();
        return self::where('serial', $serial)
            ->where('is_active', true)
            ->where('date_min', '<=', $now)
            ->where('date_max', '>=', $now)
            ->first();
    }

    /**
     * Mark reader as tested on terrain
     */
    public function markAsTested(): void
    {
        $this->update([
            'test_terrain' => true,
            'date_test' => now(),
        ]);
    }
}
