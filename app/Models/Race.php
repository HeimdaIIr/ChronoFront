<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Race extends Model
{
    

    protected $fillable = [
        'event_id',
        'display_order',
        'name',
        'type',
        'distance',
        'laps',
        'duration',
        'best_time',
        'description',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'distance' => 'decimal:2',
        'laps' => 'integer',
        'duration' => 'integer',
        'best_time' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

	protected static function boot()
	{
		parent::boot();

		static::creating(function ($race) {
			if ($race->laps === null) {
				$race->laps = 1;
			}
		});

		static::updating(function ($race) {
			if ($race->laps === null) {
				$race->laps = 1;
			}
		});
	}

    /**
     * Get the event that owns the race
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the waves for the race
     */
    public function waves(): HasMany
    {
        return $this->hasMany(Wave::class);
    }

    /**
     * Get the entrants for the race
     */
    public function entrants(): HasMany
    {
        return $this->hasMany(Entrant::class);
    }

    /**
     * Get the results for the race
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Get the screens for the race
     */
    public function screens(): HasMany
    {
        return $this->hasMany(Screen::class);
    }

    /**
     * Get the classements for the race
     */
    public function classements(): HasMany
    {
        return $this->hasMany(Classement::class);
    }
}
