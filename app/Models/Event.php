<?php

namespace App\Models;

use App\Traits\SyncToMainDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use SyncToMainDatabase;

    /**
     * The connection name for the model.
     */
    protected $connection = 'tenant';

    protected static function boot()
    {
        parent::boot();

        // When an event is deleted, ensure all related data is properly cleaned
        static::deleting(function ($event) {
            // Delete all races (which will cascade to waves, entrants, results via DB constraints)
            $event->races()->delete();

            // Delete all readers
            $event->readers()->delete();
        });
    }

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
     * Get the readers for the event
     */
    public function readers(): HasMany
    {
        return $this->hasMany(Reader::class);
    }

    /**
     * Get the entrants for the event
     */
    public function entrants(): HasMany
    {
        return $this->hasMany(Entrant::class);
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

    /**
     * Get the main database table name
     */
    protected function getMainTableName(): string
    {
        return 'main_events';
    }

    /**
     * Get the main database sync ID field name
     */
    protected function getMainSyncIdField(): string
    {
        return 'tenant_event_id';
    }

    /**
     * Get the data to sync to main database
     */
    protected function getMainSyncData(int $accountId): array
    {
        return [
            'account_id' => $accountId,
            'tenant_event_id' => $this->id,
            'name' => $this->name,
            'date' => $this->date_start,
            'location' => $this->location,
            'organizer' => null,
            'notes' => $this->description,
            'is_active' => $this->is_active,
            'alert_threshold' => null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
