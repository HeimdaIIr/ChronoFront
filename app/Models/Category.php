<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{

    protected $fillable = [
        'name',
        'code',
        'gender',
        'age_min',
        'age_max',
        'color',
    ];

    protected $casts = [
        'age_min' => 'integer',
        'age_max' => 'integer',
    ];

    /**
     * Get the entrants for the category
     */
    public function entrants(): HasMany
    {
        return $this->hasMany(Entrant::class);
    }
}
