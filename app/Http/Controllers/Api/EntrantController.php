<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entrant;
use App\Models\Category;
use App\Models\Race;
use App\Models\Wave;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EntrantController extends Controller
{
    /**
     * Display a listing of entrants
     */
    public function index(Request $request): JsonResponse
    {
        $query = Entrant::with(['category', 'race', 'wave']);

        // Search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%")
                  ->orWhere('bib_number', 'like', "%{$search}%")
                  ->orWhere('rfid_tag', 'like', "%{$search}%");
            });
        }

        // Race filter
        if ($request->has('race_id')) {
            $query->where('race_id', $request->input('race_id'));
        }

        $entrants = $query->orderBy('lastname')->orderBy('firstname')->get();

        return response()->json($entrants);
    }

    /**
     * Store a newly created entrant
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'gender' => 'required|in:M,F',
            'birth_date' => 'nullable|date',
            'email' => 'nullable|email|max:200',
            'phone' => 'nullable|string|max:50',
            'bib_number' => 'nullable|string|max:20',
            'race_id' => 'nullable|exists:races,id',
            'wave_id' => 'nullable|exists:waves,id',
            'club' => 'nullable|string|max:200',
            'team' => 'nullable|string|max:200',
        ]);

        // Générer le RFID si bib_number fourni (2000 + dossard sur 4 chiffres)
        if (isset($validated['bib_number']) && !isset($validated['rfid_tag'])) {
            $validated['rfid_tag'] = '2000' . str_pad($validated['bib_number'], 4, '0', STR_PAD_LEFT);
        }

        $entrant = Entrant::create($validated);

        // Assigne une catégorie automatiquement si naissance et genre disponibles
        if ($entrant->birth_date && $entrant->gender) {
            $entrant->assignCategory();
            $entrant->load('category');
        }

        return response()->json($entrant, 201);
    }

    /**
     * Display the specified entrant
     */
    public function show(Entrant $entrant): JsonResponse
    {
        $entrant->load(['category', 'race', 'wave', 'results']);
        return response()->json($entrant);
    }

    /**
     * Update the specified entrant
     */
    public function update(Request $request, Entrant $entrant): JsonResponse
    {
        $validated = $request->validate([
            'firstname' => 'sometimes|string|max:100',
            'lastname' => 'sometimes|string|max:100',
            'gender' => 'sometimes|in:M,F',
            'birth_date' => 'nullable|date',
            'email' => 'nullable|email|max:200',
            'phone' => 'nullable|string|max:50',
            'bib_number' => 'nullable|string|max:20',
            'rfid_tag' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'race_id' => 'nullable|exists:races,id',
            'wave_id' => 'nullable|exists:waves,id',
            'club' => 'nullable|string|max:200',
            'team' => 'nullable|string|max:200',
        ]);

        $entrant->update($validated);

        // Re-assign category if birth_date or gender changed
        if ((isset($validated['birth_date']) || isset($validated['gender'])) && $entrant->birth_date && $entrant->gender) {
            $entrant->assignCategory();
        }

        $entrant->load('category');

        return response()->json($entrant);
    }

    /**
     * Remove the specified entrant
     */
    public function destroy(Entrant $entrant): JsonResponse
    {
        $entrant->delete();
        return response()->json(['message' => 'Entrant deleted successfully']);
    }

    /**
     * Import entrants from CSV file
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'event_id' => 'required|integer',
        ]);

        // Verify event exists
        $event = \App\Models\Event::find($validated['event_id']);
        if (!$event) {
            return response()->json([
                'message' => 'Événement non trouvé',
                'error' => 'L\'événement spécifié n\'existe pas'
            ], 404);
        }

        $file = $request->file('file');
        $eventId = $request->input('event_id');

        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_map('strtolower', array_shift($csvData));

        $imported = 0;
        $errors = [];
        $racesCache = []; // Cache pour éviter de recréer les races
        $wavesCache = []; // Cache pour éviter de recréer les vagues

        DB::beginTransaction();

        try {
            foreach ($csvData as $index => $row) {
                if (count($row) !== count($headers)) {
                    continue; // Skip malformed rows
                }

                $data = array_combine($headers, $row);

                // Map CSV columns
                $firstname = $data['prenom'] ?? $data['firstname'] ?? '';
                $lastname = $data['nom'] ?? $data['lastname'] ?? '';
                $gender = strtoupper($data['sexe'] ?? $data['gender'] ?? 'M');
                $birthDate = $data['naissance'] ?? $data['birth_date'] ?? null;
                $parcours = $data['parcours'] ?? $data['race'] ?? null;
                $vague = $data['vague'] ?? $data['wave'] ?? null;
                $cat = $data['cat'] ?? $data['category'] ?? null;
                $club = $data['club'] ?? null;
                $bibNumber = $data['dossard'] ?? $data['bib'] ?? null;

                // Skip if missing required fields
                if (empty($firstname) || empty($lastname) || empty($parcours)) {
                    $errors[] = "Ligne " . ($index + 2) . ": Données manquantes (nom, prénom ou parcours)";
                    continue;
                }

                // Find or create Race based on PARCOURS
                $raceKey = strtolower(trim($parcours));
                if (!isset($racesCache[$raceKey])) {
                    $race = Race::where('event_id', $eventId)
                        ->where('name', trim($parcours))
                        ->first();

                    if (!$race) {
                        $race = Race::create([
                            'event_id' => $eventId,
                            'name' => trim($parcours),
                            'type' => '1 passage', // Type par défaut
                        ]);
                    }
                    $racesCache[$raceKey] = $race;
                } else {
                    $race = $racesCache[$raceKey];
                }

                // Find or create Wave based on VAGUE
                $waveId = null;
                if (!empty($vague)) {
                    $vagueValue = trim($vague);
                    $waveKey = $race->id . '_' . $vagueValue;

                    // Extraire le numéro de vague
                    // Si c'est juste un nombre (1, 2, 3...), on l'utilise directement
                    // Sinon on essaie d'extraire un nombre de "Vague 1", "Wave 2", etc.
                    $waveNumber = null;
                    if (is_numeric($vagueValue)) {
                        $waveNumber = (int)$vagueValue;
                    } elseif (preg_match('/(\d+)/', $vagueValue, $matches)) {
                        $waveNumber = (int)$matches[1];
                    }

                    if (!isset($wavesCache[$waveKey])) {
                        // Chercher la vague par numéro d'abord, sinon par nom
                        if ($waveNumber) {
                            $wave = Wave::where('race_id', $race->id)
                                ->where('wave_number', $waveNumber)
                                ->first();
                        }

                        if (!isset($wave)) {
                            $wave = Wave::where('race_id', $race->id)
                                ->where('name', $vagueValue)
                                ->first();
                        }

                        if (!$wave) {
                            // Créer la vague avec numéro si disponible
                            $waveData = [
                                'race_id' => $race->id,
                                'name' => $vagueValue,
                            ];

                            if ($waveNumber) {
                                $waveData['wave_number'] = $waveNumber;
                            }

                            $wave = Wave::create($waveData);
                        }
                        $wavesCache[$waveKey] = $wave;
                    } else {
                        $wave = $wavesCache[$waveKey];
                    }
                    $waveId = $wave->id;
                }

                // Parse birth date (format français DD/MM/YYYY)
                $parsedBirthDate = null;
                if ($birthDate) {
                    try {
                        // Essayer le format français DD/MM/YYYY
                        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $birthDate, $matches)) {
                            $parsedBirthDate = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                        } else {
                            $parsedBirthDate = \Carbon\Carbon::parse($birthDate)->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        $parsedBirthDate = null;
                    }
                }

                // Generate RFID tag from bib number (2000 + dossard sur 4 chiffres)
                $rfidTag = null;
                if ($bibNumber) {
                    $rfidTag = '2000' . str_pad($bibNumber, 4, '0', STR_PAD_LEFT);
                }

                // Clean gender
                $gender = in_array($gender, ['M', 'F']) ? $gender : 'M';

                // Prepare entrant data
                $entrantData = [
                    'firstname' => trim($firstname),
                    'lastname' => trim($lastname),
                    'gender' => $gender,
                    'birth_date' => $parsedBirthDate,
                    'bib_number' => $bibNumber,
                    'rfid_tag' => $rfidTag,
                    'club' => $club,
                    'event_id' => $eventId,
                    'race_id' => $race->id,
                    'wave_id' => $waveId,
                ];

                // Check if entrant already exists (by bib_number + event_id)
                $entrant = Entrant::where('event_id', $eventId)
                    ->where('bib_number', $bibNumber)
                    ->first();

                if ($entrant) {
                    // Update existing entrant
                    $entrant->update($entrantData);
                } else {
                    // Create new entrant
                    $entrant = Entrant::create($entrantData);
                }

                // Handle category
                if (!empty($cat)) {
                    // Try to find category by name (e.g., SE-M, M0-F, etc.)
                    $category = Category::where('name', strtoupper(trim($cat)))->first();
                    if ($category) {
                        $entrant->category_id = $category->id;
                        $entrant->save();
                    } else {
                        // If category name not found, auto-assign based on age and gender
                        if ($entrant->birth_date && $entrant->gender) {
                            $entrant->assignCategory();
                        }
                    }
                } else {
                    // Auto-assign category based on age and gender
                    if ($entrant->birth_date && $entrant->gender) {
                        $entrant->assignCategory();
                    }
                }

                $imported++;
            }

            DB::commit();

            return response()->json([
                'message' => "Import réussi",
                'imported' => $imported,
                'total_rows' => count($csvData),
                'races_created' => count($racesCache),
                'waves_created' => count($wavesCache),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Import échoué',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search entrants by various criteria
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');

        $entrants = Entrant::with(['category', 'race'])
            ->where(function ($q) use ($query) {
                $q->where('firstname', 'like', "%{$query}%")
                  ->orWhere('lastname', 'like', "%{$query}%")
                  ->orWhere('bib_number', 'like', "%{$query}%")
                  ->orWhere('rfid_tag', 'like', "%{$query}%")
                  ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$query}%"]);
            })
            ->limit(50)
            ->get();

        return response()->json($entrants);
    }

    /**
     * Delete all entrants
     */
    public function deleteAll(): JsonResponse
    {
        try {
            $count = Entrant::count();
            Entrant::truncate();

            return response()->json([
                'message' => 'Tous les participants ont été supprimés',
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
