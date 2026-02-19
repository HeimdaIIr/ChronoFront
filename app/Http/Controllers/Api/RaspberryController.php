<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reader;
use App\Models\Entrant;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\Api\RfidLogController;
use App\Models\RfidDetection;



class RaspberryController extends Controller
{
    /**
     * Handle RFID reader detections from Raspberry Pi
     * Endpoint compatible with Impinj Speedway reader format
     *
     * Expected JSON format:
     * [
     *   {"serial": "2000003", "timestamp": 743084027.091},
     *   {"serial": "2000125", "timestamp": 743084028.234}
     * ]
     */
    public function store(Request $request): JsonResponse
    {
        // Get reader serial from header
        $readerSerial = $request->header('Serial');

        if (!$readerSerial) {
            RfidLogController::logRequest($request, 400);
            return response()->json(['error' => 'Missing Serial header'], 400);
        }

        // Get reader configuration
        $reader = Reader::getActiveConfig($readerSerial);

        if (!$reader) {
            $errorResponse = [
                'error' => 'Reader not configured or not active',
                'serial' => $readerSerial
            ];
            RfidLogController::logRequest($request, 404, $errorResponse);
            return response()->json($errorResponse, 404);
        }

        // Mark reader as tested
        $reader->markAsTested();

        // Get JSON data from request body
        $detections = $request->json()->all();

        if (!is_array($detections)) {
            return response()->json([
                'error' => 'Invalid JSON format, expected array'
            ], 400);
        }

        $results = [];
        $processed = 0;
        $skipped = 0;
        $racesToRecalculate = [];
        $rfidDetectionsToStore = []; // Collect audit data, bulk insert after commit

        ob_start();

        // PRE-LOAD: Batch fetch all entrants for this batch (1 query instead of N)
        $allBibs = collect($detections)
            ->map(function($d) { return $this->serialToBib(trim($d['serial'] ?? '', '[]')); })
            ->filter(function($bib) { return $bib && $bib > 0; })
            ->unique()
            ->values()
            ->map(function($bib) { return (string) $bib; }); // Cast to string to match DB column type

        $entrantsQuery = Entrant::whereIn('bib_number', $allBibs->toArray())
            ->with(['race', 'wave']);
        if ($reader->race_id) {
            $entrantsQuery->where('race_id', $reader->race_id);
        } elseif ($reader->event_id) {
            // Scope to this reader's event to avoid loading entrants from other events
            $entrantsQuery->where('event_id', $reader->event_id);
        }
        $entrantsMap = $entrantsQuery->get()->keyBy('bib_number');

        // Single DB transaction for the entire batch (1 commit instead of N)
        \DB::beginTransaction();
        try {

        foreach ($detections as $detection) {
            $serial = trim($detection['serial'] ?? '', '[]');
            $timestamp = $detection['timestamp'] ?? null;

            if (empty($serial) || empty($timestamp)) {
                $skipped++;
                continue;
            }

            // Convert serial to bib number (remove "200" prefix)
            $bibNumber = $this->serialToBib($serial);

            if (!$bibNumber || $bibNumber <= 0) {
                $skipped++;
                continue;
            }

            // Convert timestamp to datetime
            $datetime = $this->timestampToDatetime($timestamp);

            // Get milliseconds
            $milliseconds = $this->extractMilliseconds($timestamp);

            // Find entrant from pre-loaded map (0 queries)
            $entrant = $entrantsMap[(string) $bibNumber] ?? null;

            if (!$entrant) {
                $skipped++;
                continue;
            }

            // Determine effective location
            $effectiveLocation = $this->determineEffectiveLocation($reader, $datetime, $entrant);

            if ($effectiveLocation === null) {
                $skipped++;
                continue;
            }

            // Check if detection is from before the race start (stale/cached RFID data)
            $race = $entrant->race;
            if ($race && $race->start_time) {
                $raceStart = Carbon::parse($race->start_time);
                if ($datetime->lt($raceStart)) {
                    $skipped++;
                    continue;
                }
            }

            // Block non-DEPART detections if race hasn't started yet (no TOP départ)
            if ($race && !$race->start_time && $effectiveLocation !== 'DEPART') {
                $skipped++;
                continue;
            }

            // Check anti-rebounce (now with effective location)
            $antiRebounceCheck = $this->checkAntiRebounce($entrant, $reader, $datetime, $effectiveLocation);
            if (!$antiRebounceCheck) {
                $skipped++;
                continue;
            }

            // Check race duration for infinite_loop type
            if ($race && $race->type === 'infinite_loop' && $race->duration && $race->start_time) {
                $raceStartTime = Carbon::parse($race->start_time);
                $raceDurationSeconds = $race->duration * 60;
                $elapsedSeconds = $datetime->diffInSeconds($raceStartTime);

                if ($elapsedSeconds > $raceDurationSeconds) {
                    $skipped++;
                    continue;
                }
            }

            // Get passage number
            $passageNumber = $this->getPassageNumber($entrant, $reader);

            // Check if max laps exceeded for n_laps races
            if ($race && $race->type === 'n_laps' && $race->laps > 0) {
                if ($passageNumber > $race->laps) {
                    $skipped++;
                    continue;
                }
            }

            if ($effectiveLocation === 'DEPART') {
                // DEPART: Update entrant's individual start time
                $entrant->start_time = $datetime->format('H:i:s');
                $entrant->save();

                $results[] = [
                    'bib' => $bibNumber,
                    'action' => 'start_time_updated',
                    'time' => $datetime->format('Y-m-d H:i:s'),
                    'location' => $effectiveLocation,
                ];

                $processed++;

                // Collect audit data (will be bulk inserted after commit)
                $rfidDetectionsToStore[] = [
                    'reader_id' => $reader->id,
                    'serial' => $serial,
                    'raw_time' => $datetime->format('Y-m-d H:i:s'),
                    'entrant_id' => $entrant->id,
                    'wave_id' => $entrant->wave_id,
                    'processed' => true,
                    'created_result_id' => null,
                    'action_taken' => 'start_time_updated',
                    'error_message' => null,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ];

                continue;
            }

            // For ARRIVEE and Inter checkpoints: Create Result
            if ($race && !in_array($race->type, ['n_laps', 'infinite_loop'])) {
                $existingResult = Result::where('entrant_id', $entrant->id)
                    ->where('reader_location', $effectiveLocation)
                    ->where('race_id', $entrant->race_id)
                    ->first();

                if ($existingResult) {
                    $skipped++;
                    continue;
                }

                // FALLBACK: For Mode 2, if entrant has no start_time, use wave's real_start_time
                if ($reader->mode === 'single_reader_waves' && !$entrant->start_time) {
                    $wave = $entrant->wave;
                    if ($wave && $wave->real_start_time) {
                        $realStartTime = Carbon::parse($wave->real_start_time);
                        $entrant->start_time = $realStartTime->format('H:i:s');
                        $entrant->save();
                    }
                }
            }

            $result = Result::create([
                'race_id' => $entrant->race_id,
                'entrant_id' => $entrant->id,
                'wave_id' => $entrant->wave_id,
                'reader_id' => $reader->id,
                'rfid_tag' => $entrant->rfid_tag,
                'serial' => $serial,
                'reader_location' => $effectiveLocation,
                'raw_time' => $datetime,
                'lap_number' => $passageNumber,
                'is_manual' => false,
                'status' => 'V',
            ]);

            // Set relations from pre-loaded data (avoid re-querying DB)
            $result->setRelation('race', $entrant->race);
            $result->setRelation('wave', $entrant->wave);
            $result->setRelation('entrant', $entrant);

            // Calculate time and speed (uses pre-set relations, no extra queries)
            $this->calculateResult($result);

            // Collect audit data (will be bulk inserted after commit)
            $rfidDetectionsToStore[] = [
                'reader_id' => $reader->id,
                'serial' => $serial,
                'raw_time' => $datetime->format('Y-m-d H:i:s'),
                'entrant_id' => $entrant->id,
                'wave_id' => $entrant->wave_id,
                'processed' => true,
                'created_result_id' => $result->id,
                'action_taken' => 'result_created',
                'error_message' => null,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];

            // Track races that need position recalculation (done once after batch)
            if ($race) {
                $racesToRecalculate[$race->id] = $race;
            }

            // Log for compatibility with old system
            $logEntry = "[{$serial}]:a" . date('YmdHis', intval($timestamp)) . $milliseconds;

            $results[] = [
                'bib' => $bibNumber,
                'passage' => $passageNumber,
                'time' => $datetime->format('Y-m-d H:i:s'),
                'location' => $reader->location,
                'log' => $logEntry
            ];

            $processed++;

            // Echo for reader (compatibility)
            echo $logEntry . "\n";
        }

        // Recalculate positions ONCE for all affected races (inside transaction)
        foreach ($racesToRecalculate as $raceId => $raceObj) {
            $this->recalculateRacePositions($raceId);
        }

        \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            Log::error('RFID batch processing failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            ob_end_clean();
            return response()->json(['error' => 'Processing failed'], 500);
        }

        $bufferedOutput = ob_get_clean();

        $responseData = [
            'success' => true,
            'reader' => $readerSerial,
            'location' => $reader->location,
            'processed' => $processed,
            'skipped' => $skipped,
            'results' => $results
        ];

        // Defer ALL non-critical work AFTER the HTTP response is sent
        $logReaderSerial = $readerSerial;
        $logReaderLocation = $reader->location;
        $logDetectionCount = count($detections);
        $requestData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'serial' => $request->header('Serial'),
            'json_data' => $request->json() ? $request->json()->all() : [],
            'user_agent' => $request->userAgent(),
        ];

        app()->terminating(function () use (
            $logReaderSerial, $logReaderLocation, $processed, $skipped,
            $logDetectionCount, $bufferedOutput, $requestData, $responseData,
            $rfidDetectionsToStore
        ) {
            // Bulk insert audit detections (deferred, outside transaction)
            if (!empty($rfidDetectionsToStore)) {
                try {
                    RfidDetection::insert($rfidDetectionsToStore);
                } catch (\Exception $e) {
                    Log::warning('Failed to insert RFID audit detections', ['error' => $e->getMessage()]);
                }
            }

            // Log to file
            $this->logToFile($logReaderSerial, $logReaderLocation, $bufferedOutput);

            // Log to RFID raw logs for /rfidlive-ultra display
            if ($logDetectionCount > 0) {
                RfidLogController::logRequestDeferred($requestData, 200, $responseData);
            }
        });

        return response()->json($responseData);
    }

