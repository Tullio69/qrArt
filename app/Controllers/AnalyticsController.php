<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\AnalyticsEventService;

class AnalyticsController extends BaseController
{
    protected AnalyticsEventService $analyticsService;

    public function __construct()
    {
        $this->analyticsService = new AnalyticsEventService();
    }

    /**
     * Vista overview Google Analytics (legacy)
     */
    public function overview(): string
    {
        $service = service('googleAnalytics');

        $end   = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-7 days'));

        try {
            $report = $service->getOverview($start, $end);
            return view('analytics/overview', ['report' => $report]);
        } catch (\Throwable $e) {
            return view('analytics/overview', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Health check per verificare se le tabelle analytics esistono
     * GET /api/analytics/health
     */
    public function health(): ResponseInterface
    {
        try {
            $db = \Config\Database::connect();

            // Verifica connessione database
            if (!$db->connID) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Database not connected',
                    'message' => 'Il database non è connesso. Verifica la configurazione in app/Config/Database.php'
                ]);
            }

            // Verifica esistenza tabelle
            $tables = ['analytics_events', 'content_metrics', 'user_sessions'];
            $missingTables = [];

            foreach ($tables as $table) {
                if (!$db->tableExists($table)) {
                    $missingTables[] = $table;
                }
            }

            if (!empty($missingTables)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Analytics tables not found',
                    'missing_tables' => $missingTables,
                    'message' => 'Esegui le migrations con: php spark migrate'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Analytics system is ready',
                'database' => $db->database,
                'tables' => $tables
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Health check failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Traccia un evento analytics dal frontend
     * POST /api/analytics/track
     */
    public function trackEvent(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            if (!isset($data['event_type'])) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => 'Event type is required'
                ]);
            }

            $eventId = $this->analyticsService->trackEvent($data['event_type'], $data);

            if ($eventId) {
                return $this->response->setJSON([
                    'success' => true,
                    'event_id' => $eventId
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error' => 'Failed to track event'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error tracking event: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'An error occurred while tracking the event'
            ]);
        }
    }

    /**
     * Ottiene le statistiche globali
     * GET /api/analytics/stats/overview
     */
    public function getStatsOverview(): ResponseInterface
    {
        try {
            $stats = $this->analyticsService->getGlobalStats();

            return $this->response->setJSON([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting overview: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'An error occurred while retrieving analytics overview'
            ]);
        }
    }

    /**
     * Ottiene le statistiche per un contenuto specifico
     * GET /api/analytics/content/{contentId}
     */
    public function getContentStats($contentId): ResponseInterface
    {
        try {
            $stats = $this->analyticsService->getContentStats($contentId);

            if (!$stats) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error' => 'No analytics data found for this content'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting content stats: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'An error occurred while retrieving content statistics'
            ]);
        }
    }

    /**
     * Ottiene gli eventi recenti per un contenuto
     * GET /api/analytics/content/{contentId}/events
     */
    public function getContentEvents($contentId): ResponseInterface
    {
        try {
            $limit = $this->request->getGet('limit') ?? 100;
            $events = $this->analyticsService->getRecentEvents($contentId, $limit);

            return $this->response->setJSON([
                'success' => true,
                'data' => $events
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting content events: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'An error occurred while retrieving content events'
            ]);
        }
    }

    /**
     * Ottiene statistiche aggregate per periodo
     * GET /api/analytics/stats
     */
    public function getStats(): ResponseInterface
    {
        try {
            $db = \Config\Database::connect();

            // Ottiene parametri di filtro
            $startDate = $this->request->getGet('start_date');
            $endDate = $this->request->getGet('end_date');
            $contentId = $this->request->getGet('content_id');

            // Query di base per eventi
            $builder = $db->table('analytics_events');

            if ($startDate) {
                $builder->where('created_at >=', $startDate);
            }
            if ($endDate) {
                $builder->where('created_at <=', $endDate);
            }
            if ($contentId) {
                $builder->where('content_id', $contentId);
            }

            // Conta eventi per tipo
            $eventCounts = $builder
                ->select('event_type, COUNT(*) as count')
                ->groupBy('event_type')
                ->get()
                ->getResultArray();

            // Raggruppa per device type
            $deviceStats = $db->table('analytics_events')
                ->select('device_type, COUNT(*) as count')
                ->groupBy('device_type');

            if ($startDate) $deviceStats->where('created_at >=', $startDate);
            if ($endDate) $deviceStats->where('created_at <=', $endDate);
            if ($contentId) $deviceStats->where('content_id', $contentId);

            $deviceData = $deviceStats->get()->getResultArray();

            // Raggruppa per lingua
            $languageStats = $db->table('analytics_events')
                ->select('language, COUNT(*) as count')
                ->where('language IS NOT NULL')
                ->groupBy('language');

            if ($startDate) $languageStats->where('created_at >=', $startDate);
            if ($endDate) $languageStats->where('created_at <=', $endDate);
            if ($contentId) $languageStats->where('content_id', $contentId);

            $languageData = $languageStats->get()->getResultArray();

            // Timeline (eventi per giorno)
            $timeline = $db->table('analytics_events')
                ->select('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('DATE(created_at)')
                ->orderBy('date', 'ASC');

            if ($startDate) $timeline->where('created_at >=', $startDate);
            if ($endDate) $timeline->where('created_at <=', $endDate);
            if ($contentId) $timeline->where('content_id', $contentId);

            $timelineData = $timeline->get()->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'event_counts' => $eventCounts,
                    'device_stats' => $deviceData,
                    'language_stats' => $languageData,
                    'timeline' => $timelineData
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting stats: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'An error occurred while retrieving statistics'
            ]);
        }
    }

    /**
     * Ottiene le metriche aggregate per tutti i contenuti
     * GET /api/analytics/metrics
     */
    public function getMetrics(): ResponseInterface
    {
        try {
            $db = \Config\Database::connect();

            // Ottiene tutte le metriche con info contenuto
            $metrics = $db->table('content_metrics cm')
                ->select('cm.*, c.title as content_title, c.content_type, c.created_at as content_created_at')
                ->join('content c', 'cm.content_id = c.id')
                ->orderBy('cm.total_scans', 'DESC')
                ->get()
                ->getResultArray();

            // Decodifica i campi JSON
            foreach ($metrics as &$metric) {
                $metric['language_stats'] = json_decode($metric['language_stats'] ?? '[]', true);
                $metric['device_stats'] = json_decode($metric['device_stats'] ?? '[]', true);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting metrics: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'An error occurred while retrieving metrics'
            ]);
        }
    }

    /**
     * Ottiene le sessioni utente recenti
     * GET /api/analytics/sessions
     */
    public function getSessions(): ResponseInterface
    {
        try {
            $db = \Config\Database::connect();

            $limit = $this->request->getGet('limit') ?? 50;
            $startDate = $this->request->getGet('start_date');

            $builder = $db->table('user_sessions')
                ->orderBy('last_seen', 'DESC')
                ->limit($limit);

            if ($startDate) {
                $builder->where('first_seen >=', $startDate);
            }

            $sessions = $builder->get()->getResultArray();

            // Decodifica contents_viewed
            foreach ($sessions as &$session) {
                $session['contents_viewed'] = json_decode($session['contents_viewed'] ?? '[]', true);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $sessions
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting sessions: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'An error occurred while retrieving sessions'
            ]);
        }
    }

    /**
     * Dashboard view per analytics
     * GET /analytics/dashboard
     */
    public function dashboard(): string
    {
        return view('analytics_dashboard');
    }
}
