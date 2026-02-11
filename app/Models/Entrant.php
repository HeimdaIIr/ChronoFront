<?php

namespace App\Models;

use App\Traits\SyncToMainDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Entrant extends Model
{
    use SyncToMainDatabase;

    /**
     * The connection name for the model.
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'firstname',
        'lastname',
        'gender',
        'birth_date',
        'email',
        'phone',
        'rfid_tag',
        'bib_number',
        'event_id',
        'category_id',
        'race_id',
        'wave_id',
        'club',
        'team',
        'start_time', // Heure de départ individuelle (colonne TOP du CSV)
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d', // Format yyyy-MM-dd pour les inputs HTML
        'start_time' => 'datetime:H:i:s', // Cast vers Time pour contre-la-montre
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

    /**
     * Get the main database table name
     */
    protected function getMainTableName(): string
    {
        return 'main_entrants';
    }

    /**
     * Get the main database sync ID field name
     */
    protected function getMainSyncIdField(): string
    {
        return 'tenant_entrant_id';
    }

    /**
     * Get the data to sync to main database
     */
    protected function getMainSyncData(int $accountId): array
    {
        return [
            'account_id' => $accountId,
            'tenant_entrant_id' => $this->id,
            'tenant_event_id' => $this->event_id,
            'first_name' => $this->firstname,
            'last_name' => $this->lastname,
            'bib_number' => $this->bib_number,
            'gender' => $this->gender,
            'age' => $this->age,
            'category' => $this->category ? $this->category->name : null,
            'club' => $this->club,
            'team' => $this->team,
            'rfid' => $this->rfid_tag,
            'start_time' => $this->start_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
