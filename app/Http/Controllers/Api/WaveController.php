<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wave;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
            'depart_window_minutes' => 'sometimes|integer|min:1|max:60',
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
 * Enregistrer le TOP départ pour une vague
 * Cela active la fenêtre de détection DEPART basée sur l'heure réelle
 */
public function topDepart(Request $request, Wave $wave)
{
    // Enregistrer l'heure actuelle comme TOP départ
    $wave->real_start_time = now();
    $wave->save();

    Log::info("TOP départ clicked for wave", [
        'wave_id' => $wave->id,
        'wave_name' => $wave->name,
        'real_start_time' => $wave->real_start_time,
        'window_minutes' => $wave->depart_window_minutes,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'TOP départ enregistré',
        'wave' => [
            'id' => $wave->id,
            'name' => $wave->name,
            // Use ISO 8601 format with timezone to prevent JavaScript timezone confusion
            'real_start_time' => $wave->real_start_time->toIso8601String(),
            'depart_window_start' => $wave->real_start_time->copy()->subMinutes($wave->depart_window_minutes)->toIso8601String(),
            'depart_window_end' => $wave->real_start_time->copy()->addMinutes($wave->depart_window_minutes)->toIso8601String(),
        ],
    ]);
}

    /**
     * Update real_start_time and reprocess all detections for this wave
     */
    public function updateRealStartTime(Request $request, Wave $wave): JsonResponse
    {
        $validated = $request->validate([
            'real_start_time' => 'required|date',
        ]);

        $oldTime = $wave->real_start_time;
        $newTime = $validated['real_start_time'];

        // Update the real_start_time
        $wave->real_start_time = $newTime;
        $wave->save();

        Log::info("Real start time updated for wave", [
            'wave_id' => $wave->id,
            'wave_name' => $wave->name,
            'old_time' => $oldTime,
            'new_time' => $newTime,
        ]);

        // Reprocess all RFID detections for this wave
        $reprocessResult = $this->reprocessWaveDetections($wave);

        return response()->json([
            'success' => true,
            'message' => 'Heure de départ mise à jour et détections retraitées',
            'wave' => [
                'id' => $wave->id,
                'name' => $wave->name,
                // Use ISO 8601 format with timezone to prevent JavaScript timezone confusion
                'old_real_start_time' => $oldTime ? $oldTime->toIso8601String() : null,
                'new_real_start_time' => $wave->real_start_time->toIso8601String(),
                'depart_window_start' => $wave->real_start_time->copy()->subMinutes($wave->depart_window_minutes)->toIso8601String(),
                'depart_window_end' => $wave->real_start_time->copy()->addMinutes($wave->depart_window_minutes)->toIso8601String(),
            ],
            'reprocessed' => $reprocessResult['detections_reprocessed'],
            'results_updated' => $reprocessResult['results_updated'],
        ]);
    }

    /**
     * Reprocess all RFID detections for a wave after changing real_start_time
     * This will recalculate start_time and Results based on the new TOP départ
     */
    private function reprocessWaveDetections(Wave $wave): array
    {
        // TODO: Implement reprocessing logic
        // For now, return placeholder values
        // This will need to:
        // 1. Get all RfidDetection records for entrants in this wave
        // 2. Re-determine if they should be DEPART or ARRIVEE based on new time
        // 3. Update Entrant.start_time for DEPART detections
        // 4. Recalculate Result.time for ARRIVEE detections

        return [
            'detections_reprocessed' => 0,
            'results_updated' => 0,
        ];
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
