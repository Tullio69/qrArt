<?php

namespace App\Libraries;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\HTTP\IncomingRequest;

class AnalyticsEventService
{
    protected ConnectionInterface $db;
    protected IncomingRequest $request;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->request = service('request');
    }

    /**
     * Traccia un evento di analytics
     *
     * @param string $eventType Tipo di evento (qr_scan, content_view, playback_start, etc.)
     * @param array $data Dati aggiuntivi dell'evento
     * @return int|false ID dell'evento creato o false in caso di errore
     */
    public function trackEvent(string $eventType, array $data = [])
    {
        $sessionId = $this->getOrCreateSessionId();
        $deviceInfo = $this->parseUserAgent();

        $eventData = [
            'event_type' => $eventType,
            'content_id' => $data['content_id'] ?? null,
            'short_code' => $data['short_code'] ?? null,
            'session_id' => $sessionId,
            'language' => $data['language'] ?? null,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'os' => $deviceInfo['os'],
            'referrer' => $this->request->getServer('HTTP_REFERER'),
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Inserisce l'evento
        $builder = $this->db->table('analytics_events');
        $inserted = $builder->insert($eventData);

        if ($inserted) {
            $eventId = $this->db->insertID();

            // Aggiorna la sessione utente
            $this->updateSession($sessionId, $data['content_id'] ?? null);

            // Aggiorna le metriche aggregate se è un evento rilevante
            if (in_array($eventType, ['qr_scan', 'content_view', 'playback_start', 'playback_complete'])) {
                $this->updateContentMetrics($eventType, $data);
            }

            return $eventId;
        }

        return false;
    }

    /**
     * Ottiene o crea un session ID per l'utente
     *
     * @return string Session ID
     */
    protected function getOrCreateSessionId(): string
    {
        // Cerca cookie di sessione esistente
        $cookie = $this->request->getCookie('analytics_session');

        if ($cookie) {
            return $cookie;
        }

        // Genera nuovo session ID
        $sessionId = bin2hex(random_bytes(32));

        // Imposta cookie (24 ore)
        setcookie('analytics_session', $sessionId, time() + 86400, '/');

        return $sessionId;
    }

    /**
     * Aggiorna o crea record di sessione utente
     *
     * @param string $sessionId
     * @param int|null $contentId
     */
    protected function updateSession(string $sessionId, ?int $contentId = null): void
    {
        $builder = $this->db->table('user_sessions');
        $session = $builder->where('session_id', $sessionId)->get()->getRowArray();

        $deviceInfo = $this->parseUserAgent();

        if ($session) {
            // Aggiorna sessione esistente
            $updateData = [
                'last_seen' => date('Y-m-d H:i:s'),
                'total_events' => $session['total_events'] + 1,
            ];

            // Aggiorna contents_viewed se presente content_id
            if ($contentId) {
                $contentsViewed = json_decode($session['contents_viewed'] ?? '[]', true);
                if (!in_array($contentId, $contentsViewed)) {
                    $contentsViewed[] = $contentId;
                    $updateData['contents_viewed'] = json_encode($contentsViewed);
                }
            }

            $builder->where('session_id', $sessionId)->update($updateData);
        } else {
            // Crea nuova sessione
            $insertData = [
                'session_id' => $sessionId,
                'first_seen' => date('Y-m-d H:i:s'),
                'last_seen' => date('Y-m-d H:i:s'),
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent()->getAgentString(),
                'device_type' => $deviceInfo['device_type'],
                'total_events' => 1,
                'contents_viewed' => $contentId ? json_encode([$contentId]) : json_encode([]),
            ];

            $builder->insert($insertData);
        }
    }

    /**
     * Aggiorna le metriche aggregate per un contenuto
     *
     * @param string $eventType
     * @param array $data
     */
    protected function updateContentMetrics(string $eventType, array $data): void
    {
        if (!isset($data['content_id'])) {
            return;
        }

        $contentId = $data['content_id'];
        $builder = $this->db->table('content_metrics');
        $metrics = $builder->where('content_id', $contentId)->get()->getRowArray();

        if (!$metrics) {
            // Crea nuovo record di metriche
            $builder->insert([
                'content_id' => $contentId,
                'total_scans' => 0,
                'unique_visitors' => 0,
                'total_views' => 0,
                'playback_starts' => 0,
                'playback_completes' => 0,
                'avg_completion_rate' => 0.00,
                'language_stats' => json_encode([]),
                'device_stats' => json_encode([]),
                'last_scan_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $metrics = $builder->where('content_id', $contentId)->get()->getRowArray();
        }

        // Prepara aggiornamenti
        $updateData = [];

        switch ($eventType) {
            case 'qr_scan':
                $updateData['total_scans'] = $metrics['total_scans'] + 1;
                $updateData['last_scan_at'] = date('Y-m-d H:i:s');

                // Calcola unique visitors
                $uniqueCount = $this->db->table('analytics_events')
                    ->where('content_id', $contentId)
                    ->where('event_type', 'qr_scan')
                    ->distinct()
                    ->countAllResults('session_id');
                $updateData['unique_visitors'] = $uniqueCount;
                break;

            case 'content_view':
                $updateData['total_views'] = $metrics['total_views'] + 1;
                break;

            case 'playback_start':
                $updateData['playback_starts'] = $metrics['playback_starts'] + 1;
                break;

            case 'playback_complete':
                $updateData['playback_completes'] = $metrics['playback_completes'] + 1;

                // Ricalcola completion rate
                $starts = $metrics['playback_starts'];
                if ($starts > 0) {
                    $completes = $updateData['playback_completes'];
                    $updateData['avg_completion_rate'] = round(($completes / $starts) * 100, 2);
                }
                break;
        }

        // Aggiorna language_stats se presente
        if (isset($data['language']) && $data['language']) {
            $languageStats = json_decode($metrics['language_stats'] ?? '[]', true);
            $language = $data['language'];
            $languageStats[$language] = ($languageStats[$language] ?? 0) + 1;
            $updateData['language_stats'] = json_encode($languageStats);
        }

        // Aggiorna device_stats
        $deviceInfo = $this->parseUserAgent();
        if ($deviceInfo['device_type']) {
            $deviceStats = json_decode($metrics['device_stats'] ?? '[]', true);
            $deviceType = $deviceInfo['device_type'];
            $deviceStats[$deviceType] = ($deviceStats[$deviceType] ?? 0) + 1;
            $updateData['device_stats'] = json_encode($deviceStats);
        }

        // Applica aggiornamenti
        if (!empty($updateData)) {
            $builder->where('content_id', $contentId)->update($updateData);
        }
    }

    /**
     * Analizza lo user agent per estrarre informazioni dispositivo
     *
     * @return array
     */
    protected function parseUserAgent(): array
    {
        $agent = $this->request->getUserAgent();

        return [
            'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            'browser' => $agent->getBrowser(),
            'os' => $agent->getPlatform(),
        ];
    }

    /**
     * Ottiene statistiche per un contenuto specifico
     *
     * @param int $contentId
     * @return array|null
     */
    public function getContentStats(int $contentId): ?array
    {
        $builder = $this->db->table('content_metrics');
        $metrics = $builder->where('content_id', $contentId)->get()->getRowArray();

        if (!$metrics) {
            return null;
        }

        // Decodifica JSON fields
        $metrics['language_stats'] = json_decode($metrics['language_stats'] ?? '[]', true);
        $metrics['device_stats'] = json_decode($metrics['device_stats'] ?? '[]', true);

        return $metrics;
    }

    /**
     * Ottiene gli eventi recenti per un contenuto
     *
     * @param int $contentId
     * @param int $limit
     * @return array
     */
    public function getRecentEvents(int $contentId, int $limit = 100): array
    {
        $builder = $this->db->table('analytics_events');
        return $builder
            ->where('content_id', $contentId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Ottiene le statistiche globali (top contents, etc.)
     *
     * @return array
     */
    public function getGlobalStats(): array
    {
        // Top contents per scans
        $topByScans = $this->db->table('content_metrics cm')
            ->select('cm.*, c.title, c.content_type')
            ->join('content c', 'cm.content_id = c.id')
            ->orderBy('cm.total_scans', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        // Statistiche totali
        $totalBuilder = $this->db->table('content_metrics');
        $totals = [
            'total_scans' => $totalBuilder->selectSum('total_scans')->get()->getRow()->total_scans ?? 0,
            'total_views' => $totalBuilder->selectSum('total_views')->get()->getRow()->total_views ?? 0,
            'unique_visitors' => $totalBuilder->selectSum('unique_visitors')->get()->getRow()->unique_visitors ?? 0,
        ];

        // Eventi recenti (ultimi 24h)
        $recentEvents = $this->db->table('analytics_events')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        return [
            'top_contents' => $topByScans,
            'totals' => $totals,
            'recent_events' => $recentEvents,
        ];
    }
}
