<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RaceController extends Controller
{
    /**
     * Display a listing of races
     */
    public function index(): JsonResponse
    {
        $races = Race::with(['event', 'waves'])->get();
        return response()->json($races);
    }

    /**
     * Get races for a specific event
     */
    public function byEvent(int $eventId): JsonResponse
    {
        $races = Race::where('event_id', $eventId)
            ->with('waves')
            ->get();

        return response()->json($races);
    }

    /**
     * Store a newly created race
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:200',
            'type' => 'required|in:1_passage,n_laps,infinite_loop',
            'distance' => 'required|numeric|min:0',
            'laps' => 'nullable|integer|min:1',
            'best_time' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $race = Race::create($validated);

        return response()->json($race, 201);
    }

    /**
     * Display the specified race
     */
    public function show(Race $race): JsonResponse
    {
        $race->load(['event', 'waves.entrants', 'results.entrant']);
        return response()->json($race);
    }

    /**
     * Update the specified race
     */
    public function update(Request $request, Race $race): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'type' => 'sometimes|in:1_passage,n_laps,infinite_loop',
            'distance' => 'sometimes|numeric|min:0',
            'laps' => 'integer|min:1',
            'best_time' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $race->update($validated);

        return response()->json($race);
    }

    /**
     * Remove the specified race
     */
    public function destroy(Race $race): JsonResponse
    {
        $race->delete();
        return response()->json(['message' => 'Race deleted successfully']);
    }

    /**
     * Start a race
     */
    public function start(Race $race): JsonResponse
    {
        if ($race->start_time) {
            return response()->json([
                'message' => 'Race already started'
            ], 400);
        }

        $race->update([
            'start_time' => now()
        ]);

        return response()->json([
            'message' => 'Race started successfully',
            'race' => $race
        ]);
    }

    /**
     * Update race start time (for corrections after false start or timing errors)
     * Also recalculates all existing results for this race
     */
    public function updateStartTime(Request $request, Race $race): JsonResponse
    {
        $validated = $request->validate([
            'start_time' => 'required|date'
        ]);

        $race->update([
            'start_time' => $validated['start_time']
        ]);

        // Recalculate all results for this race
        $results = \App\Models\Result::where('race_id', $race->id)->get();
        $recalculatedCount = 0;

        foreach ($results as $result) {
            $result->load(['wave', 'race', 'entrant']);

            // Calculate time from wave start or race TOP DÉPART
            if (($result->wave && $result->wave->start_time) || ($result->race && $result->race->start_time)) {
                // Use Result model's calculateTime method
                $result->calculateTime();

                // Calculate speed if distance is available
                if ($result->race && $result->race->distance > 0 && $result->calculated_time > 0) {
                    $result->calculateSpeed($result->race->distance);
                }

                // Calculate lap time if this is not the first lap
                if ($result->lap_number > 1) {
                    $previousLap = \App\Models\Result::where('race_id', $result->race_id)
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
                $recalculatedCount++;
            }
        }

        return response()->json([
            'message' => 'Start time updated successfully',
            'race' => $race,
            'recalculated_results' => $recalculatedCount
        ]);
    }

    /**
     * End a race
     */
    public function end(Race $race): JsonResponse
    {
        if (!$race->start_time) {
            return response()->json([
                'message' => 'Race has not started yet'
            ], 400);
        }

        if ($race->end_time) {
            return response()->json([
                'message' => 'Race already ended'
            ], 400);
        }

        $race->update([
            'end_time' => now()
        ]);

        return response()->json([
            'message' => 'Race ended successfully',
            'race' => $race
        ]);
    }

    /**
     * Update display order for races
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:races,id',
            'orders.*.display_order' => 'required|integer|min:1',
        ]);

        foreach ($validated['orders'] as $orderData) {
            Race::where('id', $orderData['id'])->update([
                'display_order' => $orderData['display_order']
            ]);
        }

        return response()->json([
            'message' => 'Ordre des parcours mis à jour avec succès'
        ]);
    }
}
