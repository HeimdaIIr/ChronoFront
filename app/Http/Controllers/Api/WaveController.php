<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wave;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WaveController extends Controller
{
    /**
     * Display a listing of waves
     */
    public function index(): JsonResponse
    {
        $waves = Wave::with(['race.event', 'entrants'])->get();
        return response()->json($waves);
    }

    /**
     * Get waves for a specific race
     */
    public function byRace(int $raceId): JsonResponse
    {
        $waves = Wave::where('race_id', $raceId)
            ->with(['race.event', 'entrants'])
            ->get();

        return response()->json($waves);
    }

    /**
     * Store a newly created wave
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'race_id' => 'required|exists:races,id',
            'wave_number' => 'required|integer|min:1',
            'name' => 'required|string|max:100',
        ]);

        // Vérifie que le numéro de vague n'existe pas déjà pour cette épreuve
        $exists = Wave::where('race_id', $validated['race_id'])
            ->where('wave_number', $validated['wave_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ce numéro de vague existe déjà pour cette épreuve'
            ], 422);
        }

        $wave = Wave::create($validated);

        return response()->json($wave, 201);
    }

    /**
     * Display the specified wave
     */
    public function show(Wave $wave): JsonResponse
    {
        $wave->load(['race', 'entrants.category']);
        return response()->json($wave);
    }

    /**
     * Update the specified wave
     */
    public function update(Request $request, Wave $wave): JsonResponse
    {
        $validated = $request->validate([
            'wave_number' => 'sometimes|integer|min:1',
            'name' => 'sometimes|string|max:100',
        ]);

        // Si le numéro de vague est modifié, vérifier qu'il n'existe pas déjà
        if (isset($validated['wave_number']) && $validated['wave_number'] != $wave->wave_number) {
            $exists = Wave::where('race_id', $wave->race_id)
                ->where('wave_number', $validated['wave_number'])
                ->where('id', '!=', $wave->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Ce numéro de vague existe déjà pour cette épreuve'
                ], 422);
            }
        }

        $wave->update($validated);

        return response()->json($wave);
    }

    /**
     * Remove the specified wave
     */
    public function destroy(Wave $wave): JsonResponse
    {
        $wave->delete();
        return response()->json(['message' => 'Wave deleted successfully']);
    }

    /**
     * Start a wave
     */
    public function start(Wave $wave): JsonResponse
    {
        if ($wave->is_started) {
            return response()->json([
                'message' => 'Wave already started'
            ], 400);
        }

        $wave->update([
            'start_time' => now(),
            'is_started' => true
        ]);

        return response()->json([
            'message' => 'Wave started successfully',
            'wave' => $wave
        ]);
    }

    /**
     * End a wave
     */
    public function end(Wave $wave): JsonResponse
    {
        if (!$wave->is_started) {
            return response()->json([
                'message' => 'Wave has not started yet'
            ], 400);
        }

        if ($wave->end_time) {
            return response()->json([
                'message' => 'Wave already ended'
            ], 400);
        }

        $wave->update([
            'end_time' => now()
        ]);

        return response()->json([
            'message' => 'Wave ended successfully',
            'wave' => $wave
        ]);
    }

    /**
     * Assign all entrants of a race to this wave
     */
    public function assignAllEntrants(Wave $wave): JsonResponse
    {
        // Compter les participants déjà assignés à d'autres vagues de cette épreuve
        $alreadyAssignedCount = \App\Models\ChronoFront\Entrant::where('race_id', $wave->race_id)
            ->whereNotNull('wave_id')
            ->where('wave_id', '!=', $wave->id)
            ->count();

        // Assigner tous les participants de cette épreuve sans vague à cette vague
        $updated = \App\Models\ChronoFront\Entrant::where('race_id', $wave->race_id)
            ->whereNull('wave_id')
            ->update(['wave_id' => $wave->id]);

        return response()->json([
            'message' => "Assignation terminée : {$updated} participant(s) assigné(s) à la vague {$wave->name}",
            'assigned_count' => $updated,
            'already_assigned_elsewhere' => $alreadyAssignedCount,
            'wave' => $wave->load('entrants')
        ]);
    }
}