    /**
     * Convert serial to bib number (remove "200" prefix)
     */
    private function serialToBib(string $serial): ?int
    {
        $validPrefix = "200";

        if (strpos($serial, $validPrefix) === 0) {
            $bib = substr($serial, 3); // Remove "200" prefix
            $bib = ltrim($bib, "0");   // Remove leading zeros
            return (int) $bib;
        }

        return null;
    }

    /**
     * Convert timestamp to Carbon datetime
     * Unix timestamp is UTC - convert to app timezone for SQLite storage
     */
    private function timestampToDatetime(float $timestamp): Carbon
    {
        // Create from UTC timestamp and convert to app timezone
        // This ensures SQLite stores the correct local time
        return Carbon::createFromTimestamp(intval($timestamp))->setTimezone(config('app.timezone'));
    }

    /**
     * Extract milliseconds from timestamp
     */
    private function extractMilliseconds(float $timestamp): string
    {
        $parts = explode(".", (string) $timestamp);
        $ms = $parts[1] ?? "0";

        // Format with 3 digits
        if ($ms == 0) {
            $ms = "000";
        } elseif (strlen($ms) == 1) {
            $ms = $ms . "00";
        } elseif (strlen($ms) == 2) {
            $ms = $ms . "0";
        } elseif (strlen($ms) > 3) {
            $ms = substr($ms, 0, 3);
        }

        return $ms;
    }

