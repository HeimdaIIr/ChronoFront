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

            // Try to ping
            $timeout = 1; // 1 second timeout for bulk ping
            $context = stream_context_create([
                'http' => [
                    'timeout' => $timeout,
                    'ignore_errors' => true
                ]
            ]);

            $url = "http://{$readerIp}";
            $response = @file_get_contents($url, false, $context);

            if ($response !== false || isset($http_response_header)) {
                $reader->update([
                    'test_terrain' => true,
                    'date_test' => now(),
                ]);
                $results[] = [
                    'reader_id' => $reader->id,
                    'serial' => $reader->serial,
                    'ip' => $readerIp,
                    'network_type' => $reader->network_type,
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

        // Try to ping the reader (HTTP request to check if it's alive)
        try {
            $timeout = 2; // 2 seconds timeout
            $context = stream_context_create([
                'http' => [
                    'timeout' => $timeout,
                    'ignore_errors' => true
                ]
            ]);

            // Try to reach the reader (you can adjust the endpoint)
            $url = "http://{$readerIp}";
            $response = @file_get_contents($url, false, $context);

            // If we got any response, consider it online
            if ($response !== false || isset($http_response_header)) {
                $reader->update([
                    'test_terrain' => true,
                    'date_test' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Reader is online',
                    'ip' => $readerIp,
                    'network_type' => $reader->network_type,
                    'reader' => $reader
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Reader is offline or unreachable',
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
