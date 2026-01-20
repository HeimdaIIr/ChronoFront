<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wave extends Model
{
    

    protected $fillable = [
        'race_id',
        'wave_number',
        'name',
        'start_time',
        'end_time',
        'is_started',
        'real_start_time',
        'depart_window_minutes',
        'use_top_depart',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'real_start_time' => 'datetime',
        'is_started' => 'boolean',
        'use_top_depart' => 'boolean',
    ];

    /**
     * Get the race that owns the wave
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * Get the entrants for the wave
     */
    public function entrants(): HasMany
    {
        return $this->hasMany(Entrant::class);
    }

    /**
     * Get the results for the wave
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }
}
