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
     * Récupère la catégorie du coureur
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Récupère le parcours du coureur
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * Récupère la vague du coureur
     */
    public function wave(): BelongsTo
    {
        return $this->belongsTo(Wave::class);
    }

    /**
     * Récupère le résultat du coureur
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Récupère l'age du coureur
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }
        return Carbon::parse($this->birth_date)->age;
    }

    /**
     * Récupère le nom et prénom du coureur
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }

    /**
     * Assigne une catégorie FFA basé sur l'age et le sexe
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