    /**
     * Check if enough time has passed since last read (anti-rebounce)
     * For multi-lap races: DISABLED - we rely on max_laps validation instead
     * For single-passage races: uses configured anti-rebounce PER CHECKPOINT
     *
     * IMPORTANT: Anti-rebounce is now PER CHECKPOINT (reader_location), not per reader!
     * This allows a runner to be detected at Inter1, then immediately at ARRIVEE
     * even if both checkpoints use the same physical reader (with time ranges)
     */
    private function checkAntiRebounce(Entrant $entrant, Reader $reader, Carbon $currentTime, string $effectiveLocation): bool
    {
        // Check anti-rebounce PER CHECKPOINT for all race types
        // This prevents duplicate detections at the SAME checkpoint, but allows
        // detections at DIFFERENT checkpoints (Inter1, Inter2, ARRIVEE, etc.)
        // For multi-lap races, this also prevents duplicate RFID reads within
        // the anti-rebounce window while allowing legitimate successive laps
        $lastResult = Result::where('entrant_id', $entrant->id)
            ->where('reader_id', $reader->id)
            ->where('reader_location', $effectiveLocation)
            ->orderBy('raw_time', 'desc')
            ->first();

        if (!$lastResult) {
            return true; // No previous passage at this checkpoint, allow
        }

        $lastTime = Carbon::parse($lastResult->raw_time);
        $secondsSinceLast = abs($currentTime->diffInSeconds($lastTime));

        return $secondsSinceLast >= $reader->anti_rebounce_seconds;
    }

