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
        // Don't use 'datetime' cast for raw_time with SQLite
        // We'll handle timezone manually with accessors/mutators
        'calculated_time' => 'integer', // en secondes
        'lap_number' => 'integer',
        'lap_time' => 'integer', // en secondes
        'speed' => 'decimal:2',
        'position' => 'integer',
        'category_position' => 'integer',
        'is_manual' => 'boolean',
    ];

    /**
     * Attributes to append to JSON responses
     */
    protected $appends = [
        'formatted_time',
        'formatted_lap_time',
    ];

    /**
     * Get raw_time attribute - convert to Carbon in app timezone
     */
    public function getRawTimeAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Parse as app timezone (SQLite stores local time as text)
        return \Carbon\Carbon::parse($value, config('app.timezone'));
    }

    /**
     * Set raw_time attribute - store as local time string
     */
    public function setRawTimeAttribute($value)
    {
        if ($value instanceof \Carbon\Carbon) {
            // Store as local time string (Y-m-d H:i:s)
            $this->attributes['raw_time'] = $value->format('Y-m-d H:i:s');
        } else {
            $this->attributes['raw_time'] = $value;
        }
    }

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
     * Calculate time from individual start, wave start or race start (TOP DÉPART)
     * Priorité : entrant.start_time > wave.start_time > race.start_time
     */
    public function calculateTime(): void
    {
        // PRIORITÉ 1 : Heure de départ individuelle (contre-la-montre)
        if ($this->entrant && $this->entrant->start_time) {
            $rawTime = \Carbon\Carbon::parse($this->raw_time);

            // Gérer à la fois les formats TIME (HH:MM:SS) et DATETIME (Y-m-d H:i:s)
            $startTimeStr = (string) $this->entrant->start_time;

            // Si start_time contient déjà une date (format Y-m-d), utiliser directement
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $startTimeStr)) {
                $startDateTime = \Carbon\Carbon::parse($startTimeStr);
            } else {
                // Sinon c'est juste un TIME, combiner avec la date du passage
                $startDateTime = \Carbon\Carbon::parse($rawTime->format('Y-m-d') . ' ' . $startTimeStr);
            }

            $this->calculated_time = $rawTime->diffInSeconds($startDateTime);
            return;
        }

        // PRIORITÉ 2 : Heure de départ de la vague
        if ($this->wave && $this->wave->start_time) {
            $start = \Carbon\Carbon::parse($this->wave->start_time);
            $end = \Carbon\Carbon::parse($this->raw_time);

            $this->calculated_time = abs($end->diffInSeconds($start));
            return;
        }

        // PRIORITÉ 3 : TOP DÉPART de la course (fallback)
        if ($this->race && $this->race->start_time) {
            $start = \Carbon\Carbon::parse($this->race->start_time);
            $end = \Carbon\Carbon::parse($this->raw_time);

            $this->calculated_time = abs($end->diffInSeconds($start));
            return;
        }
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
