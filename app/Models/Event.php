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
}