    /**
     * Get the next passage number for this entrant at this reader
     */
    private function getPassageNumber(Entrant $entrant, Reader $reader): int
    {
        $lastPassage = Result::where('entrant_id', $entrant->id)
            ->where('reader_id', $reader->id)
            ->max('lap_number');

        return ($lastPassage ?? 0) + 1;
    }

    /**
     * Calculate time and speed for a result
     */
    private function calculateResult(Result $result): void
    {
        // Only load relations that aren't already set (avoids 3 queries per detection)
        $toLoad = [];
        if (!$result->relationLoaded('wave')) $toLoad[] = 'wave';
        if (!$result->relationLoaded('race')) $toLoad[] = 'race';
        if (!$result->relationLoaded('entrant')) $toLoad[] = 'entrant';
        if (!empty($toLoad)) {
            $result->load($toLoad);
        }

        // Check race type for different calculation logic
        $raceType = $result->race->type ?? '1_passage';

        if (in_array($raceType, ['n_laps', 'infinite_loop'])) {
            // === LOGIC FOR N LAPS AND INFINITE LOOP ===

            if ($result->lap_number == 1) {
                // TOUR 1: lap_time = passage time - start time (TOP or individual start)
                $result->calculateTime(); // This sets calculated_time based on start time
                $result->lap_time = $result->calculated_time;
            } else {
                // TOURS SUIVANTS: lap_time = passage actuel - passage précédent
                $previousLap = Result::where('race_id', $result->race_id)
                    ->where('entrant_id', $result->entrant_id)
                    ->where('lap_number', $result->lap_number - 1)
                    ->first();

                if ($previousLap) {
                    // Calculate lap time: current passage - previous passage
                    $currentPassage = Carbon::parse($result->raw_time);
                    $previousPassage = Carbon::parse($previousLap->raw_time);
                    $result->lap_time = abs($currentPassage->diffInSeconds($previousPassage));

                    // Calculate total time: previous total + current lap time
                    $result->calculated_time = $previousLap->calculated_time + $result->lap_time;
                } else {
                    // Fallback if previous lap not found
                    $result->calculateTime();
                    $result->lap_time = $result->calculated_time;
                }
            }

            // Calculate average speed based on TOTAL distance and TOTAL time
            // This gives the true average speed (not average of lap speeds)
            if ($result->race->distance > 0 && $result->calculated_time > 0) {
                $totalDistance = $result->race->distance * $result->lap_number; // Total km covered
                $hours = $result->calculated_time / 3600; // Total time in hours
                $result->speed = round($totalDistance / $hours, 2);
            }

        } else {
            // === LOGIC FOR 1_PASSAGE (original logic) ===

            // Calculate time from individual start, wave start or race start (TOP DÉPART)
            $result->calculateTime();

            // Calculate speed
            if ($result->race && $result->race->distance > 0 && $result->calculated_time > 0) {
                $result->calculateSpeed($result->race->distance);
            }

            // Calculate lap time if not first lap
            if ($result->lap_number > 1) {
                $previousLap = Result::where('race_id', $result->race_id)
                    ->where('entrant_id', $result->entrant_id)
                    ->where('reader_id', $result->reader_id)
                    ->where('lap_number', $result->lap_number - 1)
                    ->first();

                if ($previousLap && $previousLap->calculated_time && $result->calculated_time) {
                    $result->lap_time = $result->calculated_time - $previousLap->calculated_time;
                }
            } else {
                $result->lap_time = $result->calculated_time;
            }
        }

        $result->save();
    }

