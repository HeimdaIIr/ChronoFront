<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    

    protected $fillable = [
        'race_id',
        'entrant_id',
        'wave_id',
        'reader_id',
        'rfid_tag',
        'serial',
        'reader_location',
        'raw_time',
        'calculated_time',
        'lap_number',
        'lap_time',
        'speed',
        'position',
        'category_position',
        'status',
        'is_manual',
    ];

    protected $casts = [
        'raw_time' => 'datetime',
        'calculated_time' => 'integer', // en secondes
        'lap_number' => 'integer',
        'lap_time' => 'integer', // en secondes
        'speed' => 'decimal:2',
        'position' => 'integer',
        'category_position' => 'integer',
        'is_manual' => 'boolean',
    ];

    /**
     * Get the race that owns the result
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * Get the entrant that owns the result
     */
    public function entrant(): BelongsTo
    {
        return $this->belongsTo(Entrant::class);
    }

    /**
     * Get the wave that owns the result
     */
    public function wave(): BelongsTo
    {
        return $this->belongsTo(Wave::class);
    }

    /**
     * Get the reader that detected this result
     */
    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    /**
     * Format calculated time as HH:MM:SS
     */
    public function getFormattedTimeAttribute(): string
    {
        if (!$this->calculated_time) {
            return 'N/A';
        }

        $hours = floor($this->calculated_time / 3600);
        $minutes = floor(($this->calculated_time % 3600) / 60);
        $seconds = $this->calculated_time % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Format lap time as HH:MM:SS
     */
    public function getFormattedLapTimeAttribute(): string
    {
        if (!$this->lap_time) {
            return 'N/A';
        }

        $hours = floor($this->lap_time / 3600);
        $minutes = floor(($this->lap_time % 3600) / 60);
        $seconds = $this->lap_time % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Calculate time from wave start or race start (TOP DÉPART)
     */
    public function calculateTime(): void
    {
        // Try wave start time first, then fallback to race start time (TOP DÉPART)
        $startTime = null;

        if ($this->wave && $this->wave->start_time) {
            $startTime = $this->wave->start_time;
        } elseif ($this->race && $this->race->start_time) {
            // Use race TOP DÉPART if no wave start time
            $startTime = $this->race->start_time;
        }

        if (!$startTime) {
            return; // No start time available
        }

        $start = \Carbon\Carbon::parse($startTime);
        $end = \Carbon\Carbon::parse($this->raw_time);

        $this->calculated_time = $end->diffInSeconds($start);
    }

    /**
     * Calculate speed based on distance
     */
    public function calculateSpeed(float $distance): void
    {
        if (!$this->calculated_time || $this->calculated_time == 0) {
            return;
        }

        // Speed in km/h
        $hours = $this->calculated_time / 3600;
        $this->speed = round($distance / $hours, 2);
    }
}
