<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Entrant;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    /**
     * Display all results across all races
     */
    public function index(): JsonResponse
    {
        $results = Result::with(['entrant.category', 'wave', 'race'])
            ->orderBy('raw_time', 'desc')
            ->limit(100) // Limit to last 100 results for performance
            ->get();

        return response()->json($results);
    }

    /**
     * Display results for a specific race
     */
    public function byRace(int $raceId): JsonResponse
    {
        $results = Result::where('race_id', $raceId)
            ->with(['entrant.category', 'wave'])
            ->orderBy('position')
            ->get();

        return response()->json($results);
    }

    /**
     * Add a new timing result
     */
    public function addTime(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'race_id' => 'sometimes|exists:races,id',
            'entrant_id' => 'sometimes|exists:entrants,id',
            'bib_number' => 'sometimes|string',
            'rfid_tag' => 'sometimes|string',
            'raw_time' => 'nullable|date',
            'is_manual' => 'boolean',
        ]);

        // Find entrant by bib_number or rfid_tag if entrant_id not provided
        if (!isset($validated['entrant_id'])) {
            $entrant = null;

            if (isset($validated['bib_number'])) {
                // If race_id provided, filter by it, otherwise search across all races
                $query = Entrant::where('bib_number', $validated['bib_number']);
                if (isset($validated['race_id'])) {
                    $query->where('race_id', $validated['race_id']);
                }
                $entrant = $query->first();
            } elseif (isset($validated['rfid_tag'])) {
                $query = Entrant::where('rfid_tag', $validated['rfid_tag']);
                if (isset($validated['race_id'])) {
                    $query->where('race_id', $validated['race_id']);
                }
                $entrant = $query->first();
            }

            if (!$entrant) {
                return response()->json([
                    'message' => 'Participant non trouvé'
                ], 404);
            }

            $validated['entrant_id'] = $entrant->id;
            $validated['rfid_tag'] = $entrant->rfid_tag;
            // Get race_id from entrant if not provided
            if (!isset($validated['race_id'])) {
                $validated['race_id'] = $entrant->race_id;
            }
        } else {
            $entrant = Entrant::findOrFail($validated['entrant_id']);
            $validated['rfid_tag'] = $entrant->rfid_tag;
            // Get race_id from entrant if not provided
            if (!isset($validated['race_id'])) {
                $validated['race_id'] = $entrant->race_id;
            }
        }

        // Set raw_time to now if not provided
        if (!isset($validated['raw_time'])) {
            $validated['raw_time'] = now();
        }

        // Get wave_id from entrant
        $validated['wave_id'] = $entrant->wave_id;

        // Determine lap number
        $lapNumber = Result::where('race_id', $validated['race_id'])
            ->where('entrant_id', $validated['entrant_id'])
            ->max('lap_number') ?? 0;

        $validated['lap_number'] = $lapNumber + 1;
        $validated['is_manual'] = $validated['is_manual'] ?? true;

        // Create result
        $result = Result::create($validated);

        // Calculate time and speed
        $this->calculateResult($result);

        $result->load(['entrant.category', 'wave', 'race']);

        return response()->json([
            'message' => 'Temps ajouté avec succès',
            'result' => $result
        ], 201);
    }

    /**
     * Add multiple manual timing results in batch
     * Used for manual timing when RFID fails
     *
     * Expected format:
     * {
     *   "event_id": 3,
     *   "times": [
     *     {"timestamp": "2025-12-01 09:45:32", "bib_number": "422"},
     *     {"timestamp": "2025-12-01 09:45:38", "bib_number": "156"},
     *     ...
     *   ]
     * }
     */
    public function storeManualBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'times' => 'required|array',
            'times.*.timestamp' => 'required|date',
            'times.*.bib_number' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $created = [];
            $errors = [];

            foreach ($validated['times'] as $index => $timeData) {
                // Find entrant by bib number
                $entrant = Entrant::where('bib_number', $timeData['bib_number'])
                    ->whereHas('race', function($q) use ($validated) {
                        $q->where('event_id', $validated['event_id']);
                    })
                    ->with('race')
                    ->first();

                if (!$entrant) {
                    $errors[] = [
                        'index' => $index + 1,
                        'bib_number' => $timeData['bib_number'],
                        'error' => 'Participant non trouvé'
                    ];
                    continue;
                }

                // Determine lap number
                $lapNumber = Result::where('race_id', $entrant->race_id)
                    ->where('entrant_id', $entrant->id)
                    ->max('lap_number') ?? 0;

                // Create result
                $result = Result::create([
                    'race_id' => $entrant->race_id,
                    'entrant_id' => $entrant->id,
                    'wave_id' => $entrant->wave_id,
                    'rfid_tag' => $entrant->rfid_tag,
                    'reader_location' => 'ARRIVEE',
                    'raw_time' => $timeData['timestamp'],
                    'lap_number' => $lapNumber + 1,
                    'is_manual' => true,
                    'status' => 'V',
                ]);

                // Calculate time and speed
                $this->calculateResult($result);

                $created[] = $result->load(['entrant.category', 'wave', 'race']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($created) . ' temps ajoutés avec succès',
                'created' => count($created),
                'errors' => count($errors),
                'results' => $created,
                'error_details' => $errors
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création des résultats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import RFID detections from reader memory file
     * Updates existing results or creates new ones (upsert logic)
     *
     * Expected format:
     * {
     *   "race_id": 1,
     *   "reader_id": 3,
     *   "detections": [
     *     {"rfid_tag": "20000002", "timestamp": "2025-12-01 14:02:20"},
     *     {"rfid_tag": "20001695", "timestamp": "2025-12-01 14:02:22"},
     *     ...
     *   ]
     * }
     */
    public function importRfidBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'race_id' => 'sometimes|exists:races,id',
            'reader_id' => 'required|exists:readers,id',
            'detections' => 'required|array',
            'detections.*.rfid_tag' => 'required|string',
            'detections.*.timestamp' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            $reader = \App\Models\Reader::findOrFail($validated['reader_id']);
            $updated = [];
            $created = [];
            $errors = [];

            foreach ($validated['detections'] as $index => $detection) {
                try {
                    // Find entrant by RFID tag
                    $query = Entrant::where('rfid_tag', $detection['rfid_tag']);
                    if (isset($validated['race_id'])) {
                        $query->where('race_id', $validated['race_id']);
                    }
                    $entrant = $query->first();

                    if (!$entrant) {
                        $errors[] = [
                            'index' => $index + 1,
                            'rfid_tag' => $detection['rfid_tag'],
                            'error' => 'Participant non trouvé'
                        ];
                        continue;
                    }

                    // Check if result already exists for this entrant at this checkpoint
                    $existingResult = Result::where('entrant_id', $entrant->id)
                        ->where('race_id', $entrant->race_id)
                        ->where('reader_id', $reader->id)
                        ->first();

                    if ($existingResult) {
                        // Update existing result
                        $existingResult->update([
                            'raw_time' => $detection['timestamp'],
                            'is_manual' => false,
                        ]);

                        // Recalculate time and speed
                        $this->calculateResult($existingResult);

                        $updated[] = $existingResult->load(['entrant.category', 'wave', 'race']);
                    } else {
                        // Determine lap number
                        $lapNumber = Result::where('race_id', $entrant->race_id)
                            ->where('entrant_id', $entrant->id)
                            ->max('lap_number') ?? 0;

                        // Create new result
                        $result = Result::create([
                            'race_id' => $entrant->race_id,
                            'entrant_id' => $entrant->id,
                            'wave_id' => $entrant->wave_id,
                            'rfid_tag' => $entrant->rfid_tag,
                            'reader_id' => $reader->id,
                            'reader_location' => $reader->location,
                            'raw_time' => $detection['timestamp'],
                            'lap_number' => $lapNumber + 1,
                            'is_manual' => false,
                            'status' => 'V',
                        ]);

                        // Calculate time and speed
                        $this->calculateResult($result);

                        $created[] = $result->load(['entrant.category', 'wave', 'race']);
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index + 1,
                        'rfid_tag' => $detection['rfid_tag'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($created) + count($updated) . ' détections traitées',
                'created' => count($created),
                'updated' => count($updated),
                'errors' => count($errors),
                'results' => array_merge($created, $updated),
                'error_details' => $errors
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import des détections RFID',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate all positions for a race
     */
    public function recalculatePositions(int $raceId): JsonResponse
    {
        $race = Race::findOrFail($raceId);

        DB::beginTransaction();

        try {
            // Get all results for this race, grouped by entrant
            // For races with multiple laps, take best time or last lap depending on race type
            $results = Result::where('race_id', $raceId)
                ->where('status', 'V')
                ->with(['entrant.category'])
                ->get()
                ->groupBy('entrant_id')
                ->map(function ($entrantResults) use ($race) {
                    // For best_time races, keep best time
                    // Otherwise keep last lap
                    if ($race->best_time) {
                        return $entrantResults->sortBy('calculated_time')->first();
                    } else {
                        return $entrantResults->sortByDesc('lap_number')->first();
                    }
                })
                ->sortBy('calculated_time')
                ->values();

            // Calculate overall positions
            $position = 1;
            foreach ($results as $result) {
                $result->update(['position' => $position++]);
            }

            // Calculate category positions
            $resultsByCategory = $results->groupBy(function ($result) {
                return $result->entrant->category_id;
            });

            foreach ($resultsByCategory as $categoryId => $categoryResults) {
                $categoryPosition = 1;
                foreach ($categoryResults as $result) {
                    $result->update(['category_position' => $categoryPosition++]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Positions recalculées avec succès',
                'total_results' => $results->count()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors du recalcul des positions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export results to CSV
     */
    public function export(int $raceId): \Illuminate\Http\Response
    {
        $race = Race::with('event')->findOrFail($raceId);

        $results = Result::where('race_id', $raceId)
            ->with(['entrant.category'])
            ->orderBy('position')
            ->get();

        $csv = "Position,Dossard,Nom,Prénom,Sexe,Catégorie,Club,Temps,Vitesse,Position Catégorie,Statut\n";

        foreach ($results as $result) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $result->position ?? 'N/A',
                $result->entrant->bib_number ?? '',
                $result->entrant->lastname ?? '',
                $result->entrant->firstname ?? '',
                $result->entrant->gender ?? '',
                $result->entrant->category->name ?? '',
                $result->entrant->club ?? '',
                $result->formatted_time ?? '',
                $result->speed ? number_format($result->speed, 2) . ' km/h' : '',
                $result->category_position ?? 'N/A',
                $result->status
            );
        }

        $filename = sprintf(
            'resultats_%s_%s_%s.csv',
            $race->event->name ?? 'event',
            $race->name,
            now()->format('Y-m-d')
        );

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Update a result
     */
    public function update(Request $request, Result $result): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:V,DNS,DNF,DSQ,NS',
            'raw_time' => 'sometimes|date',
        ]);

        $result->update($validated);

        if (isset($validated['raw_time'])) {
            $this->calculateResult($result);
        }

        return response()->json($result);
    }

    /**
     * Delete a result
     */
    public function destroy(Result $result): JsonResponse
    {
        $result->delete();
        return response()->json(['message' => 'Result deleted successfully']);
    }

    /**
     * Calculate time and speed for a result
     */
    private function calculateResult(Result $result): void
    {
        $result->load(['wave', 'race', 'entrant']);

        // Calculate time from wave start or race TOP DÉPART
        if (($result->wave && $result->wave->start_time) || ($result->race && $result->race->start_time)) {
            $result->calculateTime();
        }

        // Calculate speed
        if ($result->race && $result->race->distance > 0 && $result->calculated_time > 0) {
            $result->calculateSpeed($result->race->distance);
        }

        // Calculate lap time if this is not the first lap
        if ($result->lap_number > 1) {
            $previousLap = Result::where('race_id', $result->race_id)
                ->where('entrant_id', $result->entrant_id)
                ->where('lap_number', $result->lap_number - 1)
                ->first();

            if ($previousLap && $previousLap->calculated_time && $result->calculated_time) {
                $result->lap_time = $result->calculated_time - $previousLap->calculated_time;
            }
        } else {
            $result->lap_time = $result->calculated_time;
        }

        $result->save();
    }
}
