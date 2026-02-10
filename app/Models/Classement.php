<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Classement extends Model
{
    /**
     * The connection name for the model.
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'race_id',
        'name',
        'type',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    /**
     * Get the race that owns the classement
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