    /**
     * Log to file for debugging (optional)
     */
    private function logToFile(string $readerSerial, string $location, string $content): void
    {
        $logDir = storage_path('logs/rfid');

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/reader-' . $readerSerial . '-' . date('Ymd') . '.txt';
        file_put_contents($logFile, $content, FILE_APPEND);
    }

	 /**
     * Store raw RFID detection in database
     */
    private function storeRfidDetection(
        Reader $reader, 
        string $serial, 
        Carbon $datetime, 
        ?Entrant $entrant = null,
        ?int $waveId = null,
        string $actionTaken = null,
        ?int $createdResultId = null,
        ?string $errorMessage = null
    ): RfidDetection {
        return RfidDetection::create([
            'reader_id' => $reader->id,
            'serial' => $serial,
            'raw_time' => $datetime,
            'entrant_id' => $entrant ? $entrant->id : null,
            'wave_id' => $waveId,
            'processed' => true,
            'created_result_id' => $createdResultId,
            'action_taken' => $actionTaken,
            'error_message' => $errorMessage,
        ]);
    }

	
    /**
     * Determine effective location based on reader mode
     *
     * 4 MODES:
     * 1. single_reader_simple: Plages horaires uniquement (pas de vagues)
     * 2. single_reader_waves: Vagues + TOP départ avec fenêtre ±X min
     * 3. multi_reader: Multi lecteurs, chaque lecteur = checkpoint fixe
     * 4. multi_reader_waves: Multi lecteurs + vagues, départ groupé (heure exacte)
     */
    private function determineEffectiveLocation(Reader $reader, Carbon $datetime, ?Entrant $entrant = null): ?string
    {
        $readerMode = $reader->mode ?? 'multi_reader'; // Default if not set

        switch ($readerMode) {
            case 'single_reader_simple':
                // MODE 1: Plages horaires uniquement, pas de vagues
                return $this->determineByTimeRanges($reader, $datetime);

            case 'single_reader_waves':
                // MODE 2: Vagues + TOP départ avec fenêtre ±X min
                if ($entrant && $entrant->wave_id) {
                    $wave = $entrant->wave;
                    if ($wave && $wave->real_start_time) {
                        return $this->determineByWaveWindow($wave, $datetime);
                    } else {
                        return null;
                    }
                }
                // Pas de vague assignée = IGNORE
                return null;

            case 'multi_reader':
                // MODE 3: Multi lecteurs sans vagues, chaque lecteur = checkpoint fixe
                return $reader->location; // DEPART, INTER1, ARRIVEE, etc.

            case 'multi_reader_waves':
                // MODE 4: Multi lecteurs avec vagues, départ groupé
                // Chaque lecteur retourne toujours sa location fixe
                // Pour le lecteur DEPART: on n'enregistre PAS de start_time individuel
                // On utilisera real_start_time de la vague pour tous les coureurs
                // Block non-DEPART detections if wave hasn't had TOP départ yet
                if ($reader->location !== 'DEPART' && $entrant && $entrant->wave_id) {
                    $wave = $entrant->wave;
                    if ($wave && !$wave->real_start_time) {
                        return null;
                    }
                }
                return $reader->location;

            default:
                // Fallback: comportement multi_reader par défaut
                Log::warning("Unknown reader mode, falling back to multi_reader", [
                    'reader_mode' => $readerMode,
                ]);
                return $reader->location;
        }
    }

