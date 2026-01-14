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
     *
     * SOLUTION for php artisan serve: Run TWO servers
     * - Port 8000: Main app (php artisan serve)
     * - Port 8001: SSE only (php artisan serve --port=8001)
     *
     * Frontend will connect to port 8001 for SSE, port 8000 for everything else.
     */
    public function liveStream(Request $request)
    {
        $response = new StreamedResponse(function() {
            // For php artisan serve (mono-thread): timeout after 30s to free server
            // Browser will auto-reconnect thanks to EventSource
            $maxDuration = 30;
            set_time_limit($maxDuration + 5);
            ignore_user_abort(false);

            // Start from the latest ID to avoid sending history
            $allLogs = Cache::get('rfid_raw_logs', []);
            $lastId = count($allLogs) > 0 ? max(array_column($allLogs, 'id')) : 0;

            // Send headers
            echo "retry: 1000\n\n";
            ob_flush();
            flush();

            // Send initial "connected" event to trigger onopen in browser
            echo "event: connected\n";
            echo "data: {\"status\":\"connected\",\"timestamp\":\"" . now()->toIso8601String() . "\"}\n\n";
            ob_flush();
            flush();

            $startTime = time();

            // Keep connection alive and send new detections
            while (true) {
                // Auto-close after 30s to free server (php artisan serve is mono-thread)
                if ((time() - $startTime) >= $maxDuration) {
                    // Close gracefully - browser will reconnect automatically
                    break;
                }

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

        $serial = $request->header('Serial');
        $data = $request->getContent();
        $currentTime = now();

        // Extract tag number for display purposes only (NO deduplication)
        $tagNumber = null;
        try {
            $body = json_decode($data, true);

            // Handle double encapsulation: {"data":{"serial":"[20000005]",...}}
            if (isset($body['data'])) {
                if (is_string($body['data'])) {
                    $body = json_decode($body['data'], true);
                } else {
                    $body = $body['data'];
                }
            }

            // Extract and clean tag number (remove brackets)
            if (isset($body['serial'])) {
                $tagNumber = preg_replace('/[\[\]]/', '', $body['serial']);
            } elseif (isset($body['tag'])) {
                $tagNumber = preg_replace('/[\[\]]/', '', $body['tag']);
            }
        } catch (\Exception $e) {
            // If parsing fails, continue anyway
        }

        // Log EVERY incoming request for debugging
        \Log::info('📥 RFID request received', [
            'serial' => $serial,
            'tag_number' => $tagNumber,
            'data_preview' => substr($data, 0, 200),
            'status' => $status,
            'current_cache_size' => count($allLogs)
        ]);

        // NO DEDUPLICATION - This is a RAW debug tool, show EVERYTHING

        // Generate unique ID
        $lastId = count($allLogs) > 0 ? max(array_column($allLogs, 'id')) : 0;
        $newId = $lastId + 1;

        $log = [
            'id' => $newId,
            'timestamp' => $currentTime->format('Y-m-d H:i:s'),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'serial' => $serial,
            'ip' => $request->ip(),
            'status' => $status,
            'data' => $data,
            'response' => $responseData,
        ];

        // Add to beginning of array
        array_unshift($allLogs, $log);

        // Keep only last 500 logs (increased from 100 to handle large scans)
        $allLogs = array_slice($allLogs, 0, 500);

        // Store in cache for 1 hour
        Cache::put('rfid_raw_logs', $allLogs, 3600);

        // Log successful addition
        \Log::info('✅ RFID detection added to cache', [
            'new_id' => $newId,
            'tag' => $tagNumber,
            'cache_size_after' => count($allLogs)
        ]);
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
