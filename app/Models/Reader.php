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
        'network_type',
        'custom_ip',
        'http_username',
        'http_password',
        'event_id',
        'race_id',
        'location',
        'distance_from_start',
        'checkpoint_order',
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
        'distance_from_start' => 'decimal:2',
        'checkpoint_order' => 'integer',
        'http_password' => 'encrypted',
    ];

    protected $hidden = [
        'http_password',
    ];

    protected $appends = [
        'calculated_ip',
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
     * Calculate IP address based on network type and serial
     */
    public function getCalculatedIpAttribute(): string
    {
        switch ($this->network_type ?? 'local') {
            case 'vpn':
                return "10.8.0.{$this->serial}";
            case 'custom':
                return $this->custom_ip ?? '0.0.0.0';
            case 'local':
            default:
                $lastTwoDigits = substr((string)$this->serial, -2);
                $ipSuffix = 150 + (int)$lastTwoDigits;
                return "192.168.10.{$ipSuffix}";
        }
    }

    /**
     * Check if reader is currently active based on date range
     * If date_min/date_max are NULL, reader is always active (no time restriction)
     */
    public function isCurrentlyActive(): bool
    {
        $now = now();
        $withinDateRange = true;

        // Check date_min if set
        if ($this->date_min !== null && $now < $this->date_min) {
            $withinDateRange = false;
        }

        // Check date_max if set
        if ($this->date_max !== null && $now > $this->date_max) {
            $withinDateRange = false;
        }

        return $this->is_active && $withinDateRange;
    }

    /**
     * Get active configuration for a reader by serial number
     * If date_min/date_max are NULL, reader is always active (no time restriction)
     */
    public static function getActiveConfig(string $serial): ?self
    {
        $now = now();
        return self::where('serial', $serial)
            ->where('is_active', true)
            ->where(function($q) use ($now) {
                // If date_min is NULL, no start restriction
                $q->whereNull('date_min')
                  ->orWhere('date_min', '<=', $now);
            })
            ->where(function($q) use ($now) {
                // If date_max is NULL, no end restriction
                $q->whereNull('date_max')
                  ->orWhere('date_max', '>=', $now);
            })
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
