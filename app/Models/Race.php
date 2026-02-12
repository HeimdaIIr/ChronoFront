<?php

namespace App\Models;

use App\Traits\SyncToMainDatabase;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Race extends Model
{
    use SyncToMainDatabase;

    /**
     * The connection name for the model.
     */
    protected $connection = 'tenant';

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

    /**
     * Serialize dates with explicit timezone to prevent JS timezone mismatch.
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return Carbon::instance($date)->format('Y-m-d\TH:i:s.uP');
    }

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

    /**
     * Get the main database table name
     */
    protected function getMainTableName(): string
    {
        return 'main_races';
    }

    /**
     * Get the main database sync ID field name
     */
    protected function getMainSyncIdField(): string
    {
        return 'tenant_race_id';
    }

    /**
     * Get the data to sync to main database
     */
    protected function getMainSyncData(int $accountId): array
    {
        return [
            'account_id' => $accountId,
            'tenant_race_id' => $this->id,
            'tenant_event_id' => $this->event_id,
            'name' => $this->name,
            'distance' => $this->distance,
            'start_time' => $this->start_time,
            'duration' => $this->duration,
            'display_order' => $this->display_order ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
