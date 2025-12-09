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
    ];

    /**
     * Attributes to append to model's array/JSON form
     */
    protected $appends = [
        'calculated_ip',
        'web_config_url',
        'chronofront_url',
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

    /**
     * Calculate IP address based on network type and serial
     *
     * - local: 192.168.10.{150 + last2digits} (legacy mode)
     * - vpn: 10.8.0.{serial} (VPN ATS Sport)
     * - custom: uses custom_ip field
     */
    public function getCalculatedIpAttribute(): string
    {
        switch ($this->network_type) {
            case 'vpn':
                // VPN ATS Sport: 10.8.0.{serial}
                return "10.8.0.{$this->serial}";

            case 'custom':
                // IP personnalisée
                return $this->custom_ip ?? '0.0.0.0';

            case 'local':
            default:
                // Mode local legacy: 192.168.10.{150 + last2digits}
                $lastTwoDigits = substr((string)$this->serial, -2);
                $ipSuffix = 150 + (int)$lastTwoDigits;
                return "192.168.10.{$ipSuffix}";
        }
    }

    /**
     * Get the web config URL (for VPN ATS Sport)
     */
    public function getWebConfigUrlAttribute(): ?string
    {
        if ($this->network_type === 'vpn') {
            return "http://{$this->serial}.conf.ats-sport.com/";
        }
        return null;
    }

    /**
     * Get the ChronoFront URL (for VPN ATS Sport)
     */
    public function getChronoFrontUrlAttribute(): ?string
    {
        if ($this->network_type === 'vpn') {
            return "http://{$this->serial}.course.ats-sport.com/";
        }
        return null;
    }
}
