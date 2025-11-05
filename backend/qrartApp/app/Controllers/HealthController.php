<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Health Check Controller
 *
 * Provides endpoints for monitoring application health
 */
class HealthController extends Controller
{
    /**
     * Basic health check
     * Returns 200 if application is running
     *
     * GET /api/health
     */
    public function index(): ResponseInterface
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => $this->getAppVersion(),
            'environment' => ENVIRONMENT
        ];

        return $this->response
            ->setStatusCode(200)
            ->setJSON($health);
    }

    /**
     * Detailed health check
     * Checks database, cache, and file system
     *
     * GET /api/health/detailed
     */
    public function detailed(): ResponseInterface
    {
        $checks = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => $this->getAppVersion(),
            'environment' => ENVIRONMENT,
            'checks' => []
        ];

        // Database check
        $checks['checks']['database'] = $this->checkDatabase();

        // Cache check
        $checks['checks']['cache'] = $this->checkCache();

        // Filesystem check
        $checks['checks']['filesystem'] = $this->checkFilesystem();

        // Determine overall status
        $allHealthy = true;
        foreach ($checks['checks'] as $check) {
            if ($check['status'] !== 'healthy') {
                $allHealthy = false;
                break;
            }
        }

        $checks['status'] = $allHealthy ? 'healthy' : 'degraded';
        $statusCode = $allHealthy ? 200 : 503;

        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON($checks);
    }

    /**
     * Check database connectivity and performance
     */
    private function checkDatabase(): array
    {
        try {
            $db = \Config\Database::connect();

            $startTime = microtime(true);
            $db->query("SELECT 1");
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Get database size
            $dbConfig = config('Database')->default;
            $result = $db->query(
                "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = ?",
                [$dbConfig['database']]
            )->getRowArray();

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'database_size_mb' => $result['size_mb'] ?? 0,
                'connection' => 'active'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check cache system
     */
    private function checkCache(): array
    {
        try {
            $cache = \Config\Services::cache();

            $testKey = 'health_check_' . time();
            $testValue = 'test_value';

            $startTime = microtime(true);

            // Test write
            $cache->save($testKey, $testValue, 60);

            // Test read
            $retrieved = $cache->get($testKey);

            // Test delete
            $cache->delete($testKey);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $cacheConfig = config('Cache');
            $handler = $cacheConfig->handler;

            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'handler' => $handler,
                    'response_time_ms' => $responseTime,
                    'read_write' => 'operational'
                ];
            } else {
                return [
                    'status' => 'degraded',
                    'handler' => $handler,
                    'message' => 'Cache read/write mismatch'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check filesystem (writable directories and disk space)
     */
    private function checkFilesystem(): array
    {
        $checks = [
            'status' => 'healthy',
            'writable_dirs' => [],
            'disk_space' => []
        ];

        // Check writable directories
        $writableDirs = [
            'cache' => WRITEPATH . 'cache',
            'logs' => WRITEPATH . 'logs',
            'session' => WRITEPATH . 'session',
            'uploads' => WRITEPATH . 'uploads',
            'media' => FCPATH . 'media'
        ];

        foreach ($writableDirs as $name => $path) {
            $checks['writable_dirs'][$name] = [
                'path' => $path,
                'writable' => is_writable($path),
                'exists' => file_exists($path)
            ];

            if (!is_writable($path)) {
                $checks['status'] = 'degraded';
            }
        }

        // Check disk space
        $diskFree = disk_free_space(FCPATH);
        $diskTotal = disk_total_space(FCPATH);
        $diskUsedPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);

        $checks['disk_space'] = [
            'free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
            'total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
            'used_percent' => $diskUsedPercent
        ];

        // Warn if disk usage > 90%
        if ($diskUsedPercent > 90) {
            $checks['status'] = 'degraded';
            $checks['disk_space']['warning'] = 'Low disk space';
        }

        return $checks;
    }

    /**
     * Get application version from git or config
     */
    private function getAppVersion(): string
    {
        // Try to get from git
        $gitVersion = shell_exec('git describe --tags --always 2>/dev/null');
        if ($gitVersion) {
            return trim($gitVersion);
        }

        // Try to get commit hash
        $gitCommit = shell_exec('git rev-parse --short HEAD 2>/dev/null');
        if ($gitCommit) {
            return 'commit-' . trim($gitCommit);
        }

        // Fallback to CodeIgniter version
        return \CodeIgniter\CodeIgniter::CI_VERSION;
    }

    /**
     * Liveness probe (Kubernetes-style)
     * Returns 200 if application process is running
     *
     * GET /api/health/live
     */
    public function live(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'status' => 'alive',
                'timestamp' => date('c')
            ]);
    }

    /**
     * Readiness probe (Kubernetes-style)
     * Returns 200 if application is ready to serve traffic
     *
     * GET /api/health/ready
     */
    public function ready(): ResponseInterface
    {
        // Check critical dependencies
        $ready = true;
        $checks = [];

        // Database must be accessible
        try {
            $db = \Config\Database::connect();
            $db->query("SELECT 1");
            $checks['database'] = true;
        } catch (\Exception $e) {
            $ready = false;
            $checks['database'] = false;
        }

        // Cache must be accessible
        try {
            $cache = \Config\Services::cache();
            $cache->get('test');
            $checks['cache'] = true;
        } catch (\Exception $e) {
            $ready = false;
            $checks['cache'] = false;
        }

        $statusCode = $ready ? 200 : 503;

        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status' => $ready ? 'ready' : 'not_ready',
                'timestamp' => date('c'),
                'checks' => $checks
            ]);
    }
}
