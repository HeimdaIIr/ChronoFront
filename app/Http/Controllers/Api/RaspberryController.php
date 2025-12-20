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
            return response()->json([
                'error' => 'Missing Serial header'
            ], 400);
        }

        // Get reader configuration
        $reader = Reader::getActiveConfig($readerSerial);

        if (!$reader) {
            return response()->json([
                'error' => 'Reader not configured or not active',
                'serial' => $readerSerial
            ], 404);
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

            // Find entrant
            $entrant = Entrant::where('bib_number', $bibNumber)
                ->where(function($q) use ($reader) {
                    // Match by race_id if reader has one, otherwise just by event
                    if ($reader->race_id) {
                        $q->where('race_id', $reader->race_id);
                    }
                })
                ->first();

            if (!$entrant) {
                Log::warning("Entrant not found for bib {$bibNumber}");
                $skipped++;
                continue;
            }

            // Check anti-rebounce
            if (!$this->checkAntiRebounce($entrant, $reader, $datetime)) {
                Log::info("Anti-rebounce triggered for bib {$bibNumber}");
                $skipped++;
                continue;
            }

            // Get passage number
            $passageNumber = $this->getPassageNumber($entrant, $reader);

            // Create result
            $result = Result::create([
                'race_id' => $entrant->race_id,
                'entrant_id' => $entrant->id,
                'wave_id' => $entrant->wave_id,
                'reader_id' => $reader->id,
                'rfid_tag' => $entrant->rfid_tag,
                'serial' => $serial,
                'reader_location' => $reader->location,
                'raw_time' => $datetime,
                'lap_number' => $passageNumber,
                'is_manual' => false,
                'status' => 'V',
            ]);

            // Calculate time and speed
            $this->calculateResult($result);

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

        // Log to file (optional, for debugging)
        $this->logToFile($readerSerial, $reader->location, ob_get_clean());

        return response()->json([
            'success' => true,
            'reader' => $readerSerial,
            'location' => $reader->location,
            'processed' => $processed,
            'skipped' => $skipped,
            'results' => $results
        ]);
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
     */
    private function checkAntiRebounce(Entrant $entrant, Reader $reader, Carbon $currentTime): bool
    {
        $lastResult = Result::where('entrant_id', $entrant->id)
            ->where('reader_id', $reader->id)
            ->orderBy('raw_time', 'desc')
            ->first();

        if (!$lastResult) {
            return true; // No previous passage, allow
        }

        $lastTime = Carbon::parse($lastResult->raw_time);
        $secondsSinceLast = $currentTime->diffInSeconds($lastTime);

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
        $result->load(['wave', 'race', 'entrant']);

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

            // Calculate speed based on lap distance
            if ($result->race->distance > 0 && $result->lap_time > 0) {
                $hours = $result->lap_time / 3600;
                $result->speed = round($result->race->distance / $hours, 2);
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
}