    /**
     * Determine location by reader time ranges (Mode 1: single_reader_simple)
     */
    private function determineByTimeRanges(Reader $reader, Carbon $datetime): ?string
    {
        $currentTime = $datetime->format('H:i:s');

        // Check if reader has time ranges configured
        $hasDepartRange = $reader->depart_time_start && $reader->depart_time_end;
        $hasArrivalRange = $reader->arrival_time_start;

        // If no time ranges configured, use the fixed location
        if (!$hasDepartRange && !$hasArrivalRange) {
            return $reader->location;
        }

        // IMPORTANT: Add :00 seconds to database times for proper comparison
        $departStart = $reader->depart_time_start ? $reader->depart_time_start . ':00' : null;
        $departEnd = $reader->depart_time_end ? $reader->depart_time_end . ':00' : null;
        $arrivalStart = $reader->arrival_time_start ? $reader->arrival_time_start . ':00' : null;
        $arrivalEnd = $reader->arrival_time_end ? $reader->arrival_time_end . ':00' : null;

        // Check DEPART time range
        if ($hasDepartRange) {
            $isInDepartRange = $currentTime >= $departStart && $currentTime <= $departEnd;

            if ($isInDepartRange) {
                return 'DEPART';
            }
        }

        // Check ARRIVEE time range
        if ($hasArrivalRange) {
            $isAfterArrivalStart = $currentTime >= $arrivalStart;

            if ($arrivalEnd) {
                $isBeforeArrivalEnd = $currentTime <= $arrivalEnd;
                $isInArrivalRange = $isAfterArrivalStart && $isBeforeArrivalEnd;
            } else {
                // No end time = active until end of day
                $isInArrivalRange = $isAfterArrivalStart;
            }

            if ($isInArrivalRange) {
                return 'ARRIVEE';
            }
        }

        // If time ranges ARE configured but none matches = we're in a "gap" → IGNORE detection
        return null;
    }

    /**
     * Determine location by wave window (Mode 2: single_reader_waves)
     * Uses ±depart_window_minutes around real_start_time
     */
    private function determineByWaveWindow($wave, Carbon $datetime): ?string
    {
        $realStartTime = Carbon::parse($wave->real_start_time);
        $windowMinutes = $wave->depart_window_minutes ?? 5;

        $departWindowStart = $realStartTime->copy()->subMinutes($windowMinutes);
        $departWindowEnd = $realStartTime->copy()->addMinutes($windowMinutes);

        // Detection within DEPART window?
        if ($datetime >= $departWindowStart && $datetime <= $departWindowEnd) {
            return 'DEPART';
        }

        // After DEPART window = ARRIVEE
        if ($datetime > $departWindowEnd) {
            return 'ARRIVEE';
        }

        // Before window = IGNORE
        return null;
    }


