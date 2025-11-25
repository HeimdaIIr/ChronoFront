<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Entrant extends Model
{

    protected $fillable = [
        'firstname',
        'lastname',
        'gender',
        'birth_date',
        'email',
        'phone',
        'rfid_tag',
        'bib_number',
        'category_id',
        'race_id',
        'wave_id',
        'club',
        'team',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Get the category that owns the entrant
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the race that owns the entrant
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * Get the wave that owns the entrant
     */
    public function wave(): BelongsTo
    {
        return $this->belongsTo(Wave::class);
    }

    /**
     * Get the results for the entrant
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Get entrant's age
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }
        return Carbon::parse($this->birth_date)->age;
    }

    /**
     * Get entrant's full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }

    /**
     * Assign category based on age and gender
     */
    public function assignCategory(): void
    {
        if (!$this->birth_date || !$this->gender) {
            return;
        }

        $age = $this->age;
        $category = Category::where('gender', $this->gender)
            ->where('age_min', '<=', $age)
            ->where('age_max', '>=', $age)
            ->first();

        if ($category) {
            $this->category_id = $category->id;
            $this->save();
        }
    }
}
