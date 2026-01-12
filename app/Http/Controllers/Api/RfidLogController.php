<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RfidLogController extends Controller
{
    /**
     * Get raw RFID request logs
     * Used by /rfidlive for real-time display
     */
    public function getRawLogs(Request $request)
    {
        $sinceId = $request->get('since', 0);

        // Get logs from cache
        $allLogs = Cache::get('rfid_raw_logs', []);

        // Filter logs newer than sinceId
        $newLogs = array_filter($allLogs, function($log) use ($sinceId) {
            return $log['id'] > $sinceId;
        });

        // Sort by ID descending (newest first)
        usort($newLogs, function($a, $b) {
            return $b['id'] - $a['id'];
        });

        return response()->json([
            'success' => true,
            'logs' => array_values($newLogs),
            'count' => count($newLogs)
        ]);
    }

    /**
     * Log a raw RFID request
     * Called by RaspberryController
     */
    public static function logRequest(Request $request, int $status, $responseData = null)
    {
        $allLogs = Cache::get('rfid_raw_logs', []);

        // Generate unique ID
        $lastId = count($allLogs) > 0 ? max(array_column($allLogs, 'id')) : 0;
        $newId = $lastId + 1;

        $log = [
            'id' => $newId,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'serial' => $request->header('Serial'),
            'ip' => $request->ip(),
            'status' => $status,
            'data' => $request->getContent(),
            'response' => $responseData,
        ];

        // Add to beginning of array
        array_unshift($allLogs, $log);

        // Keep only last 100 logs
        $allLogs = array_slice($allLogs, 0, 100);

        // Store in cache for 1 hour
        Cache::put('rfid_raw_logs', $allLogs, 3600);
    }

    /**
     * Clear all logs
     */
    public function clearLogs()
    {
        Cache::forget('rfid_raw_logs');

        return response()->json([
            'success' => true,
            'message' => 'All logs cleared'
        ]);
    }
}
