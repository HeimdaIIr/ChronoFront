<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * SSE endpoint for live streaming RFID detections
     */
    public function liveStream(Request $request)
    {
        $response = new StreamedResponse(function() {
            $lastId = 0;

            // Send headers
            echo "retry: 1000\n\n";
            ob_flush();
            flush();

            // Keep connection alive and send new detections
            while (true) {
                // Get all logs
                $allLogs = Cache::get('rfid_raw_logs', []);

                // Find new logs
                $newLogs = array_filter($allLogs, function($log) use ($lastId) {
                    return $log['id'] > $lastId;
                });

                // Send new logs
                foreach ($newLogs as $log) {
                    $data = [
                        'id' => $log['id'],
                        'timestamp' => $log['timestamp'],
                        'serial' => $log['serial'],
                        'data' => $log['data'],
                        'status' => $log['status']
                    ];

                    echo "event: detection\n";
                    echo "data: " . json_encode($data) . "\n\n";

                    if ($log['id'] > $lastId) {
                        $lastId = $log['id'];
                    }

                    ob_flush();
                    flush();
                }

                // Check if client disconnected
                if (connection_aborted()) {
                    break;
                }

                // Sleep for 100ms (10 checks per second = very responsive)
                usleep(100000);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
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