    /**
     * Recalculate positions for a race
     * Called automatically when a runner finishes all laps
     */
    private function recalculateRacePositions(int $raceId): void
    {
        try {
            $race = \App\Models\Race::findOrFail($raceId);

            // Check if race has intermediate checkpoints (readers with location other than ARRIVEE)
            $hasIntermediateCheckpoints = Reader::where('event_id', $race->event_id)
                ->where('location', '!=', 'ARRIVEE')
                ->where('location', '!=', 'DEPART')
                ->where('is_active', true)
                ->exists();

            // Get all results for this race
            $allResults = Result::where('race_id', $raceId)
                ->where('status', 'V')
                ->with(['entrant.category'])
                ->get();

            // If race has intermediate checkpoints (like Inter1, Inter2, etc.)
            // ONLY rank runners who have an ARRIVEE time
            if ($hasIntermediateCheckpoints && $race->type === '1_passage') {
                // Get only ARRIVEE results for ranking
                $results = $allResults
                    ->where('reader_location', 'ARRIVEE')
                    ->sortBy('calculated_time')
                    ->values();
            } elseif ($race->type === 'infinite_loop') {
                // INFINITE LOOP: Sort by distance (laps), then by time
                $entrantResults = $allResults->groupBy('entrant_id')
                    ->map(function ($entrantResults) use ($race) {
                        if ($race->best_time) {
                            return $entrantResults->sortBy('calculated_time')->first();
                        } else {
                            return $entrantResults->sortByDesc('lap_number')->first();
                        }
                    });

                $results = $entrantResults->sort(function ($a, $b) {
                    // Primary: lap count (descending - more laps = better)
                    if ($a->lap_number != $b->lap_number) {
                        return $b->lap_number <=> $a->lap_number;
                    }
                    // Secondary: time (ascending - faster is better)
                    return $a->calculated_time <=> $b->calculated_time;
                })->values();

            } elseif ($race->type === 'n_laps') {
                // N LAPS: Sort by lap count descending, then by time ascending
                // Runners with more laps are always ranked above runners with fewer laps
                $entrantResults = $allResults->groupBy('entrant_id')
                    ->map(function ($entrantResults) use ($race) {
                        if ($race->best_time) {
                            return $entrantResults->sortBy('calculated_time')->first();
                        } else {
                            return $entrantResults->sortByDesc('lap_number')->first();
                        }
                    });

                $results = $entrantResults->sort(function ($a, $b) {
                    // Primary: lap count (descending - more laps = better)
                    if ($a->lap_number != $b->lap_number) {
                        return $b->lap_number <=> $a->lap_number;
                    }
                    // Secondary: time (ascending - faster is better)
                    return $a->calculated_time <=> $b->calculated_time;
                })->values();

            } else {
                // 1_PASSAGE without intermediate checkpoints
                $results = $allResults
                    ->groupBy('entrant_id')
                    ->map(function ($entrantResults) use ($race) {
                        if ($race->best_time) {
                            return $entrantResults->sortBy('calculated_time')->first();
                        } else {
                            return $entrantResults->sortByDesc('lap_number')->first();
                        }
                    })
                    ->sortBy('calculated_time')
                    ->values();
            }

            // Bulk update positions using CASE WHEN (2 queries instead of N individual UPDATEs)
            if ($results->isNotEmpty()) {
                // Build position assignments
                $positionCases = [];
                $catPositionCases = [];
                $ids = [];

                $position = 1;
                foreach ($results as $result) {
                    $ids[] = $result->id;
                    $positionCases[] = "WHEN {$result->id} THEN {$position}";
                    $position++;
                }

                // Calculate category positions
                $resultsByCategory = $results->groupBy(function($r) { return $r->entrant ? $r->entrant->category_id : null; });
                foreach ($resultsByCategory as $categoryId => $categoryResults) {
                    $catPos = 1;
                    foreach ($categoryResults as $result) {
                        $catPositionCases[] = "WHEN {$result->id} THEN {$catPos}";
                        $catPos++;
                    }
                }

                $idList = implode(',', $ids);
                $posCases = implode(' ', $positionCases);
                $catCases = implode(' ', $catPositionCases);

                \DB::connection('tenant')->statement("UPDATE results SET position = CASE id {$posCases} END, category_position = CASE id {$catCases} END WHERE id IN ({$idList})");
            }
        } catch (\Throwable $e) {
            Log::error("Failed to recalculate positions", [
                'race_id' => $raceId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
