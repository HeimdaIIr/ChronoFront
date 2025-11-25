<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Screen extends Model
{

    protected $fillable = [
        'name',
        'race_id',
        'layout',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the race that owns the screen
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
