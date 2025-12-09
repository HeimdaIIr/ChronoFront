<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reader;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReaderController extends Controller
{
    /**
     * Display a listing of readers
     */
    public function index(): JsonResponse
    {
        $readers = Reader::with(['event', 'race'])
            ->orderBy('location')
            ->get()
            ->map(function ($reader) {
                // Check reader connection status
                $now = now();

                // DEBUG: Add debug info to response
                $reader->debug_now = $now->toDateTimeString();
                $reader->debug_date_test = $reader->date_test ? $reader->date_test->toDateTimeString() : null;
                $reader->debug_diff_seconds = $reader->date_test ? abs($now->diffInSeconds($reader->date_test)) : null;

                if (!$reader->date_test) {
                    // Never received data from this reader
                    $reader->is_online = false;
                    $reader->connection_status = 'never_connected';
                } elseif (abs($now->diffInSeconds($reader->date_test)) < 20) {
                    // Received data within last 20 seconds (2 pings missed max)
                    // Use abs() to handle timezone conversion issues
                    $reader->is_online = true;
                    $reader->connection_status = 'online';
                } else {
                    // Last data older than 20 seconds
                    $reader->is_online = false;
                    $reader->connection_status = 'offline';
                    $reader->last_seen = $reader->date_test->diffForHumans();
                }
                return $reader;
            });

        return response()->json($readers);
    }

    /**
     * Store a newly created reader
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'serial' => 'required|string|max:50',
            'name' => 'nullable|string|max:200',
            'network_type' => 'nullable|in:local,vpn,custom',
            'custom_ip' => 'nullable|string|max:50|ip',
            'http_username' => 'nullable|string|max:100',
            'http_password' => 'nullable|string|max:255',
            'event_id' => 'required|exists:events,id',
            'race_id' => 'nullable|exists:races,id',
            'location' => 'required|string|max:100',
            'distance_from_start' => 'required|numeric|min:0',
            'anti_rebounce_seconds' => 'nullable|integer|min:0',
            'date_min' => 'nullable|date',
            'date_max' => 'nullable|date|after_or_equal:date_min',
            'is_active' => 'nullable|boolean',
        ]);

        // Calculate checkpoint_order based on distance for this event
        $validated['checkpoint_order'] = $this->calculateCheckpointOrder(
            $validated['event_id'],
            $validated['distance_from_start'],
            null // no reader id for new reader
        );

        $reader = Reader::create($validated);

        return response()->json($reader, 201);
    }

    /**
     * Display the specified reader
     */
    public function show(Reader $reader): JsonResponse
    {
        $reader->load(['event', 'race']);
        return response()->json($reader);
    }

    /**
     * Update the specified reader
     */
    public function update(Request $request, Reader $reader): JsonResponse
    {
        $validated = $request->validate([
            'serial' => 'sometimes|string|max:50',
            'name' => 'nullable|string|max:200',
            'network_type' => 'sometimes|in:local,vpn,custom',
            'custom_ip' => 'nullable|string|max:50|ip',
            'http_username' => 'nullable|string|max:100',
            'http_password' => 'nullable|string|max:255',
            'event_id' => 'sometimes|exists:events,id',
            'race_id' => 'nullable|exists:races,id',
            'location' => 'sometimes|string|max:100',
            'distance_from_start' => 'sometimes|numeric|min:0',
            'anti_rebounce_seconds' => 'nullable|integer|min:0',
            'date_min' => 'nullable|date',
            'date_max' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        // Recalculate checkpoint_order if distance or event changed
        if (isset($validated['distance_from_start']) || isset($validated['event_id'])) {
            $eventId = $validated['event_id'] ?? $reader->event_id;
            $distance = $validated['distance_from_start'] ?? $reader->distance_from_start;
            $validated['checkpoint_order'] = $this->calculateCheckpointOrder($eventId, $distance, $reader->id);
        }

        $reader->update($validated);

        return response()->json($reader);
    }

    /**
     * Remove the specified reader
     */
    public function destroy(Reader $reader): JsonResponse
    {
        $reader->delete();
        return response()->json(['message' => 'Reader deleted successfully']);
    }

    /**
     * Get readers for a specific event
     */
    public function byEvent(int $eventId): JsonResponse
    {
        $readers = Reader::where('event_id', $eventId)
            ->with('race')
            ->orderBy('location')
            ->get()
            ->map(function ($reader) {
                // Check reader connection status
                $now = now();

                // DEBUG: Add debug info to response
                $reader->debug_now = $now->toDateTimeString();
                $reader->debug_date_test = $reader->date_test ? $reader->date_test->toDateTimeString() : null;
                $reader->debug_diff_seconds = $reader->date_test ? abs($now->diffInSeconds($reader->date_test)) : null;

                if (!$reader->date_test) {
                    // Never received data from this reader
                    $reader->is_online = false;
                    $reader->connection_status = 'never_connected';
                } elseif (abs($now->diffInSeconds($reader->date_test)) < 20) {
                    // Received data within last 20 seconds (2 pings missed max)
                    // Use abs() to handle timezone conversion issues
                    $reader->is_online = true;
                    $reader->connection_status = 'online';
                } else {
                    // Last data older than 20 seconds
                    $reader->is_online = false;
                    $reader->connection_status = 'offline';
                    $reader->last_seen = $reader->date_test->diffForHumans();
                }
                return $reader;
            });

        return response()->json($readers);
    }

    /**
     * Ping all readers for an event
     */
    public function pingAll(int $eventId): JsonResponse
    {
        $readers = Reader::where('event_id', $eventId)->get();
        $results = [];

        foreach ($readers as $reader) {
            // Get IP from reader model (supports local/vpn/custom)
            $readerIp = $reader->calculated_ip;

            // Try to ping with curl (more reliable)
            $url = "http://{$readerIp}";
            $ch = curl_init($url);

            $curlOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 1,  // 1 second timeout for bulk ping
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_NOBODY => true,  // HEAD request, plus rapide
                CURLOPT_FAILONERROR => false,
            ];

            // Ajouter l'authentification HTTP Basic si les credentials existent
            if ($reader->http_username && $reader->http_password) {
                $curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
                $curlOptions[CURLOPT_USERPWD] = "{$reader->http_username}:{$reader->http_password}";
            }

            curl_setopt_array($ch, $curlOptions);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Si on reçoit un code HTTP (même 403, 404, etc.), le lecteur est joignable
            if ($httpCode > 0) {
                $reader->update([
                    'test_terrain' => true,
                    'date_test' => now(),
                ]);
                $results[] = [
                    'reader_id' => $reader->id,
                    'serial' => $reader->serial,
                    'ip' => $readerIp,
                    'network_type' => $reader->network_type,
                    'http_code' => $httpCode,
                    'status' => 'online'
                ];
            } else {
                $results[] = [
                    'reader_id' => $reader->id,
                    'serial' => $reader->serial,
                    'ip' => $readerIp,
                    'network_type' => $reader->network_type,
                    'status' => 'offline'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    /**
     * Ping a reader to check if it's online
     */
    public function ping(Reader $reader): JsonResponse
    {
        // Get IP from reader model (supports local/vpn/custom)
        $readerIp = $reader->calculated_ip;

        try {
            $url = "http://{$readerIp}";
            $ch = curl_init($url);

            $curlOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_FAILONERROR => false,
            ];

            // Ajouter l'authentification HTTP Basic si les credentials existent
            if ($reader->http_username && $reader->http_password) {
                $curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
                $curlOptions[CURLOPT_USERPWD] = "{$reader->http_username}:{$reader->http_password}";
            }

            curl_setopt_array($ch, $curlOptions);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Si on reçoit un code HTTP (même 403, 404, etc.), le lecteur est joignable
            if ($httpCode > 0) {
                $reader->update([
                    'test_terrain' => true,
                    'date_test' => now(),
                ]);

                $status = match($httpCode) {
                    200 => ' (Authenticated ✓)',
                    401 => ' (Auth required)',
                    403 => ' (Blocked by proxy)',
                    default => ''
                };

                return response()->json([
                    'success' => true,
                    'message' => "Reader is online (HTTP {$httpCode}){$status}",
                    'ip' => $readerIp,
                    'network_type' => $reader->network_type,
                    'http_code' => $httpCode,
                    'reader' => $reader
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Reader is offline or unreachable: ' . ($curlError ?: 'No response'),
                    'ip' => $readerIp,
                    'network_type' => $reader->network_type
                ], 503);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error pinging reader: ' . $e->getMessage(),
                'ip' => $readerIp,
                'network_type' => $reader->network_type
            ], 500);
        }
    }

    /**
     * Get configuration for a reader (auto-configuration endpoint)
     * Used by Raspberry Pi to auto-configure on boot
     */
    public function getConfig(Request $request): JsonResponse
    {
        $serial = $request->header('Serial');

        if (!$serial) {
            return response()->json([
                'error' => 'Serial header required',
                'usage' => 'Send request with header "Serial: 120"'
            ], 400);
        }

        // Find active reader for this serial
        $reader = Reader::where('serial', $serial)
                        ->where('is_active', true)
                        ->with('event')
                        ->first();

        if (!$reader) {
            return response()->json([
                'error' => 'No active configuration found for this reader',
                'serial' => $serial,
                'help' => 'Create a reader with this serial in ChronoFront and activate it'
            ], 404);
        }

        // Return configuration for the Raspberry Pi
        return response()->json([
            'target_url' => url('/api/raspberry'),
            'target_method' => 'PUT',
            'serial' => $reader->serial,
            'event_id' => $reader->event_id,
            'event_name' => $reader->event?->name,
            'race_id' => $reader->race_id,
            'location' => $reader->location,
            'anti_rebounce_seconds' => $reader->anti_rebounce_seconds ?? 5,
            'date_min' => $reader->date_min?->toIso8601String(),
            'date_max' => $reader->date_max?->toIso8601String(),
            'configured_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Generate configuration instructions for secondary readers
     */
    public function generateConfigInstructions(int $eventId): JsonResponse
    {
        // Get primary reader for this event
        $primaryReader = Reader::where('event_id', $eventId)
                               ->where('is_primary', true)
                               ->first();

        if (!$primaryReader) {
            return response()->json([
                'error' => 'No primary reader configured for this event',
                'help' => 'Please select a primary reader (ARRIVÉE) first'
            ], 404);
        }

        // Get all secondary readers
        $secondaryReaders = Reader::where('event_id', $eventId)
                                  ->where('is_primary', false)
                                  ->orderBy('distance_from_start')
                                  ->get();

        if ($secondaryReaders->isEmpty()) {
            return response()->json([
                'error' => 'No secondary readers configured',
                'help' => 'Add secondary readers (DÉPART, KM5, etc.) to the event'
            ], 404);
        }

        // Build target URL for secondary readers
        $targetUrl = "http://{$primaryReader->serial}.course.ats-sport.com/api/raspberry";

        // Generate instructions for each secondary reader
        $instructions = [];
        foreach ($secondaryReaders as $reader) {
            $instructions[] = [
                'reader' => [
                    'serial' => $reader->serial,
                    'name' => $reader->name,
                    'location' => $reader->location,
                    'distance_km' => $reader->distance_from_start,
                ],
                'web_interface' => "http://{$reader->serial}.course.ats-sport.com/",
                'configuration' => [
                    'step' => 'Activate Upload 2 module',
                    'enable' => true,
                    'url' => $targetUrl,
                    'method' => 'PUT',
                ],
                'http_auth' => [
                    'required' => !empty($primaryReader->http_username),
                    'username' => $primaryReader->http_username,
                    'password' => '••••••••', // Masqué pour sécurité
                ],
                'instructions_fr' => [
                    "1. Accéder à http://{$reader->serial}.course.ats-sport.com/",
                    "2. Activer Upload 2 module (cocher la case)",
                    "3. URL: {$targetUrl}",
                    "4. Method: PUT",
                    "5. Sauvegarder la configuration",
                    "6. Le lecteur enverra automatiquement au lecteur principal ({$primaryReader->serial})"
                ]
            ];
        }

        return response()->json([
            'event_id' => $eventId,
            'primary_reader' => [
                'serial' => $primaryReader->serial,
                'name' => $primaryReader->name,
                'location' => $primaryReader->location,
                'url' => "http://{$primaryReader->serial}.course.ats-sport.com/",
                'receives_from' => $secondaryReaders->pluck('serial')->toArray(),
            ],
            'target_url' => $targetUrl,
            'secondary_readers_count' => $secondaryReaders->count(),
            'instructions' => $instructions,
        ]);
    }

    /**
     * Calculate checkpoint order based on distance from start
     * Readers are ordered by distance (ascending)
     */
    private function calculateCheckpointOrder(int $eventId, float $distance, ?int $excludeReaderId = null): int
    {
        // Count how many readers have a smaller distance in this event
        $query = Reader::where('event_id', $eventId)
            ->where('distance_from_start', '<', $distance);

        if ($excludeReaderId) {
            $query->where('id', '!=', $excludeReaderId);
        }

        $smallerCount = $query->count();

        // Order is count + 1 (1-indexed)
        return $smallerCount + 1;
    }
}
