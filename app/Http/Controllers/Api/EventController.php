<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    /**
     * Display a listing of events
     */
    public function index(): JsonResponse
    {
        $events = Event::with('races')
            ->orderBy('date_start', 'desc')
            ->get();

        return response()->json($events);
    }

    /**
     * Get only currently active events (for timing interface)
     * Active = is_active flag + current date between date_start and date_end
     */
    public function activeEvents(): JsonResponse
    {
        $events = Event::active()
            ->with('races')
            ->orderBy('date_start', 'desc')
            ->get();

        return response()->json($events);
    }

    /**
     * Store a newly created event
     */
    public function store(Request $request): JsonResponse
    {
        \Log::info('Event creation request data:', $request->all());

        // Convert empty strings to null for date fields (HTML5 datetime-local sends empty strings)
        $data = $request->all();
        if (isset($data['date_start']) && $data['date_start'] === '') {
            $data['date_start'] = null;
        }
        if (isset($data['date_end']) && $data['date_end'] === '') {
            $data['date_end'] = null;
        }

        try {
            $validated = validator($data, [
                'name' => 'required|string|max:200',
                'date_start' => 'required|date',
                'date_end' => 'required|date|after_or_equal:date_start',
                'location' => 'nullable|string|max:200',
                'description' => 'nullable|string',
                'is_active' => 'sometimes|boolean',
            ])->validate();

            \Log::info('Event validation passed:', $validated);

            // Ensure is_active has a default value if not provided
            if (!isset($validated['is_active'])) {
                $validated['is_active'] = true;
            }

            $event = Event::create($validated);

            return response()->json($event, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Event validation failed:', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            throw $e;
        }
    }

    /**
     * Display the specified event
     */
    public function show(Event $event): JsonResponse
    {
        $event->load(['races.waves', 'races.entrants']);
        return response()->json($event);
    }

    /**
     * Update the specified event
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'date_start' => 'sometimes|date',
            'date_end' => 'sometimes|date|after_or_equal:date_start',
            'location' => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $event->update($validated);

        return response()->json($event);
    }

    /**
     * Remove the specified event
     */
    public function destroy(Event $event): JsonResponse
    {
        $event->delete();
        return response()->json(['message' => 'Event deleted successfully']);
    }
}
