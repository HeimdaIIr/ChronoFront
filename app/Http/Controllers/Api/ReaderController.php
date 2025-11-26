<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reader;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ReaderController extends Controller
{
    /**
     * Get all active readers for a specific race
     */
    public function byRace(int $raceId): JsonResponse
    {
        $readers = Reader::where('race_id', $raceId)
            ->where('is_active', true)
            ->orderBy('location')
            ->get()
            ->map(function ($reader) {
                return [
                    'id' => $reader->id,
                    'serial' => $reader->serial,
                    'name' => $reader->name,
                    'location' => $reader->location,
                    'ip' => $this->calculateIp($reader->serial),
                    'anti_rebounce_seconds' => $reader->anti_rebounce_seconds,
                    'is_active' => $reader->is_active,
                ];
            });

        return response()->json($readers);
    }

    /**
     * Ping a reader to check if it's online
     * Returns: { online: true/false, response_time: milliseconds }
     */
    public function ping(int $id): JsonResponse
    {
        $reader = Reader::findOrFail($id);
        $ip = $this->calculateIp($reader->serial);
        $url = "http://{$ip}/";

        $startTime = microtime(true);
        $online = false;
        $responseTime = null;
        $error = null;

        try {
            // Tentative de connexion avec timeout court (2 secondes)
            $response = Http::timeout(2)->get($url);
            $endTime = microtime(true);

            $online = $response->successful() || $response->status() > 0;
            $responseTime = round(($endTime - $startTime) * 1000); // en millisecondes
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);
            $online = false;
            $error = $e->getMessage();
        }

        return response()->json([
            'reader_id' => $reader->id,
            'serial' => $reader->serial,
            'ip' => $ip,
            'online' => $online,
            'response_time' => $responseTime,
            'error' => $error,
        ]);
    }

    /**
     * Ping all active readers for a race
     * Returns array of reader statuses
     */
    public function pingRace(int $raceId): JsonResponse
    {
        $readers = Reader::where('race_id', $raceId)
            ->where('is_active', true)
            ->get();

        $statuses = [];

        foreach ($readers as $reader) {
            $ip = $this->calculateIp($reader->serial);
            $url = "http://{$ip}/";

            $startTime = microtime(true);
            $online = false;

            try {
                $response = Http::timeout(2)->get($url);
                $online = $response->successful() || $response->status() > 0;
            } catch (\Exception $e) {
                $online = false;
            }

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            $statuses[] = [
                'reader_id' => $reader->id,
                'serial' => $reader->serial,
                'name' => $reader->name,
                'location' => $reader->location,
                'ip' => $ip,
                'online' => $online,
                'response_time' => $responseTime,
            ];
        }

        return response()->json($statuses);
    }

    /**
     * Calculate IP address from reader serial number
     * Formula: 192.168.10.{50 + serial}
     *
     * Examples:
     * - Serial 101 → 192.168.10.151
     * - Serial 104 → 192.168.10.154
     * - Serial 120 → 192.168.10.170
     */
    private function calculateIp(string $serial): string
    {
        $number = (int) $serial;
        $lastOctet = 50 + $number;

        return "192.168.10.{$lastOctet}";
    }
}
