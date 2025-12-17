<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Entrant;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ResultController extends Controller
{
    /**
     * Display all results across all races
     * Supports filtering via query parameters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Result::with(['entrant.category', 'wave', 'race']);

        // Apply filters if provided
        $hasFilters = false;

        // Search by bib number or name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('entrant', function($q) use ($search) {
                $q->where('bib_number', 'like', "%{$search}%")
                  ->orWhere('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%");
            });
            $hasFilters = true;
        }

        // Filter by race
        if ($request->has('race_id') && $request->race_id) {
            $query->where('race_id', $request->race_id);
            $hasFilters = true;
        }

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->whereHas('entrant.category', function($q) use ($request) {
                $q->where('name', $request->category);
            });
            $hasFilters = true;
        }

        // Filter by wave
        if ($request->has('wave') && $request->wave) {
            $query->whereHas('wave', function($q) use ($request) {
                $q->where('name', $request->wave);
            });
            $hasFilters = true;
        }

        // Filter by checkpoint/reader location
        if ($request->has('checkpoint') && $request->checkpoint) {
            $query->where('reader_location', $request->checkpoint);
            $hasFilters = true;
        }

        $query->orderBy('raw_time', 'desc');

        // Only apply limit if no filters (for performance)
        if (!$hasFilters) {
            $query->limit(500);
        }

        $results = $query->get();

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

        // Recalculate positions for this race
        $this->recalculateRacePositions($result->race_id);

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
            'reader_id' => 'nullable|exists:readers,id',
            'times' => 'required|array',
            'times.*.timestamp' => 'required|date',
            'times.*.bib_number' => 'required|string',
            'times.*.reader_id' => 'nullable|exists:readers,id',
        ]);

        DB::beginTransaction();

        try {
            $created = [];
            $errors = [];

            // Get reader info if provided at batch level
            $defaultReader = null;
            $defaultLocation = 'ARRIVEE';
            if (isset($validated['reader_id'])) {
                $defaultReader = \App\Models\Reader::find($validated['reader_id']);
                $defaultLocation = $defaultReader ? $defaultReader->location : 'ARRIVEE';
            }

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

                // Get reader info for this specific time entry (overrides batch level)
                $readerId = $timeData['reader_id'] ?? $validated['reader_id'] ?? null;
                $readerLocation = $defaultLocation;

                if (isset($timeData['reader_id']) && $timeData['reader_id'] !== ($validated['reader_id'] ?? null)) {
                    $reader = \App\Models\Reader::find($timeData['reader_id']);
                    $readerLocation = $reader ? $reader->location : 'UNKNOWN';
                }

                // Create result
                $resultData = [
                    'race_id' => $entrant->race_id,
                    'entrant_id' => $entrant->id,
                    'wave_id' => $entrant->wave_id,
                    'rfid_tag' => $entrant->rfid_tag,
                    'reader_location' => $readerLocation,
                    'raw_time' => $timeData['timestamp'],
                    'lap_number' => $lapNumber + 1,
                    'is_manual' => true,
                    'status' => 'V',
                ];

                if ($readerId) {
                    $resultData['reader_id'] = $readerId;
                }

                $result = Result::create($resultData);

                // Calculate time and speed
                $this->calculateResult($result);

                $created[] = $result->load(['entrant.category', 'wave', 'race']);
            }

            // Recalculate positions for affected races
            $affectedRaces = collect($created)->pluck('race_id')->unique();
            foreach ($affectedRaces as $raceId) {
                $this->recalculateRacePositions($raceId);
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

            // Recalculate positions for affected races
            $allResults = array_merge($created, $updated);
            $affectedRaces = collect($allResults)->pluck('race_id')->unique();
            foreach ($affectedRaces as $raceId) {
                $this->recalculateRacePositions($raceId);
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
     * Recalculate positions for ALL races
     */
    public function recalculateAllPositions(): JsonResponse
    {
        DB::beginTransaction();

        try {
            $races = Race::all();
            $totalResults = 0;

            foreach ($races as $race) {
                // Recalculate speeds for ALL results if distance is defined
                if ($race->distance > 0) {
                    $allResults = Result::where('race_id', $race->id)->get();
                    foreach ($allResults as $result) {
                        if ($result->calculated_time > 0) {
                            $result->calculateSpeed($race->distance);
                            $result->save();
                        }
                    }
                }

                // Get all results for this race, grouped by entrant
                $results = Result::where('race_id', $race->id)
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

                $totalResults += $results->count();
            }

            DB::commit();

            return response()->json([
                'message' => 'Positions et vitesses recalculées pour toutes les courses',
                'total_races' => $races->count(),
                'total_results' => $totalResults
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

        // UTF-8 BOM pour Excel
        $csv = "\xEF\xBB\xBF";
        $csv .= "Position;Dossard;Nom;Prénom;Sexe;Catégorie;Club;Temps;Vitesse;Position Catégorie;Statut\n";

        foreach ($results as $result) {
            $csv .= sprintf(
                "%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s\n",
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
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export results to PDF
     */
    public function exportPdf(int $raceId, Request $request)
    {
        $race = Race::with('event')->findOrFail($raceId);

        // Get filters from request
        $displayMode = $request->query('display_mode', 'general');
        $statusFilter = $request->query('status_filter', 'all');
        $autoPrint = $request->query('print', false);

        // Build query
        $query = Result::where('race_id', $raceId)
            ->with(['entrant.category'])
            ->orderBy('position');

        // Apply status filter
        if ($statusFilter === 'V') {
            $query->where('status', 'V');
        }

        $results = $query->get();

        // Group by category if needed
        $resultsByCategory = [];
        if ($displayMode === 'category') {
            $resultsByCategory = $results->groupBy(function ($result) {
                return $result->entrant->category->name ?? 'Sans catégorie';
            })->map(function ($categoryResults) {
                return $categoryResults->sortBy('category_position');
            });
        }

        // Prepare data for PDF
        $data = [
            'race' => $race,
            'results' => $results,
            'displayMode' => $displayMode,
            'resultsByCategory' => $resultsByCategory,
            'autoPrint' => $autoPrint,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('chronofront.pdf.results', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = sprintf(
            'resultats_%s_%s_%s.pdf',
            $race->event->name ?? 'event',
            $race->name,
            now()->format('Y-m-d')
        );

        // Si print=true, afficher en ligne au lieu de télécharger
        if ($autoPrint) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Export awards (récompenses) to PDF
     */
    public function exportAwardsPdf(int $raceId, Request $request)
    {
        $race = Race::with('event')->findOrFail($raceId);

        // Get award configuration from request
        $topScratch = (int) $request->query('topScratch', 0);
        $topGender = (int) $request->query('topGender', 0);
        $topCategory = (int) $request->query('topCategory', 0);
        $topGenderCategory = (int) $request->query('topGenderCategory', 0);
        $autoPrint = $request->query('print', false);

        // Get all valid results sorted by position
        $allResults = Result::where('race_id', $raceId)
            ->where('status', 'V')
            ->with(['entrant.category'])
            ->orderBy('position')
            ->get();

        $awardedResults = collect();

        // Top Scratch général
        if ($topScratch > 0) {
            $scratchResults = $allResults->take($topScratch)->map(function($r) {
                $r->award_reason = "Top {$r->position} Scratch";
                return $r;
            });
            $awardedResults = $awardedResults->merge($scratchResults);
        }

        // Top par Genre (F/H)
        if ($topGender > 0) {
            foreach (['F', 'H'] as $gender) {
                $genderResults = $allResults
                    ->where('entrant.gender', $gender)
                    ->take($topGender);

                $position = 1;
                foreach ($genderResults as $result) {
                    $result->award_reason = "Top {$position} " . ($gender === 'F' ? 'Femmes' : 'Hommes');
                    $awardedResults->push($result);
                    $position++;
                }
            }
        }

        // Top par Catégorie
        if ($topCategory > 0) {
            $byCategory = $allResults->groupBy('entrant.category.name');
            foreach ($byCategory as $categoryName => $categoryResults) {
                $topCatResults = $categoryResults->take($topCategory);
                $position = 1;
                foreach ($topCatResults as $result) {
                    $result->award_reason = "Top {$position} {$categoryName}";
                    $awardedResults->push($result);
                    $position++;
                }
            }
        }

        // Top par Genre ET Catégorie
        if ($topGenderCategory > 0) {
            $byCategory = $allResults->groupBy('entrant.category.name');
            foreach ($byCategory as $categoryName => $categoryResults) {
                foreach (['F', 'H'] as $gender) {
                    $genderCatResults = $categoryResults
                        ->where('entrant.gender', $gender)
                        ->take($topGenderCategory);

                    $position = 1;
                    foreach ($genderCatResults as $result) {
                        $genderLabel = $gender === 'F' ? 'Femmes' : 'Hommes';
                        $result->award_reason = "Top {$position} {$genderLabel} {$categoryName}";
                        $awardedResults->push($result);
                        $position++;
                    }
                }
            }
        }

        // Remove duplicates based on result ID
        $awardedResults = $awardedResults->unique('id')->values();

        // Sort by position
        $awardedResults = $awardedResults->sortBy('position')->values();

        // Prepare data for PDF
        $data = [
            'race' => $race,
            'results' => $awardedResults,
            'autoPrint' => $autoPrint,
            'config' => [
                'topScratch' => $topScratch,
                'topGender' => $topGender,
                'topCategory' => $topCategory,
                'topGenderCategory' => $topGenderCategory,
            ]
        ];

        // Generate PDF
        $pdf = Pdf::loadView('chronofront.pdf.awards', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = sprintf(
            'recompenses_%s_%s_%s.pdf',
            $race->event->name ?? 'event',
            $race->name,
            now()->format('Y-m-d')
        );

        // Si print=true, afficher en ligne au lieu de télécharger
        if ($autoPrint) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
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

        // Recalculate positions for this race
        $this->recalculateRacePositions($result->race_id);

        return response()->json($result);
    }

    /**
     * Delete a result
     */
    public function destroy(Result $result): JsonResponse
    {
        $raceId = $result->race_id;
        $result->delete();

        // Recalculate positions for this race
        $this->recalculateRacePositions($raceId);

        return response()->json(['message' => 'Result deleted successfully']);
    }

    /**
     * Calculate time and speed for a result
     */
    private function calculateResult(Result $result): void
    {
        $result->load(['wave', 'race', 'entrant']);

        // Calculate time from individual start, wave start or race start (TOP DÉPART)
        // The calculateTime() method handles the 3-level priority internally
        $result->calculateTime();

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

    /**
     * Recalculate positions for a specific race (private helper)
     */
    private function recalculateRacePositions(int $raceId): void
    {
        try {
            $race = Race::find($raceId);
            if (!$race) {
                return;
            }

            // Get all results for this race, grouped by entrant
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
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            \Log::error("Error recalculating positions for race {$raceId}: " . $e->getMessage());
        }
    }

    /**
     * Add a single manual entry (time or status like DNS/DNF)
     *
     * Expected payload:
     * {
     *   "event_id": 1,
     *   "bib_number": "234",
     *   "reader_id": 3,
     *   "status": "normal|dns|dnf",
     *   "raw_time": "2025-12-01 14:30:00" (optional, only for normal status)
     * }
     */
    public function storeManualSingle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'bib_number' => 'required|string',
            'reader_id' => 'required|exists:readers,id',
            'status' => 'required|in:normal,dns,dnf',
            'raw_time' => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            // Find entrant by bib number
            $entrant = Entrant::where('bib_number', $validated['bib_number'])
                ->whereHas('race', function($q) use ($validated) {
                    $q->where('event_id', $validated['event_id']);
                })
                ->with('race')
                ->first();

            if (!$entrant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dossard ' . $validated['bib_number'] . ' non trouvé dans cet événement'
                ], 404);
            }

            // Get reader location
            $reader = \App\Models\Reader::find($validated['reader_id']);
            $readerLocation = $reader ? $reader->location : 'UNKNOWN';

            // Determine lap number
            $lapNumber = Result::where('race_id', $entrant->race_id)
                ->where('entrant_id', $entrant->id)
                ->max('lap_number') ?? 0;

            // Map frontend status to database status
            $statusMap = [
                'normal' => 'V',    // Validated
                'dns' => 'DNS',     // Did Not Start
                'dnf' => 'DNF',     // Did Not Finish
            ];

            $dbStatus = $statusMap[$validated['status']] ?? 'V';

            // Create result
            $resultData = [
                'race_id' => $entrant->race_id,
                'entrant_id' => $entrant->id,
                'wave_id' => $entrant->wave_id,
                'rfid_tag' => $entrant->rfid_tag,
                'reader_id' => $validated['reader_id'],
                'reader_location' => $readerLocation,
                'lap_number' => $lapNumber + 1,
                'is_manual' => true,
                'status' => $dbStatus,
            ];

            // Only add raw_time if status is normal and time is provided
            if ($validated['status'] === 'normal' && isset($validated['raw_time'])) {
                $resultData['raw_time'] = $validated['raw_time'];
            }

            $result = Result::create($resultData);

            // Calculate time and speed only for normal status with time
            if ($validated['status'] === 'normal' && isset($validated['raw_time'])) {
                $this->calculateResult($result);
            }

            // Recalculate positions
            $this->recalculateRacePositions($entrant->race_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Coureur ajouté avec succès',
                'result' => $result->load(['entrant.category', 'wave', 'race'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du coureur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the status of an existing result
     *
     * Expected payload:
     * {
     *   "status": "active|dns|dnf"
     * }
     */
    public function updateStatus(Result $result, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,dns,dnf',
        ]);

        DB::beginTransaction();

        try {
            // Map frontend status to database status
            $statusMap = [
                'active' => 'V',    // Validated
                'dns' => 'DNS',     // Did Not Start
                'dnf' => 'DNF',     // Did Not Finish
            ];

            $dbStatus = $statusMap[$validated['status']] ?? 'V';

            $result->update(['status' => $dbStatus]);

            // Recalculate positions for the race
            $this->recalculateRacePositions($result->race_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'result' => $result->load(['entrant.category', 'wave', 'race'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark runners as ABD (DNF) by bib numbers
     */
    public function markAsABD(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'bib_numbers' => 'required|array',
            'bib_numbers.*' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $updated = 0;
            $created = 0;
            $notFound = [];
            $errors = [];
            $racesAffected = [];

            foreach ($validated['bib_numbers'] as $bibNumber) {
                try {
                    // Trim and clean bib number
                    $cleanBib = trim($bibNumber);

                    // Find entrant by bib number in this event
                    $entrant = Entrant::where('event_id', $validated['event_id'])
                        ->where('bib_number', $cleanBib)
                        ->first();

                    if (!$entrant) {
                        $notFound[] = $cleanBib;
                        continue;
                    }

                    // Use the entrant's race_id (each entrant is registered for a specific race)
                    if (!$entrant->race_id) {
                        $errors[] = $cleanBib;
                        \Log::warning("ABD - Entrant sans race assignée: {$cleanBib}");
                        continue;
                    }

                    // Track affected races for position recalculation
                    if (!in_array($entrant->race_id, $racesAffected)) {
                        $racesAffected[] = $entrant->race_id;
                    }

                    // Check if result already exists for this entrant in their race
                    $result = Result::where('race_id', $entrant->race_id)
                        ->where('entrant_id', $entrant->id)
                        ->first();

                    if ($result) {
                        // Update existing result to DNF
                        $result->update(['status' => 'DNF']);
                        $updated++;
                    } else {
                        // Create new result with DNF status
                        Result::create([
                            'race_id' => $entrant->race_id,
                            'entrant_id' => $entrant->id,
                            'rfid_tag' => $entrant->rfid_tag ?? 'ABD',
                            'raw_time' => now(),
                            'status' => 'DNF',
                            'position' => null,
                            'wave_id' => $entrant->wave_id,
                            'is_manual' => true,
                        ]);
                        $created++;
                    }
                } catch (\Exception $e) {
                    $errors[] = $cleanBib;
                    \Log::error("Erreur ABD pour dossard {$cleanBib}: " . $e->getMessage());
                }
            }

            // Recalculate positions for all affected races
            foreach ($racesAffected as $raceId) {
                $this->recalculateRacePositions($raceId);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ABD enregistrés avec succès',
                'updated' => $updated,
                'created' => $created,
                'not_found' => $notFound,
                'errors' => $errors,
                'not_found_count' => count($notFound),
                'error_count' => count($errors),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement des ABD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Live feed pour écran speaker - retourne les derniers résultats
     */
    public function liveFeed(): JsonResponse
    {
        try {
            $results = Result::with(['entrant.category', 'race', 'reader'])
                ->where('status', 'V') // Only validated results
                ->orderBy('created_at', 'desc')
                ->limit(50) // Last 50 results
                ->get();

            // Add intermediate times for each result
            $results = $results->map(function($result) {
                $intermediates = [];

                if ($result->entrant_id && $result->race_id) {
                    // Get all checkpoint results for this entrant in this race
                    // Exclude the finish line (assume finish is the reader with highest checkpoint_order or no checkpoint_order)
                    $checkpointResults = Result::with('reader')
                        ->where('entrant_id', $result->entrant_id)
                        ->where('race_id', $result->race_id)
                        ->where('status', 'V')
                        ->whereHas('reader', function($q) {
                            $q->whereNotNull('checkpoint_order');
                        })
                        ->get()
                        ->sortBy('reader.checkpoint_order');

                    foreach ($checkpointResults as $checkpoint) {
                        if ($checkpoint->reader && $checkpoint->reader->checkpoint_order !== null) {
                            // Skip the current result's checkpoint to avoid duplication
                            if ($checkpoint->id === $result->id) {
                                continue;
                            }

                            $intermediates[] = [
                                'checkpoint' => $checkpoint->reader->location ?? 'KM' . $checkpoint->reader->distance_from_start,
                                'distance' => $checkpoint->reader->distance_from_start,
                                'time' => $checkpoint->formatted_time,
                                'order' => $checkpoint->reader->checkpoint_order,
                            ];
                        }
                    }
                }

                // Sort by order
                usort($intermediates, function($a, $b) {
                    return $a['order'] <=> $b['order'];
                });

                $result->intermediates = $intermediates;
                return $result;
            });

            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du chargement du flux live',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
