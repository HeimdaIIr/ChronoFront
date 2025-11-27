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
                // Check if reader is online (heartbeat within last 60 seconds)
                $reader->is_online = $reader->date_test &&
                    now()->diffInSeconds($reader->date_test) < 60;
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
            'name' => 'required|string|max:200',
            'event_id' => 'nullable|exists:events,id',
            'race_id' => 'nullable|exists:races,id',
            'location' => 'required|string|max:100',
            'anti_rebounce_seconds' => 'integer|min:0',
            'date_min' => 'required|date',
            'date_max' => 'required|date|after_or_equal:date_min',
            'is_active' => 'boolean',
        ]);

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
            'name' => 'sometimes|string|max:200',
            'event_id' => 'nullable|exists:events,id',
            'race_id' => 'nullable|exists:races,id',
            'location' => 'sometimes|string|max:100',
            'anti_rebounce_seconds' => 'integer|min:0',
            'date_min' => 'sometimes|date',
            'date_max' => 'sometimes|date',
            'is_active' => 'boolean',
        ]);

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
                // Check if reader is online (heartbeat within last 60 seconds)
                $reader->is_online = $reader->date_test &&
                    now()->diffInSeconds($reader->date_test) < 60;
                return $reader;
            });

        return response()->json($readers);
    }
}
