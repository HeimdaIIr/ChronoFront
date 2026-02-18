<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * RFID Log Controller
 * Logs all RFID detection requests for real-time monitoring via rfidlive-ultra
 */
class RfidLogController extends Controller
{
    private const CACHE_KEY = 'rfid_raw_logs';
    private const MAX_LOGS = 200; // Keep last 200 logs (reduced for SD card performance)

    /**
     * Log an incoming RFID request (called by RaspberryController)
     */
    public static function logRequest(Request $request, int $statusCode, array $responseData = null): void
    {
        try {
            $logs = Cache::get(self::CACHE_KEY, []);

            // Generate unique ID
            $lastId = !empty($logs) ? max(array_column($logs, 'id')) : 0;
            $newId = $lastId + 1;

            // Extract reader serial from HTTP header or response data
            $readerSerial = $request->header('Serial');

            // If header is not available, try to get it from response data
            if (!$readerSerial && $responseData && isset($responseData['reader'])) {
                $readerSerial = $responseData['reader'];
            }

            // Get JSON body for rfidlive-ultra parsing
            // Use json() instead of getContent() because the stream may already be consumed by RaspberryController
            $jsonData = $request->json();
            $bodyData = $jsonData ? $jsonData->all() : [];

            // Fallback to all() if json is empty (for non-JSON requests)
            if (empty($bodyData)) {
                $bodyData = $request->all();
            }

            // Create log entry (matching structure expected by rfidlive-ultra)
            $logEntry = [
                'id' => $newId,
                'timestamp' => Carbon::now()->toIso8601String(),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'status_code' => $statusCode,
                'data' => $bodyData, // rfidlive-ultra expects 'data' as parsed JSON array
                'serial' => $readerSerial, // Reader serial from HTTP header or response
                'response_data' => $responseData,
                'user_agent' => $request->userAgent(),
            ];

            // Add to logs array
            $logs[] = $logEntry;

            // Keep only last MAX_LOGS entries
            if (count($logs) > self::MAX_LOGS) {
                $logs = array_slice($logs, -self::MAX_LOGS);
            }

            // Store back to cache (keep for 24 hours)
            Cache::put(self::CACHE_KEY, $logs, now()->addHours(24));

            Log::debug('RFID request logged', [
                'id' => $newId,
                'status' => $statusCode,
                'url' => $request->path()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log RFID request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Log an RFID request after HTTP response is sent (non-blocking)
     * Uses pre-extracted request data instead of Request object
     */
    public static function logRequestDeferred(array $requestData, int $statusCode, array $responseData = null): void
    {
        try {
            $logs = Cache::get(self::CACHE_KEY, []);

            $lastId = !empty($logs) ? max(array_column($logs, 'id')) : 0;
            $newId = $lastId + 1;

            $readerSerial = $requestData['serial'] ?? null;
            if (!$readerSerial && $responseData && isset($responseData['reader'])) {
                $readerSerial = $responseData['reader'];
            }

            $logEntry = [
                'id' => $newId,
                'timestamp' => Carbon::now()->toIso8601String(),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'method' => $requestData['method'] ?? 'POST',
                'url' => $requestData['url'] ?? '',
                'ip' => $requestData['ip'] ?? '',
                'status_code' => $statusCode,
                'data' => $requestData['json_data'] ?? [],
                'serial' => $readerSerial,
                'response_data' => $responseData,
                'user_agent' => $requestData['user_agent'] ?? '',
            ];

            $logs[] = $logEntry;

            if (count($logs) > self::MAX_LOGS) {
                $logs = array_slice($logs, -self::MAX_LOGS);
            }

            Cache::put(self::CACHE_KEY, $logs, now()->addHours(24));
        } catch (\Exception $e) {
            Log::error('Failed to log RFID request (deferred)', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get raw logs since a specific ID (for polling)
     * GET /api/rfid/raw-logs?since={id}
     */
    public function rawLogs(Request $request): JsonResponse
    {
        $sinceId = (int) $request->get('since', 0);
        $logs = Cache::get(self::CACHE_KEY, []);

        // Filter logs with ID greater than sinceId
        $newLogs = array_filter($logs, function($log) use ($sinceId) {
            return $log['id'] > $sinceId;
        });

        // Re-index array to return as proper JSON array
        $newLogs = array_values($newLogs);

        return response()->json([
            'success' => true,
            'since_id' => $sinceId,
            'count' => count($newLogs),
            'logs' => $newLogs,
            'last_id' => !empty($logs) ? max(array_column($logs, 'id')) : 0,
        ]);
    }

    /**
     * Get all raw logs (no filtering)
     * GET /api/rfid/raw-logs/all
     */
    public function allRawLogs(): JsonResponse
    {
        $logs = Cache::get(self::CACHE_KEY, []);

        return response()->json([
            'success' => true,
            'count' => count($logs),
            'logs' => $logs,
            'last_id' => !empty($logs) ? max(array_column($logs, 'id')) : 0,
        ]);
    }

    /**
     * Clear all raw logs
     * DELETE /api/rfid/raw-logs
     */
    public function clearLogs(): JsonResponse
    {
        Cache::forget(self::CACHE_KEY);

        Log::info('RFID raw logs cleared');

        return response()->json([
            'success' => true,
            'message' => 'Raw logs cleared successfully'
        ]);
    }

    /**
     * Get statistics about logged requests
     * GET /api/rfid/raw-logs/stats
     */
    public function stats(): JsonResponse
    {
        $logs = Cache::get(self::CACHE_KEY, []);

        $stats = [
            'total_count' => count($logs),
            'last_id' => !empty($logs) ? max(array_column($logs, 'id')) : 0,
            'first_id' => !empty($logs) ? min(array_column($logs, 'id')) : 0,
            'status_codes' => [],
            'oldest_timestamp' => null,
            'newest_timestamp' => null,
        ];

        if (!empty($logs)) {
            // Count by status code
            foreach ($logs as $log) {
                $code = $log['status_code'];
                $stats['status_codes'][$code] = ($stats['status_codes'][$code] ?? 0) + 1;
            }

            // Get time range
            $stats['oldest_timestamp'] = $logs[0]['timestamp'] ?? null;
            $stats['newest_timestamp'] = end($logs)['timestamp'] ?? null;
        }

        return response()->json($stats);
    }
}
